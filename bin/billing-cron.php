#!/usr/bin/env php
<?php
/**
 * Unattended billing jobs: generate month-end bills, due reminders, overdue notices.
 *
 * Usage:
 *   php bin/billing-cron.php
 *   php bin/billing-cron.php all
 *   php bin/billing-cron.php generate|reminders|overdue
 *
 * Intended to be run daily via system cron (see deploy/fusionlink-billing.cron).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$root = dirname(__DIR__);
chdir($root);

$appConfig = require $root . '/config/app.php';
$timezone = trim((string)($appConfig['timezone'] ?? 'Asia/Manila'));
if ($timezone !== '') {
    date_default_timezone_set($timezone);
}

require_once $root . '/app/Services/BillingCycleService.php';
if (is_file($root . '/app/Services/ReferralService.php')) {
    require_once $root . '/app/Services/ReferralService.php';
}
if (is_file($root . '/app/Services/MailService.php')) {
    require_once $root . '/app/Services/MailService.php';
}
if (is_file($root . '/app/Services/EmailAlertService.php')) {
    require_once $root . '/app/Services/EmailAlertService.php';
}
if (is_file($root . '/app/Services/ActivityLogger.php')) {
    require_once $root . '/app/Services/ActivityLogger.php';
}

$task = strtolower(trim((string)($argv[1] ?? 'all')));
if ($task === '') {
    $task = 'all';
}

try {
    $dbConfig = require $root . '/config/database.php';
    $dbName = $dbConfig['db'] ?? ($dbConfig['name'] ?? null);
    if (!$dbName) {
        throw new RuntimeException("Database config error: missing 'db' (or 'name').");
    }

    $host = $dbConfig['host'] ?? '127.0.0.1';
    $user = $dbConfig['user'] ?? '';
    $pass = $dbConfig['pass'] ?? '';
    $charset = $dbConfig['charset'] ?? 'utf8mb4';
    $dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $result = BillingCycleService::runScheduledJobs($pdo, $task);

    if (class_exists('ActivityLogger')) {
        ActivityLogger::log(
            null,
            'cron@system',
            'SYSTEM',
            'Billing',
            'CRON',
            'CLI billing cron task="' . $task . '" result=' . json_encode($result)
        );
    }

    $payload = [
        'ok' => true,
        'task' => $task,
        'date' => BillingCycleService::today(),
        'result' => $result,
    ];

    echo json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    $message = 'Billing cron failed: ' . $e->getMessage();
    fwrite(STDERR, $message . PHP_EOL);
    error_log($message);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]) . PHP_EOL;
    exit(1);
}
