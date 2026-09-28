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

}
