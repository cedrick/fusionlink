<?php

/**
 * Staff-issued BIR Official Receipts for VAT-inclusive invoices.
 * Files are stored outside the public web root and served only to staff
 * or the invoice's customer through InvoiceController.
 */
class OfficialReceiptService
{
    public static function ensureSchema(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'invoices')) {
            return;
        }

        if (!self::columnExists($pdo, 'invoices', 'official_receipt_path')) {
            $pdo->exec("
                ALTER TABLE invoices
                ADD COLUMN official_receipt_path VARCHAR(255) NULL AFTER vat_amount
            ");
        }
    }

    public static function isVatInvoice(array $invoice): bool
    {
        return (float)($invoice['vat_amount'] ?? 0) > 0;
    }

    public static function storageDir(): string
    {
        return __DIR__ . '/../../storage/official-receipts';
    }

    public static function absolutePath(string $storedName): string
    {
        $storedName = basename(trim($storedName));
        if ($storedName === '' || $storedName === '.' || $storedName === '..') {
            return '';
        }

        return rtrim(self::storageDir(), '/') . '/' . $storedName;
    }

    /**
     * @param array $file $_FILES['official_receipt']
     * @return array{ok:bool,message:string,path:?string}
     */
    public static function attach(PDO $pdo, int $invoiceId, array $file): array
    {
        self::ensureSchema($pdo);

        if ($invoiceId <= 0) {
            return ['ok' => false, 'message' => 'Invoice is required.', 'path' => null];
        }

        $stmt = $pdo->prepare("
            SELECT id, vat_amount, official_receipt_path
            FROM invoices
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) {
            return ['ok' => false, 'message' => 'Invoice not found.', 'path' => null];
        }

        if (!self::isVatInvoice($invoice)) {
            return ['ok' => false, 'message' => 'Official Receipts can only be attached to VAT-inclusive bills.', 'path' => null];
        }

        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'message' => 'Please choose an Official Receipt file.', 'path' => null];
        }
        if ($error !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'message' => 'Official Receipt upload failed. Please try again.', 'path' => null];
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['ok' => false, 'message' => 'Invalid Official Receipt file.', 'path' => null];
        }

        if ((int)($file['size'] ?? 0) > 8 * 1024 * 1024) {
            return ['ok' => false, 'message' => 'Official Receipt is too large. Maximum size is 8 MB.', 'path' => null];
        }

        $ext = self::allowedExtension((string)($file['name'] ?? ''), $tmpName);
        if ($ext === null) {
            return ['ok' => false, 'message' => 'Invalid file type. Allowed: PDF, JPG, JPEG, PNG, WEBP.', 'path' => null];
        }

        $dir = self::storageDir();
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return ['ok' => false, 'message' => 'Failed to prepare Official Receipt storage.', 'path' => null];
        }
        if (!is_writable($dir)) {
            @chmod($dir, 0775);
        }
        if (!is_writable($dir)) {
            return ['ok' => false, 'message' => 'Official Receipt storage is not writable.', 'path' => null];
        }

        $filename = 'or_' . $invoiceId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = rtrim($dir, '/') . '/' . $filename;

        if (!move_uploaded_file($tmpName, $target)) {
            return ['ok' => false, 'message' => 'Failed to save Official Receipt.', 'path' => null];
        }

        $old = trim((string)($invoice['official_receipt_path'] ?? ''));
        $stmt = $pdo->prepare('UPDATE invoices SET official_receipt_path = ? WHERE id = ?');
        $stmt->execute([$filename, $invoiceId]);

        if ($old !== '' && $old !== $filename) {
            $oldPath = self::absolutePath($old);
            if ($oldPath !== '' && is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        return [
            'ok' => true,
            'message' => 'Official Receipt attached. The customer can now view it in their portal.',
            'path' => $filename,
        ];
    }

    public static function mimeType(string $absolutePath): string
    {
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        return match ($ext) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };
    }

    private static function allowedExtension(string $originalName, string $tmpName): ?string
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            return null;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmpName);
        $ok = [
            'application/pdf' => ['pdf'],
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/webp' => ['webp'],
        ];

        if (!isset($ok[$mime]) || !in_array($ext, $ok[$mime], true)) {
            return null;
        }

        return $ext;
    }

    private static function tableExists(PDO $pdo, string $tableName): bool
    {
        $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$tableName]);
        return (bool)$stmt->fetchColumn();
    }

    private static function columnExists(PDO $pdo, string $tableName, string $columnName): bool
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$tableName, $columnName]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
