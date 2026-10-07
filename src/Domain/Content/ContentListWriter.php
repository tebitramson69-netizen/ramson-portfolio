<?php

declare(strict_types=1);

namespace App\Domain\Content;

use App\Core\Database;

/**
 * Writes for the ordered content lists.
 *
 * One class for both lists rather than two identical ones. The table name is
 * never a string from the caller: it comes from the ContentList enum, so the
 * interpolation below can only ever produce a name this application declared.
 */
final class ContentListWriter
{
    public function __construct(private readonly ContentList $list)
    {
    }

    /** New rows go last, which is where someone adding one expects to find it. */
    public function create(string $title, string $description): int
    {
        $next = (int) Database::connection()
            ->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$this->list->value}")
            ->fetchColumn();

        Database::connection()->prepare(
            "INSERT INTO {$this->list->value} (title, description, sort_order)
             VALUES (:title, :description, :sort_order)"
        )->execute([
            'title'       => $title,
            'description' => $description,
            'sort_order'  => $next,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, string $title, string $description): void
    {
        Database::connection()->prepare(
            "UPDATE {$this->list->value}
             SET title = :title, description = :description
             WHERE id = :id"
        )->execute(['title' => $title, 'description' => $description, 'id' => $id]);
    }

    /**
     * Hiding is not deleting.
     *
     * A hidden row keeps its text, so the owner can take a service off the
     * site for a month and put it back without rewriting it. Deleting is the
     * separate, explicit action below.
     */
    public function setVisible(int $id, bool $visible): void
    {
        Database::connection()
            ->prepare("UPDATE {$this->list->value} SET is_visible = :v WHERE id = :id")
            ->execute(['v' => $visible ? 1 : 0, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        Database::connection()
            ->prepare("DELETE FROM {$this->list->value} WHERE id = :id")
            ->execute(['id' => $id]);
    }

    /**
     * Rewrite positions wholesale from the submitted order.
     *
     * Dense integers assigned from the posted sequence, inside a transaction —
     * the same approach as ProjectWriter::reorder(). Writing the positions the
     * form submitted verbatim would preserve whatever gaps and ties the author
     * typed, and a tie makes the list order depend on the id tiebreak rather
     * than on what they meant.
     *
     * @param list<int> $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        Database::transaction(function () use ($orderedIds): void {
            $statement = Database::connection()->prepare(
                "UPDATE {$this->list->value} SET sort_order = :sort_order WHERE id = :id"
            );

            $position = 1;

            foreach ($orderedIds as $id) {
                $statement->execute(['sort_order' => $position++, 'id' => (int) $id]);
            }
        });
    }
}
