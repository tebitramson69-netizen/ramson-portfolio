<?php

declare(strict_types=1);

namespace App\Domain\Skill;

use App\Core\Database;
use PDOException;

/**
 * Every write to skill_categories and skills.
 *
 * Split from SkillRepository for the same reason ProjectWriter is split from
 * ProjectRepository: the reads share a grouping path that both the public site
 * and the admin need, and the writes share none of it.
 *
 * The vocabulary this manages is not cosmetic. One spelling of "MySQL" is used
 * by the technology strip, the project tags and the JSON-LD knowsAbout
 * property, so a rename here changes all three at once — which is the point of
 * there being one table.
 */
final class SkillWriter
{
    // ------------------------------------------------------------ categories

    /** @return int|null The new id, or null when the slug is already taken. */
    public function createCategory(string $name, string $slug): ?int
    {
        $next = $this->nextOrder('skill_categories');

        try {
            Database::connection()->prepare(
                'INSERT INTO skill_categories (name, slug, sort_order) VALUES (:name, :slug, :sort_order)'
            )->execute(['name' => $name, 'slug' => $slug, 'sort_order' => $next]);
        } catch (PDOException $e) {
            return $this->isDuplicate($e) ? null : throw $e;
        }

        return (int) Database::connection()->lastInsertId();
    }

    /** @return bool False when the slug collides with another category. */
    public function updateCategory(int $id, string $name, string $slug): bool
    {
        try {
            Database::connection()->prepare(
                'UPDATE skill_categories SET name = :name, slug = :slug WHERE id = :id'
            )->execute(['name' => $name, 'slug' => $slug, 'id' => $id]);
        } catch (PDOException $e) {
            return $this->isDuplicate($e) ? false : throw $e;
        }

        return true;
    }

    public function setCategoryVisible(int $id, bool $visible): void
    {
        Database::connection()
            ->prepare('UPDATE skill_categories SET is_visible = :v WHERE id = :id')
            ->execute(['v' => $visible ? 1 : 0, 'id' => $id]);
    }

    /**
     * Delete a category, but ONLY when it is empty.
     *
     * fk_skill_category is ON DELETE RESTRICT, so the database would refuse
     * this anyway — but it would refuse with a PDOException and a 500, which
     * tells the author nothing. Counting first turns that into a message
     * naming how many skills are in the way, and the caller can say so.
     *
     * Cascading instead was never an option. These rows are the site's
     * technology vocabulary, referenced by every project's tags; deleting a
     * category because someone clicked Delete would silently strip tags off
     * published case studies, and there is no undo for that.
     *
     * @return int Skills still in the category; 0 means it was deleted.
     */
    public function deleteCategory(int $id): int
    {
        $statement = Database::connection()->prepare(
            'SELECT COUNT(*) FROM skills WHERE category_id = :id'
        );
        $statement->execute(['id' => $id]);

        $inUse = (int) $statement->fetchColumn();

        if ($inUse > 0) {
            return $inUse;
        }

        Database::connection()
            ->prepare('DELETE FROM skill_categories WHERE id = :id')
            ->execute(['id' => $id]);

        return 0;
    }

    // ---------------------------------------------------------------- skills

    /** @return int|null The new id, or null when the slug is already taken. */
    public function createSkill(int $categoryId, string $name, string $slug): ?int
    {
        $next = $this->nextOrder('skills', $categoryId);

        try {
            Database::connection()->prepare(
                'INSERT INTO skills (category_id, name, slug, sort_order)
                 VALUES (:category_id, :name, :slug, :sort_order)'
            )->execute([
                'category_id' => $categoryId,
                'name'        => $name,
                'slug'        => $slug,
                'sort_order'  => $next,
            ]);
        } catch (PDOException $e) {
            return $this->isDuplicate($e) ? null : throw $e;
        }

        return (int) Database::connection()->lastInsertId();
    }

    /** @return bool False when the slug collides with another skill. */
    public function updateSkill(int $id, int $categoryId, string $name, string $slug): bool
    {
        try {
            Database::connection()->prepare(
                'UPDATE skills SET category_id = :category_id, name = :name, slug = :slug
                 WHERE id = :id'
            )->execute([
                'category_id' => $categoryId,
                'name'        => $name,
                'slug'        => $slug,
                'id'          => $id,
            ]);
        } catch (PDOException $e) {
            return $this->isDuplicate($e) ? false : throw $e;
        }

        return true;
    }

    public function setSkillVisible(int $id, bool $visible): void
    {
        Database::connection()
            ->prepare('UPDATE skills SET is_visible = :v WHERE id = :id')
            ->execute(['v' => $visible ? 1 : 0, 'id' => $id]);
    }

    /**
     * Delete a skill, but ONLY when no project tags it.
     *
     * Same reasoning as deleteCategory(): project_technologies references this
     * row, and a cascade would quietly remove a tag from a published case
     * study. Counting first lets the caller explain the refusal.
     *
     * @return int Projects still tagged with it; 0 means it was deleted.
     */
    public function deleteSkill(int $id): int
    {
        $statement = Database::connection()->prepare(
            'SELECT COUNT(*) FROM project_technologies WHERE skill_id = :id'
        );
        $statement->execute(['id' => $id]);

        $inUse = (int) $statement->fetchColumn();

        if ($inUse > 0) {
            return $inUse;
        }

        Database::connection()
            ->prepare('DELETE FROM skills WHERE id = :id')
            ->execute(['id' => $id]);

        return 0;
    }

    /**
     * Rewrite positions wholesale — see ContentListWriter::reorder().
     *
     * @param list<int> $orderedIds
     */
    public function reorderSkills(array $orderedIds): void
    {
        $this->reorder('skills', $orderedIds);
    }

    /** @param list<int> $orderedIds */
    public function reorderCategories(array $orderedIds): void
    {
        $this->reorder('skill_categories', $orderedIds);
    }

    /**
     * @param 'skills'|'skill_categories' $table Literal from this class only.
     * @param list<int>                   $orderedIds
     */
    private function reorder(string $table, array $orderedIds): void
    {
        Database::transaction(function () use ($table, $orderedIds): void {
            $statement = Database::connection()->prepare(
                "UPDATE {$table} SET sort_order = :sort_order WHERE id = :id"
            );

            $position = 1;

            foreach ($orderedIds as $id) {
                $statement->execute(['sort_order' => $position++, 'id' => (int) $id]);
            }
        });
    }

    /** @param 'skills'|'skill_categories' $table Literal from this class only. */
    private function nextOrder(string $table, ?int $categoryId = null): int
    {
        if ($categoryId === null) {
            return (int) Database::connection()
                ->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$table}")
                ->fetchColumn();
        }

        $statement = Database::connection()->prepare(
            "SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$table} WHERE category_id = :id"
        );
        $statement->execute(['id' => $categoryId]);

        return (int) $statement->fetchColumn();
    }

    /** SQLSTATE 23000 with MySQL error 1062 is a unique-key violation. */
    private function isDuplicate(PDOException $e): bool
    {
        return $e->getCode() === '23000' && ($e->errorInfo[1] ?? 0) === 1062;
    }
}
