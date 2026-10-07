<?php

declare(strict_types=1);

namespace App\Domain\Content;

use App\Core\Database;
use PDO;

/**
 * Reads for the ordered content lists (services, process steps).
 *
 * The method split mirrors ProjectRepository's: `visible*` is what the public
 * site may render, `all*` is the admin view. Naming them differently rather
 * than passing an $includeHidden flag means a public template cannot
 * accidentally show a hidden row by passing true — the mistake has to be
 * spelled out to happen.
 */
final class ContentListRepository
{
    /** @var array<string, list<ContentItem>> */
    private array $cache = [];

    /** @return list<ContentItem> */
    public function visible(ContentList $list): array
    {
        $key = $list->value . ':visible';

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $rows = Database::connection()->query(
            "SELECT id, title, description, sort_order, is_visible
             FROM {$list->value}
             WHERE is_visible = 1
             ORDER BY sort_order, id"
        )->fetchAll(PDO::FETCH_ASSOC);

        return $this->cache[$key] = array_map(ContentItem::fromRow(...), $rows);
    }

    /** @return list<ContentItem> */
    public function all(ContentList $list): array
    {
        $rows = Database::connection()->query(
            "SELECT id, title, description, sort_order, is_visible
             FROM {$list->value}
             ORDER BY sort_order, id"
        )->fetchAll(PDO::FETCH_ASSOC);

        return array_map(ContentItem::fromRow(...), $rows);
    }

    public function find(ContentList $list, int $id): ?ContentItem
    {
        $statement = Database::connection()->prepare(
            "SELECT id, title, description, sort_order, is_visible
             FROM {$list->value} WHERE id = :id"
        );
        $statement->execute(['id' => $id]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : ContentItem::fromRow($row);
    }
}
