<?php

declare(strict_types=1);

namespace App\Domain\Media;

use App\Core\Database;
use PDO;

/**
 * Media persistence.
 */
final class MediaRepository
{
    public function find(int $id): ?Media
    {
        $statement = Database::connection()->prepare(
            'SELECT id, storage_key, mime_type, width, height, alt_text,
                    UNIX_TIMESTAMP(updated_at) AS updated_ts
             FROM media
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);

        $row = $statement->fetch();

        return $row === false ? null : $this->hydrate($row, $this->variantsFor([$id])[$id] ?? []);
    }

    /**
     * Fetch variants for several media rows in ONE query.
     *
     * Written this way from the start because the obvious per-row version is
     * the N+1 that would otherwise appear the moment a project gallery is
     * rendered.
     *
     * @param list<int> $mediaIds
     * @return array<int, list<MediaVariant>>
     */
    public function variantsFor(array $mediaIds): array
    {
        if ($mediaIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($mediaIds), '?'));

        $statement = Database::connection()->prepare(
            "SELECT media_id, variant, format, path, width, height
             FROM media_variants
             WHERE media_id IN ({$placeholders})
             ORDER BY media_id, variant, format"
        );
        $statement->execute(array_values($mediaIds));

        $grouped = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $grouped[(int) $row['media_id']][] = MediaVariant::fromRow($row);
        }

        return $grouped;
    }

    /**
     * @param array<string, mixed> $row
     * @param list<MediaVariant> $variants
     */
    public function hydrate(array $row, array $variants = []): Media
    {
        return new Media(
            id:         (int) $row['id'],
            storageKey: (string) $row['storage_key'],
            mimeType:   (string) $row['mime_type'],
            width:      (int) $row['width'],
            height:     (int) $row['height'],
            altText:    $row['alt_text'] !== null ? (string) $row['alt_text'] : null,
            updatedAt:  (int) $row['updated_ts'],
            variants:   $variants,
        );
    }

    /**
     * @param array{storage_key:string, original_name:string, mime_type:string,
     *              width:int, height:int, byte_size:int, checksum:string,
     *              alt_text:?string} $data
     */
    public function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO media
                (storage_key, original_name, mime_type, width, height, byte_size, checksum, alt_text)
             VALUES
                (:storage_key, :original_name, :mime_type, :width, :height, :byte_size, :checksum, :alt_text)'
        );
        $statement->execute($data);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @param list<array{variant:string, format:string, path:string,
     *                   width:int, height:int, bytes:int}> $variants
     */
    public function addVariants(int $mediaId, array $variants): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO media_variants (media_id, variant, format, path, width, height, byte_size)
             VALUES (:media_id, :variant, :format, :path, :width, :height, :bytes)'
        );

        foreach ($variants as $variant) {
            $statement->execute($variant + ['media_id' => $mediaId]);
        }
    }

    public function updateAltText(int $mediaId, ?string $altText): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE media SET alt_text = :alt WHERE id = :id'
        );
        $statement->execute(['alt' => $altText, 'id' => $mediaId]);
    }

    /** Variants cascade; a row still referenced elsewhere is refused by RESTRICT. */
    public function delete(int $mediaId): void
    {
        $statement = Database::connection()->prepare('DELETE FROM media WHERE id = :id');
        $statement->execute(['id' => $mediaId]);
    }

    public function count(): int
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM media')->fetchColumn();
    }
}
