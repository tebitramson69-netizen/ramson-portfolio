#!/usr/bin/env php
<?php

/**
 * Phase 9 self-check — self-hosted fonts, a 'self' CSP, crawler files and
 * measured colour contrast.
 *
 *     php bin/verify-phase9.php
 *
 * CLI only. Read-only: it reads files and the database but changes nothing.
 *
 * Every assertion here exists because the thing it checks fails SILENTLY.
 * A font that quietly falls back to Georgia, a CSP that quietly re-admits a
 * third party, a contrast ratio that quietly drifts under 4.5 — none of them
 * produce an error, and all of them are invisible to the person who made the
 * change. That is the whole case for checking them mechanically.
 *
 * Exit status is 0 when every assertion passes, 1 otherwise.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("This script is CLI-only.\n");
}

require __DIR__ . '/../src/Core/Autoloader.php';

use App\Core\Autoloader;
use App\Core\Config;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectRepository;
use App\Domain\Media\MediaRepository;

Autoloader::register('App', __DIR__ . '/../src');
Config::load(__DIR__ . '/../config');

$root = dirname(__DIR__);
$pass = 0;
$fail = 0;

function heading(string $text): void
{
    echo "\n" . $text . "\n" . str_repeat('-', strlen($text)) . "\n";
}

function check(string $label, bool $ok, string $detail = ''): void
{
    global $pass, $fail;
    $ok ? $pass++ : $fail++;
    printf("  [%s] %s%s\n", $ok ? 'PASS' : 'FAIL', $label, $detail === '' ? '' : ' — ' . $detail);
}

/** Relative luminance, WCAG 2.1 definition. */
function luminance(string $hex): float
{
    $hex = ltrim($hex, '#');
    $channel = static function (int $v): float {
        $c = $v / 255;

        return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    };

    return 0.2126 * $channel((int) hexdec(substr($hex, 0, 2)))
         + 0.7152 * $channel((int) hexdec(substr($hex, 2, 2)))
         + 0.0722 * $channel((int) hexdec(substr($hex, 4, 2)));
}

function contrast(string $a, string $b): float
{
    $la = luminance($a);
    $lb = luminance($b);

    return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
}

echo "Phase 9 self-check\n==================\n";

$css = (string) file_get_contents($root . '/public/assets/css/main.css');

// ------------------------------------------------------------- 1. FONTS

heading('1. Fonts are served from this origin');

preg_match_all('/@font-face\s*\{(.*?)\}/s', $css, $faces);
$blocks = $faces[1];

check('main.css declares @font-face rules', $blocks !== [], count($blocks) . ' faces');

$external = 0;
$missing  = [];
$sources  = [];

foreach ($blocks as $block) {
    if (preg_match('#url\(\s*[\'"]?(https?://[^\'")]+)#i', $block)) {
        $external++;
        continue;
    }

    if (preg_match('#url\(\s*[\'"]?([^\'")]+)#', $block, $m)) {
        $relative = $m[1];
        $sources[] = $relative;
        $path = realpath($root . '/public/assets/css/' . $relative);

        if ($path === false || !is_file($path)) {
            $missing[] = $relative;
        }
    }
}

check('No @font-face loads from a third-party origin', $external === 0, $external . ' external');
check('Every @font-face file exists on disk', $missing === [], implode(', ', $missing));

// Google serves a variable font once and repeats it under each weight, so
// naming faces by weight stores the same bytes several times.
$hashes = [];
foreach (glob($root . '/public/assets/fonts/*.woff2') ?: [] as $file) {
    $hashes[] = md5_file($file);
}
check(
    'No duplicate font files',
    count($hashes) === count(array_unique($hashes)),
    count($hashes) . ' files, ' . count(array_unique($hashes)) . ' distinct',
);

$head = (string) file_get_contents($root . '/templates/partials/head-meta.php');
check(
    'The document head loads no third-party font',
    !str_contains($head, 'fonts.googleapis.com') && !str_contains($head, 'fonts.gstatic.com'),
);

// ---------------------------------------------------------------- 2. CSP

heading('2. Content-Security-Policy admits no third party');

