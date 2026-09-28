<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Read-only configuration access with dot notation: Config::get('database.host').
 *
 * Merges the committed defaults (config/app.php) with the machine-local file
 * (config/config.php). The local file is required: failing loudly with a
 * useful message beats silently running on defaults that may be wrong.
 */
final class Config
{
    /** @var array<string, mixed> */
    private static array $items = [];
    private static bool $loaded = false;

    public static function load(string $configDirectory): void
    {
        $local = $configDirectory . '/config.php';

        if (!is_file($local)) {
            throw new RuntimeException(
                'Missing config/config.php. Copy config/config.example.php to '
                . 'config/config.php and set your database credentials. '
                . 'It is git-ignored on purpose — credentials are never committed.'
            );
        }

        $defaults = require $configDirectory . '/app.php';
        $machine  = require $local;

        self::$items  = array_replace_recursive($defaults, $machine);
        self::$loaded = true;

        // A production environment never shows debug output, whatever the
        // local file says. Enforced here so it cannot be got wrong by hand.
        if (self::get('app.env') === 'production') {
            self::$items['app']['debug'] = false;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            throw new RuntimeException('Config::load() must run before Config::get().');
        }

        $value = self::$items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public static function isDebug(): bool
    {
        return (bool) self::get('app.debug', false);
    }

    public static function isProduction(): bool
    {
        return self::get('app.env') === 'production';
    }
}
