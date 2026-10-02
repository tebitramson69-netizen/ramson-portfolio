<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Core\Database;
use PDOException;

/**
 * Every write to the projects tables.
 *
 * Separate from ProjectRepository, which reads. The reads share a large
 * hydration path that admin and public both need, so splitting those in two
 * would mean two copies that can drift; the writes share nothing with it, so
 * they live here and the read class stays a read class.
 *
 * Child collections are REPLACED rather than diffed: delete the project's rows,
 * insert the submitted set, inside one transaction. Diffing would mean matching
 * submitted items to existing ids, which is more code and more ways to be
 * wrong, for a form that posts the whole collection anyway. The transaction is
 * what makes "delete then insert" safe — a failure mid-way rolls back to the
 * previous set rather than leaving a project with no sections.
 */
final class ProjectWriter
{
    /** Columns the caller may set. A fixed allowlist: no request value ever
     *  reaches the SQL as an identifier. */
    private const FIELDS = [
        'slug', 'title', 'category_label', 'problem_statement', 'summary',
        'role', 'year_label', 'status_label', 'publication', 'is_featured',
        'github_url', 'live_url', 'reading_minutes',
    ];

    /**
     * @param array<string, mixed> $data
     * @return int|null The new id, or null when the slug is already taken.
     */
    public function create(array $data): ?int
    {
        $fields = array_intersect_key($data, array_flip(self::FIELDS));

        if (!isset($fields['slug'], $fields['title'])) {
            return null;
        }

        $columns      = implode(', ', array_keys($fields));
        $placeholders = ':' . implode(', :', array_keys($fields));

        try {
            Database::connection()
                ->prepare("INSERT INTO projects ({$columns}) VALUES ({$placeholders})")
                ->execute($fields);
        } catch (PDOException $e) {
            // A duplicate slug is a user mistake, not an exception the admin
            // should see as a 500. The unique index is on (slug, alive), so a
            // slug freed by a soft delete inserts fine and only a live clash
            // lands here.
            return $this->isDuplicate($e) ? null : throw $e;
        }

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     * @return bool False when the slug collides with another live project.
     */
    public function update(int $id, array $data): bool
    {
        $fields = array_intersect_key($data, array_flip(self::FIELDS));

        if ($fields === []) {
            return true;
        }

        $assignments = implode(', ', array_map(
            static fn (string $column): string => "{$column} = :{$column}",
            array_keys($fields)
        ));

        try {
            Database::connection()
                ->prepare("UPDATE projects SET {$assignments} WHERE id = :id")
                ->execute($fields + ['id' => $id]);
        } catch (PDOException $e) {
            return $this->isDuplicate($e) ? false : throw $e;
        }

        return true;
    }

    public function setPublication(int $id, string $state): void
    {
        if (!in_array($state, ['draft', 'published', 'archived'], true)) {
            return;
        }

        Database::connection()
            ->prepare('UPDATE projects SET publication = :state WHERE id = :id')
            ->execute(['state' => $state, 'id' => $id]);
    }

    public function setFeatured(int $id, bool $featured): void
    {
        Database::connection()
            ->prepare('UPDATE projects SET is_featured = :f WHERE id = :id')
            ->execute(['f' => $featured ? 1 : 0, 'id' => $id]);
    }

    public function setThumbnail(int $id, ?int $mediaId): void
    {
        Database::connection()
            ->prepare('UPDATE projects SET thumbnail_media_id = :m WHERE id = :id')
            ->execute(['m' => $mediaId, 'id' => $id]);
    }

    public function setCover(int $id, ?int $mediaId): void
    {
        Database::connection()
            ->prepare('UPDATE projects SET cover_media_id = :m WHERE id = :id')
            ->execute(['m' => $mediaId, 'id' => $id]);
    }

    /**
     * Soft delete. The `alive` generated column becomes NULL, which releases
     * the slug for reuse while the row and its history remain.
     */
    public function softDelete(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE projects SET deleted_at = NOW(), publication = :state WHERE id = :id')
            ->execute(['state' => 'draft', 'id' => $id]);
    }

    /**
     * @return bool False when the slug was taken by another project while this
     *              one was deleted — the caller renames it and tries again.
     */
    public function restore(int $id): bool
    {
        try {
            Database::connection()
                ->prepare('UPDATE projects SET deleted_at = NULL WHERE id = :id')
                ->execute(['id' => $id]);
        } catch (PDOException $e) {
            return $this->isDuplicate($e) ? false : throw $e;
        }

        return true;
    }

    /**
     * Rewrite sort_order from a list of ids, in one transaction.
     *
     * Dense integers rewritten wholesale, per the decision recorded in
     * 05-ARCHITECTURE.md §2.6b: fewer than twenty rows, reordered by one
     * person, so a single small UPDATE is correct and cannot drift.
     *
     * @param list<int> $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        Database::transaction(function () use ($orderedIds): void {
            $statement = Database::connection()
                ->prepare('UPDATE projects SET sort_order = :order WHERE id = :id');

            foreach (array_values($orderedIds) as $position => $id) {
                $statement->execute(['order' => $position, 'id' => (int) $id]);
            }
        });
    }

    /**
     * @param list<array{key:string, body:string}> $sections
     */
    public function replaceSections(int $projectId, array $sections): void
    {
        Database::transaction(function () use ($projectId, $sections): void {
            Database::connection()
                ->prepare('DELETE FROM project_sections WHERE project_id = :id')
                ->execute(['id' => $projectId]);

            $insert = Database::connection()->prepare(
                'INSERT INTO project_sections (project_id, section_key, heading, body, sort_order)
                 VALUES (:project_id, :section_key, :heading, :body, :sort_order)'
            );

            foreach ($sections as $section) {
                $key  = trim($section['key']);
                $body = trim($section['body']);

                // An empty body is a removal, not an empty section: the public
                // template would otherwise render a heading with nothing under
                // it, which reads as broken rather than as absent.
                if ($key === '' || $body === '') {
                    continue;
                }

                $insert->execute([
                    'project_id'  => $projectId,
                    'section_key' => mb_substr($key, 0, 40),
                    'heading'     => '',   // SectionKey::heading() supplies it
                    'body'        => $body,
                    // Canonical order, so a section added later still lands in
                    // the right place without the author sequencing anything.
                    'sort_order'  => SectionKey::position($key),
                ]);
            }
        });
    }

    /**
     * @param list<array{title:string, description:string}> $features
     */
    public function replaceFeatures(int $projectId, array $features): void
    {
        Database::transaction(function () use ($projectId, $features): void {
            Database::connection()
                ->prepare('DELETE FROM project_features WHERE project_id = :id')
                ->execute(['id' => $projectId]);

            $insert = Database::connection()->prepare(
                'INSERT INTO project_features (project_id, title, description, sort_order)
                 VALUES (:project_id, :title, :description, :sort_order)'
            );

            $position = 0;

            foreach ($features as $feature) {
                $title = trim($feature['title']);

                if ($title === '') {
                    continue;
                }

                $insert->execute([
                    'project_id'  => $projectId,
                    'title'       => mb_substr($title, 0, 200),
                    'description' => mb_substr(trim($feature['description']), 0, 500),
                    'sort_order'  => $position++,
                ]);
            }
        });
    }

    /**
     * @param list<int> $skillIds
     * @param list<int> $primaryIds
     */
    public function replaceTechnologies(int $projectId, array $skillIds, array $primaryIds = []): void
    {
        Database::transaction(function () use ($projectId, $skillIds, $primaryIds): void {
            Database::connection()
                ->prepare('DELETE FROM project_technologies WHERE project_id = :id')
                ->execute(['id' => $projectId]);

            $insert = Database::connection()->prepare(
                'INSERT INTO project_technologies (project_id, skill_id, is_primary, sort_order)
                 VALUES (:project_id, :skill_id, :is_primary, :sort_order)'
            );

            $position = 0;

            foreach (array_values(array_unique(array_map('intval', $skillIds))) as $skillId) {
                $insert->execute([
                    'project_id' => $projectId,
                    'skill_id'   => $skillId,
                    'is_primary' => in_array($skillId, array_map('intval', $primaryIds), true) ? 1 : 0,
                    'sort_order' => $position++,
                ]);
            }
        });
    }

    /** Media ids this project points at, so the caller can clean up files. */
    public function mediaIdsFor(int $projectId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT thumbnail_media_id, cover_media_id FROM projects WHERE id = :id'
        );
        $statement->execute(['id' => $projectId]);

        $row = $statement->fetch();

        if ($row === false) {
            return [];
        }

        return array_values(array_filter([
            $row['thumbnail_media_id'] !== null ? (int) $row['thumbnail_media_id'] : null,
            $row['cover_media_id'] !== null ? (int) $row['cover_media_id'] : null,
        ], static fn (?int $v): bool => $v !== null));
    }

    /** SQLSTATE 23000 with MySQL error 1062 is a unique-key violation. */
    private function isDuplicate(PDOException $e): bool
    {
        return $e->getCode() === '23000' && ($e->errorInfo[1] ?? 0) === 1062;
    }
}
