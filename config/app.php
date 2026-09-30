<?php

/**
 * Committed application defaults.
 *
 * These are decisions about the application, not about the machine it runs
 * on, so they belong in version control. Anything machine-specific or
 * secret lives in config/config.php instead.
 */

declare(strict_types=1);

return [

    /**
     * Content-Security-Policy.
     *
     * 'unsafe-inline' is deliberately ABSENT from script-src. That is why
     * there is no inline JavaScript anywhere in the templates.
     *
     * fonts.googleapis.com / fonts.gstatic.com are allowed only because
     * Phase 1 loads Instrument Serif and Inter from Google Fonts. Phase 9
     * self-hosts them as subsetted WOFF2, at which point both hosts are
     * removed from this policy and it collapses to 'self' throughout.
     */
    'csp' => [
        'default-src'     => ["'self'"],
        'script-src'      => ["'self'"],
        'style-src'       => ["'self'", 'https://fonts.googleapis.com'],
        'font-src'        => ["'self'", 'https://fonts.gstatic.com'],
        'img-src'         => ["'self'", 'data:'],
        'connect-src'     => ["'self'"],
        'object-src'      => ["'none'"],
        'base-uri'        => ["'self'"],
        'form-action'     => ["'self'"],
        'frame-ancestors' => ["'none'"],
    ],

    /**
     * Authentication.
     *
     * Argon2id is used whenever the PHP build provides it. OWASP's floor is
     * 19 MiB / t=2 / p=1, which measured 18 ms here — cheap for an attacker
     * too. A login happens rarely, so the cost is raised well above the
     * floor; more memory buys more GPU resistance than more iterations.
     * 64 MiB keeps it comfortable on modest shared hosting.
     */
    'auth' => [
        'argon' => [
            'memory_cost' => 65536,   // KiB — 64 MiB
            'time_cost'   => 3,
            'threads'     => 1,
        ],

        // Only reached when Argon2id is absent from the PHP build.
        'bcrypt_cost' => 12,

        'throttle' => [
            'max_attempts'  => 5,
            'decay_seconds' => 900,   // 15 minutes
        ],

        // Server-enforced idle timeout for an admin session.
        'idle_timeout' => 7200,       // 2 hours
    ],

    /**
     * Upload limits, read by the media pipeline in Phase 5. Declared now so
     * the schema and the validator agree from the start.
     */
    'uploads' => [
        'max_bytes'      => 5 * 1024 * 1024,
        'min_dimension'  => 400,
        'max_dimension'  => 8000,
        'max_pixels'     => 40_000_000,
        'allowed_mime'   => ['image/jpeg', 'image/png', 'image/webp'],
    ],
];
