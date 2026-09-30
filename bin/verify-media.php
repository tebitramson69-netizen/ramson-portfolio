#!/usr/bin/env php
<?php

/**
 * Media pipeline self-check.
 *
 *     php bin/verify-media.php
 *
 * CLI only, and it touches NOTHING: no database, no uploads directory, no
 * configuration file. It builds its own fixtures in the system temp directory,
 * runs them through the real ImageProcessor and ImageValidator, and deletes
 * them again.
 *
 * It exists because the three failures this pipeline is prone to are all
 * silent. A sideways phone photo, a crop that decapitates its subject, and an
 * upload limit that php.ini quietly overrides each produce a plausible-looking
 * result rather than an error, so each needs an assertion that fails loudly
 * instead of a code comment claiming it works.
 *
 * Exit status is 0 when every assertion passes, 1 otherwise — so it can be
 * run before a deployment and believed.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("This script is CLI-only.\n");
}

require __DIR__ . '/../src/Core/Autoloader.php';

use App\Core\Autoloader;
use App\Core\Config;
use App\Domain\Media\ImageProcessor;
use App\Domain\Media\ImageValidator;
use App\Http\Controllers\Admin\ProfileController;

Autoloader::register('App', __DIR__ . '/../src');
Config::load(__DIR__ . '/../config');

$pass = 0;
$fail = 0;
$temp = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'rp-media-check';

if (!is_dir($temp) && !mkdir($temp, 0700, true) && !is_dir($temp)) {
    exit("Could not create {$temp}\n");
}

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

/* ------------------------------------------------------------ environment */

heading('Environment');

check('GD extension', extension_loaded('gd'));
check('exif extension', extension_loaded('exif'), 'without it a phone photo stays sideways');
check('fileinfo extension', extension_loaded('fileinfo'), 'without it the MIME sniff cannot run');

$formats = [];
foreach (['jpeg', 'png', 'webp', 'avif'] as $format) {
    $formats[$format] = ImageProcessor::supports($format);
}
check('JPEG encoder', $formats['jpeg'], 'the fallback every browser understands');
echo '  ..   optional: '
    . 'WebP ' . ($formats['webp'] ? 'yes' : 'no')
    . ', AVIF ' . ($formats['avif'] ? 'yes' : 'no') . "\n";

$configured = (int) Config::get('uploads.max_bytes', 5 * 1024 * 1024);
$uploadMax  = ProfileController::iniBytes((string) ini_get('upload_max_filesize'));
$postMax    = ProfileController::iniBytes((string) ini_get('post_max_size'));

check(
    'upload_max_filesize covers the configured limit',
    $uploadMax === 0 || $uploadMax >= $configured,
    'php.ini ' . ini_get('upload_max_filesize') . ' vs configured ' . ImageValidator::formatBytes($configured)
);
check(
    'post_max_size exceeds upload_max_filesize',
    $postMax === 0 || $postMax > $uploadMax,
    'a body at the limit plus its form fields must still fit'
);

$memory = ImageProcessor::memoryAvailable();
$worst  = ImageProcessor::estimateMemory(1, (int) Config::get('uploads.max_pixels', 24_000_000));
check(
    'memory_limit covers the largest accepted image',
    $memory === null || $memory >= $worst,
    'need ' . ImageValidator::formatBytes($worst) . ', have '
        . ($memory === null ? 'unlimited' : ImageValidator::formatBytes($memory))
);

/* ------------------------------------------------- EXIF orientation, 1..8 */

heading('EXIF orientation');

/**
 * Write a JPEG whose PIXELS are stored exactly as a camera carrying this
 * orientation tag would store them — the inverse of the correction. Loading it
 * must therefore reproduce the original, which is a real assertion rather than
 * a restatement of the code under test.
 */
