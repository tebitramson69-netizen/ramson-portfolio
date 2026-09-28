<?php

declare(strict_types=1);

namespace App\Domain\Project;

final class ProjectFeature
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            title:       (string) $row['title'],
            description: (string) ($row['description'] ?? ''),
        );
    }
}
