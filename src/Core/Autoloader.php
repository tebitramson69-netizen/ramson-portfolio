<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal PSR-4 autoloader.
 *
 * The project has no runtime dependencies yet, so requiring `composer install`
 * before the site will run at all would add a failure point for no benefit.
 * composer.json is present and declares the same PSR-4 mapping, so when the
 * first real dependency arrives (a Markdown parser and an HTML sanitiser in
 * Phase 6) Composer's autoloader takes over and this class is simply no
 * longer registered. Nothing has to move.
 */
final class Autoloader
{
    public static function register(string $namespacePrefix, string $baseDirectory): void
    {
        $prefix = rtrim($namespacePrefix, '\\') . '\\';
        $base   = rtrim($baseDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $length = strlen($prefix);

        spl_autoload_register(static function (string $class) use ($prefix, $base, $length): void {
            if (strncmp($prefix, $class, $length) !== 0) {
                return;
            }

            $relative = substr($class, $length);
            $file     = $base . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

            if (is_file($file)) {
                require $file;
            }
        });
    }
}
