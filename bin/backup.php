#!/usr/bin/env php
<?php

/**
 * Database and uploads backup.
 *
 *     php bin/backup.php
 *     php bin/backup.php --keep=10
 *
 * Writes two files per run into storage/backups/ — a .sql dump and a .zip of
 * the uploaded media. storage/ already carries an .htaccess deny, so they are
 * not reachable from the web where they are written.
 *
 * BOTH halves matter, and that is why they are one command. The database
 * holds the rows that point at the media; the media directory holds the
 * files those rows name. Restoring one without the other gives you a site
 * full of broken images or a directory of orphans nobody references, and
 * whichever you forgot is the one you needed.
 *
 * mysqldump is used when the host provides it and a PHP dump is written when
 * it does not. Shared hosting varies, and a backup script that only works on
 * a machine with shell tools is a backup script that fails on the day it
 * matters.
 *
 * A BACKUP NOBODY HAS RESTORED IS NOT A BACKUP. The restore procedure is in
 * docs/portfolio/06-DEPLOYMENT.md and it is a thing to DO once, now, not to
 * read later during an emergency.
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

$root = dirname(__DIR__);

Autoloader::register('App', $root . '/src');
Config::load($root . '/config');

$keep = 7;
foreach ($argv as $arg) {
    if (preg_match('/^--keep=(\d+)$/', $arg, $m)) {
        $keep = max(1, (int) $m[1]);
    }
}

$dir = $root . '/storage/backups';

if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
    exit("  Could not create {$dir}\n");
}

$stamp    = date('Y-m-d_His');
$database = (string) Config::get('database.database', '');
$sqlPath  = $dir . '/' . $stamp . '-' . $database . '.sql';
$zipPath  = $dir . '/' . $stamp . '-uploads.zip';

echo "Backup\n======\n";

// ------------------------------------------------------------- 1. DATABASE

echo "\n  Database: {$database}\n";

$dumped = false;

if (function_exists('exec')) {
    // Credentials go through the environment, never the command line: an
    // argument is visible in `ps` to every other user on a shared host, which
    // is the one place this script is most likely to run.
    $command = sprintf(
        'mysqldump --host=%s --port=%d --user=%s --single-transaction '
        . '--default-character-set=utf8mb4 --no-tablespaces %s 2>&1',
        escapeshellarg((string) Config::get('database.host', '127.0.0.1')),
        (int) Config::get('database.port', 3306),
        escapeshellarg((string) Config::get('database.username', '')),
        escapeshellarg($database),
    );

    putenv('MYSQL_PWD=' . (string) Config::get('database.password', ''));

    $output = [];
    $status = 0;
    exec($command . ' > ' . escapeshellarg($sqlPath), $output, $status);

    putenv('MYSQL_PWD');

    if ($status === 0 && is_file($sqlPath) && filesize($sqlPath) > 0) {
        $dumped = true;
        echo "    mysqldump: " . number_format((int) filesize($sqlPath)) . " bytes\n";
    } else {
        @unlink($sqlPath);
        echo "    mysqldump unavailable or failed — falling back to a PHP dump\n";
    }
}

if (!$dumped) {
    $pdo    = Database::connection();
    $handle = fopen($sqlPath, 'wb');

    if ($handle === false) {
        exit("    Could not open {$sqlPath} for writing\n");
    }

    fwrite($handle, "-- Portfolio backup {$stamp}\n");
    fwrite($handle, "-- Written by bin/backup.php without mysqldump.\n");
    fwrite($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $create = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch(PDO::FETCH_NUM);

        fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
        fwrite($handle, $create[1] . ";\n\n");

        // Unbuffered so a large table is streamed rather than loaded whole —
        // shared hosts are where both the memory limit and the data are.
        $rows = $pdo->query('SELECT * FROM `' . $table . '`');

        foreach ($rows as $row) {
            $values = array_map(
                static fn ($v): string => $v === null ? 'NULL' : $pdo->quote((string) $v),
                array_values($row),
            );

            fwrite($handle, "INSERT INTO `{$table}` VALUES (" . implode(',', $values) . ");\n");
        }

        fwrite($handle, "\n");
    }

    fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($handle);

    echo "    PHP dump: " . number_format((int) filesize($sqlPath)) . " bytes, "
        . count($tables) . " tables\n";
}

// -------------------------------------------------------------- 2. UPLOADS

echo "\n  Uploads\n";

if (!class_exists('ZipArchive')) {
    echo "    SKIPPED — the zip extension is not installed. Copy public/uploads\n";
    echo "    and storage/uploads by hand, or the database alone is useless.\n";
} else {
    $zip = new ZipArchive();

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        exit("    Could not create {$zipPath}\n");
    }

    $files = 0;

    // Both directories: public/uploads holds the served variants, and
    // storage/uploads holds the untouched original every variant was derived
    // from. Losing the originals means no variant can ever be regenerated.
    foreach (['public/uploads', 'storage/uploads'] as $relative) {
        $base = $root . '/' . $relative;

        if (!is_dir($base)) {
            continue;
        }

        $walker = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($walker as $file) {
            if (!$file->isFile() || $file->getFilename() === '.htaccess') {
                continue;
            }

            $zip->addFile($file->getPathname(), $relative . '/' . $file->getFilename());
            $files++;
        }
    }

    $zip->close();

    echo "    {$files} files, " . number_format((int) filesize($zipPath)) . " bytes\n";
}

// -------------------------------------------------------------- 3. ROTATION

$all = glob($dir . '/*-*.{sql,zip}', GLOB_BRACE) ?: [];
rsort($all);

$seen    = [];
$removed = 0;

foreach ($all as $file) {
    $prefix = substr(basename($file), 0, 17);   // YYYY-MM-DD_HHMMSS

    if (!in_array($prefix, $seen, true)) {
        $seen[] = $prefix;
    }

    if (count($seen) > $keep) {
        unlink($file);
        $removed++;
    }
}

echo "\n  Kept the newest {$keep} runs"
    . ($removed > 0 ? ", removed {$removed} older file(s)" : '')
    . ".\n";

echo "\n  Written to storage/backups/ (denied to the web by storage/.htaccess).\n";
echo "\n  THIS IS NOT YET A BACKUP. Restore it into a scratch database and load\n";
echo "  the site from that copy — the procedure is in 06-DEPLOYMENT.md. Until\n";
echo "  you have done it once, these are two files you have never read back.\n\n";
