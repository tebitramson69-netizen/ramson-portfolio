<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Core\Config;
use RuntimeException;

/**
 * Password hashing.
 *
 * ARGON2ID, NOT PASSWORD_DEFAULT.
 *
 * PASSWORD_DEFAULT is still bcrypt in PHP 8.4 — verified, it produces "$2y$".
 * bcrypt silently truncates at 72 bytes, which is not a theoretical concern:
 * hashing a 72-character password and then verifying a 100-character password
 * that merely starts with those 72 characters SUCCEEDS. Reproduced on PHP
 * 8.4.19. A long passphrase would therefore be quietly reduced to its first
 * 72 bytes.
 *
 * Argon2id has no such limit and is OWASP's first recommendation. PHP has
 * supported it since 7.2, and it is present in the Windows builds XAMPP
 * ships. Where it is genuinely unavailable this class falls back to bcrypt
 * and REJECTS anything over 72 bytes rather than truncating it — refusing is
 * honest, truncating is a silent downgrade.
 *
 * Parameters: OWASP's floor is 19 MiB / t=2 / p=1. That measured 18 ms here,
 * which is cheap for an attacker too. Since a login happens rarely, the cost
 * is raised to 64 MiB / t=3 / p=1 — measured ~143 ms on this machine, and
 * more memory buys more GPU resistance than more iterations. 64 MiB rather
 * than 128 MiB so the hash still fits comfortably on modest shared hosting.
 */
final class PasswordHasher
{
    public const BCRYPT_MAX_BYTES = 72;
    public const MIN_LENGTH = 12;

    public static function algorithm(): string
    {
        return self::argon2Available() ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }

    public static function argon2Available(): bool
    {
        return defined('PASSWORD_ARGON2ID') && in_array('argon2id', password_algos(), true);
    }

    public static function hash(string $password): string
    {
        self::guard($password);

        $hash = password_hash($password, self::algorithm(), self::options());

        // password_hash returns a string or throws in PHP 8; belt and braces.
        if (!is_string($hash) || $hash === '') {
            throw new RuntimeException('Password hashing failed.');
        }

        return $hash;
    }

    public static function verify(string $password, string $hash): bool
    {
        // password_verify is already constant-time for the comparison itself.
        return password_verify($password, $hash);
    }

    /** True when the stored hash was made with weaker settings than current. */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, self::algorithm(), self::options());
    }

    /**
     * Reject what cannot be hashed safely.
     *
     * @throws RuntimeException with a message safe to show the operator (this
     *         is reached from the CLI account tool, not from a web form).
     */
    public static function guard(string $password): void
    {
        if (strlen($password) < self::MIN_LENGTH) {
            throw new RuntimeException(
                'Password must be at least ' . self::MIN_LENGTH . ' characters.'
            );
        }

        if (!self::argon2Available() && strlen($password) > self::BCRYPT_MAX_BYTES) {
            throw new RuntimeException(
                'Argon2id is not available in this PHP build, so bcrypt is in use, and bcrypt '
                . 'silently truncates beyond ' . self::BCRYPT_MAX_BYTES . ' bytes. '
                . 'Use a shorter password, or enable Argon2id.'
            );
        }
    }

    /** @return array<string, int> */
    private static function options(): array
    {
        if (self::argon2Available()) {
            return [
                'memory_cost' => (int) Config::get('auth.argon.memory_cost', 65536),
                'time_cost'   => (int) Config::get('auth.argon.time_cost', 3),
                'threads'     => (int) Config::get('auth.argon.threads', 1),
            ];
        }

        return ['cost' => (int) Config::get('auth.bcrypt_cost', 12)];
    }

    /** Human-readable description for the dashboard health panel. */
    public static function describe(): string
    {
        if (!self::argon2Available()) {
            return 'bcrypt (cost ' . Config::get('auth.bcrypt_cost', 12)
                 . ') — Argon2id unavailable in this PHP build';
        }

        return sprintf(
            'Argon2id (%d MiB, %d iterations, %d thread)',
            (int) Config::get('auth.argon.memory_cost', 65536) / 1024,
            (int) Config::get('auth.argon.time_cost', 3),
            (int) Config::get('auth.argon.threads', 1)
        );
    }
}
