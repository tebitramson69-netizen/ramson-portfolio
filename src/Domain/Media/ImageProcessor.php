<?php

declare(strict_types=1);

namespace App\Domain\Media;

use GdImage;
use RuntimeException;

/**
 * Decode, orient, crop, resize and re-encode images with GD.
 *
 * Three things here are not obvious and are the reason this class exists
 * rather than a few inline calls:
 *
 * 1. EXIF ORIENTATION. GD ignores it. Verified: imagecreatefromjpeg() returns
 *    identical pixels for orientation 1, 3, 6 and 8, so a phone portrait —
 *    almost always orientation 6 — is decoded on its side. Because the
 *    re-encode strips EXIF (which is the security and privacy win), the
 *    rotation must be BAKED IN first or the photo is stored sideways forever.
 *    All eight orientation values are handled, including the mirrored ones.
 *
 * 2. MEMORY. GD holds a truecolor image at four bytes per pixel, so an
 *    8000x8000 source needs ~244 MB — measured — which exceeds a typical
 *    128 MB memory_limit and would produce a fatal error, not an exception,
 *    and therefore a blank 500 with nothing useful logged. The size is
 *    estimated and refused up front instead.
 *
 * 3. CROP ANCHOR. A centre crop to a tall frame cuts heads off, because faces
 *    sit in the upper part of a portrait. Tall crops anchor at 38% from the
 *    top instead of 50%.
 */
final class ImageProcessor
{
    /** Bytes per pixel GD uses for a truecolor image, plus headroom. */
    private const BYTES_PER_PIXEL = 4;
    private const MEMORY_HEADROOM = 2.2;

    /**
     * Where a vertical crop starts, as a fraction of the height being thrown
     * away. 0.5 would be centred; 0.38 leans towards the top.
     */
    private const PORTRAIT_ANCHOR = 0.38;

    /**
     * Hard ceiling on that offset, as a fraction of the SOURCE height.
     *
     * Needed because a fraction of the discarded height is the wrong measure
     * once the discard is large. Cropping 1200x1500 to the 1200x630 social
     * card throws away 870 px, and 38% of that starts the frame 330 px down —
     * past the face entirely. This cap keeps the frame within the top tenth of
     * the source no matter how much is being removed, which is the difference
     * between a social preview showing a face and one showing a torso.
     */
    private const MAX_HEADROOM = 0.10;

    /**
     * Estimated peak memory, in bytes, to hold a source of this size plus a
     * destination.
     */
    public static function estimateMemory(int $width, int $height): int
    {
        return (int) ($width * $height * self::BYTES_PER_PIXEL * self::MEMORY_HEADROOM);
    }

    /** Bytes PHP will still allow us. Null when memory_limit is unlimited. */
    public static function memoryAvailable(): ?int
    {
        $limit = trim((string) ini_get('memory_limit'));

        if ($limit === '' || $limit === '-1') {
            return null;
        }

        $bytes = (int) $limit;
        $unit  = strtolower(substr($limit, -1));
        $bytes *= match ($unit) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        };

