<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session handling, started lazily.
 *
 * Public pages never call start(), which matters for more than tidiness: an
 * unconditional session_start() emits a Set-Cookie header on every response,
 * which makes the page uncacheable by any shared cache and costs a file
 * write per visitor for no benefit. Only the admin area and CSRF-protected
 * forms need a session.
 *
 * Settings follow the OWASP PHP Configuration cheat sheet: strict mode on,
 * cookies only, HttpOnly, SameSite=Strict, and Secure plus the __Host-
 * prefix whenever the request is actually over HTTPS.
 */
final class Session
{
    private static bool $started = false;

    /**
     * The cookie name this application uses, resolvable WITHOUT starting a
     * session.
     *
     * PHP's own session_name() returns the ini default ("PHPSESSID") until
     * session_name() has been called inside start(). Anything that needs to
     * ask "is there a session cookie on this request?" before deciding to
     * start one — which is the whole point of starting lazily — must use this
     * instead, or it will look for the wrong cookie and never find it.
     */
    public static function cookieName(): string
    {
        $name = (string) Config::get('session.name', 'rp_session');

        return self::requestIsSecure() ? '__Host-' . $name : $name;
    }

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $secure = self::requestIsSecure();

        // The __Host- prefix binds the cookie to the exact origin and forbids
        // a Domain attribute, which is the strongest available protection
        // against subdomain cookie injection. It REQUIRES Secure, so it can
        // only be used over HTTPS — on local XAMPP over plain HTTP the
        // browser would reject the cookie entirely and logins would appear to
        // fail for no visible reason.
        session_name(self::cookieName());

        $savePath = dirname(__DIR__, 2) . '/storage/sessions';
        if (is_dir($savePath) && is_writable($savePath)) {
            session_save_path($savePath);
        }

        session_set_cookie_params([
            'lifetime' => 0,          // browser session; idle timeout is enforced server-side
            'path'     => '/',
            'domain'   => '',         // must be empty for __Host-
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.gc_maxlifetime', (string) Config::get('session.lifetime', 7200));

        // session.sid_length and session.sid_bits_per_character were set here
        // to widen the session id. Both are DEPRECATED as of PHP 8.4 and
        // emit a deprecation notice. They are also unnecessary: PHP already
        // generates ids from a CSPRNG with ample entropy, and use_strict_mode
        // above is what actually prevents an attacker-chosen id being
        // accepted.

        session_start();
        self::$started = true;
    }

    /** Rotate the session id, keeping data. Call on every privilege change. */
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Strict',
            ]);
        }

        session_destroy();
        self::$started = false;
    }

    private static function requestIsSecure(): bool
    {
        if (($_SERVER['HTTPS'] ?? '') !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        return ((int) ($_SERVER['SERVER_PORT'] ?? 0)) === 443;
    }
}
