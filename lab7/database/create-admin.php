<?php
/**
 * CLI helper: create an Administrator account for Lab 7 demos.
 *
 * Usage:
 *   php create-admin.php "Test Administrator" admin@example.com "TestPassword123"
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/services/userService.php';

$fullName = $argv[1] ?? '';
$email = $argv[2] ?? '';
$password = $argv[3] ?? '';

if ($fullName === '' || $email === '' || $password === '') {
    fwrite(STDERR, "Usage: php create-admin.php \"Full Name\" email@example.com \"PasswordAtLeast12\"\n");
    exit(1);
}

try {
    $pdo = openDatabase();
    // Ensure users table exists (upgrade may not have been run).
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetch();
    if (!$tables) {
        fwrite(STDERR, "users table not found. Run upgrade-to-user-management.sql first.\n");
        exit(1);
    }

    $userId = createAdministratorAccount($pdo, $fullName, $email, $password);
    echo "Administrator account created successfully (user_id={$userId}, email={$email}, role=Administrator).\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
