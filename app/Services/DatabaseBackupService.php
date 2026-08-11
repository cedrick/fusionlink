<?php

class DatabaseBackupService
{
    public const RETENTION_DAYS = 14;
    public const MAX_UPLOAD_BYTES = 64 * 1024 * 1024;

    public static function backupDirectory(): string
    {
        return dirname(__DIR__, 2) . '/storage/backups';
    }

    public static function ensureBackupDirectory(): string
    {
        $dir = self::backupDirectory();
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Failed to create backup directory.');
        }

        return $dir;
    }

    /**
     * @return array{host:string,db:string,user:string,pass:string,charset:string}
     */
    public static function dbConfig(): array
    {
        $config = require dirname(__DIR__, 2) . '/config/database.php';

        return [
            'host' => (string)($config['host'] ?? '127.0.0.1'),
            'db' => (string)($config['db'] ?? ($config['name'] ?? '')),
            'user' => (string)($config['user'] ?? ''),
            'pass' => (string)($config['pass'] ?? ''),
            'charset' => (string)($config['charset'] ?? 'utf8mb4'),
        ];
    }

    /**
     * Effective upload cap: min(configured app max, PHP upload_max, PHP post_max).
     */
    public static function effectiveUploadLimitBytes(): int
    {
        $limit = self::MAX_UPLOAD_BYTES;
        $uploadMax = self::iniBytes('upload_max_filesize');
        $postMax = self::iniBytes('post_max_size');

        if ($uploadMax > 0) {
            $limit = min($limit, $uploadMax);
        }
        if ($postMax > 0) {
            $limit = min($limit, $postMax);
        }

        return max(1, $limit);
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return number_format($bytes / (1024 * 1024), 1) . ' MB';
    }

    /**
     * @return list<array{name:string,path:string,size:int,modified_at:string,mtime:int,source:string}>
     */
    public static function listBackups(): array
    {
        $dir = self::ensureBackupDirectory();
        $files = glob($dir . '/*.sql') ?: [];
        $items = [];

        foreach ($files as $path) {
            if (!is_file($path)) {
                continue;
            }
            $size = filesize($path);
            $mtime = filemtime($path);
            if (!is_int($size) || $size <= 0 || $mtime === false) {
                continue;
            }

            $name = basename($path);
            $source = 'manual';
            if (str_starts_with($name, 'isp_billing_uploaded_')) {
                $source = 'uploaded';
            } elseif (str_starts_with($name, 'isp_billing_auto_')) {
                $source = 'automatic';
            } elseif (str_starts_with($name, 'isp_billing_backup_')) {
                $source = 'manual';
            }

            $items[] = [
                'name' => $name,
                'path' => $path,
                'size' => $size,
                'mtime' => $mtime,
                'modified_at' => date('Y-m-d H:i:s', $mtime),
                'source' => $source,
            ];
        }

        usort($items, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);

        return $items;
    }

    public static function getLatestBackupMeta(): ?array
    {
        $list = self::listBackups();
        return $list[0] ?? null;
    }

    public static function resolveBackupPath(string $fileName): string
    {
        $fileName = basename(trim($fileName));
        if ($fileName === '' || !preg_match('/^[A-Za-z0-9._-]+\.sql$/', $fileName)) {
            throw new RuntimeException('Invalid backup file name.');
        }

        $dir = realpath(self::ensureBackupDirectory());
        if ($dir === false) {
            throw new RuntimeException('Backup directory is not available.');
        }

        $path = $dir . DIRECTORY_SEPARATOR . $fileName;
        $real = realpath($path);
        if ($real === false || !is_file($real) || !str_starts_with($real, $dir . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Backup file not found.');
        }

        return $real;
    }

    /**
     * @return array{path:string,name:string,size:int}
     */
    public static function createBackup(string $prefix = 'isp_billing_backup'): array
    {
        $dbConfig = self::dbConfig();
        if ($dbConfig['db'] === '' || $dbConfig['user'] === '') {
            throw new RuntimeException('Database configuration is incomplete.');
        }

        $backupDir = self::ensureBackupDirectory();
        $fileName = $prefix . '_' . date('Y-m-d_H-i-s') . '.sql';
        $filePath = $backupDir . '/' . $fileName;
        $defaultsFile = self::buildMysqlDefaultsFile($dbConfig);

        try {
            $command = sprintf(
                '/usr/bin/mysqldump --defaults-extra-file=%s --single-transaction --routines --triggers --events %s > %s',
                escapeshellarg($defaultsFile),
                escapeshellarg($dbConfig['db']),
                escapeshellarg($filePath)
            );

            $output = [];
            $returnVar = 1;
            $ok = self::runShellCommand($command, $output, $returnVar);

            if (!$ok || !is_file($filePath) || filesize($filePath) === 0) {
                if (is_file($filePath)) {
                    @unlink($filePath);
                }
                $detail = $output !== [] ? ' ' . trim(implode(' ', $output)) : '';
                throw new RuntimeException('Database backup failed.' . $detail);
            }

            return [
                'path' => $filePath,
                'name' => $fileName,
                'size' => (int)filesize($filePath),
            ];
        } finally {
            @unlink($defaultsFile);
        }
    }

    public static function restoreBackup(string $sqlFilePath): void
    {
        if (!is_file($sqlFilePath) || filesize($sqlFilePath) <= 0) {
            throw new RuntimeException('Backup file is empty or missing.');
        }

        $dbConfig = self::dbConfig();
        $pdo = self::pdo($dbConfig);
        self::dropAllTables($pdo);

        $defaultsFile = self::buildMysqlDefaultsFile($dbConfig);

        try {
            $command = sprintf(
                '/usr/bin/mysql --defaults-extra-file=%s %s < %s',
                escapeshellarg($defaultsFile),
                escapeshellarg($dbConfig['db']),
                escapeshellarg($sqlFilePath)
            );

            $output = [];
            $returnVar = 1;
            $ok = self::runShellCommand($command, $output, $returnVar);

            if (!$ok) {
                $errorMessage = 'Failed to restore backup file.';
                if ($output !== []) {
                    $errorMessage .= ' ' . trim(implode(' ', $output));
                }
                throw new RuntimeException($errorMessage);
            }
        } finally {
            @unlink($defaultsFile);
        }
    }

    public static function deleteBackup(string $fileName): void
    {
        $path = self::resolveBackupPath($fileName);
        if (!@unlink($path)) {
            throw new RuntimeException('Failed to delete backup file.');
        }
    }

    /**
     * @return array{deleted:int,kept:int}
     */
    public static function pruneOldBackups(int $retentionDays = self::RETENTION_DAYS): array
    {
        $retentionDays = max(1, $retentionDays);
        $cutoff = time() - ($retentionDays * 86400);
        $deleted = 0;
        $kept = 0;

        foreach (self::listBackups() as $item) {
            $name = (string)($item['name'] ?? '');
            $isAutomatic = str_starts_with($name, 'isp_billing_auto_');

            // Only prune automatic daily backups. Keep manual/uploaded copies.
            if ($isAutomatic && (int)$item['mtime'] < $cutoff) {
                if (@unlink($item['path'])) {
                    $deleted++;
                    continue;
                }
            }
            $kept++;
        }

        return ['deleted' => $deleted, 'kept' => $kept];
    }

    /**
     * Store an uploaded .sql backup into storage/backups.
     *
     * @param array<string,mixed> $file
     */
    public static function storeUploadedBackup(array $file): string
    {
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('Please choose a .sql backup file to upload.');
        }
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException(
                'Backup file exceeds the upload limit (' . self::formatBytes(self::effectiveUploadLimitBytes()) . ').'
            );
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Backup upload failed. Please try again.');
        }

        $originalName = basename((string)($file['name'] ?? 'backup.sql'));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'sql') {
            throw new RuntimeException('Only .sql backup files are allowed.');
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0) {
            throw new RuntimeException('Backup file is empty.');
        }

        $maxBytes = self::effectiveUploadLimitBytes();
        if ($size > $maxBytes) {
            throw new RuntimeException(
                'Backup file is too large. Maximum upload size is ' . self::formatBytes($maxBytes) . '.'
            );
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new RuntimeException('Invalid uploaded backup file.');
        }

        $backupDir = self::ensureBackupDirectory();
        $safeOriginal = preg_replace('/[^A-Za-z0-9._-]+/', '_', $originalName) ?: 'backup.sql';
        if ($safeOriginal === '.sql') {
            $safeOriginal = 'backup.sql';
        }

        $storedPath = $backupDir . '/isp_billing_uploaded_' . date('Y-m-d_H-i-s') . '_' . $safeOriginal;
        if (!move_uploaded_file($tmpPath, $storedPath)) {
            throw new RuntimeException('Failed to save uploaded backup file.');
        }

        return $storedPath;
    }

    private static function pdo(array $dbConfig): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $dbConfig['host'],
            $dbConfig['db'],
            $dbConfig['charset']
        );

        return new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    private static function dropAllTables(PDO $pdo): void
    {
        $stmt = $pdo->query('SHOW TABLES');
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);
        if ($rows === []) {
            return;
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($rows as $row) {
            if (!empty($row[0])) {
                $pdo->exec('DROP TABLE IF EXISTS `' . str_replace('`', '``', (string)$row[0]) . '`');
            }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private static function buildMysqlDefaultsFile(array $dbConfig): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ispdb_');
        if ($tempFile === false) {
            throw new RuntimeException('Unable to create temporary MySQL credentials file.');
        }

        $content = "[client]\n";
        $content .= 'host=' . $dbConfig['host'] . "\n";
        $content .= 'user=' . $dbConfig['user'] . "\n";
        $content .= 'password="' . str_replace(['\\', '"'], ['\\\\', '\\"'], $dbConfig['pass']) . "\"\n";

        file_put_contents($tempFile, $content);
        @chmod($tempFile, 0600);

        return $tempFile;
    }

    private static function runShellCommand(string $command, ?array &$output = null, ?int &$returnVar = null): bool
    {
        $output = [];
        $returnVar = 1;

        if (!function_exists('exec')) {
            return false;
        }

        exec($command . ' 2>&1', $output, $returnVar);
        return $returnVar === 0;
    }

    private static function iniBytes(string $key): int
    {
        $raw = trim((string)ini_get($key));
        if ($raw === '' || $raw === '0') {
            return 0;
        }

        $unit = strtolower(substr($raw, -1));
        $value = (float)$raw;

        return (int) match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => (float)$raw,
        };
    }
}
