<?php

declare(strict_types=1);

namespace App\Domain\Media;

use App\Core\Config;

/**
 * Validation for an uploaded image.
 *
 * Ordered cheapest and most decisive first, and NOTHING the client sends is
 * trusted. The two checks that do most of the work are the real MIME sniff
 * and the dimension bounds; the extension and $_FILES['type'] are ignored
 * entirely, because both are supplied by the client and trivially forged.
 *
 * Returns a list of human-readable problems. Empty means acceptable.
 */
final class ImageValidator
{
    /**
     * @param array{name?:string, type?:string, tmp_name?:string, error?:int, size?:int} $file
     * @return array{errors: list<string>, type: int|null, width: int, height: int, mime: string}
     */
    public function validate(array $file): array
    {
        $result = ['errors' => [], 'type' => null, 'width' => 0, 'height' => 0, 'mime' => ''];

        // 1. Upload error codes. A silent failure here is the classic
        //    "why isn't my photo changing" bug, so each gets its own message.
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($error !== UPLOAD_ERR_OK) {
            $result['errors'][] = match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                    'That file is larger than the server accepts. The PHP limit is '
                    . ini_get('upload_max_filesize') . '.',
                UPLOAD_ERR_PARTIAL   => 'The upload was interrupted. Please try again.',
                UPLOAD_ERR_NO_FILE   => 'No file was selected.',
                UPLOAD_ERR_NO_TMP_DIR=> 'The server has no temporary directory configured.',
                UPLOAD_ERR_CANT_WRITE=> 'The server could not write the uploaded file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension blocked the upload.',
                default              => 'The upload failed.',
            };

            return $result;
        }

        $path = (string) ($file['tmp_name'] ?? '');

        // 2. The file must genuinely be an upload, not a path the caller chose.
        if ($path === '' || !is_uploaded_file($path)) {
            $result['errors'][] = 'That file was not received as an upload.';

            return $result;
        }

        // 3. Size, measured on disk rather than from the reported value.
        $bytes    = (int) filesize($path);
        $maxBytes = (int) Config::get('uploads.max_bytes', 5 * 1024 * 1024);

        if ($bytes > $maxBytes) {
            $result['errors'][] = sprintf(
                'That image is %s. The limit is %s.',
                self::formatBytes($bytes),
                self::formatBytes($maxBytes)
            );

            return $result;
        }

        if ($bytes === 0) {
            $result['errors'][] = 'That file is empty.';

            return $result;
        }

        // 4. REAL mime type. Never $_FILES['type'] (client-supplied) and never
        //    the filename extension.
        $mime = '';

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo !== false) {
                $mime = (string) finfo_file($finfo, $path);
                finfo_close($finfo);
            }
        }

        $allowed = (array) Config::get('uploads.allowed_mime', ['image/jpeg', 'image/png', 'image/webp']);

        if ($mime === '' || !in_array($mime, $allowed, true)) {
            $result['errors'][] = 'That is not a supported image. Accepted: JPEG, PNG and WebP.';

            return $result;
        }

        $result['mime'] = $mime;

        // 5. It must parse as a real image, not merely begin with the right
        //    magic bytes.
        $info = @getimagesize($path);

        if ($info === false || !isset($info[0], $info[1], $info[2])) {
            $result['errors'][] = 'That file could not be read as an image.';

            return $result;
        }

        [$width, $height, $type] = $info;
        $result['width']  = (int) $width;
        $result['height'] = (int) $height;
        $result['type']   = (int) $type;

        if (!in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            $result['errors'][] = 'That image format is not supported.';

            return $result;
        }

        // 6. Dimension bounds.
        $min = (int) Config::get('uploads.min_dimension', 400);
        $max = (int) Config::get('uploads.max_dimension', 8000);

        if ($width < $min || $height < $min) {
            // Rejected rather than upscaled: a blurry hero is worse than a
            // clear error.
            $result['errors'][] = sprintf(
                'That image is %d×%d. It needs to be at least %d pixels on both sides, '
                . 'or it will look soft in the hero.',
                $width, $height, $min
            );
        }

        if ($width > $max || $height > $max) {
            $result['errors'][] = sprintf('That image is %d×%d. The maximum is %d on either side.', $width, $height, $max);
        }

        // 7. Total pixels — a decompression-bomb guard. A small file can
        //    declare enormous dimensions.
        $pixels    = $width * $height;
        $maxPixels = (int) Config::get('uploads.max_pixels', 24_000_000);

        if ($pixels > $maxPixels) {
            $result['errors'][] = sprintf(
                'That image is %.1f megapixels. The maximum is %.0f.',
                $pixels / 1_000_000,
                $maxPixels / 1_000_000
            );
        }

        // 8. Will it actually fit in memory? GD dies with a fatal error, not
        //    an exception, so refusing here is the difference between a clear
        //    message and a blank 500.
        $needed    = ImageProcessor::estimateMemory($width, $height);
        $available = ImageProcessor::memoryAvailable();

        if ($available !== null && $needed > $available) {
            $result['errors'][] = sprintf(
                'That image needs about %s to process and only %s is available. '
                . 'Use a smaller image, or raise memory_limit.',
                self::formatBytes($needed),
                self::formatBytes($available)
            );
        }

        return $result;
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        return max(1, (int) round($bytes / 1024)) . ' KB';
    }
}
