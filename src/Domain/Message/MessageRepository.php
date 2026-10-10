<?php

declare(strict_types=1);

namespace App\Domain\Message;

use App\Core\Database;
use PDO;

/**
 * Reads for the contact inbox.
 *
 * Every one of these is admin-only. Nothing public reads this table, which is
 * why there is no visible-versus-published naming split here as there is in
 * ProjectRepository — there is no public read that could pick the wrong one.
 */
final class MessageRepository
{
    private const COLUMNS = 'id, name, email, subject, body, ip_address,
                             user_agent, status, created_at, read_at';

    /**
     * @param  list<string> $statuses
     * @return list<Message>
     */
    public function byStatus(array $statuses): array
    {
        if ($statuses === []) {
            return [];
        }

        // Placeholders generated from the COUNT, never from the values: the
        // list is a fixed vocabulary from the controller, and building the
        // SQL this way means a caller cannot smuggle anything into it.
        $placeholders = implode(', ', array_fill(0, count($statuses), '?'));

        $statement = Database::connection()->prepare(
            'SELECT ' . self::COLUMNS . ' FROM messages
             WHERE status IN (' . $placeholders . ')
             ORDER BY created_at DESC, id DESC'
        );
        $statement->execute(array_values($statuses));

        return array_map(Message::fromRow(...), $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function find(int $id): ?Message
    {
        $statement = Database::connection()->prepare(
            'SELECT ' . self::COLUMNS . ' FROM messages WHERE id = :id'
        );
        $statement->execute(['id' => $id]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : Message::fromRow($row);
    }

    public function unreadCount(): int
    {
        return (int) Database::connection()
            ->query("SELECT COUNT(*) FROM messages WHERE status = 'unread'")
            ->fetchColumn();
    }

    /** @return array<string, int> status => count, for the inbox tabs. */
    public function countsByStatus(): array
    {
        $rows = Database::connection()
            ->query('SELECT status, COUNT(*) AS n FROM messages GROUP BY status')
            ->fetchAll(PDO::FETCH_ASSOC);

        $counts = ['unread' => 0, 'read' => 0, 'archived' => 0];

        foreach ($rows as $row) {
            $counts[(string) $row['status']] = (int) $row['n'];
        }

        return $counts;
    }
}
