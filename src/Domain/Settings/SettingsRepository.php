<?php

declare(strict_types=1);

namespace App\Domain\Settings;

use App\Core\Database;
use PDO;

/**
 * Site settings, loaded once per request and cast to their declared type.
 */
final class SettingsRepository
{
    /** @var array<string, mixed>|null */
    private ?array $cache = null;

    /** @return array<string, mixed> */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $rows = Database::connection()
            ->query('SELECT setting_key, setting_value, value_type FROM settings')
            ->fetchAll(PDO::FETCH_ASSOC);

        $settings = [];

        foreach ($rows as $row) {
            $settings[(string) $row['setting_key']] = $this->cast(
                $row['setting_value'],
                (string) $row['value_type']
            );
        }

        return $this->cache = $settings;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    private function cast(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            default   => (string) $value,
        };
    }
}
