<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Core\Database;
use App\Domain\Media\Media;
use App\Domain\Media\MediaRepository;
use App\Domain\Skill\Skill;
use PDO;

/**
 * The only place that reads the projects tables.
 *
 * PUBLICATION IS ENFORCED HERE, NOT IN TEMPLATES.
 *
 * Public methods are named findPublished*; the admin methods that arrive in
 * Phase 6 will be named findAll*. Two distinct names, rather than a
 * $includeDrafts flag, because a flag defaults to something and a default is
 * exactly how a draft leaks. A template cannot leak a draft it was never
 * handed, and a reviewer can grep for "findAll" in the public controllers to
 * prove none is there.
 *
 * Every list method batches its child queries. The natural per-project loop is
 * the N+1 that would otherwise appear the first time the home page renders
 * two projects with technologies.
 */
final class ProjectRepository
{
    private const PUBLISHED = "p.publication = 'published' AND p.deleted_at IS NULL";

    public function __construct(private readonly MediaRepository $media)
    {
    }

    // ---------------------------------------------------------------- public

    /**
     * Featured first, then by explicit order. Used by the home page.
     *
     * @return list<Project>
     */
    public function findPublishedForHome(int $limit = 4): array
    {
        $sql = 'SELECT ' . $this->columns() . '
                FROM projects p
                ' . $this->mediaJoins() . '
                WHERE ' . self::PUBLISHED . '
                ORDER BY p.is_featured DESC, p.sort_order ASC, p.id ASC
                LIMIT :limit';

        $statement = Database::connection()->prepare($sql);
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->execute();

        return $this->hydrateList($statement->fetchAll(PDO::FETCH_ASSOC), withChildren: false);
    }

    /** @return list<Project> */
    public function findAllPublished(): array
    {
        $sql = 'SELECT ' . $this->columns() . '
                FROM projects p
                ' . $this->mediaJoins() . '
                WHERE ' . self::PUBLISHED . '
                ORDER BY p.is_featured DESC, p.sort_order ASC, p.id ASC';

        return $this->hydrateList(
            Database::connection()->query($sql)->fetchAll(PDO::FETCH_ASSOC),
            withChildren: false
        );
    }

    /**
     * A single published project with its full case study.
     *
     * Returns null for a slug that does not exist, is a draft, is archived or
     * is soft-deleted — the controller turns that into a real 404, so an
     * unpublished project is unreachable by URL rather than merely unlinked.
     */
    public function findPublishedBySlug(string $slug): ?Project
    {
        $sql = 'SELECT ' . $this->columns() . '
                FROM projects p
                ' . $this->mediaJoins() . '
                WHERE p.slug = :slug AND ' . self::PUBLISHED . '
                LIMIT 1';

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['slug' => $slug]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->hydrateList([$row], withChildren: true)[0] ?? null;
    }

