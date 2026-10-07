<?php

declare(strict_types=1);

namespace App\Domain\Settings;

use App\Core\Database;
use PDO;

/**
 * Writes to the settings table.
 *
 * Deliberately narrow: this updates the VALUE of a key that already exists.
 * It cannot create keys and it cannot delete them.
 *
 * That is the whole design. Settings are read by name all over the templates —
 * `site_title`, `meta_description`, `site_locale` — so a key is part of the
 * code, not data. Letting an admin screen invent `site_titel` would produce a
 * row nothing reads and a site title that silently fell back to its default,
 * with no error anywhere. New keys arrive in a seed, alongside the template
 * that reads them.
 */
final class SettingsWriter
{
    /**
     * Update the values of existing keys, ignoring anything unrecognised.
     *
     * Unknown keys are dropped rather than rejected: the form posts whatever
     * the screen rendered, and a key removed from the seed between the render
     * and the submit should not fail the whole save.
     *
     * Booleans are normalised to '1'/'0' and integers to a decimal string, so
     * SettingsRepository::cast() reads back what was meant rather than
     * whatever the browser posted ('on', 'true', ' 7 ').
     *
     * @param  array<string, mixed> $values key => raw submitted value
     * @return int Number of settings actually changed.
     */
    public function updateMany(array $values): int
    {
        $known = $this->editable();

        if ($known === []) {
            return 0;
        }

        return (int) Database::transaction(function () use ($values, $known): int {
            $statement = Database::connection()->prepare(
                'UPDATE settings SET setting_value = :value WHERE setting_key = :key'
            );

            $changed = 0;

            foreach ($known as $key => $type) {
                if (!array_key_exists($key, $values)) {
                    // A boolean that is off posts nothing at all, so its
                    // absence is a value, not a missing field.
                    if ($type !== 'boolean') {
                        continue;
                    }

                    $values[$key] = false;
                }

                $statement->execute([
                    'value' => $this->normalise($values[$key], $type),
                    'key'   => $key,
                ]);

                $changed += $statement->rowCount();
            }

            return $changed;
        });
    }

    /**
     * The keys that exist, with their declared type.
     *
     * @return array<string, string>
     */
    public function editable(): array
    {
        $rows = Database::connection()
            ->query('SELECT setting_key, value_type FROM settings')
            ->fetchAll(PDO::FETCH_ASSOC);

        $types = [];

        foreach ($rows as $row) {
            $types[(string) $row['setting_key']] = (string) $row['value_type'];
        }

        return $types;
    }

    /**
     * Rows as the admin screen needs them: key, label, type and current value.
     *
     * @return list<array{key:string, label:string, type:string, value:?string}>
     */
    public function rows(): array
    {
        $rows = Database::connection()->query(
            'SELECT setting_key, setting_value, value_type, label
             FROM settings ORDER BY setting_key'
        )->fetchAll(PDO::FETCH_ASSOC);

        return array_map(static fn (array $row): array => [
            'key'   => (string) $row['setting_key'],
            'label' => (string) $row['label'],
            'type'  => (string) $row['value_type'],
            'value' => $row['setting_value'] === null ? null : (string) $row['setting_value'],
        ], $rows);
    }

    private function normalise(mixed $value, string $type): ?string
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL) ? '1' : '0',
            'integer' => (string) (int) $value,
            'text'    => mb_substr(trim((string) $value), 0, 5000),
            default   => mb_substr(trim((string) $value), 0, 500),
        };
    }
}
