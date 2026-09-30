<?php

declare(strict_types=1);

namespace App\Domain\Auth;

final class AdminUser
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly string $sessionToken,
        public readonly ?string $lastLoginAt,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id:            (int) $row['id'],
            name:          (string) $row['name'],
            email:         (string) $row['email'],
            passwordHash:  (string) $row['password_hash'],
            sessionToken:  (string) $row['session_token'],
            lastLoginAt:   $row['last_login_at'] !== null ? (string) $row['last_login_at'] : null,
        );
    }

    public function firstName(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];

        return $parts[0] ?? $this->name;
    }
}
