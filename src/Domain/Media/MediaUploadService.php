<?php

declare(strict_types=1);

namespace App\Domain\Media;

use App\Core\Config;
use App\Core\Database;
use RuntimeException;
use Throwable;

/**
 * Turns an uploaded file into a media record with its derived variants.
 *
 * The order matters and is the whole design:
 *
 *   validate -> decode -> orient -> derive variants -> write files
 *            -> COMMIT database -> only then delete the old files
 *
 * Files are written before the transaction commits, and the OLD files are
 * removed only after it succeeds. If anything fails the database is untouched
 * and the previous photo is still live; the orphaned new files are cleaned up.
 * The site is never left pointing at a row that has no files, or a photo that
 * has no row.
 *
 * The uploaded bytes are NEVER stored. Every variant is decoded and
 * re-encoded, which strips EXIF — removing GPS coordinates from a phone photo
 * — and neutralises polyglot files and payloads hidden in metadata, because
 * such a payload simply does not survive a decode and re-encode.
 */
final class MediaUploadService
{
    public function __construct(
        private readonly MediaRepository $media,
        private readonly ImageValidator $validator,
        private readonly string $publicUploadRoot,
        private readonly string $storageUploadRoot,
    ) {
    }

    /**
     * @param array{name?:string, type?:string, tmp_name?:string, error?:int, size?:int} $file
     * @return array{media: Media|null, errors: list<string>}
     */
    public function store(array $file, string $altText, string $folder = 'profile'): array
    {
        $checked = $this->validator->validate($file);

        if ($checked['errors'] !== []) {
            return ['media' => null, 'errors' => $checked['errors']];
        }

        $path      = (string) $file['tmp_name'];
        $imageType = (int) $checked['type'];

        // Opaque, server-generated name. No user-controlled byte ever reaches
        // the filesystem; the original filename is kept only as a label.
        $storageKey = bin2hex(random_bytes(16));

        $written = [];

        try {
            $source = ImageProcessor::load($path, $imageType);
        } catch (Throwable $e) {
            return ['media' => null, 'errors' => ['That file could not be read as an image.']];
        }

        try {
            $this->ensureDirectories($folder);

            /** @var array<string, array{width:int,height:int,formats:list<string>}> $variants */
            $variants = (array) Config::get('uploads.variants', []);
            $quality  = (array) Config::get('uploads.quality', []);

            $rows = [];

            foreach ($variants as $name => $spec) {
                $derived = ImageProcessor::cover($source, (int) $spec['width'], (int) $spec['height']);

                foreach ($spec['formats'] as $format) {
                    if (!ImageProcessor::supports($format)) {
                        // A build without AVIF simply produces fewer variants;
                        // the <picture> element falls through to the next
                        // source. Failing the whole upload would be worse.
                        continue;
                    }

                    $relative = sprintf('%s/%s-%s.%s', $folder, $storageKey, $name, $format);
                    $absolute = $this->publicUploadRoot . '/' . $relative;

                    if (!ImageProcessor::encode($derived, $format, $absolute, (int) ($quality[$format] ?? 82))) {
                        throw new RuntimeException('Could not write the ' . $name . ' ' . $format . ' variant.');
                    }

                    $written[] = $absolute;

                    $rows[] = [
                        'variant' => $name,
                        'format'  => $format,
                        'path'    => $relative,
                        'width'   => imagesx($derived),
                        'height'  => imagesy($derived),
                        'bytes'   => (int) filesize($absolute),
                    ];
                }

                imagedestroy($derived);
            }

            if ($rows === []) {
                throw new RuntimeException('No image variants could be produced.');
            }

            // The canonical re-encoded original, kept outside the web root so
            // a future variant size can be derived without asking for the
            // photo again.
            $originalPath = $this->storageUploadRoot . '/' . $folder . '/' . $storageKey . '-original.jpg';
            ImageProcessor::encode($source, 'jpeg', $originalPath, 92);
            $written[] = $originalPath;

            $mediaId = Database::transaction(function () use ($storageKey, $file, $checked, $source, $originalPath, $altText, $rows): int {
                $id = $this->media->create([
                    'storage_key'   => $storageKey,
                    'original_name' => mb_substr(basename((string) ($file['name'] ?? 'upload')), 0, 255),
                    'mime_type'     => (string) $checked['mime'],
                    'width'         => imagesx($source),
                    'height'        => imagesy($source),
                    'byte_size'     => (int) filesize($originalPath),
                    'checksum'      => hash_file('sha256', $originalPath) ?: '',
                    'alt_text'      => $altText !== '' ? $altText : null,
                ]);

                $this->media->addVariants($id, $rows);

                return $id;
            });

            imagedestroy($source);

            $stored = $this->media->find($mediaId);

            if ($stored === null) {
                throw new RuntimeException('The media record could not be read back.');
            }

            return ['media' => $stored, 'errors' => []];
        } catch (Throwable $e) {
            // Nothing was committed, so remove the files we just wrote rather
            // than leaving them orphaned on disk.
            foreach ($written as $orphan) {
                @unlink($orphan);
            }

            if (isset($source)) {
                @imagedestroy($source);
            }

            return ['media' => null, 'errors' => ['The image could not be processed. ' . $e->getMessage()]];
        }
    }

    /**
     * Delete a media record and every file behind it.
     *
     * The database row goes first: a foreign key with ON DELETE RESTRICT will
     * refuse if anything still points at it, and that refusal must happen
     * BEFORE any file is removed. Deleting files first would leave the site
     * referencing images that no longer exist.
     */
    public function delete(int $mediaId): bool
    {
        $media = $this->media->find($mediaId);

        if ($media === null) {
            return false;
        }

        $paths    = [];
        $relatives = $media->variantPaths();

        foreach ($relatives as $relative) {
            $paths[] = $this->publicUploadRoot . '/' . $relative;
        }

        // The folder is read back from a variant's stored path rather than
        // assumed to be 'profile'. store() takes the folder as an argument, so
        // hard-coding it here would silently orphan the canonical original of
        // every media item stored anywhere else — a project screenshot, for
        // instance, once Phase 6 uploads them.
        $folder = $relatives === [] ? 'profile' : dirname((string) $relatives[0]);

        $paths[] = $this->storageUploadRoot . '/' . $folder . '/' . $media->storageKey . '-original.jpg';

        $this->media->delete($mediaId);

        foreach ($paths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        return true;
    }

    private function ensureDirectories(string $folder): void
    {
        foreach ([$this->publicUploadRoot . '/' . $folder, $this->storageUploadRoot . '/' . $folder] as $directory) {
            if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException('Could not create ' . $directory);
            }

            if (!is_writable($directory)) {
                throw new RuntimeException($directory . ' is not writable.');
            }
        }
    }
}
