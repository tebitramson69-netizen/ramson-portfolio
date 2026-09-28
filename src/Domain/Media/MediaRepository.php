<?php

declare(strict_types=1);

namespace App\Domain\Media;

use App\Core\Database;
use PDO;

/**
 * Read access to media. Writes arrive in Phase 5 with the upload pipeline.
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
}
