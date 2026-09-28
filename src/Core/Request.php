<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Immutable view of the incoming request.
 *
 * Everything the application knows about the request comes through here, so
 * superglobals are read in exactly one place.
 */
final class Request
{
    private function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly string $basePath,
        public readonly bool $isSecure,
        /** @var array<string, string> */
        public readonly array $query,
        /** @var array<string, mixed> */
        public readonly array $post,
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        // Base path supports installing under a subdirectory, which is the
        // normal XAMPP case (htdocs/ramson-portfolio/public).
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath   = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

        $uri  = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        $path = '/' . trim(rawurldecode($path), '/');

        return new self(
            method:   $method,
            path:     $path === '/' ? '/' : rtrim($path, '/'),
            basePath: $basePath,
            isSecure: self::detectHttps(),
            query:    array_map('strval', $_GET),
            post:     $_POST,
        );
    }

    private static function detectHttps(): bool
    {
        if (($_SERVER['HTTPS'] ?? '') !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        // Only trust a forwarding header when the deployment says to. Blindly
        // believing X-Forwarded-Proto lets a client claim HTTPS.
        if (Config::get('app.trust_proxy', false)) {
            return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        }

        return ((int) ($_SERVER['SERVER_PORT'] ?? 0)) === 443;
    }

    public function isGet(): bool
    {
        return $this->method === 'GET' || $this->method === 'HEAD';
    }

    /** Absolute URL for a path relative to the application root. */
    public function url(string $path = '/'): string
    {
        $base = rtrim((string) Config::get('app.url'), '/');
        return $base . '/' . ltrim($path, '/');
    }
}
