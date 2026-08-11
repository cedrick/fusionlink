<?php

class PaymentMethodService
{
    public static function ensureTable(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS payment_methods (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                type ENUM('bank', 'gcash') NOT NULL,
                account_name VARCHAR(190) NOT NULL DEFAULT '',
                account_number VARCHAR(190) NOT NULL DEFAULT '',
                bank_branch VARCHAR(190) NULL,
                qr_path VARCHAR(255) NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_payment_methods_sort (sort_order),
                INDEX idx_payment_methods_type (type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public static function migrateLegacyFromSettings(PDO $pdo): void
    {
        self::ensureTable($pdo);

        $count = (int)$pdo->query('SELECT COUNT(*) FROM payment_methods')->fetchColumn();
        if ($count > 0) {
            return;
        }

        if (!self::columnExists($pdo, 'settings', 'bank_account')) {
            return;
        }

        $stmt = $pdo->query('
            SELECT bank_account, gcash_account
            FROM settings
            ORDER BY id ASC
            LIMIT 1
        ');
        $row = $stmt->fetch();
        if (!$row) {
            return;
        }

        $sort = 0;
        $bank = trim((string)($row['bank_account'] ?? ''));
        $gcash = trim((string)($row['gcash_account'] ?? ''));

        if ($bank !== '') {
            self::insertMethod($pdo, [
                'type' => 'bank',
                'account_name' => '',
                'account_number' => $bank,
                'bank_branch' => '',
                'qr_path' => null,
                'sort_order' => $sort++,
            ]);
        }

        if ($gcash !== '') {
            self::insertMethod($pdo, [
                'type' => 'gcash',
                'account_name' => '',
                'account_number' => $gcash,
                'bank_branch' => null,
                'qr_path' => null,
                'sort_order' => $sort++,
            ]);
        }
    }

    public static function getAll(PDO $pdo, bool $activeOnly = false): array
    {
        self::ensureTable($pdo);
        self::migrateLegacyFromSettings($pdo);

        $sql = '
            SELECT id, type, account_name, account_number, bank_branch, qr_path, sort_order, is_active
            FROM payment_methods
        ';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';

        return $pdo->query($sql)->fetchAll() ?: [];
    }

    public static function saveFromRequest(PDO $pdo, array $postedMethods, array $uploadedQrFiles): void
    {
        self::ensureTable($pdo);

        $existing = self::getAll($pdo, false);
        $existingById = [];
        foreach ($existing as $row) {
            $existingById[(int)$row['id']] = $row;
        }

        $keptIds = [];
        $sort = 0;

        foreach ($postedMethods as $index => $method) {
            if (!is_array($method)) {
                continue;
            }

            $type = strtolower(trim((string)($method['type'] ?? '')));
            if (!in_array($type, ['bank', 'gcash'], true)) {
                continue;
            }

            $accountName = trim((string)($method['account_name'] ?? ''));
            $accountNumber = trim((string)($method['account_number'] ?? ''));
            $bankBranch = trim((string)($method['bank_branch'] ?? ''));
            $id = (int)($method['id'] ?? 0);

            if ($accountNumber === '' && $accountName === '' && $bankBranch === '') {
                continue;
            }

            $qrPath = trim((string)($method['existing_qr_path'] ?? ''));
            if ($id > 0 && isset($existingById[$id])) {
                $qrPath = (string)($existingById[$id]['qr_path'] ?? '');
            }

            if (!empty($uploadedQrFiles['name'][$index])) {
                $newPath = self::storeQrUpload([
                    'name' => $uploadedQrFiles['name'][$index] ?? '',
                    'type' => $uploadedQrFiles['type'][$index] ?? '',
                    'tmp_name' => $uploadedQrFiles['tmp_name'][$index] ?? '',
                    'error' => $uploadedQrFiles['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $uploadedQrFiles['size'][$index] ?? 0,
                ]);

                if ($newPath !== null) {
                    if ($qrPath !== '' && $qrPath !== $newPath) {
                        self::deleteQrFile($qrPath);
                    }
                    $qrPath = $newPath;
                }
            }

            if ($type === 'bank') {
                $qrPath = null;
            }

            $payload = [
                'type' => $type,
                'account_name' => $accountName,
                'account_number' => $accountNumber,
                'bank_branch' => $type === 'bank' ? $bankBranch : null,
                'qr_path' => $type === 'gcash' ? ($qrPath !== '' ? $qrPath : null) : null,
                'sort_order' => $sort++,
            ];

            if ($id > 0 && isset($existingById[$id])) {
                self::updateMethod($pdo, $id, $payload);
                $keptIds[] = $id;
                continue;
            }

            $newId = self::insertMethod($pdo, $payload);
            if ($newId > 0) {
                $keptIds[] = $newId;
            }
        }

        foreach ($existing as $row) {
            $existingId = (int)($row['id'] ?? 0);
            if ($existingId > 0 && !in_array($existingId, $keptIds, true)) {
                if (!empty($row['qr_path'])) {
                    self::deleteQrFile((string)$row['qr_path']);
                }
                $stmt = $pdo->prepare('DELETE FROM payment_methods WHERE id = ?');
                $stmt->execute([$existingId]);
            }
        }
    }

    public static function formatInvoiceLines(array $methods): array
    {
        $lines = [];

        foreach ($methods as $method) {
            if ((int)($method['is_active'] ?? 1) !== 1) {
                continue;
            }

            $line = self::formatPlainText($method);
            if ($line !== '') {
                $lines[] = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
            }
        }

        return $lines;
    }

    public static function formatPlainText(array $method): string
    {
        $type = strtolower((string)($method['type'] ?? ''));
        $accountName = trim((string)($method['account_name'] ?? ''));
        $accountNumber = trim((string)($method['account_number'] ?? ''));
        $bankBranch = trim((string)($method['bank_branch'] ?? ''));

        if ($type === 'bank') {
            $parts = ['Bank Transfer'];
            if ($accountName !== '') {
                $parts[] = 'Account Name: ' . $accountName;
            }
            if ($accountNumber !== '') {
                $parts[] = 'Account No: ' . $accountNumber;
            }
            if ($bankBranch !== '') {
                $parts[] = 'Branch: ' . $bankBranch;
            }
            return implode(' | ', $parts);
        }

        if ($type === 'gcash') {
            $parts = ['GCash'];
            if ($accountName !== '') {
                $parts[] = 'Name: ' . $accountName;
            }
            if ($accountNumber !== '') {
                $parts[] = 'Number: ' . $accountNumber;
            }
            if (!empty($method['qr_path'])) {
                $parts[] = 'QR available';
            }
            return implode(' | ', $parts);
        }

        return '';
    }

    public static function publicUrl(?string $path): string
    {
        $path = trim((string)$path);
        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url($path);
    }

    private static function insertMethod(PDO $pdo, array $data): int
    {
        $stmt = $pdo->prepare('
            INSERT INTO payment_methods (type, account_name, account_number, bank_branch, qr_path, sort_order, is_active)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ');
        $stmt->execute([
            $data['type'],
            $data['account_name'],
            $data['account_number'],
            $data['bank_branch'],
            $data['qr_path'],
            (int)($data['sort_order'] ?? 0),
        ]);

        return (int)$pdo->lastInsertId();
    }

    private static function updateMethod(PDO $pdo, int $id, array $data): void
    {
        $stmt = $pdo->prepare('
            UPDATE payment_methods
            SET type = ?, account_name = ?, account_number = ?, bank_branch = ?, qr_path = ?, sort_order = ?, is_active = 1
            WHERE id = ?
        ');
        $stmt->execute([
            $data['type'],
            $data['account_name'],
            $data['account_number'],
            $data['bank_branch'],
            $data['qr_path'],
            (int)($data['sort_order'] ?? 0),
            $id,
        ]);
    }

    private static function storeQrUpload(array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mime = (string)($file['type'] ?? '');
        if (!in_array($mime, $allowed, true)) {
            return null;
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            return null;
        }

        $uploadDir = __DIR__ . '/../../public/uploads/payment-methods';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };

        $filename = 'qr_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = $uploadDir . '/' . $filename;

        if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
            return null;
        }

        return '/uploads/payment-methods/' . $filename;
    }

    private static function deleteQrFile(string $path): void
    {
        $path = trim($path);
        if ($path === '' || str_contains($path, '..')) {
            return;
        }

        $fullPath = __DIR__ . '/../../public' . (str_starts_with($path, '/') ? $path : '/' . $path);
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    private static function columnExists(PDO $pdo, string $tableName, string $columnName): bool
    {
        $stmt = $pdo->prepare('
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ');
        $stmt->execute([$tableName, $columnName]);

        return (int)$stmt->fetchColumn() > 0;
    }
}
