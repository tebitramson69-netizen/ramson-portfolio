<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Core\Config;
use App\Core\Database;

/**
 * Login throttling.
 *
 * Counts recent FAILED attempts against two independent keys — the submitted
 * email and the client IP — and blocks when either crosses the limit.
 * Throttling on the account alone lets a botnet spread attempts across many
 * addresses; throttling on the address alone lets one attacker lock the owner
 * out of their own account. Either signal tripping is the safe combination.
 *
 * A successful login clears that account's failures, so a legitimate user who
 * mistypes twice and then succeeds starts clean.
 */
final class LoginThrottle
{
    public function maxAttempts(): int
    {
        return (int) Config::get('auth.throttle.max_attempts', 5);
    }

    public function decaySeconds(): int
    {
        return (int) Config::get('auth.throttle.decay_seconds', 900);
    }

    /** Failed attempts within the window, for whichever key is worse. */
    public function recentFailures(string $identifier, ?string $ipAddress): int
    {
        $sql = 'SELECT COUNT(*) FROM login_attempts
                WHERE succeeded = 0
                  AND attempted_at > (NOW() - INTERVAL :decay SECOND)
                  AND identifier = :identifier';

        $params = [
            'decay'      => $this->decaySeconds(),
            'identifier' => $this->normalise($identifier),
        ];

        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
        $byAccount = (int) $statement->fetchColumn();

        $byIp = 0;
        $packed = $this->packIp($ipAddress);

        if ($packed !== null) {
            $statement = Database::connection()->prepare(
                'SELECT COUNT(*) FROM login_attempts
                 WHERE succeeded = 0
                   AND attempted_at > (NOW() - INTERVAL :decay SECOND)
                   AND ip_address = :ip'
            );
            $statement->execute(['decay' => $this->decaySeconds(), 'ip' => $packed]);
            $byIp = (int) $statement->fetchColumn();
        }

        return max($byAccount, $byIp);
    }

    public function isLocked(string $identifier, ?string $ipAddress): bool
    {
        return $this->recentFailures($identifier, $ipAddress) >= $this->maxAttempts();
    }

    /** Seconds until the oldest counted failure falls out of the window. */
    public function retryAfter(string $identifier, ?string $ipAddress): int
    {
        $statement = Database::connection()->prepare(
            'SELECT TIMESTAMPDIFF(SECOND, NOW(), MIN(attempted_at) + INTERVAL :decay SECOND)
             FROM login_attempts
             WHERE succeeded = 0
               AND attempted_at > (NOW() - INTERVAL :decay2 SECOND)
               AND (identifier = :identifier OR (ip_address IS NOT NULL AND ip_address = :ip))'
        );
        $statement->execute([
            'decay'      => $this->decaySeconds(),
            'decay2'     => $this->decaySeconds(),
            'identifier' => $this->normalise($identifier),
            'ip'         => $this->packIp($ipAddress),
        ]);

        return max(1, (int) $statement->fetchColumn());
    }

    public function record(string $identifier, ?string $ipAddress, bool $succeeded, string $userAgent = ''): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO login_attempts (identifier, ip_address, user_agent, succeeded)
             VALUES (:identifier, :ip, :agent, :succeeded)'
        );
        $statement->execute([
            'identifier' => $this->normalise($identifier),
            'ip'         => $this->packIp($ipAddress),
            'agent'      => mb_substr($userAgent, 0, 255),
            'succeeded'  => $succeeded ? 1 : 0,
        ]);

        if ($succeeded) {
            $this->clear($identifier);
        }
    }

    public function clear(string $identifier): void
    {
        $statement = Database::connection()->prepare(
            'DELETE FROM login_attempts WHERE identifier = :identifier AND succeeded = 0'
        );
        $statement->execute(['identifier' => $this->normalise($identifier)]);
    }

    /**
     * Drop rows older than the window.
     *
     * Called opportunistically on login rather than by a scheduled job, which
     * would be one more moving part for a table that gathers a handful of
     * rows a year on a personal site.
     */
    public function prune(): void
    {
        Database::connection()->prepare(
            'DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 30 DAY)'
        )->execute();
    }

    private function normalise(string $identifier): string
    {
        return mb_substr(mb_strtolower(trim($identifier)), 0, 191);
    }

    private function packIp(?string $ipAddress): ?string
    {
        if ($ipAddress === null || $ipAddress === '') {
            return null;
        }

        $packed = @inet_pton($ipAddress);

        return $packed === false ? null : $packed;
    }
}
