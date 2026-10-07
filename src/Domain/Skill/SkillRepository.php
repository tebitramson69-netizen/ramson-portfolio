<?php

declare(strict_types=1);

namespace App\Domain\Skill;

use App\Core\Database;
use PDO;

/**
 * Skills grouped by category, for the skills section and the technology strip.
 */
final class SkillRepository
{
    /** @var array<string, list<Skill>>|null */
    private ?array $grouped = null;

    /** @return array<string, list<Skill>> category name => skills */
    public function visibleGroupedByCategory(): array
    {
        if ($this->grouped !== null) {
            return $this->grouped;
        }

        $rows = Database::connection()->query(
            "SELECT s.id, s.name, s.slug, sc.name AS category_name
             FROM skills s
             JOIN skill_categories sc ON sc.id = s.category_id
             WHERE s.is_visible = 1 AND sc.is_visible = 1
             ORDER BY sc.sort_order, sc.name, s.sort_order, s.name"
        )->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(string) $row['category_name']][] = Skill::fromRow($row);
        }

        return $this->grouped = $grouped;
    }

    /**
     * Flat list for the technology strip under the hero.
     *
     * Derived from the already-memoised grouped result rather than issuing a
     * second query: the home page renders both, and the strip is just the
     * first few skills in the same display order. One query instead of two,
     * for identical output.
     *
     * @return list<string>
     */
    public function stripNames(int $limit = 8): array
    {
        $names = [];

        foreach ($this->visibleGroupedByCategory() as $group) {
            foreach ($group as $skill) {
                $names[] = $skill->name;

                if (count($names) >= $limit) {
                    return $names;
                }
            }
        }

        return $names;
    }

    // ----------------------------------------------------------------- admin
    //
    // Named `all*` against the public `visible*` above, the same split
    // ProjectRepository uses. A flag would let a public template show hidden
    // rows by passing true; a different name means that mistake has to be
    // spelled out to happen.

    /**
     * Every category, hidden ones included, each with its skills.
     *
     * One query and a group in PHP rather than one query per category: the
     * admin screen renders all of them at once, and the N+1 would grow with
     * the vocabulary.
     *
     * @return list<array{id:int, name:string, slug:string, is_visible:bool, skills:list<array<string,mixed>>}>
     */
    public function allCategoriesWithSkills(): array
    {
        $categories = Database::connection()->query(
            'SELECT id, name, slug, sort_order, is_visible
             FROM skill_categories ORDER BY sort_order, name'
        )->fetchAll(PDO::FETCH_ASSOC);

        $skills = Database::connection()->query(
            'SELECT id, category_id, name, slug, sort_order, is_visible
             FROM skills ORDER BY sort_order, name'
        )->fetchAll(PDO::FETCH_ASSOC);

        $byCategory = [];
        foreach ($skills as $skill) {
            $byCategory[(int) $skill['category_id']][] = $skill;
        }

        return array_map(static fn (array $row): array => [
            'id'         => (int) $row['id'],
            'name'       => (string) $row['name'],
            'slug'       => (string) $row['slug'],
            'is_visible' => (bool) $row['is_visible'],
            'skills'     => $byCategory[(int) $row['id']] ?? [],
        ], $categories);
    }

    /** @return array<string, mixed>|null */
    public function findCategory(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, slug, is_visible FROM skill_categories WHERE id = :id'
        );
        $statement->execute(['id' => $id]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /** @return array<string, mixed>|null */
    public function findSkill(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, category_id, name, slug, is_visible FROM skills WHERE id = :id'
        );
        $statement->execute(['id' => $id]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }
}
