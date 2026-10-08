<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Media\Media;
use App\Domain\Skill\Skill;

final class Project
{
    /**
     * @param list<ProjectSection> $sections
     * @param list<ProjectFeature> $features
     * @param list<Skill>          $technologies
     */
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $title,
        public readonly string $categoryLabel,
        public readonly string $problemStatement,
        public readonly ?string $summary,
        public readonly string $role,
        public readonly string $yearLabel,
        public readonly string $statusLabel,
        public readonly string $publication,
        public readonly bool $isFeatured,
        public readonly ?string $githubUrl,
        public readonly ?string $liveUrl,
        public readonly ?Media $thumbnail,
        public readonly ?Media $cover,
        public readonly int $readingMinutes,
        public readonly array $sections = [],
        public readonly array $features = [],
        public readonly array $technologies = [],

        /**
         * When the row last changed, as MySQL returns it.
         *
         * Carried for <lastmod> in the sitemap. Kept as the raw string rather
         * than a DateTimeImmutable because nothing else needs date arithmetic
         * on it, and a value object that parses dates nobody compares is
         * ceremony.
         */
        public readonly ?string $updatedAt = null,
    ) {
    }

    /** W3C datetime for <lastmod>, or null when the row has no timestamp. */
    public function lastModified(): ?string
    {
        if ($this->updatedAt === null) {
            return null;
        }

        $time = strtotime($this->updatedAt);

        return $time === false ? null : date('Y-m-d', $time);
    }

    public function isPublished(): bool
    {
        return $this->publication === 'published';
    }

    public function url(): string
    {
        return '/work/' . $this->slug;
    }

    public function hasLinks(): bool
    {
        return $this->githubUrl !== null || $this->liveUrl !== null;
    }

    /** Sections in display order, empty bodies excluded. */
    public function orderedSections(): array
    {
        $sections = array_values(array_filter(
            $this->sections,
            static fn (ProjectSection $s): bool => trim($s->body) !== ''
        ));

        usort(
            $sections,
            static fn (ProjectSection $a, ProjectSection $b): int
                => [SectionKey::position($a->key), $a->sortOrder]
                <=> [SectionKey::position($b->key), $b->sortOrder]
        );

        return $sections;
    }

    public function section(string $key): ?ProjectSection
    {
        foreach ($this->sections as $section) {
            if ($section->key === $key) {
                return $section;
            }
        }

        return null;
    }

    /** @return list<Skill> */
    public function primaryTechnologies(): array
    {
        return array_values(array_filter(
            $this->technologies,
            static fn (Skill $s): bool => $s->isPrimary
        ));
    }
}
