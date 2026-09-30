#!/usr/bin/env php
<?php

/**
 * Create or update the administrator account.
 *
 *     php bin/create-admin.php
 *     php bin/create-admin.php --email you@example.com --name "Your Name"
 *
 * CLI ONLY. This is deliberately not a web route: the application has no
 * registration endpoint at all, and a route that does not exist cannot be
 * attacked, brute-forced or left reachable by accident. A forgotten password
 * is reset by running this again.
 *
 * The password is read without echo where the terminal allows it, and is
 * never accepted as a command-line argument — arguments are visible in the
 * process list and land in shell history.
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
use App\Domain\Auth\AdminUserRepository;
use App\Domain\Auth\PasswordHasher;

Autoloader::register('App', __DIR__ . '/../src');

$root = dirname(__DIR__);

try {
    Config::load($root . '/config');
    Database::connection();
} catch (Throwable $e) {
    fwrite(STDERR, "\n  " . $e->getMessage() . "\n");
    fwrite(STDERR, "  Check config/config.php and that the database is running.\n\n");
    exit(1);
}

/** Read a command-line option, e.g. --email value. */
$option = static function (string $name) use ($argv): ?string {
    $index = array_search('--' . $name, $argv, true);

    return $index !== false && isset($argv[$index + 1]) ? (string) $argv[$index + 1] : null;
};

$prompt = static function (string $label, ?string $preset = null): string {
    if ($preset !== null && $preset !== '') {
        echo "  {$label}: {$preset}\n";
        return $preset;
    }

    echo "  {$label}: ";
    $value = fgets(STDIN);

    return $value === false ? '' : trim($value);
};

/**
 * Read a password without echoing it.
 *
 * `stty -echo` works on macOS, Linux and Git Bash. Windows' own cmd and
 * PowerShell have no stty, so there the input is visible — which is stated
 * plainly rather than pretended otherwise.
 */
$promptSecret = static function (string $label): string {
    $hasStty = stripos(PHP_OS_FAMILY, 'Windows') === false
        && shell_exec('command -v stty 2>/dev/null') !== null;

    if ($hasStty) {
        echo "  {$label}: ";
        shell_exec('stty -echo 2>/dev/null');
        $value = fgets(STDIN);
        shell_exec('stty echo 2>/dev/null');
        echo "\n";
    } else {
        echo "  {$label} (visible on this terminal): ";
        $value = fgets(STDIN);
    }

    return $value === false ? '' : trim($value);
};

echo "\n  Administrator account\n";
echo "  ---------------------\n";
echo "  Hashing: " . PasswordHasher::describe() . "\n\n";

$users = new AdminUserRepository();

$name  = $prompt('Name', $option('name'));
$email = $prompt('Email', $option('email'));

if ($name === '' || $email === '') {
    fwrite(STDERR, "\n  Name and email are both required.\n\n");
    exit(1);
}

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "\n  '{$email}' is not a valid email address.\n\n");
    exit(1);
}

$existing = $users->findByEmail($email);

if ($existing !== null) {
    echo "\n  An account already exists for {$email}.\n";
    echo "  Continuing will REPLACE its password and sign out every existing session.\n";
    echo "  Continue? [y/N]: ";
    $answer = strtolower(trim((string) fgets(STDIN)));

    if ($answer !== 'y' && $answer !== 'yes') {
        echo "  Cancelled. Nothing changed.\n\n";
        exit(0);
    }
} elseif ($users->count() > 0) {
    // The design is explicitly single-administrator. Adding a second account
    // silently would be a change to the security model, not a convenience.
    echo "\n  An administrator already exists and this project is designed for ONE.\n";
    echo "  Create an additional account anyway? [y/N]: ";
    $answer = strtolower(trim((string) fgets(STDIN)));

    if ($answer !== 'y' && $answer !== 'yes') {
        echo "  Cancelled. Nothing changed.\n\n";
        exit(0);
    }
}

echo "\n";
$password = $promptSecret('Password (minimum ' . PasswordHasher::MIN_LENGTH . ' characters)');
$confirm  = $promptSecret('Confirm password');

if ($password !== $confirm) {
    fwrite(STDERR, "\n  The passwords do not match. Nothing changed.\n\n");
    exit(1);
}

try {
    PasswordHasher::guard($password);
    $hash = PasswordHasher::hash($password);
} catch (Throwable $e) {
    fwrite(STDERR, "\n  " . $e->getMessage() . "\n\n");
    exit(1);
}

try {
    if ($existing !== null) {
        $users->updatePasswordHash($existing->id, $hash);
        // Rotating the session token signs out every existing session.
        $users->recordLogin($existing->id, null);
        echo "\n  Password updated for {$email}.\n";
        echo "  All existing sessions have been signed out.\n\n";
    } else {
        $users->create($name, $email, $hash);
        echo "\n  Administrator created: {$email}\n";
        echo "  Sign in at /admin/login\n\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "\n  Could not save the account: " . $e->getMessage() . "\n\n");
    exit(1);
}
