#!/usr/bin/env php
<?php

/**
 * Production readiness check — and a way to evaluate a host BEFORE paying.
 *
 *     php bin/verify-production.php
 *
 * Two jobs, one script.
 *
 * Before you buy: upload the repository to a trial account and run this. It
 * answers, from the host itself, whether this application can run there —
 * PHP version, GD, fileinfo, exif, the upload ceiling, writable directories.
 * A feature list on a sales page is a claim; this is a measurement. Most
 * hosts have a refund window, and that window is only useful if something
 * actually tests the server inside it.
 *
 * After you deploy: run it again and expect every line to pass. Running it
 * on a LOCAL machine should FAIL several checks — debug on, a root database
 * user, an http:// URL — and that is the point. A check that passes
 * everywhere is checking nothing.
 *
 * CLI ONLY, and never exposed over HTTP. It prints the database user, the
 * PHP configuration and the directory layout, which is precisely the
 * information an attacker scanning the site would like. On a shared host
 * with no SSH, run it from cPanel's Terminal, or as a one-off cron job that
 * mails you the output — both keep it off the public web.
 *
 * Exit status: 0 when nothing FAILS, 1 otherwise. WARN does not fail the run.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("This script is CLI-only, deliberately. See the comment at the top of the file.\n");
}

require __DIR__ . '/../src/Core/Autoloader.php';

use App\Core\Autoloader;
use App\Core\Config;
use App\Core\Database;
use App\Domain\Admin\DashboardStats;

Autoloader::register('App', __DIR__ . '/../src');

$root = dirname(__DIR__);

if (!is_file($root . '/config/config.php')) {
    exit("  FAIL  config/config.php does not exist. Copy config.example.php and fill it in.\n");
}

Config::load($root . '/config');

$pass = 0;
$warn = 0;
$fail = 0;

function heading(string $text): void
{
    echo "\n" . $text . "\n" . str_repeat('-', strlen($text)) . "\n";
}

/** @param 'PASS'|'WARN'|'FAIL' $status */
function report(string $label, string $status, string $detail = ''): void
{
    global $pass, $warn, $fail;

    match ($status) {
        'PASS' => $pass++,
        'WARN' => $warn++,
        default => $fail++,
    };

    printf("  [%s] %-46s %s\n", $status, $label, $detail);
}

function verdict(bool $ok, string $label, string $detail = ''): void
{
    report($label, $ok ? 'PASS' : 'FAIL', $detail);
}

echo "Production readiness\n====================\n";
echo "  host      : " . php_uname('n') . "\n";
echo "  php       : " . PHP_VERSION . ' (' . PHP_SAPI . ")\n";
echo "  root      : " . $root . "\n";

// ------------------------------------------------- 1. CAN THIS HOST RUN IT

heading('1. Can this host run the application at all');

// Reused wholesale from the admin dashboard rather than restated here. These
// are the same checks the owner sees at /admin, so the two can never give
// different answers about the same server.
foreach ((new DashboardStats())->health() as $check) {
    // Debug mode is covered properly in section 2, with production context.
    if ($check['label'] === 'Debug mode off') {
        continue;
    }

    verdict((bool) $check['ok'], (string) $check['label'], (string) $check['detail']);
}

// ------------------------------------------------------- 2. IS IT IN PROD

heading('2. Is this configured as production');

$env = (string) Config::get('app.env', 'local');
verdict($env === 'production', 'APP_ENV is production', 'currently "' . $env . '"');

verdict(
    !Config::isDebug(),
    'APP_DEBUG is off',
    Config::isDebug() ? 'ON — a stack trace would be shown to visitors' : 'errors show no internal detail',
);

$url = (string) Config::get('app.url', '');
verdict(
    str_starts_with($url, 'https://'),
    'APP_URL is https',
    $url === '' ? 'not set' : $url,
);

// Session cookies take `secure` from the request, not from configuration
// (src/Core/Session.php), so an https APP_URL is what makes them secure in
// practice. Stated rather than silently assumed.
report(
    'Session cookie security',
    str_starts_with($url, 'https://') ? 'PASS' : 'WARN',
    'derived from the request; httponly and SameSite=Strict are always set',
);

// --------------------------------------------------------- 3. THE DATABASE

heading('3. Database credentials');

$user = (string) Config::get('database.username', '');
$pwd  = (string) Config::get('database.password', '');

verdict(
    $user !== 'root',
    'Database user is not root',
    $user === 'root'
        ? 'running as root — a SQL injection anywhere becomes DROP DATABASE'
        : 'user "' . $user . '"',
);

verdict($pwd !== '', 'Database password is set', $pwd === '' ? 'EMPTY' : str_repeat('*', 8));

try {
    Database::connection()->query('SELECT 1');
    report('Database reachable', 'PASS', (string) Config::get('database.database', ''));
} catch (\Throwable $e) {
    report('Database reachable', 'FAIL', $e->getMessage());
}

// ------------------------------------------------------- 4. APACHE MODULES

heading('4. Apache modules the .htaccess files rely on');

// apache_get_modules() exists only under mod_php. Under CGI, FastCGI or PHP-FPM
// — which is most shared hosting — there is no way to ask from PHP. Saying
// UNKNOWN is the honest answer; printing PASS because a function was missing
// would be the worst of both.
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();

    foreach ([
        'mod_rewrite' => 'pretty URLs — without it every page is a 404',
        'mod_headers' => 'security headers on static files',
        'mod_expires' => 'the one-year asset cache',
        'mod_deflate' => 'compression; the site works without it, just heavier',
    ] as $module => $why) {
        $present = in_array($module, $modules, true);
        report($module, $present ? 'PASS' : ($module === 'mod_deflate' ? 'WARN' : 'FAIL'), $why);
    }
} else {
    report(
        'Apache modules',
        'WARN',
        'cannot be read under ' . PHP_SAPI . ' — check in a browser instead (see 06-DEPLOYMENT.md)',
    );
}

// ------------------------------------------------------------ 5. THE FILES

heading('5. Files that must or must not be there');

verdict(
    !is_file($root . '/public/robots.txt'),
    'No static public/robots.txt',
    'it would shadow the generated route',
);

$exampleHash = is_file($root . '/config/config.example.php')
    ? md5_file($root . '/config/config.example.php')
    : null;

verdict(
    $exampleHash === null || md5_file($root . '/config/config.php') !== $exampleHash,
    'config.php is not an unedited copy of the example',
);

report(
    'storage/ is denied by .htaccess',
    is_file($root . '/storage/.htaccess') ? 'PASS' : 'FAIL',
    'backups and sessions live here',
);

$fonts = count(glob($root . '/public/assets/fonts/*.woff2') ?: []);
verdict($fonts >= 8, 'Self-hosted fonts uploaded', $fonts . ' .woff2 files');

printf("\n  PASS %d    WARN %d    FAIL %d\n", $pass, $warn, $fail);

if ($fail > 0) {
    echo "\n  Not ready. Everything marked FAIL above has to be fixed first.\n";
} elseif ($warn > 0) {
    echo "\n  No failures. Read the warnings — each names what could not be checked from here.\n";
} else {
    echo "\n  Ready.\n";
}

echo "\n  Reminder: the browser-only checks in docs/portfolio/06-DEPLOYMENT.md\n";
echo "  (/.git/config -> 403, a draft URL -> 404) cannot be done from the CLI.\n\n";

exit($fail === 0 ? 0 : 1);
