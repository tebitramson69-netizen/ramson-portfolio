<?php

/**
 * Front controller — the single entry point for every request.
 *
 * Everything above this directory (src, config, database, storage, templates)
 * is outside the document root and unreachable over HTTP.
 */

declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\Kernel;
use App\Core\Request;

$basePath = dirname(__DIR__);

require $basePath . '/src/Core/Autoloader.php';

// Composer takes over when the first real dependency arrives (Phase 6). Until
// then the project runs with no install step, which matters on XAMPP.
if (is_file($basePath . '/vendor/autoload.php')) {
    require $basePath . '/vendor/autoload.php';
} else {
    Autoloader::register('App', $basePath . '/src');
}

$kernel = new Kernel($basePath);

// ONLY the bootstrap is wrapped. Once boot() returns, ErrorHandler is
// registered and owns every failure — it logs with a reference and renders
// the styled 500 page. Wrapping handle() here as well would swallow those
// exceptions before the registered handler ever saw them, silently losing
// both the log entry and the designed error page.
try {
    $kernel->boot();
} catch (Throwable $e) {
    // Bootstrap failed before the error handler existed — most likely a
    // missing config/config.php. Keep this last resort dependency-free.
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');

    $debug   = getenv('APP_DEBUG') !== 'false';
    $message = $debug
        ? htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        : 'The application could not start.';

    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
       . '<title>Application error</title></head>'
       . '<body style="background:#0A0B0D;color:#A8ADB8;font-family:system-ui;padding:3rem;line-height:1.6">'
       . '<h1 style="color:#F4F5F7;font-size:1.25rem">Application error</h1>'
       . '<p>' . $message . '</p></body></html>';

    exit(1);
}

$kernel->handle(Request::fromGlobals())->send();
