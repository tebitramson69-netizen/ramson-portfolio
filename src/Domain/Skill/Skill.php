<?php

declare(strict_types=1);

namespace App\Domain\Skill;

final class Skill
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $categoryName = '',
        public readonly bool $isPrimary = false,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id:           (int) $row['id'],
            name:         (string) $row['name'],
            slug:         (string) $row['slug'],
            categoryName: (string) ($row['category_name'] ?? ''),
            isPrimary:    (bool) ($row['is_primary'] ?? false),
        );
    }
}
