<?php

declare(strict_types=1);

namespace App\Domain\Content;

/**
 * One row of an ordered, visibility-flagged content list.
 *
 * Services and process steps are the same thing structurally — a title, a
 * short body, a position and a visibility flag — so they share one value
 * object rather than two identical ones. What differs between them is where
 * they render and what the owner is being asked to write, neither of which is
 * the row's business.
 */
final class ContentItem
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $description,
        public readonly int $sortOrder,
        public readonly bool $isVisible,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id:          (int) $row['id'],
            title:       (string) $row['title'],
            description: (string) ($row['description'] ?? ''),
            sortOrder:   (int) ($row['sort_order'] ?? 0),
            isVisible:   (bool) ($row['is_visible'] ?? true),
        );
    }
}
