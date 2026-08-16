#!/usr/bin/env php
<?php
/**
 * Unattended database + receipt/OR file backup for FusionLink.
 *
 * Usage:
 *   php bin/db-backup.php
 *   php bin/db-backup.php --keep-days=14
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

require_once $root . '/app/Services/DatabaseBackupService.php';
if (is_file($root . '/app/Services/ActivityLogger.php')) {
    require_once $root . '/app/Services/ActivityLogger.php';
}

$keepDays = DatabaseBackupService::RETENTION_DAYS;
foreach ($argv as $arg) {
    if (preg_match('/^--keep-days=(\d+)$/', (string)$arg, $m)) {
        $keepDays = max(1, (int)$m[1]);
    }
}

try {
    $created = DatabaseBackupService::createBackup('isp_billing_auto');
    $pruned = DatabaseBackupService::pruneOldBackups($keepDays);

    if (class_exists('ActivityLogger')) {
        ActivityLogger::log(
            null,
            'cron@system',
            'SYSTEM',
            'Settings',
            'BACKUP',
            'Automatic backup created: ' . $created['name']
                . ' (pruned ' . $pruned['deleted'] . ', kept ' . $pruned['kept'] . ')'
        );
    }

    $payload = [
        'ok' => true,
        'backup' => $created,
        'pruned' => $pruned,
        'keep_days' => $keepDays,
        'date' => date('Y-m-d H:i:s'),
    ];

    echo json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    $message = 'DB backup cron failed: ' . $e->getMessage();
    fwrite(STDERR, $message . PHP_EOL);
    error_log($message);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]) . PHP_EOL;
    exit(1);
}