    /**
     * Slugs of published projects, in display order — for previous/next
     * navigation without loading every project.
     *
     * @return list<array{slug:string, title:string, category_label:string}>
     */
    public function publishedNavigation(): array
    {
        $sql = "SELECT p.slug, p.title, p.category_label
                FROM projects p
                WHERE " . self::PUBLISHED . "
                ORDER BY p.is_featured DESC, p.sort_order ASC, p.id ASC";

        /** @var list<array{slug:string, title:string, category_label:string}> */
        return Database::connection()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // ----------------------------------------------------------------- admin

    /**
     * EVERY project, whatever its publication state, including soft-deleted
     * ones when asked.
     *
     * Named findAll* rather than taking an $includeDrafts flag, for the reason
     * in the class docblock: a flag has a default, and a default is how a draft
     * leaks onto a public page. A reviewer proves the public side is clean by
     * grepping the public controllers for "findAll" and finding nothing.
     *
     * @return list<Project>
     */
    public function findAll(bool $includeDeleted = false): array
    {
        $where = $includeDeleted ? '1 = 1' : 'p.deleted_at IS NULL';

        $sql = 'SELECT ' . $this->columns() . '
                FROM projects p
                ' . $this->mediaJoins() . '
                WHERE ' . $where . '
                ORDER BY p.deleted_at IS NOT NULL, p.is_featured DESC,
                         p.sort_order ASC, p.id ASC';

        return $this->hydrateList(
            Database::connection()->query($sql)->fetchAll(PDO::FETCH_ASSOC),
            withChildren: false
        );
    }

    /** Soft-deleted projects only, for the restore list. @return list<Project> */
    public function findAllDeleted(): array
    {
        $sql = 'SELECT ' . $this->columns() . '
                FROM projects p
                ' . $this->mediaJoins() . '
                WHERE p.deleted_at IS NOT NULL
                ORDER BY p.deleted_at DESC';

        return $this->hydrateList(
            Database::connection()->query($sql)->fetchAll(PDO::FETCH_ASSOC),
            withChildren: false
        );
    }

    /**
     * One project by id with its full case study, whatever its state.
     *
     * The editor needs this; nothing on the public side may call it, which is
     * why it is an id lookup rather than a slug one — a URL carries a slug, so
     * an id-only method cannot be reached by guessing a public address.
     */
    public function findAnyById(int $id): ?Project
    {
        $sql = 'SELECT ' . $this->columns() . '
                FROM projects p
                ' . $this->mediaJoins() . '
                WHERE p.id = :id
                LIMIT 1';

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['id' => $id]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : ($this->hydrateList([$row], withChildren: true)[0] ?? null);
    }

    /**
     * One project by slug whatever its state — for the signed-in draft preview
     * only. WorkController still uses findPublishedBySlug and must continue to:
     * this method is reached only after the kernel's auth guard has run.
     */
    public function findAnyBySlug(string $slug): ?Project
    {
        $sql = 'SELECT ' . $this->columns() . '
                FROM projects p
                ' . $this->mediaJoins() . '
                WHERE p.slug = :slug AND p.deleted_at IS NULL
                LIMIT 1';

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['slug' => $slug]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : ($this->hydrateList([$row], withChildren: true)[0] ?? null);
    }

    // --------------------------------------------------------------- private

    private function columns(): string
    {
        return 'p.id, p.slug, p.title, p.category_label, p.problem_statement, p.summary,
                p.role, p.year_label, p.status_label, p.publication, p.is_featured,
                p.github_url, p.live_url, p.reading_minutes,
                t.id AS thumb_id, t.storage_key AS thumb_key, t.mime_type AS thumb_mime,
                t.width AS thumb_w, t.height AS thumb_h, t.alt_text AS thumb_alt,
                UNIX_TIMESTAMP(t.updated_at) AS thumb_ts,
                c.id AS cover_id, c.storage_key AS cover_key, c.mime_type AS cover_mime,
                c.width AS cover_w, c.height AS cover_h, c.alt_text AS cover_alt,
                UNIX_TIMESTAMP(c.updated_at) AS cover_ts';
    }

    private function mediaJoins(): string
    {
        return 'LEFT JOIN media t ON t.id = p.thumbnail_media_id
                LEFT JOIN media c ON c.id = p.cover_media_id';
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<Project>
     */
    private function hydrateList(array $rows, bool $withChildren): array
    {
        if ($rows === []) {
            return [];
        }

        $ids = array_map(static fn (array $r): int => (int) $r['id'], $rows);

        // Batched child fetches — one query each, never one per project.
        $technologies = $this->technologiesFor($ids);
        $sections     = $withChildren ? $this->sectionsFor($ids) : [];
        $features     = $withChildren ? $this->featuresFor($ids) : [];

        // Media variants for every thumbnail and cover in a single query.
        $mediaIds = [];
        foreach ($rows as $row) {
            foreach (['thumb_id', 'cover_id'] as $key) {
                if ($row[$key] !== null) {
                    $mediaIds[] = (int) $row[$key];
                }
            }
        }
        $variants = $mediaIds === [] ? [] : $this->media->variantsFor(array_values(array_unique($mediaIds)));

        $projects = [];

        foreach ($rows as $row) {
            $id = (int) $row['id'];

            $projects[] = new Project(
                id:               $id,
                slug:             (string) $row['slug'],
                title:            (string) $row['title'],
                categoryLabel:    (string) $row['category_label'],
                problemStatement: (string) $row['problem_statement'],
                summary:          $row['summary'] !== null ? (string) $row['summary'] : null,
                role:             (string) $row['role'],
                yearLabel:        (string) $row['year_label'],
                statusLabel:      (string) $row['status_label'],
                publication:      (string) $row['publication'],
                isFeatured:       (bool) $row['is_featured'],
                githubUrl:        $row['github_url'] !== null ? (string) $row['github_url'] : null,
                liveUrl:          $row['live_url'] !== null ? (string) $row['live_url'] : null,
                thumbnail:        $this->mediaFrom($row, 'thumb', $variants),
                cover:            $this->mediaFrom($row, 'cover', $variants),
                readingMinutes:   (int) $row['reading_minutes'],
                sections:         $sections[$id] ?? [],
                features:         $features[$id] ?? [],
                technologies:     $technologies[$id] ?? [],
            );
        }

        return $projects;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, list<\App\Domain\Media\MediaVariant>> $variants
     */
    private function mediaFrom(array $row, string $prefix, array $variants): ?Media
    {
        if ($row[$prefix . '_id'] === null) {
            return null;
        }

        $id = (int) $row[$prefix . '_id'];

        return $this->media->hydrate([
            'id'          => $id,
            'storage_key' => $row[$prefix . '_key'],
            'mime_type'   => $row[$prefix . '_mime'],
            'width'       => $row[$prefix . '_w'],
            'height'      => $row[$prefix . '_h'],
            'alt_text'    => $row[$prefix . '_alt'],
            'updated_ts'  => $row[$prefix . '_ts'],
        ], $variants[$id] ?? []);
    }

    /**
     * @param list<int> $ids
     * @return array<int, list<Skill>>
     */
    private function technologiesFor(array $ids): array
    {
        $in = $this->placeholders($ids);

        $statement = Database::connection()->prepare(
            "SELECT pt.project_id, pt.is_primary, s.id, s.name, s.slug,
                    sc.name AS category_name
             FROM project_technologies pt
             JOIN skills s           ON s.id = pt.skill_id
             JOIN skill_categories sc ON sc.id = s.category_id
             WHERE pt.project_id IN ({$in})
             ORDER BY pt.project_id, pt.is_primary DESC, pt.sort_order, s.name"
        );
        $statement->execute($ids);

        $grouped = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $grouped[(int) $row['project_id']][] = Skill::fromRow($row);
        }

        return $grouped;
    }

    /**
     * @param list<int> $ids
     * @return array<int, list<ProjectSection>>
     */
    private function sectionsFor(array $ids): array
    {
        $in = $this->placeholders($ids);

        $statement = Database::connection()->prepare(
            "SELECT project_id, section_key, heading, body, sort_order
             FROM project_sections
             WHERE project_id IN ({$in})
             ORDER BY project_id, sort_order"
        );
        $statement->execute($ids);

        $grouped = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $grouped[(int) $row['project_id']][] = ProjectSection::fromRow($row);
        }

        return $grouped;
    }

    /**
     * @param list<int> $ids
     * @return array<int, list<ProjectFeature>>
     */
    private function featuresFor(array $ids): array
    {
        $in = $this->placeholders($ids);

        $statement = Database::connection()->prepare(
            "SELECT project_id, title, description
             FROM project_features
             WHERE project_id IN ({$in})
             ORDER BY project_id, sort_order"
        );
        $statement->execute($ids);

        $grouped = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $grouped[(int) $row['project_id']][] = ProjectFeature::fromRow($row);
        }

        return $grouped;
    }

    /**
     * Positional placeholders for an IN clause.
     *
     * The count comes from an array of integers the application built itself,
     * never from request data, and every value is still bound — nothing is
     * interpolated into the SQL but the placeholder string.
     *
     * @param list<int> $ids
     */
    private function placeholders(array $ids): string
    {
        return implode(',', array_fill(0, count($ids), '?'));
    }
}