$csp     = (array) Config::get('csp', []);
$offends = [];

foreach ($csp as $directive => $sources) {
    foreach ((array) $sources as $source) {
        if (preg_match('#^https?://#i', (string) $source)) {
            $offends[] = $directive . ' ' . $source;
        }
    }
}

check('No directive names an external host', $offends === [], implode('; ', $offends));
check("style-src is 'self' only", ($csp['style-src'] ?? []) === ["'self'"]);
check("font-src is 'self' only", ($csp['font-src'] ?? []) === ["'self'"]);
check("script-src has no 'unsafe-inline'", !in_array("'unsafe-inline'", (array) ($csp['script-src'] ?? []), true));

// ------------------------------------------------------------ 3. CONTRAST

heading('3. Colour contrast, measured from the tokens');

/** Pull a hex token straight out of the stylesheet, so this cannot drift. */
$token = static function (string $name) use ($css): ?string {
    return preg_match('/--' . preg_quote($name, '/') . ':\s*(#[0-9A-Fa-f]{6})/', $css, $m)
        ? strtoupper($m[1])
        : null;
};

// Against the LIGHTEST surface a token is used on, which is the worst case.
// Measuring against the darkest is how a failing ratio gets labelled AA.
$worstSurface = $token('surface-overlay');

check('--surface-overlay is readable from the stylesheet', $worstSurface !== null, (string) $worstSurface);

if ($worstSurface !== null) {
    foreach ([
        'fg-primary'   => 7.0,   // AAA
        'fg-secondary' => 7.0,   // AAA
        'fg-tertiary'  => 4.5,   // AA, normal text — meta lines and eyebrows
        'accent'       => 4.5,
    ] as $name => $floor) {
        $hex = $token($name);

        if ($hex === null) {
            check("--{$name} is readable from the stylesheet", false);
            continue;
        }

        $ratio = contrast($hex, $worstSurface);
        check(
            sprintf('--%s clears %.1f:1 on the lightest surface', $name, $floor),
            $ratio >= $floor,
            sprintf('%s measures %.2f:1', $hex, $ratio),
        );
    }

    $accent   = $token('accent');
    $accentFg = $token('accent-fg');

    if ($accent !== null && $accentFg !== null) {
        $ratio = contrast($accentFg, $accent);
        check(
            '--accent-fg clears 4.5:1 on --accent',
            $ratio >= 4.5,
            sprintf('%.2f:1', $ratio),
        );
    }
}

check('The stylesheet honours prefers-reduced-motion', str_contains($css, 'prefers-reduced-motion'));
check('Focus is styled with :focus-visible', str_contains($css, ':focus-visible'));

// ------------------------------------------------- 4. CRAWLERS AND DRAFTS

heading('4. Crawler files cannot leak a draft');

check(
    'The static public/robots.txt is gone, so the generated one wins',
    !is_file($root . '/public/robots.txt'),
    'public/.htaccess serves a real file before the front controller',
);

$sitemapSource = (string) file_get_contents($root . '/src/Http/Controllers/SitemapController.php');

check(
    'The sitemap reads through a published-only method',
    str_contains($sitemapSource, 'findAllPublished')
        && !preg_match('/findAll\s*\(|findAny/', $sitemapSource),
);

check(
    'robots.txt emits an ABSOLUTE Sitemap URL',
    str_contains($sitemapSource, "absolute_url('/sitemap.xml')"),
    'a relative path is invalid per the robots.txt spec and is ignored',
);

$published = (new ProjectRepository(new MediaRepository()))->findAllPublished();

$leaked = array_filter($published, static fn (Project $p): bool => !$p->isPublished());

check(
    'findAllPublished() returns published projects only',
    $leaked === [],
    count($published) . ' projects, ' . count($leaked) . ' not published',
);

$withTimestamps = array_filter($published, static fn (Project $p): bool => $p->lastModified() !== null);

check(
    'Published projects carry a <lastmod> date',
    count($withTimestamps) === count($published),
    count($withTimestamps) . ' of ' . count($published),
);

printf("\n%d passed, %d failed\n", $pass, $fail);

exit($fail === 0 ? 0 : 1);