function orientationFixture(int $orientation, string $path): void
{
    $width  = 200;
    $height = 100;

    $image = imagecreatetruecolor($width, $height);
    imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, imagecolorallocate($image, 30, 30, 30));
    imagefilledrectangle($image, 0, 0, 59, 29, imagecolorallocate($image, 255, 0, 0));

    [$rotate, $flip] = match ($orientation) {
        2 => [0,   IMG_FLIP_HORIZONTAL],
        3 => [180, null],
        4 => [0,   IMG_FLIP_VERTICAL],
        5 => [270, IMG_FLIP_HORIZONTAL],
        6 => [270, null],
        7 => [90,  IMG_FLIP_HORIZONTAL],
        8 => [90,  null],
        default => [0, null],
    };

    // Inverse of "rotate then flip" is "flip then rotate the other way".
    if ($flip !== null) {
        imageflip($image, $flip);
    }

    $stored = $rotate === 0 ? $image : imagerotate($image, 360 - $rotate, 0);

    ob_start();
    imagejpeg($stored, null, 98);
    $jpeg = (string) ob_get_clean();

    // Minimal little-endian TIFF header carrying only tag 0x0112.
    $tiff = 'II' . pack('v', 42) . pack('V', 8) . pack('v', 1)
          . pack('v', 0x0112) . pack('v', 3) . pack('V', 1) . pack('v', $orientation) . "\x00\x00"
          . pack('V', 0);
    $app1 = "Exif\x00\x00" . $tiff;

    file_put_contents($path, "\xFF\xD8\xFF\xE1" . pack('n', strlen($app1) + 2) . $app1 . substr($jpeg, 2));
}

foreach ([1, 2, 3, 4, 5, 6, 7, 8] as $orientation) {
    $path = $temp . DIRECTORY_SEPARATOR . 'orient-' . $orientation . '.jpg';
    orientationFixture($orientation, $path);

    $info  = getimagesize($path);
    $image = ImageProcessor::load($path, (int) ($info[2] ?? IMAGETYPE_JPEG));

    $colour = imagecolorat($image, 8, 8);
    $red    = (($colour >> 16) & 0xFF) > 180 && (($colour >> 8) & 0xFF) < 80 && ($colour & 0xFF) < 80;
    $size   = imagesx($image) . 'x' . imagesy($image);

    imagedestroy($image);
    @unlink($path);

    check(
        'orientation ' . $orientation . ' corrected',
        $size === '200x100' && $red,
        $size . ', marker ' . ($red ? 'top-left' : 'MISPLACED')
    );
}

/* -------------------------------------------------------------- crop rules */

heading('Cropping');

/** A portrait with a white marker in the top fifth — the stand-in for a face. */
function portraitFixture(int $width, int $height): GdImage
{
    $image = imagecreatetruecolor($width, $height);
    imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, imagecolorallocate($image, 20, 40, 90));
    imagefilledrectangle(
        $image,
        (int) ($width * 0.25), (int) ($height * 0.04),
        (int) ($width * 0.75), (int) ($height * 0.20),
        imagecolorallocate($image, 255, 255, 255)
    );

    return $image;
}

/** Does the marker survive this crop? */
function markerVisible(GdImage $image): bool
{
    $width  = imagesx($image);
    $height = imagesy($image);

    for ($y = 0; $y < $height; $y++) {
        $colour = imagecolorat($image, (int) ($width / 2), $y);

        if ((($colour >> 16) & 0xFF) > 200 && (($colour >> 8) & 0xFF) > 200 && ($colour & 0xFF) > 200) {
            return true;
        }
    }

    return false;
}

$source = portraitFixture(1200, 1500);

/** @var array<string, array{width:int, height:int, formats:list<string>}> $variants */
$variants = (array) Config::get('uploads.variants', []);

foreach ($variants as $name => $spec) {
    $derived = ImageProcessor::cover($source, (int) $spec['width'], (int) $spec['height']);

    $expected = round((int) $spec['width'] / (int) $spec['height'], 3);
    $actual   = round(imagesx($derived) / imagesy($derived), 3);

    check(
        "'{$name}' keeps its aspect ratio",
        abs($expected - $actual) < 0.01,
        imagesx($derived) . 'x' . imagesy($derived) . ' (' . $actual . ' vs ' . $expected . ')'
    );

    check(
        "'{$name}' keeps the subject in frame",
        markerVisible($derived),
        'a centred crop loses it on a tall source'
    );

    imagedestroy($derived);
}

// Never upscale: a small source must yield a smaller variant, not a soft one.
$tiny    = portraitFixture(420, 525);
$derived = ImageProcessor::cover($tiny, 800, 1000);
check(
    'a small source is not upscaled',
    imagesx($derived) <= 420,
    'got ' . imagesx($derived) . 'x' . imagesy($derived) . ' from a 420x525 source'
);
imagedestroy($derived);
imagedestroy($tiny);

