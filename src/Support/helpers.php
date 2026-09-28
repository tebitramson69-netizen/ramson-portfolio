<?php

/**
 * Global helpers.
 *
 * Deliberately few. Each one exists because it is used at dozens of sites in
 * templates and the alternative is noise that people stop reading — which, in
 * the case of e(), is how escaping gets forgotten.
 */

declare(strict_types=1);

use App\Core\Config;

if (!function_exists('e')) {
    /**
     * Escape for HTML body text and quoted attribute values.
     *
     * ENT_QUOTES escapes both quote styles; ENT_SUBSTITUTE replaces invalid
     * UTF-8 with U+FFFD instead of returning an empty string, which would
     * silently blank the output.
     *
     * This is NOT correct for a URL, a JavaScript literal or inside a <script>
     * or <style> block — see e_attr_url() and e_js().
     */
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('e_url')) {
    /**
     * Escape a URL for an href/src attribute.
     *
     * Rejects anything that is not http, https, mailto or a site-relative
     * path, which closes off javascript: and data: URLs arriving from the
     * database once the CMS can store links.
     */
    function e_url(?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return '';
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        $allowed = $scheme === null
            ? str_starts_with($url, '/') || str_starts_with($url, '#')
            : in_array(strtolower((string) $scheme), ['http', 'https', 'mailto'], true);

        return $allowed ? e($url) : '';
    }
}

if (!function_exists('e_js')) {
    /** Encode a value for safe embedding in a JavaScript or JSON-LD context. */
    function e_js(mixed $value): string
    {
        return (string) json_encode(
            $value,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
    }
}

if (!function_exists('asset')) {
    /**
     * URL for a file in public/, with a cache-busting version from its mtime.
     *
     * Lets CSS and JS be served with a long immutable cache while an edit
     * still reaches visitors on the next request.
     */
    function asset(string $path): string
    {
        $relative = '/' . ltrim($path, '/');
        $file     = dirname(__DIR__, 2) . '/public' . $relative;
        $base     = base_path();

        if (is_file($file)) {
            return $base . $relative . '?v=' . filemtime($file);
        }

        return $base . $relative;
    }
}

if (!function_exists('base_path')) {
    /** Path prefix the app is mounted under ('' at a domain root). */
    function base_path(): string
    {
        static $base = null;

        if ($base === null) {
            $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
            $base   = rtrim(str_replace('\\', '/', dirname($script)), '/');
        }

        return $base;
    }
}

if (!function_exists('route_url')) {
    /** Site-relative URL for an application path. */
    function route_url(string $path = '/'): string
    {
        $path = '/' . ltrim($path, '/');

        return $path === '/' ? (base_path() ?: '/') : base_path() . $path;
    }
}

if (!function_exists('absolute_url')) {
    /** Absolute URL, for canonical tags and Open Graph. */
    function absolute_url(string $path = '/'): string
    {
        return rtrim((string) Config::get('app.url'), '/') . '/' . ltrim($path, '/');
    }
}
