#!/usr/bin/env php
<?php

/**
 * Forward-only migration runner.
 *
 *     php bin/migrate.php            apply every pending migration
 *     php bin/migrate.php --status   list applied and pending, change nothing
 *     php bin/migrate.php --seed     apply migrations, then run seeds
 *
 * Migrations are plain .sql files, applied in filename order and recorded in
 * schema_migrations. SQL rather than PHP because the files are then readable
 * by anyone who knows SQL, diff cleanly, and can be run by hand in an
 * emergency without this script.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("This script is CLI-only.\n");
}

require __DIR__ . '/../src/Core/Autoloader.php';

use App\Core\Autoloader;
use App\Core\Config;
use App\Core\Database;

Autoloader::register('App', __DIR__ . '/../src');

$root = dirname(__DIR__);

try {
    Config::load($root . '/config');
} catch (Throwable $e) {
    exit("\n  " . $e->getMessage() . "\n\n");
}

$statusOnly = in_array('--status', $argv, true);
$withSeeds  = in_array('--seed', $argv, true);

try {
    $pdo = Database::connection();
} catch (Throwable $e) {
    $previous = $e->getPrevious();
    fwrite(STDERR, "\n  Could not connect to the database.\n");
    fwrite(STDERR, "  Check config/config.php and that MySQL/MariaDB is running.\n");
    if ($previous !== null) {
        fwrite(STDERR, '  Driver said: ' . $previous->getMessage() . "\n");
    }
    fwrite(STDERR, "\n");
    exit(1);
}

$directory = $root . '/database/migrations';
$files     = glob($directory . '/*.sql') ?: [];
sort($files, SORT_STRING);

if ($files === []) {
    exit("  No migrations found in database/migrations.\n");
}

// The ledger has to exist before it can be queried, and it is itself the
// first migration — so apply 0001 unconditionally if the table is absent.
$ledgerExists = (bool) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'schema_migrations'"
)->fetchColumn();

if (!$ledgerExists) {
    runStatements($pdo, file_get_contents($files[0]) ?: '');
    $ledgerExists = true;
    echo "  ledger   schema_migrations created\n";
}

$applied = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$applied = array_flip(array_map('strval', $applied));

$pending = [];
foreach ($files as $file) {
    $version = basename($file, '.sql');
    if (!isset($applied[$version])) {
        $pending[$version] = $file;
    }
}

if ($statusOnly) {
    echo "\n  Applied (" . count($applied) . "):\n";
    foreach (array_keys($applied) as $version) {
        echo "    ok       {$version}\n";
    }
    echo "\n  Pending (" . count($pending) . "):\n";
    foreach (array_keys($pending) as $version) {
        echo "    pending  {$version}\n";
    }
    echo "\n";
    exit(0);
}

if ($pending === []) {
    echo "  Nothing to migrate — the schema is up to date.\n";
} else {
    foreach ($pending as $version => $file) {
        $sql = file_get_contents($file);

        if ($sql === false) {
            fwrite(STDERR, "  Could not read {$file}\n");
            exit(1);
        }

        try {
            // DDL in MySQL/MariaDB causes an implicit commit, so wrapping a
            // migration in a transaction would give false reassurance. Each
            // file is instead kept small enough to reason about, and the
            // ledger row is written only after every statement succeeded.
            runStatements($pdo, $sql);

            $stmt = $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)');
            $stmt->execute([$version]);

            echo "  migrated {$version}\n";
        } catch (Throwable $e) {
            fwrite(STDERR, "\n  FAILED on {$version}\n  " . $e->getMessage() . "\n\n");
            exit(1);
        }
    }
}

if ($withSeeds) {
    $seeds = glob($root . '/database/seeds/*.php') ?: [];
    sort($seeds, SORT_STRING);

    foreach ($seeds as $seed) {
        $name = basename($seed, '.php');
        try {
            (static function (string $file, PDO $pdo): void {
                require $file;
            })($seed, $pdo);
            echo "  seeded   {$name}\n";
        } catch (Throwable $e) {
            fwrite(STDERR, "\n  SEED FAILED on {$name}\n  " . $e->getMessage() . "\n\n");
            exit(1);
        }
    }
}

echo "  Done.\n";

/**
 * Execute a .sql file statement by statement.
 *
 * Splitting on ';' naively breaks on semicolons inside strings and comments,
 * so this walks the file tracking quote state. The schema uses no stored
 * programs, so no DELIMITER handling is needed; if that ever changes this
 * function is the single place to extend.
 */
function runStatements(PDO $pdo, string $sql): void
{
    foreach (splitStatements($sql) as $statement) {
        $pdo->exec($statement);
    }
}

/** @return list<string> */
function splitStatements(string $sql): array
{
    $statements = [];
    $current    = '';
    $length     = strlen($sql);
    $inSingle   = false;
    $inDouble   = false;
    $inBacktick = false;
    $inLineComment  = false;
    $inBlockComment = false;

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $sql[$i + 1] ?? '';

        if ($inLineComment) {
            if ($char === "\n") {
                $inLineComment = false;
                $current .= $char;
            }
            continue;
        }

        if ($inBlockComment) {
            if ($char === '*' && $next === '/') {
                $inBlockComment = false;
                $i++;
            }
            continue;
        }

        if (!$inSingle && !$inDouble && !$inBacktick) {
            if ($char === '-' && $next === '-') {
                $inLineComment = true;
                continue;
            }
            if ($char === '#') {
                $inLineComment = true;
                continue;
            }
            if ($char === '/' && $next === '*') {
                $inBlockComment = true;
                $i++;
                continue;
            }
            if ($char === ';') {
                $trimmed = trim($current);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $current = '';
                continue;
            }
        }

        // Quote state. Backslash escapes are honoured inside quoted strings.
        if ($char === "'" && !$inDouble && !$inBacktick) {
            if (!($inSingle && ($sql[$i - 1] ?? '') === '\\')) {
                $inSingle = !$inSingle;
            }
        } elseif ($char === '"' && !$inSingle && !$inBacktick) {
            if (!($inDouble && ($sql[$i - 1] ?? '') === '\\')) {
                $inDouble = !$inDouble;
            }
        } elseif ($char === '`' && !$inSingle && !$inDouble) {
            $inBacktick = !$inBacktick;
        }

        $current .= $char;
    }

    $trimmed = trim($current);
    if ($trimmed !== '') {
        $statements[] = $trimmed;
    }

    return $statements;
}
