<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * Lazy PDO connection.
 *
 * The connection is opened on first use, not during bootstrap, so a page that
 * serves entirely from cache or static template data never pays for a
 * database handshake.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host    = (string) Config::get('database.host');
        $port    = (int) Config::get('database.port');
        $name    = (string) Config::get('database.database');
        $charset = (string) Config::get('database.charset', 'utf8mb4');

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $name, $charset);

        try {
            self::$pdo = new PDO(
                $dsn,
                (string) Config::get('database.username'),
                (string) Config::get('database.password'),
                [
                    // Exceptions, not silent false returns.
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                    // Real server-side prepared statements. With emulation on,
                    // PDO interpolates values itself, which reintroduces the
                    // very class of bug prepared statements exist to remove.
                    PDO::ATTR_EMULATE_PREPARES   => false,

                    // Integers and floats come back as PHP numbers rather than
                    // strings, so `===` comparisons behave.
                    PDO::ATTR_STRINGIFY_FETCHES  => false,
                ]
            );
        } catch (PDOException $e) {
            // The DSN contains the host and database name, and the exception
            // message can contain the username. Neither reaches the browser.
            throw new RuntimeException('Database connection failed.', 0, $e);
        }

        return self::$pdo;
    }

    /** Run a callback inside a transaction, rolling back on any throwable. */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Test helper: forget the handle so the next call reconnects. */
    public static function disconnect(): void
    {
        self::$pdo = null;
    }
}
