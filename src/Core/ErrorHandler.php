<?php

declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

/**
 * One place where every error, warning, notice and uncaught exception ends up.
 *
 * Two rules:
 *   - development shows enough to fix the problem;
 *   - production shows a styled page and NOTHING else — no stack trace, no
 *     SQL, no file paths, no credentials. The detail goes to the log with a
 *     reference the visitor can quote.
 */
final class ErrorHandler
{
    private static ?View $view = null;
    private static string $logFile = '';

    public static function register(string $logFile, ?View $view = null): void
    {
        self::$logFile = $logFile;
        self::$view    = $view;

        error_reporting(E_ALL);

        // Never print to the response body. Even in development the styled
        // handler below is more useful than output interleaved with HTML.
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');

        // Promote warnings and notices to exceptions so that a silent
        // undefined-index cannot quietly corrupt a page.
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if ((error_reporting() & $severity) === 0) {
                return false;
            }

            // Deprecations are RECORDED, not thrown.
            //
            // Promoting them to exceptions means a future PHP release can
            // take the whole site down for something that still works —
            // which is exactly what happened when PHP 8.4 deprecated
            // session.sid_length and every admin page began returning 500.
            // A deprecation is a warning about tomorrow; it should not be
            // fatal today. Warnings and notices still throw, because those
            // signal a bug now.
            if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED) {
                self::logLine(sprintf(
                    "[%s] DEPRECATION: %s in %s:%d\n",
                    date('c'),
                    $message,
                    $file,
                    $line
                ));

                return true;
            }

            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler([self::class, 'handle']);

        register_shutdown_function(static function (): void {
            $error = error_get_last();

            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::handle(new ErrorException(
                    $error['message'], 0, $error['type'], $error['file'], $error['line']
                ));
            }
        });
    }

    public static function handle(Throwable $e): void
    {
        $reference = strtoupper(bin2hex(random_bytes(4)));

        self::log($e, $reference);

        // Discard anything already buffered so a half-rendered page cannot
        // appear above the error output.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo Config::isDebug()
            ? self::renderDebug($e, $reference)
            : self::renderProduction($reference);
    }

    private static function log(Throwable $e, string $reference): void
    {
        $line = sprintf(
            "[%s] %s %s: %s in %s:%d\n%s\n\n",
            date('c'),
            $reference,
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        self::logLine($line);
    }

    private static function logLine(string $line): void
    {
        if (self::$logFile !== '' && is_dir(dirname(self::$logFile))) {
            @file_put_contents(self::$logFile, $line, FILE_APPEND | LOCK_EX);
        } else {
            error_log($line);
        }
    }

    private static function renderProduction(string $reference): string
    {
        if (self::$view !== null) {
            try {
                return self::$view->render('errors/500', [
                    'reference' => $reference,
                    'meta'      => Seo::minimal('Something went wrong'),
                    'profile'   => null,
                    'siteName'  => 'Portfolio',
                    'isHome'    => false,
                ]);
            } catch (Throwable) {
                // The error page itself failed. Fall through to plain text
                // rather than recursing.
            }
        }

        return '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<title>Something went wrong</title></head><body '
            . 'style="background:#0A0B0D;color:#A8ADB8;font-family:system-ui;padding:3rem">'
            . '<h1 style="color:#F4F5F7">Something went wrong</h1>'
            . '<p>The error has been logged. Reference: <code>'
            . htmlspecialchars($reference, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</code></p></body></html>';
    }

    private static function renderDebug(Throwable $e, string $reference): string
    {
        $esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . $esc($e::class) . '</title></head>'
            . '<body style="background:#0A0B0D;color:#A8ADB8;font-family:ui-monospace,Menlo,Consolas,monospace;'
            . 'line-height:1.6;padding:2rem;margin:0">'
            . '<p style="color:#6E7480;font-size:.75rem;letter-spacing:.12em;text-transform:uppercase">'
            . 'Development error · ' . $esc($reference) . '</p>'
            . '<h1 style="color:#F87171;font-size:1.25rem;margin:.5rem 0 0">' . $esc($e::class) . '</h1>'
            . '<p style="color:#F4F5F7;font-size:1rem;margin:.75rem 0">' . $esc($e->getMessage()) . '</p>'
            . '<p style="color:#6E7480">' . $esc($e->getFile()) . ':' . $e->getLine() . '</p>'
            . '<pre style="background:#101216;border:1px solid rgba(255,255,255,.1);border-radius:6px;'
            . 'padding:1rem;overflow:auto;font-size:.8125rem;color:#A8ADB8">'
            . $esc($e->getTraceAsString()) . '</pre>'
            . '<p style="color:#43474F;font-size:.8125rem">This detail is shown because app.debug is true. '
            . 'It is never shown when app.env is production.</p>'
            . '</body></html>';
    }
}
