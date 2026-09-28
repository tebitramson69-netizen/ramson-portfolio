<?php

/**
 * Local configuration template.
 *
 * Copy this file to config/config.php and edit it for your machine.
 * config/config.php is git-ignored and must NEVER be committed — it is the
 * only place real credentials live.
 *
 *     Windows:  copy config\config.example.php config\config.php
 *     Unix:     cp config/config.example.php config/config.php
 *
 * Why a PHP array rather than a .env file: parsing .env correctly needs a
 * library, and a returned array is opcache-compiled, type-safe and needs no
 * dependency. Any value may still be sourced from a real environment
 * variable via env() below, which keeps a future container deployment open.
 */

declare(strict_types=1);

/** Read an environment variable with a typed fallback. */
$env = static function (string $key, mixed $default = null): mixed {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return match (strtolower((string) $value)) {
        'true', '(true)'   => true,
        'false', '(false)' => false,
        'null', '(null)'   => null,
        default            => $value,
    };
};

return [

    'app' => [
        // 'local' | 'production'. Controls error display and cookie policy.
        'env'      => $env('APP_ENV', 'local'),

        // Never true in production. When env is 'production' the bootstrap
        // forces this to false regardless of what is set here.
        'debug'    => $env('APP_DEBUG', true),

        // Absolute base URL, no trailing slash. Used for canonical URLs and
        // Open Graph tags. On XAMPP with the project in htdocs this is
        // typically http://localhost/ramson-portfolio/public
        // With a virtual host pointing at public/, it is http://portfolio.test
        'url'      => $env('APP_URL', 'http://localhost/ramson-portfolio/public'),

        'timezone' => $env('APP_TIMEZONE', 'Africa/Douala'),
        'locale'   => $env('APP_LOCALE', 'en'),
    ],

    'database' => [
        'host'     => $env('DB_HOST', '127.0.0.1'),
        'port'     => (int) $env('DB_PORT', 3306),
        'database' => $env('DB_DATABASE', 'ramson_portfolio'),
        'username' => $env('DB_USERNAME', 'root'),

        // XAMPP ships with an empty root password. That is acceptable on a
        // local machine only. Create a dedicated least-privilege user with a
        // real password before deploying anywhere reachable — see
        // docs/portfolio/05-ARCHITECTURE.md.
        'password' => $env('DB_PASSWORD', ''),

        'charset'  => 'utf8mb4',
    ],

    'session' => [
        // Cookie name. The bootstrap upgrades this to the __Host- prefix
        // automatically when the request is served over HTTPS.
        'name'     => $env('SESSION_NAME', 'rp_session'),
        'lifetime' => 7200,   // idle timeout, seconds
    ],
];
