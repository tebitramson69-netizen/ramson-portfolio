<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Core\Database;

final class AdminUserRepository
{
    public function findByEmail(string $email): ?AdminUser
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, email, password_hash, session_token, last_login_at
             FROM admin_users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => mb_strtolower(trim($email))]);

        $row = $statement->fetch();

        return $row === false ? null : AdminUser::fromRow($row);
    }

    public function find(int $id): ?AdminUser
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, email, password_hash, session_token, last_login_at
             FROM admin_users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $row = $statement->fetch();

        return $row === false ? null : AdminUser::fromRow($row);
    }

    public function count(): int
    {
        return (int) Database::connection()
            ->query('SELECT COUNT(*) FROM admin_users')
            ->fetchColumn();
    }

    /** Returns the new session token. */
    public function recordLogin(int $id, ?string $ipAddress): string
    {
        $token = bin2hex(random_bytes(32));

        $statement = Database::connection()->prepare(
            'UPDATE admin_users
             SET session_token = :token, last_login_at = NOW(), last_login_ip = :ip
             WHERE id = :id'
        );
        $statement->execute([
            'token' => $token,
            'ip'    => $ipAddress !== null ? @inet_pton($ipAddress) ?: null : null,
            'id'    => $id,
        ]);

        return $token;
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE admin_users SET password_hash = :hash WHERE id = :id'
        );
        $statement->execute(['hash' => $hash, 'id' => $id]);
    }

    /** Used by the CLI tool only. */
    public function create(string $name, string $email, string $passwordHash): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO admin_users (name, email, password_hash, session_token)
             VALUES (:name, :email, :hash, :token)'
        );
        $statement->execute([
            'name'  => $name,
            'email' => mb_strtolower(trim($email)),
            'hash'  => $passwordHash,
            'token' => bin2hex(random_bytes(32)),
        ]);

        return (int) Database::connection()->lastInsertId();
    }
}
