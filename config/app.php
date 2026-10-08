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
     * Phase 9 self-hosted the three families as latin WOFF2, so
     * fonts.googleapis.com and fonts.gstatic.com are gone from this policy
     * and every directive below is now 'self', 'none' or a scheme. Nothing
     * this site serves can reach a third party, and no third party can see
     * who reads it.
     *
     * Keep it that way. Adding an external host here is a decision about
     * the reader's privacy, not only about a dependency.
     */
    'csp' => [
        'default-src'     => ["'self'"],
        'script-src'      => ["'self'"],
        'style-src'       => ["'self'"],
        'font-src'        => ["'self'"],
        // blob: is required by the admin photo picker, which previews the
        // chosen file with URL.createObjectURL() before it is uploaded. That
        // preview is the only way to see a bad crop BEFORE committing it, and
        // blob: cannot carry a cross-origin payload — the URL is only valid
        // inside the document that created it.
        'img-src'         => ["'self'", 'data:', 'blob:'],
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
        'max_bytes'     => 5 * 1024 * 1024,
        'min_dimension' => 400,
        'max_dimension' => 8000,

        // GD holds a truecolor image at four bytes per pixel, so 24 MP costs
        // roughly 92 MB before the destination is allocated. Measured: an
        // 8000x8000 source needs ~244 MB, which exceeds a typical 128 MB
        // memory_limit and dies as a fatal error rather than an exception —
        // a blank 500 with nothing useful logged. 24 MP still accepts any
        // phone photo (a 12 MP camera produces 3000x4000).
        'max_pixels'    => 24_000_000,

        'allowed_mime'  => ['image/jpeg', 'image/png', 'image/webp'],

        /**
         * Derived sizes, and the formats each is written in.
         *
         * Measured encode cost for one 800x1000 variant: JPEG 3 ms (56 KB),
         * WebP 54 ms (27 KB), AVIF 707 ms (11 KB). AVIF is twelve times
         * slower but less than half the size, which is the right trade for
         * the two variants that actually appear on the page — the audience is
         * on Cameroonian mobile data and an upload happens twice a year.
         *
         * `og` is JPEG only on purpose: social-preview scrapers are the one
         * place where WebP and AVIF still cannot be relied on.
         */
        'variants' => [
            'hero'  => ['width' => 800,  'height' => 1000, 'formats' => ['avif', 'webp', 'jpeg']],

            // 16:10 landscape, to match `.frame__body`'s aspect-ratio exactly.
            // Project cards show a website screenshot, which is landscape; the
            // 4:5 `hero` crop is right for a portrait photograph and wrong for
            // this. Asking `hero` for a card meant the pipeline cropped a wide
            // screenshot tall, then object-fit: cover cropped it wide again —
            // a narrow vertical slice stretched across the frame.
            'wide'  => ['width' => 1280, 'height' => 800,  'formats' => ['avif', 'webp', 'jpeg']],
            'about' => ['width' => 600,  'height' => 750,  'formats' => ['avif', 'webp', 'jpeg']],
            'thumb' => ['width' => 160,  'height' => 160,  'formats' => ['webp', 'jpeg']],
            'og'    => ['width' => 1200, 'height' => 630,  'formats' => ['jpeg']],
        ],

        'quality' => ['jpeg' => 82, 'webp' => 82, 'avif' => 70],
    ],
];