/* ---------------------------------------------- encoding and EXIF stripping */

heading('Encoding');

foreach (array_keys(array_filter($formats)) as $format) {
    $out = $temp . DIRECTORY_SEPARATOR . 'encoded.' . $format;
    $ok  = ImageProcessor::encode($source, (string) $format, $out, 82);

    check(
        strtoupper((string) $format) . ' encodes',
        $ok && is_file($out) && filesize($out) > 0,
        $ok ? ImageValidator::formatBytes((int) filesize($out)) : 'encoder returned false'
    );

    @unlink($out);
}

// A transparent PNG must flatten to white as JPEG, not to black.
$alpha = imagecreatetruecolor(200, 200);
imagesavealpha($alpha, true);
imagefill($alpha, 0, 0, imagecolorallocatealpha($alpha, 0, 0, 0, 127));

$flat = $temp . DIRECTORY_SEPARATOR . 'flat.jpg';
ImageProcessor::encode($alpha, 'jpeg', $flat, 90);

$read   = imagecreatefromjpeg($flat);
$corner = imagecolorat($read, 4, 4);
check(
    'transparency flattens to white as JPEG',
    (($corner >> 16) & 0xFF) > 230,
    'a transparent PNG otherwise turns black'
);
imagedestroy($read);
imagedestroy($alpha);
@unlink($flat);

// Re-encoding must drop the EXIF block, GPS coordinates included.
$withExif = $temp . DIRECTORY_SEPARATOR . 'exif.jpg';
orientationFixture(6, $withExif);

$stripped = $temp . DIRECTORY_SEPARATOR . 'stripped.jpg';
$loaded   = ImageProcessor::load($withExif, IMAGETYPE_JPEG);
ImageProcessor::encode($loaded, 'jpeg', $stripped, 90);
imagedestroy($loaded);

$before = @exif_read_data($withExif) ?: [];
$after  = @exif_read_data($stripped) ?: [];

check(
    'the fixture really carried EXIF',
    isset($before['Orientation']),
    'otherwise the next assertion proves nothing'
);
check(
    're-encoding strips EXIF',
    !isset($after['Orientation']),
    'this is what removes GPS coordinates from a phone photo'
);

@unlink($withExif);
@unlink($stripped);
imagedestroy($source);

/* ----------------------------------------------------------- the validator */

heading('Validation');

/**
 * Exercise the validator without a real HTTP upload.
 *
 * is_uploaded_file() is false for any file this script wrote, which is exactly
 * the check that must reject a caller-chosen path — so that rejection is the
 * assertion, and the rest of the pipeline is verified through the HTTP tests.
 */
$notAnUpload = $temp . DIRECTORY_SEPARATOR . 'not-an-upload.jpg';
$decoy       = portraitFixture(800, 1000);
imagejpeg($decoy, $notAnUpload, 90);
imagedestroy($decoy);

$validator = new ImageValidator();

$result = $validator->validate([
    'name'     => 'photo.jpg',
    'type'     => 'image/jpeg',
    'tmp_name' => $notAnUpload,
    'error'    => UPLOAD_ERR_OK,
    'size'     => (int) filesize($notAnUpload),
]);
check(
    'a path that is not an upload is refused',
    $result['errors'] !== [],
    $result['errors'][0] ?? 'accepted, which would be a local file read'
);

$result = $validator->validate([
    'name' => 'photo.jpg', 'type' => 'image/jpeg', 'tmp_name' => '',
    'error' => UPLOAD_ERR_INI_SIZE, 'size' => 0,
]);
check(
    'UPLOAD_ERR_INI_SIZE names the php.ini limit',
    $result['errors'] !== [] && str_contains($result['errors'][0], (string) ini_get('upload_max_filesize')),
    $result['errors'][0] ?? 'no message'
);

$result = $validator->validate([
    'name' => '', 'type' => '', 'tmp_name' => '',
    'error' => UPLOAD_ERR_NO_FILE, 'size' => 0,
]);
check(
    'an empty field is refused',
    $result['errors'] !== [],
    $result['errors'][0] ?? 'no message'
);

@unlink($notAnUpload);
@rmdir($temp);

/* ------------------------------------------------------------------ result */

echo "\n";
printf("  %d passed, %d failed\n\n", $pass, $fail);

exit($fail === 0 ? 0 : 1);
