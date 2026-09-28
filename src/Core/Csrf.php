<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Synchroniser-token CSRF protection.
 *
 * No form posts yet — the contact form arrives in Phase 8 and the admin in
 * Phase 4 — but the token lives here so that when the first POST route is
 * written there is no decision left to make and no excuse to skip it.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        Session::start();

        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION[self::SESSION_KEY];
    }

    /** Timing-safe comparison. A plain === leaks the token byte by byte. */
    public static function isValid(?string $candidate): bool
    {
        Session::start();

        $expected = $_SESSION[self::SESSION_KEY] ?? null;

        if (!is_string($expected) || !is_string($candidate) || $candidate === '') {
            return false;
        }

        return hash_equals($expected, $candidate);
    }

    /** Ready-to-echo hidden input. */
    public static function field(): string
    {
        return sprintf(
            '<input type="hidden" name="_token" value="%s">',
            htmlspecialchars(self::token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
    }
}
