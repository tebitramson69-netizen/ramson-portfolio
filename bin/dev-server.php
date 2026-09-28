<?php

/**
 * Router script for PHP's built-in development server.
 *
 *     php -S localhost:8000 -t public bin/dev-server.php
 *
 * Apache handles this with mod_rewrite in public/.htaccess; the built-in
 * server has no .htaccess, so it needs the same "serve real files, send
 * everything else to the front controller" rule expressed in PHP.
 *
 * This is a development convenience only — useful for a quick check without
 * starting XAMPP. It is never used in production, where Apache serves
 * public/ directly.
 */

declare(strict_types=1);

$publicDirectory = dirname(__DIR__) . '/public';

$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$file = $publicDirectory . '/' . ltrim(rawurldecode($path), '/');

// Let the server deliver existing static files (CSS, JS, uploads) itself.
if ($path !== '/' && is_file($file)) {
    return false;
}

require $publicDirectory . '/index.php';