        return max(0, $bytes - memory_get_usage(true));
    }

    /**
     * Load an image, with EXIF orientation already applied.
     *
     * @throws RuntimeException when the file cannot be decoded.
     */
    public static function load(string $path, int $imageType): GdImage
    {
        $image = match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default        => false,
        };

        if (!$image instanceof GdImage) {
            throw new RuntimeException('The image could not be decoded.');
        }

        // Preserve transparency for PNG and WebP sources.
        imagealphablending($image, false);
        imagesavealpha($image, true);

        if ($imageType === IMAGETYPE_JPEG) {
            $image = self::applyExifOrientation($image, $path);
        }

        return $image;
    }

    /**
     * Rotate and flip according to the EXIF Orientation tag.
     *
     * The eight values are the full set from the EXIF specification; 5 to 8
     * are mirrored, which a rotation alone cannot express, so they need a flip
     * as well. Skipping the mirrored cases is the usual half-fix.
     */
    public static function applyExifOrientation(GdImage $image, string $path): GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);

        if ($exif === false || !isset($exif['Orientation'])) {
            return $image;
        }

        $orientation = (int) $exif['Orientation'];

        // The EXIF value says what must be APPLIED to the stored pixels to
        // display them upright. For the mirrored values that is a rotation
        // FOLLOWED BY a flip — order matters, and doing it the other way
        // round produces a wrong result for 5 and 7 while still looking
        // correct for every other value. That is precisely why those two are
        // the ones usually left broken.
        //
        //   5 = transpose  (rotate 90 CW, then mirror horizontally)
        //   7 = transverse (rotate 270 CW, then mirror horizontally)
        //
        // imagerotate() takes ANTI-clockwise degrees, so 90 CW is 270 here.
        [$rotate, $flip] = match ($orientation) {
            2 => [0,   IMG_FLIP_HORIZONTAL],
            3 => [180, null],
            4 => [0,   IMG_FLIP_VERTICAL],
            5 => [270, IMG_FLIP_HORIZONTAL],
            6 => [270, null],
            7 => [90,  IMG_FLIP_HORIZONTAL],
            8 => [90,  null],
            default => [0, null],   // 1, or anything unexpected
        };

        if ($rotate !== 0) {
            $rotated = imagerotate($image, $rotate, 0);

            if ($rotated instanceof GdImage) {
                imagedestroy($image);
                imagealphablending($rotated, false);
                imagesavealpha($rotated, true);
                $image = $rotated;
            }
        }

        if ($flip !== null) {
            imageflip($image, $flip);
        }

        return $image;
    }

    /**
     * Crop to the target aspect ratio and scale to exactly width x height.
     *
     * Never upscales past the source: a small source produces a smaller
     * variant rather than a blurry enlargement.
     */
    public static function cover(GdImage $source, int $width, int $height): GdImage
    {
        $sourceWidth  = imagesx($source);
        $sourceHeight = imagesy($source);

        $targetRatio = $width / $height;
        $sourceRatio = $sourceWidth / $sourceHeight;

        if ($sourceRatio > $targetRatio) {
            // Source is wider: crop the sides, keep full height.
            $cropHeight = $sourceHeight;
            $cropWidth  = (int) round($sourceHeight * $targetRatio);
            $cropX      = (int) round(($sourceWidth - $cropWidth) / 2);
            $cropY      = 0;
        } else {
            // Source is taller: crop top and bottom.
            $cropWidth  = $sourceWidth;
            $cropHeight = (int) round($sourceWidth / $targetRatio);

            // Anchored high for EVERY vertical crop, not just the tall ones.
            // This branch only runs when height is being thrown away, and in
            // a portrait the face is in the upper part of the frame — so a
            // centred crop decapitates it just as readily at 1:1 (the avatar)
            // and at 1200x630 (the social card) as it does at 4:5.
            $discarded = $sourceHeight - $cropHeight;

            $cropX = 0;
            $cropY = (int) round(min(
                $discarded * self::PORTRAIT_ANCHOR,
                $sourceHeight * self::MAX_HEADROOM
            ));
        }

        // Never upscale. A source smaller than the requested frame yields a
        // smaller variant at the same aspect ratio rather than a soft
        // enlargement — which is why every variant's real dimensions are
        // stored per row instead of being assumed from the config.
        $scale = min(1.0, $cropWidth / $width, $cropHeight / $height);

        if ($scale < 1.0) {
            $width  = max(1, (int) round($width * $scale));
            $height = max(1, (int) round($height * $scale));
        }

        $destination = imagecreatetruecolor($width, $height);
        imagealphablending($destination, false);
        imagesavealpha($destination, true);

        imagecopyresampled(
            $destination, $source,
            0, 0,
            max(0, $cropX), max(0, $cropY),
            $width, $height,
            $cropWidth, $cropHeight
        );

        return $destination;
    }

    /**
     * Encode to a file.
     *
     * JPEG gets a white background flattened in, because a transparent PNG
     * encoded as JPEG otherwise turns black.
     */
    public static function encode(GdImage $image, string $format, string $path, int $quality): bool
    {
        if ($format === 'jpeg') {
            $flat = imagecreatetruecolor(imagesx($image), imagesy($image));
            imagefilledrectangle(
                $flat, 0, 0, imagesx($image) - 1, imagesy($image) - 1,
                imagecolorallocate($flat, 255, 255, 255)
            );
            imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

            $ok = imagejpeg($flat, $path, $quality);
            imagedestroy($flat);

            return $ok;
        }

        return match ($format) {
            'webp'  => function_exists('imagewebp') && imagewebp($image, $path, $quality),
            'avif'  => function_exists('imageavif') && imageavif($image, $path, $quality),
            'png'   => imagepng($image, $path),
            default => false,
        };
    }

    public static function supports(string $format): bool
    {
        return match ($format) {
            'jpeg', 'png' => true,
            'webp' => function_exists('imagewebp') && (bool) (gd_info()['WebP Support'] ?? false),
            'avif' => function_exists('imageavif') && (bool) (gd_info()['AVIF Support'] ?? false),
            default => false,
        };
    }
}
