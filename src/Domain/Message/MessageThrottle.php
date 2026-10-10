<?php

declare(strict_types=1);

namespace App\Domain\Message;

use App\Core\Config;
use App\Core\Database;

/**
 * How many messages one address may send in a window.
 *
 * Shaped after LoginThrottle but deliberately NOT sharing its code. That
 * class counts FAILED attempts against two keys — an account and an address —
 * and clears on success. None of those ideas apply here: there is no account,
 * nothing fails, and a sent message is not something to clear. Reusing it
 * would have meant bending three concepts to fit, and the result reads worse
 * than forty lines that say what they mean.
 *
 * There is also no table of its own. The messages ARE the evidence, counted
 * through ix_message_ip. A separate log recording that a message arrived,
 * sitting beside the message, is two places to disagree about one fact.
 *
 * A request with no usable address is never blocked. Some hosts and proxies
 * do not pass one through, and refusing those visitors to punish a hypothesis
 * about spam would turn a contact form into a wall.
 */
final class MessageThrottle
{
    public function maxMessages(): int
    {
        return (int) Config::get('contact.throttle.max_messages', 5);
    }

    public function decaySeconds(): int
    {
        return (int) Config::get('contact.throttle.decay_seconds', 3600);
    }

    public function recentCount(?string $ipAddress): int
    {
        $packed = MessageWriter::packIp($ipAddress);

        if ($packed === null) {
            return 0;
        }

        $statement = Database::connection()->prepare(
            'SELECT COUNT(*) FROM messages
             WHERE ip_address = :ip
               AND created_at > (NOW() - INTERVAL :decay SECOND)'
        );
        $statement->execute(['ip' => $packed, 'decay' => $this->decaySeconds()]);

        return (int) $statement->fetchColumn();
    }

    public function isBlocked(?string $ipAddress): bool
    {
        return $this->recentCount($ipAddress) >= $this->maxMessages();
    }

    /** Minutes to wait, rounded up, for a message the sender can act on. */
    public function retryAfterMinutes(?string $ipAddress): int
    {
        $packed = MessageWriter::packIp($ipAddress);

        if ($packed === null) {
            return 0;
        }

        $statement = Database::connection()->prepare(
            'SELECT TIMESTAMPDIFF(SECOND, NOW(), oldest + INTERVAL :decay SECOND)
             FROM (
                 SELECT MIN(created_at) AS oldest FROM messages
                 WHERE ip_address = :ip
                   AND created_at > (NOW() - INTERVAL :decay2 SECOND)
             ) AS window_start'
        );
        $statement->execute([
            'ip'     => $packed,
            'decay'  => $this->decaySeconds(),
            'decay2' => $this->decaySeconds(),
        ]);

        $seconds = (int) $statement->fetchColumn();

        return $seconds <= 0 ? 1 : (int) ceil($seconds / 60);
    }
}
