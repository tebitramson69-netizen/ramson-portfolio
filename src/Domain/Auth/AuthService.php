<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Core\Session;

/**
 * Authentication state for the request.
 *
 * The session stores the admin's id and a copy of their session_token. Every
 * request re-reads the row and compares tokens, so rotating the token —
 * which happens on every login, and will happen on a password change —
 * invalidates all other sessions immediately. A stolen session cookie stops
 * working the moment the owner logs in again.
 */
final class AuthService
{
    private const KEY_ID    = 'admin_id';
    private const KEY_TOKEN = 'admin_token';
    private const KEY_SEEN  = 'admin_last_seen';
    private const KEY_AGENT = 'admin_agent';

    private ?AdminUser $cached = null;
    private bool $resolved = false;

    public function __construct(
        private readonly AdminUserRepository $users,
        private readonly LoginThrottle $throttle,
        private readonly int $idleTimeout = 7200,
    ) {
    }

    /**
     * Attempt a login.
     *
     * Returns null on success, or a message safe to show the visitor. The
     * message is deliberately identical for "no such account" and "wrong
     * password": distinguishing them tells an attacker which emails exist.
     */
    public function attempt(string $email, string $password, ?string $ip, string $userAgent): ?string
    {
        $started = microtime(true);

        if ($this->throttle->isLocked($email, $ip)) {
            $minutes = (int) ceil($this->throttle->retryAfter($email, $ip) / 60);

            return 'Too many attempts. Try again in about ' . max(1, $minutes) . ' minute'
                 . ($minutes === 1 ? '' : 's') . '.';
        }

        $user = $this->users->findByEmail($email);

        // Verify against a dummy hash when the account does not exist, so the
        // response takes the same time either way. Without this, a fast
        // rejection reveals that an email is not registered.
        $hash = $user?->passwordHash ?? self::dummyHash();
        $ok   = PasswordHasher::verify($password, $hash) && $user !== null;

        $this->throttle->record($email, $ip, $ok, $userAgent);

        if (!$ok) {
            $this->levelTiming($started);

            return 'Those credentials are not correct.';
        }

        // Upgrade the stored hash if the algorithm or its cost has changed.
        if (PasswordHasher::needsRehash($user->passwordHash)) {
            $this->users->updatePasswordHash($user->id, PasswordHasher::hash($password));
        }

        $token = $this->users->recordLogin($user->id, $ip);
        $this->throttle->prune();

        // New session id on privilege change — the session-fixation defence.
        Session::start();
        Session::regenerate();

        $_SESSION[self::KEY_ID]    = $user->id;
        $_SESSION[self::KEY_TOKEN] = $token;
        $_SESSION[self::KEY_SEEN]  = time();
        $_SESSION[self::KEY_AGENT] = self::agentFingerprint($userAgent);

        $this->cached   = null;
        $this->resolved = false;

        $this->levelTiming($started);

        return null;
    }

    public function logout(): void
    {
        Session::destroy();
        $this->cached   = null;
        $this->resolved = true;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    /** The signed-in admin, or null. Resolved once per request. */
    public function user(): ?AdminUser
    {
        if ($this->resolved) {
            return $this->cached;
        }

        $this->resolved = true;
        $this->cached   = null;

        // Only touch the session when the cookie is actually present, so a
        // plain visitor never causes a session to be created.
        //
        // Session::cookieName() rather than session_name(): the latter still
        // reports the ini default until start() has run, so it would look for
        // "PHPSESSID", never find it, and report every signed-in admin as
        // anonymous.
        if (!isset($_COOKIE[Session::cookieName()]) && session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        Session::start();

        $id    = $_SESSION[self::KEY_ID]    ?? null;
        $token = $_SESSION[self::KEY_TOKEN] ?? null;

        if (!is_int($id) || !is_string($token)) {
            return null;
        }

        // Idle timeout, enforced server-side. A client-side timer is decoration.
        $lastSeen = $_SESSION[self::KEY_SEEN] ?? 0;
        if (!is_int($lastSeen) || (time() - $lastSeen) > $this->idleTimeout) {
            $this->logout();
            return null;
        }

        $user = $this->users->find($id);

        if ($user === null || !hash_equals($user->sessionToken, $token)) {
            $this->logout();
            return null;
        }

        // A changed user agent means the cookie is being replayed elsewhere.
        $agent = $_SESSION[self::KEY_AGENT] ?? '';
        if (!is_string($agent)
            || !hash_equals($agent, self::agentFingerprint((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')))) {
            $this->logout();
            return null;
        }

        $_SESSION[self::KEY_SEEN] = time();

        return $this->cached = $user;
    }

    private static function agentFingerprint(string $userAgent): string
    {
        return hash('sha256', $userAgent);
    }

    /**
     * A valid hash of a value nobody will submit, used so a missing account
     * costs the same time as a wrong password.
     */
    private static function dummyHash(): string
    {
        static $hash = null;

        return $hash ??= PasswordHasher::hash('not-a-real-password-' . bin2hex(random_bytes(8)));
    }

    /**
     * Hold every attempt to a floor of ~250 ms so response time carries no
     * information about which branch was taken.
     */
    private function levelTiming(float $startedAt): void
    {
        $elapsed = (microtime(true) - $startedAt) * 1_000_000;
        $floor   = 250_000;

        if ($elapsed < $floor) {
            usleep((int) ($floor - $elapsed));
        }
    }
}
