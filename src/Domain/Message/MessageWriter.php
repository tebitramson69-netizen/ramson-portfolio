<?php

declare(strict_types=1);

namespace App\Domain\Message;

use App\Core\Database;

/**
 * Writes to the contact inbox.
 *
 * store() is the only method a public request can reach, and it is why the
 * length caps live here rather than only in the controller: a validator can
 * be bypassed by a second caller, a column cannot.
 */
final class MessageWriter
{
    public function store(
        string $name,
        string $email,
        string $subject,
        string $body,
        ?string $ipAddress,
        string $userAgent,
    ): int {
        Database::connection()->prepare(
            'INSERT INTO messages (name, email, subject, body, ip_address, user_agent)
             VALUES (:name, :email, :subject, :body, :ip, :agent)'
        )->execute([
            'name'    => mb_substr($name, 0, 120),
            'email'   => mb_substr($email, 0, 191),
            'subject' => mb_substr($subject, 0, 160),
            'body'    => $body,
            'ip'      => self::packIp($ipAddress),
            'agent'   => mb_substr($userAgent, 0, 255),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * Mark read, once.
     *
     * read_at is set only on the first transition, so it records when he
     * actually first saw it rather than the last time he reopened it.
     */
    public function markRead(int $id): void
    {
        Database::connection()->prepare(
            "UPDATE messages
             SET status = 'read', read_at = COALESCE(read_at, NOW())
             WHERE id = :id AND status = 'unread'"
        )->execute(['id' => $id]);
    }

    public function setStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['unread', 'read', 'archived'], true)) {
            return false;
        }

        Database::connection()
            ->prepare('UPDATE messages SET status = :status WHERE id = :id')
            ->execute(['status' => $status, 'id' => $id]);

        return true;
    }

    public function delete(int $id): void
    {
        Database::connection()
            ->prepare('DELETE FROM messages WHERE id = :id')
            ->execute(['id' => $id]);
    }

    /**
     * inet_pton for storage — the convention login_attempts set.
     *
     * A malformed address is stored as NULL rather than refused: the message
     * matters and the address is metadata. The only cost is that the rate
     * limiter cannot count that one, which is the right way round.
     */
    public static function packIp(?string $ipAddress): ?string
    {
        if ($ipAddress === null || $ipAddress === '') {
            return null;
        }

        $packed = @inet_pton($ipAddress);

        return $packed === false ? null : $packed;
    }
}
