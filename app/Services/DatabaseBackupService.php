<?php

class DatabaseBackupService
{
    public const RETENTION_DAYS = 14;
    public const MAX_UPLOAD_BYTES = 128 * 1024 * 1024;

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
        $files = array_merge(glob($dir . '/*.sql') ?: [], glob($dir . '/*.zip') ?: []);
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
        if ($fileName === '' || !preg_match('/^[A-Za-z0-9._-]+\.(sql|zip)$/', $fileName)) {
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

        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('PHP zip extension is required for backups.');
        }

        $backupDir = self::ensureBackupDirectory();
        $fileName = $prefix . '_' . date('Y-m-d_H-i-s') . '.zip';
        $filePath = $backupDir . '/' . $fileName;
        $sqlTemp = $backupDir . '/.' . $prefix . '_' . uniqid('sql_', true) . '.sql';
        $defaultsFile = self::buildMysqlDefaultsFile($dbConfig);

        try {
            $command = sprintf(
                '/usr/bin/mysqldump --defaults-extra-file=%s --single-transaction --routines --triggers --events %s > %s',
                escapeshellarg($defaultsFile),
                escapeshellarg($dbConfig['db']),
                escapeshellarg($sqlTemp)
            );

            $output = [];
            $returnVar = 1;
            $ok = self::runShellCommand($command, $output, $returnVar);

            if (!$ok || !is_file($sqlTemp) || filesize($sqlTemp) === 0) {
                $detail = $output !== [] ? ' ' . trim(implode(' ', $output)) : '';
                throw new RuntimeException('Database backup failed.' . $detail);
            }

            $zip = new ZipArchive();
            if ($zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Failed to create backup archive.');
            }

            try {
                $zip->addFile($sqlTemp, 'database.sql');
                foreach (self::includedFileDirs() as $relative => $absolute) {
                    self::addDirectoryToZip($zip, $absolute, 'files/' . $relative);
                }
            } finally {
                $zip->close();
            }

            if (!is_file($filePath) || filesize($filePath) === 0) {
                throw new RuntimeException('Backup archive was not created.');
            }

            return [
                'path' => $filePath,
                'name' => $fileName,
                'size' => (int)filesize($filePath),
            ];
        } catch (Throwable $e) {
            if (is_file($filePath)) {
                @unlink($filePath);
            }
            throw $e;
        } finally {
            @unlink($defaultsFile);
            if (is_file($sqlTemp)) {
                @unlink($sqlTemp);
            }
        }
    }

    public static function restoreBackup(string $backupFilePath): void
    {
        if (!is_file($backupFilePath) || filesize($backupFilePath) <= 0) {
            throw new RuntimeException('Backup file is empty or missing.');
        }

        $extension = strtolower((string)pathinfo($backupFilePath, PATHINFO_EXTENSION));
        if ($extension === 'zip') {
            self::restoreZipBackup($backupFilePath);
            return;
        }

        self::restoreSqlDump($backupFilePath);
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
     * Store an uploaded .sql or .zip backup into storage/backups.
     *
     * @param array<string,mixed> $file
     */
    public static function storeUploadedBackup(array $file): string
    {
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('Please choose a .zip or .sql backup file to upload.');
        }
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException(
                'Backup file exceeds the upload limit (' . self::formatBytes(self::effectiveUploadLimitBytes()) . ').'
            );
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Backup upload failed. Please try again.');
        }

        $originalName = basename((string)($file['name'] ?? 'backup.zip'));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['zip', 'sql'], true)) {
            throw new RuntimeException('Only .zip or .sql backup files are allowed.');
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
        $safeOriginal = preg_replace('/[^A-Za-z0-9._-]+/', '_', $originalName) ?: ('backup.' . $extension);
        if ($safeOriginal === '.sql' || $safeOriginal === '.zip') {
            $safeOriginal = 'backup.' . $extension;
        }

        $storedPath = $backupDir . '/isp_billing_uploaded_' . date('Y-m-d_H-i-s') . '_' . $safeOriginal;
        if (!move_uploaded_file($tmpPath, $storedPath)) {
            throw new RuntimeException('Failed to save uploaded backup file.');
        }

        return $storedPath;
    }

    /**
     * Receipt, QR, CMS, and official-receipt files included in zip backups.
     *
     * @return array<string,string> relative path => absolute path
     */
    private static function includedFileDirs(): array
    {
        $root = dirname(__DIR__, 2);

        return [
            'public/uploads/receipts' => $root . '/public/uploads/receipts',
            'public/uploads/payment-methods' => $root . '/public/uploads/payment-methods',
            'public/uploads/cms' => $root . '/public/uploads/cms',
            'storage/official-receipts' => $root . '/storage/official-receipts',
        ];
    }

    private static function addDirectoryToZip(ZipArchive $zip, string $absoluteDir, string $zipPrefix): void
    {
        if (!is_dir($absoluteDir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absoluteDir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
                continue;
            }

            $full = $fileInfo->getPathname();
            $relative = substr($full, strlen(rtrim($absoluteDir, '/\\')) + 1);
            $relative = str_replace('\\', '/', (string)$relative);
            if ($relative === '' || str_contains($relative, '..')) {
                continue;
            }

            $zip->addFile($full, $zipPrefix . '/' . $relative);
        }
    }

    private static function restoreSqlDump(string $sqlFilePath): void
    {
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

    private static function restoreZipBackup(string $zipPath): void
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('PHP zip extension is required to restore this backup.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Unable to open backup archive.');
        }

        $sqlTemp = tempnam(sys_get_temp_dir(), 'ispdb_restore_');
        if ($sqlTemp === false) {
            $zip->close();
            throw new RuntimeException('Unable to create temporary restore file.');
        }

        try {
            $sqlIndex = $zip->locateName('database.sql', ZipArchive::FL_NOCASE);
            if ($sqlIndex === false) {
                throw new RuntimeException('Backup archive is missing database.sql.');
            }

            $sqlStream = $zip->getStream($zip->getNameIndex($sqlIndex));
            if ($sqlStream === false) {
                throw new RuntimeException('Unable to read database.sql from the archive.');
            }

            $out = fopen($sqlTemp, 'wb');
            if ($out === false) {
                fclose($sqlStream);
                throw new RuntimeException('Unable to write temporary SQL dump.');
            }
            stream_copy_to_stream($sqlStream, $out);
            fclose($out);
            fclose($sqlStream);

            self::restoreSqlDump($sqlTemp);
            self::restoreFilesFromZip($zip);
        } finally {
            $zip->close();
            @unlink($sqlTemp);
        }
    }

    private static function restoreFilesFromZip(ZipArchive $zip): void
    {
        $allowed = self::includedFileDirs();
        $dirsToReplace = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', (string)$zip->getNameIndex($i));
            if ($name === '' || str_ends_with($name, '/')) {
                continue;
            }
            if (!str_starts_with($name, 'files/') || str_contains($name, '..')) {
                continue;
            }

            $relative = substr($name, 6);
            $matchedPrefix = null;
            foreach ($allowed as $prefix => $absolute) {
                if ($relative === $prefix || str_starts_with($relative, $prefix . '/')) {
                    $matchedPrefix = $prefix;
                    break;
                }
            }
            if ($matchedPrefix === null) {
                continue;
            }

            $dirsToReplace[$matchedPrefix] = $allowed[$matchedPrefix];
        }

        foreach ($dirsToReplace as $dest) {
            self::emptyDirectory($dest);
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', (string)$zip->getNameIndex($i));
            if ($name === '' || str_ends_with($name, '/') || !str_starts_with($name, 'files/') || str_contains($name, '..')) {
                continue;
            }

            $relative = substr($name, 6);
            $matchedDest = null;
            $matchedPrefix = null;
            foreach ($allowed as $prefix => $absolute) {
                if ($relative === $prefix || str_starts_with($relative, $prefix . '/')) {
                    $matchedDest = $absolute;
                    $matchedPrefix = $prefix;
                    break;
                }
            }
            if ($matchedDest === null || $matchedPrefix === null) {
                continue;
            }

            $inside = substr($relative, strlen($matchedPrefix));
            $inside = ltrim(str_replace('\\', '/', (string)$inside), '/');
            if ($inside === '') {
                continue;
            }

            $destPath = $matchedDest . '/' . $inside;
            $parent = dirname($destPath);
            if (!is_dir($parent) && !mkdir($parent, 0775, true) && !is_dir($parent)) {
                throw new RuntimeException('Failed to restore backup files.');
            }

            $stream = $zip->getStream($name);
            if ($stream === false) {
                continue;
            }
            $out = fopen($destPath, 'wb');
            if ($out === false) {
                fclose($stream);
                throw new RuntimeException('Failed to write restored file: ' . $inside);
            }
            stream_copy_to_stream($stream, $out);
            fclose($out);
            fclose($stream);
        }
    }

    private static function emptyDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo) {
                continue;
            }
            $path = $fileInfo->getPathname();
            if ($fileInfo->isDir()) {
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }
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
