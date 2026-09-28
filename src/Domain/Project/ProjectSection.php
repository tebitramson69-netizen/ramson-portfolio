<?php

declare(strict_types=1);

namespace App\Domain\Project;

final class ProjectSection
{
    public function __construct(
        public readonly string $key,
        public readonly string $heading,
        public readonly string $body,
        public readonly int $sortOrder,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        $key      = (string) $row['section_key'];
        $override = trim((string) ($row['heading'] ?? ''));

        return new self(
            key:       $key,
            heading:   $override !== '' ? $override : SectionKey::heading($key),
            body:      (string) $row['body'],
            sortOrder: (int) $row['sort_order'],
        );
    }

    /** @return list<string> Paragraphs, for templating. */
    public function paragraphs(): array
    {
        $parts = preg_split('/\R{2,}/', trim($this->body)) ?: [];

        return array_values(array_filter(array_map('trim', $parts)));
    }
}
