<?php

if (file_exists(__DIR__ . '/../Services/ActivityLogger.php')) {
    require_once __DIR__ . '/../Services/ActivityLogger.php';
}

if (file_exists(__DIR__ . '/../Services/PaymentMethodService.php')) {
    require_once __DIR__ . '/../Services/PaymentMethodService.php';
}

if (file_exists(__DIR__ . '/../Services/EmailAlertService.php')) {
    require_once __DIR__ . '/../Services/EmailAlertService.php';
}

if (file_exists(__DIR__ . '/../Services/ReferralService.php')) {
    require_once __DIR__ . '/../Services/ReferralService.php';
}

if (file_exists(__DIR__ . '/../Services/DatabaseBackupService.php')) {
    require_once __DIR__ . '/../Services/DatabaseBackupService.php';
}

if (file_exists(__DIR__ . '/../Services/OmadaNetworkAccessService.php')) {
    require_once __DIR__ . '/../Services/OmadaNetworkAccessService.php';
}

class SettingController
{
    private function requireLogin(): void
    {
        if (!isset($_SESSION['user'])) {
            redirect('/login');
            exit;
        }
    }

    private function requireAdmin(): void
    {
        $this->requireLogin();

        $role = $_SESSION['user']['role'] ?? '';

        if (!in_array($role, ['ROLE_ADMIN', 'ADMIN', 'admin'], true)) {
            http_response_code(403);
            echo '<h1>Access Denied</h1>';
            echo '<p>This page is restricted to administrators only.</p>';
            echo "<a href='" . htmlspecialchars(url('/dashboard'), ENT_QUOTES, 'UTF-8') . "'>Back to Dashboard</a>";
            exit;
        }
    }

    private function db(): PDO
    {
        $config = require __DIR__ . '/../../config/database.php';

        $dbName = $config['db'] ?? ($config['name'] ?? null);
        if (!$dbName) {
            die("Database config error: missing 'db' (or 'name') key in config/database.php");
        }

        $host    = $config['host'] ?? '127.0.0.1';
        $user    = $config['user'] ?? '';
        $pass    = $config['pass'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    private function getDbConfig(): array
    {
        $config = require __DIR__ . '/../../config/database.php';

        return [
            'host'    => $config['host'] ?? '127.0.0.1',
            'db'      => $config['db'] ?? ($config['name'] ?? ''),
            'user'    => $config['user'] ?? '',
            'pass'    => $config['pass'] ?? '',
            'charset' => $config['charset'] ?? 'utf8mb4',
        ];
    }

    private function tableExists(PDO $pdo, string $tableName): bool
    {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        return (bool)$stmt->fetchColumn();
    }

    private function columnExists(PDO $pdo, string $tableName, string $columnName): bool
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

    private function ensureBillingDueDayColumn(PDO $pdo): void
    {
        if (!$this->tableExists($pdo, 'settings')) {
            return;
        }

        if (!$this->columnExists($pdo, 'settings', 'billing_due_day')) {
            $pdo->exec("ALTER TABLE settings ADD COLUMN billing_due_day INT NOT NULL DEFAULT 8");
        }
        if (!$this->columnExists($pdo, 'settings', 'vat_rate')) {
            $pdo->exec("ALTER TABLE settings ADD COLUMN vat_rate DECIMAL(5,2) NOT NULL DEFAULT 12.00");
        }
        if (class_exists('OmadaNetworkAccessService')) {
            OmadaNetworkAccessService::ensureSchema($pdo);
        }
    }

    private function ensureSmtpColumns(PDO $pdo): void
    {
        if (!$this->tableExists($pdo, 'settings')) {
            return;
        }

        if (!$this->columnExists($pdo, 'settings', 'smtp_host')) {
            $pdo->exec("ALTER TABLE settings ADD COLUMN smtp_host VARCHAR(190) NULL AFTER email");
        }

        if (!$this->columnExists($pdo, 'settings', 'smtp_port')) {
            $pdo->exec("ALTER TABLE settings ADD COLUMN smtp_port INT NULL AFTER smtp_host");
        }

        if (!$this->columnExists($pdo, 'settings', 'smtp_username')) {
            $pdo->exec("ALTER TABLE settings ADD COLUMN smtp_username VARCHAR(190) NULL AFTER smtp_port");
        }

        if (!$this->columnExists($pdo, 'settings', 'smtp_password')) {
            $pdo->exec("ALTER TABLE settings ADD COLUMN smtp_password VARCHAR(255) NULL AFTER smtp_username");
        }

        if (!$this->columnExists($pdo, 'settings', 'smtp_encryption')) {
            $pdo->exec("ALTER TABLE settings ADD COLUMN smtp_encryption VARCHAR(50) NULL AFTER smtp_password");
        }
    }

    private function ensureSettingsRow(PDO $pdo): array
    {
        $this->ensureBillingDueDayColumn($pdo);
        $this->ensureSmtpColumns($pdo);
        if (class_exists('ReferralService')) {
            ReferralService::ensureSchema($pdo);
        }
        if (class_exists('OmadaNetworkAccessService')) {
            OmadaNetworkAccessService::ensureSchema($pdo);
        }

        $stmt = $pdo->query("
            SELECT
                id,
                company_name,
                business_address,
                bank_account,
                gcash_account,
                contact_number,
                email,
                smtp_host,
                smtp_port,
                smtp_username,
                smtp_password,
                smtp_encryption,
                billing_due_day,
                referral_reward_amount,
                vat_rate,
                omada_enabled,
                omada_base_url,
                omada_omadac_id,
                omada_site_id,
                omada_client_id,
                omada_client_secret,
                omada_username,
                omada_password,
                omada_allow_insecure
            FROM settings
            ORDER BY id ASC
            LIMIT 1
        ");
        $settings = $stmt->fetch();

        if ($settings) {
            $settings['billing_due_day'] = (int)($settings['billing_due_day'] ?? 8);
            if ($settings['billing_due_day'] < 1 || $settings['billing_due_day'] > 31) {
                $settings['billing_due_day'] = 8;
            }

            $settings['smtp_port'] = isset($settings['smtp_port']) && $settings['smtp_port'] !== null
                ? (int)$settings['smtp_port']
                : 587;

            $settings['referral_reward_amount'] = round((float)($settings['referral_reward_amount'] ?? 500), 2);
            if ($settings['referral_reward_amount'] <= 0) {
                $settings['referral_reward_amount'] = 500;
            }

            $settings['vat_rate'] = round((float)($settings['vat_rate'] ?? 12), 2);
            if ($settings['vat_rate'] < 0 || $settings['vat_rate'] > 100) {
                $settings['vat_rate'] = 12.0;
            }

            $settings['omada_enabled'] = (int)($settings['omada_enabled'] ?? 0);
            $settings['omada_allow_insecure'] = (int)($settings['omada_allow_insecure'] ?? 1);

            return $settings;
        }

        $stmt = $pdo->prepare("
            INSERT INTO settings (
                company_name,
                business_address,
                bank_account,
                gcash_account,
                contact_number,
                email,
                smtp_host,
                smtp_port,
                smtp_username,
                smtp_password,
                smtp_encryption,
                billing_due_day
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'ISP-BILLING-LITE',
            '',
            '',
            '',
            '',
            '',
            'smtp.gmail.com',
            587,
            '',
            '',
            'tls',
            1,
        ]);

        $id = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("
            SELECT
                id,
                company_name,
                business_address,
                bank_account,
                gcash_account,
                contact_number,
                email,
                smtp_host,
                smtp_port,
                smtp_username,
                smtp_password,
                smtp_encryption,
                billing_due_day
            FROM settings
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);

        return $stmt->fetch() ?: [
            'id' => $id,
            'company_name' => 'ISP-BILLING-LITE',
            'business_address' => '',
            'bank_account' => '',
            'gcash_account' => '',
            'contact_number' => '',
            'email' => '',
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'tls',
            'billing_due_day' => 8,
            'referral_reward_amount' => 500,
            'vat_rate' => 12,
            'omada_enabled' => 0,
            'omada_base_url' => '',
            'omada_omadac_id' => '',
            'omada_site_id' => '',
            'omada_client_id' => '',
            'omada_client_secret' => '',
            'omada_username' => '',
            'omada_password' => '',
            'omada_allow_insecure' => 1,
        ];
    }

    private function redirectWithStatus(string $type, string $message): void
    {
        header('Location: ' . url('/settings') . '?' . http_build_query([
            $type => '1',
            'message' => $message,
        ]));
        exit;
    }

    private function normalizeSmtpUsername(string $smtpUsername): string
    {
        return strtolower(trim($smtpUsername));
    }

    private function validateSmtpUsername(string $smtpUsername): ?string
    {
        if ($smtpUsername === '') {
            return null;
        }

        if (!filter_var($smtpUsername, FILTER_VALIDATE_EMAIL)) {
            return 'Invalid SMTP username email address.';
        }

        if (preg_match('/@gmai\.com$/i', $smtpUsername)) {
            return 'SMTP username looks like a typo. Use @gmail.com, not @gmai.com.';
        }

        if (preg_match('/@gmial\.com$|@gmail\.co$|@gmal\.com$/i', $smtpUsername)) {
            return 'SMTP username looks misspelled. Double-check the Gmail address.';
        }

        return null;
    }

    private function runShellCommand(string $command, ?array &$output = null, ?int &$returnVar = null): bool
    {
        $output = [];
        $returnVar = 1;

        if (!function_exists('exec')) {
            return false;
        }

        exec($command . ' 2>&1', $output, $returnVar);
        return $returnVar === 0;
    }

    private function buildMysqlDefaultsFile(array $dbConfig): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ispdb_');

        $content = "[client]\n";
        $content .= "host=" . $dbConfig['host'] . "\n";
        $content .= "user=" . $dbConfig['user'] . "\n";
        $content .= "password=" . $dbConfig['pass'] . "\n";

        file_put_contents($tempFile, $content);
        @chmod($tempFile, 0600);

        return $tempFile;
    }

    private function getBackupDirectory(): string
    {
        return class_exists('DatabaseBackupService')
            ? DatabaseBackupService::backupDirectory()
            : (__DIR__ . '/../../storage/backups');
    }

    private function getLatestBackupFile(): ?string
    {
        if (class_exists('DatabaseBackupService')) {
            $latest = DatabaseBackupService::getLatestBackupMeta();
            return $latest['path'] ?? null;
        }

        $backupDir = $this->getBackupDirectory();
        if (!is_dir($backupDir)) {
            return null;
        }

        $files = array_merge(glob($backupDir . '/*.sql') ?: [], glob($backupDir . '/*.zip') ?: []);
        if (!$files || !is_array($files)) {
            return null;
        }

        usort($files, static function (string $a, string $b): int {
            return filemtime($b) <=> filemtime($a);
        });

        $latest = $files[0] ?? null;
        if (!$latest || !is_file($latest) || filesize($latest) <= 0) {
            return null;
        }

        return $latest;
    }

    private function getLatestBackupMeta(): ?array
    {
        if (class_exists('DatabaseBackupService')) {
            return DatabaseBackupService::getLatestBackupMeta();
        }

        $latest = $this->getLatestBackupFile();
        if ($latest === null) {
            return null;
        }

        $size = filesize($latest);
        $modified = filemtime($latest);

        return [
            'path' => $latest,
            'name' => basename($latest),
            'size' => is_int($size) ? $size : 0,
            'modified_at' => $modified !== false ? date('Y-m-d H:i:s', $modified) : '',
        ];
    }

    private function getBackupList(): array
    {
        if (class_exists('DatabaseBackupService')) {
            return DatabaseBackupService::listBackups();
        }

        return [];
    }

    private function getAllDatabaseTables(PDO $pdo): array
    {
        $stmt = $pdo->query("SHOW TABLES");
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);

        $tables = [];
        foreach ($rows as $row) {
            if (!empty($row[0])) {
                $tables[] = (string)$row[0];
            }
        }

        return $tables;
    }

    private function countTableRows(PDO $pdo, string $table): int
    {
        if (!$this->tableExists($pdo, $table)) {
            return 0;
        }

        $stmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
        return (int)$stmt->fetchColumn();
    }

    private function resetBusinessData(PDO $pdo): array
    {
        $summary = [
            'customers' => $this->countTableRows($pdo, 'customers'),
            'subscriptions' => $this->countTableRows($pdo, 'subscriptions'),
            'invoices' => $this->countTableRows($pdo, 'invoices'),
            'payments' => $this->countTableRows($pdo, 'payments'),
            'notifications' => $this->countTableRows($pdo, 'notifications'),
            'inquiries' => $this->countTableRows($pdo, 'service_requests'),
            'customer_users' => 0,
            'login_otps' => $this->countTableRows($pdo, 'login_otps'),
            'otp_logins' => $this->countTableRows($pdo, 'otp_logins'),
        ];

        if ($this->tableExists($pdo, 'users')) {
            $summary['customer_users'] = (int)$pdo->query("
                SELECT COUNT(*)
                FROM users
                WHERE role = 'ROLE_CUSTOMER'
            ")->fetchColumn();
        }

        $tablesToTruncate = [
            'payments',
            'notifications',
            'invoices',
            'referral_rewards',
            'subscriptions',
            'customers',
            'service_requests',
        ];

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tablesToTruncate as $table) {
            if ($this->tableExists($pdo, $table)) {
                $pdo->exec("TRUNCATE TABLE `{$table}`");
            }
        }

        if ($this->tableExists($pdo, 'users')) {
            $pdo->exec("DELETE FROM users WHERE role = 'ROLE_CUSTOMER'");
        }

        foreach (['login_otps', 'otp_logins'] as $table) {
            if ($this->tableExists($pdo, $table)) {
                $pdo->exec("TRUNCATE TABLE `{$table}`");
            }
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        return $summary;
    }

    private function formatResetSummary(array $summary): string
    {
        $parts = [];

        if (($summary['customers'] ?? 0) > 0) {
            $parts[] = (int)$summary['customers'] . ' customer(s)';
        }
        if (($summary['subscriptions'] ?? 0) > 0) {
            $parts[] = (int)$summary['subscriptions'] . ' subscription(s)';
        }
        if (($summary['invoices'] ?? 0) > 0) {
            $parts[] = (int)$summary['invoices'] . ' invoice(s)';
        }
        if (($summary['payments'] ?? 0) > 0) {
            $parts[] = (int)$summary['payments'] . ' payment(s)';
        }
        if (($summary['notifications'] ?? 0) > 0) {
            $parts[] = (int)$summary['notifications'] . ' notification(s)';
        }
        if (($summary['inquiries'] ?? 0) > 0) {
            $parts[] = (int)$summary['inquiries'] . ' inquiry/application(s)';
        }
        if (($summary['customer_users'] ?? 0) > 0) {
            $parts[] = (int)$summary['customer_users'] . ' customer portal login(s)';
        }
        if (($summary['login_otps'] ?? 0) > 0 || ($summary['otp_logins'] ?? 0) > 0) {
            $parts[] = ((int)$summary['login_otps'] + (int)$summary['otp_logins']) . ' pending login code(s)';
        }

        if ($parts === []) {
            return 'Billing and application data cleared. No matching records were found.';
        }

        return 'Billing and application data cleared: ' . implode(', ', $parts) . '.';
    }

    private function dropAllTables(PDO $pdo): void
    {
        $tables = $this->getAllDatabaseTables($pdo);

        if ($tables === []) {
            return;
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private function storeUploadedBackupFile(array $file): string
    {
        if (class_exists('DatabaseBackupService')) {
            return DatabaseBackupService::storeUploadedBackup($file);
        }

        throw new RuntimeException('DatabaseBackupService is unavailable.');
    }

    private function importSqlBackup(string $sqlFilePath, string $label): void
    {
        if (class_exists('DatabaseBackupService')) {
            DatabaseBackupService::restoreBackup($sqlFilePath);
        } else {
            throw new RuntimeException('DatabaseBackupService is unavailable.');
        }

        $pdo = $this->db();
        if (class_exists('PaymentMethodService')) {
            PaymentMethodService::ensureTable($pdo);
        }

        if (class_exists('ActivityLogger')) {
            ActivityLogger::logSession('Settings', 'RESTORE', 'Restored backup (database and files): ' . $label);
        }
    }

    public function index(): void
    {
        $this->requireAdmin();

        $settings = [
            'id' => 0,
            'company_name' => 'ISP-BILLING-LITE',
            'business_address' => '',
            'bank_account' => '',
            'gcash_account' => '',
            'contact_number' => '',
            'email' => '',
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'tls',
            'billing_due_day' => 8,
            'referral_reward_amount' => 500,
            'vat_rate' => 12,
            'omada_enabled' => 0,
            'omada_base_url' => '',
            'omada_omadac_id' => '',
            'omada_site_id' => '',
            'omada_client_id' => '',
            'omada_client_secret' => '',
            'omada_username' => '',
            'omada_password' => '',
            'omada_allow_insecure' => 1,
        ];

        $paymentMethods = [];

        try {
            $pdo = $this->db();
            $settings = $this->ensureSettingsRow($pdo);
            if (class_exists('PaymentMethodService')) {
                $paymentMethods = PaymentMethodService::getAll($pdo);
            }
        } catch (Throwable $e) {
            error_log('SettingController@index error: ' . $e->getMessage());
        }

        View::render('settings/index', [
            'title' => 'Settings',
            'settings' => $settings,
            'paymentMethods' => $paymentMethods,
            'hasSmtpPassword' => trim((string)($settings['smtp_password'] ?? '')) !== '',
            'success' => isset($_GET['success']) && $_GET['success'] === '1',
            'error' => isset($_GET['error']) && $_GET['error'] === '1',
            'message' => trim((string)($_GET['message'] ?? '')),
            'latestBackupFile' => $this->getLatestBackupFile(),
            'latestBackupMeta' => $this->getLatestBackupMeta(),
            'backupFiles' => $this->getBackupList(),
            'backupUploadLimit' => class_exists('DatabaseBackupService')
                ? DatabaseBackupService::formatBytes(DatabaseBackupService::effectiveUploadLimitBytes())
                : '2 MB',
            'backupRetentionDays' => class_exists('DatabaseBackupService')
                ? DatabaseBackupService::RETENTION_DAYS
                : 14,
        ]);
    }

    public function update(): void
    {
        $this->requireAdmin();

        try {
            $pdo = $this->db();
            $settings = $this->ensureSettingsRow($pdo);

            $id = (int)($settings['id'] ?? 0);

            $companyName     = trim((string)($_POST['company_name'] ?? ''));
            $businessAddress = trim((string)($_POST['business_address'] ?? ''));
            $contactNumber   = trim((string)($_POST['contact_number'] ?? ''));
            $email           = trim((string)($_POST['email'] ?? ''));
            $smtpHost        = trim((string)($_POST['smtp_host'] ?? ''));
            $smtpPort        = (int)($_POST['smtp_port'] ?? 587);
            $smtpUsername    = $this->normalizeSmtpUsername((string)($_POST['smtp_username'] ?? ''));
            $smtpPassword    = trim((string)($_POST['smtp_password'] ?? ''));
            $smtpEncryption  = strtolower(trim((string)($_POST['smtp_encryption'] ?? 'tls')));
            $billingDueDay   = (int)($_POST['billing_due_day'] ?? 8);
            $referralRewardAmount = (float)($_POST['referral_reward_amount'] ?? 500);
            $vatRate = round((float)($_POST['vat_rate'] ?? 12), 2);
            $omadaEnabled = isset($_POST['omada_enabled']) && (string)$_POST['omada_enabled'] === '1' ? 1 : 0;
            $omadaBaseUrl = rtrim(trim((string)($_POST['omada_base_url'] ?? '')), '/');
            $omadaOmadacId = trim((string)($_POST['omada_omadac_id'] ?? ''));
            $omadaSiteId = trim((string)($_POST['omada_site_id'] ?? ''));
            $omadaClientId = trim((string)($_POST['omada_client_id'] ?? ''));
            $omadaClientSecret = trim((string)($_POST['omada_client_secret'] ?? ''));
            $omadaUsername = trim((string)($_POST['omada_username'] ?? ''));
            $omadaPassword = trim((string)($_POST['omada_password'] ?? ''));
            $omadaAllowInsecure = isset($_POST['omada_allow_insecure']) && (string)$_POST['omada_allow_insecure'] === '1' ? 1 : 0;

            if ($omadaClientSecret === '') {
                $omadaClientSecret = (string)($settings['omada_client_secret'] ?? '');
            }
            if ($omadaPassword === '') {
                $omadaPassword = (string)($settings['omada_password'] ?? '');
            }

            if ($companyName === '') {
                $companyName = 'ISP-BILLING-LITE';
            }

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->redirectWithStatus('error', 'Invalid company email address.');
            }

            if ($smtpHost === '') {
                $smtpHost = 'smtp.gmail.com';
            }

            if ($smtpPort <= 0) {
                $smtpPort = 587;
            }

            $smtpUsernameError = $this->validateSmtpUsername($smtpUsername);
            if ($smtpUsernameError !== null) {
                $this->redirectWithStatus('error', $smtpUsernameError);
            }

            if ($smtpPassword === '') {
                $smtpPassword = trim((string)($settings['smtp_password'] ?? ''));
            }

            if ($smtpUsername !== '' && $smtpPassword === '') {
                $this->redirectWithStatus('error', 'SMTP app password is required when an SMTP username is set.');
            }

            if (!in_array($smtpEncryption, ['tls', 'ssl', 'starttls', ''], true)) {
                $smtpEncryption = 'tls';
            }

            if ($billingDueDay < 1 || $billingDueDay > 31) {
                $billingDueDay = 8;
            }

            if ($referralRewardAmount <= 0) {
                $referralRewardAmount = 500;
            }

            if ($vatRate < 0 || $vatRate > 100) {
                $vatRate = 12.0;
            }

            $stmt = $pdo->prepare("
                UPDATE settings
                SET
                    company_name = ?,
                    business_address = ?,
                    contact_number = ?,
                    email = ?,
                    smtp_host = ?,
                    smtp_port = ?,
                    smtp_username = ?,
                    smtp_password = ?,
                    smtp_encryption = ?,
                    billing_due_day = ?,
                    referral_reward_amount = ?,
                    vat_rate = ?,
                    omada_enabled = ?,
                    omada_base_url = ?,
                    omada_omadac_id = ?,
                    omada_site_id = ?,
                    omada_client_id = ?,
                    omada_client_secret = ?,
                    omada_username = ?,
                    omada_password = ?,
                    omada_allow_insecure = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $companyName,
                $businessAddress,
                $contactNumber,
                $email,
                $smtpHost,
                $smtpPort,
                $smtpUsername,
                $smtpPassword,
                $smtpEncryption,
                $billingDueDay,
                $referralRewardAmount,
                $vatRate,
                $omadaEnabled,
                $omadaBaseUrl !== '' ? $omadaBaseUrl : null,
                $omadaOmadacId !== '' ? $omadaOmadacId : null,
                $omadaSiteId !== '' ? $omadaSiteId : null,
                $omadaClientId !== '' ? $omadaClientId : null,
                $omadaClientSecret !== '' ? $omadaClientSecret : null,
                $omadaUsername !== '' ? $omadaUsername : null,
                $omadaPassword !== '' ? $omadaPassword : null,
                $omadaAllowInsecure,
                $id
            ]);

            if (class_exists('PaymentMethodService')) {
                PaymentMethodService::saveFromRequest(
                    $pdo,
                    is_array($_POST['payment_methods'] ?? null) ? $_POST['payment_methods'] : [],
                    is_array($_FILES['payment_methods_qr'] ?? null) ? $_FILES['payment_methods_qr'] : []
                );
            }

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession('Settings', 'UPDATE', 'Updated system settings including SMTP configuration.');
            }

            $this->redirectWithStatus('success', 'Settings updated successfully.');
        } catch (Throwable $e) {
            error_log('SettingController@update error: ' . $e->getMessage());
            $this->redirectWithStatus('error', 'Failed to update settings.');
        }
    }

    public function testOmada(): void
    {
        $this->requireAdmin();

        try {
            $pdo = $this->db();
            $this->ensureSettingsRow($pdo);
            if (!class_exists('OmadaNetworkAccessService')) {
                $this->redirectWithStatus('error', 'Omada service unavailable.');
            }

            $result = OmadaNetworkAccessService::testConnection($pdo);
            if (!empty($result['ok'])) {
                $this->redirectWithStatus('success', (string)$result['message']);
            }
            $this->redirectWithStatus('error', (string)($result['message'] ?? 'Omada connection failed.'));
        } catch (Throwable $e) {
            error_log('SettingController@testOmada: ' . $e->getMessage());
            $this->redirectWithStatus('error', 'Omada test failed: ' . $e->getMessage());
        }
    }

    public function testEmail(): void
    {
        $this->requireAdmin();

        try {
            $pdo = $this->db();
            $settings = $this->ensureSettingsRow($pdo);
            $testEmail = trim((string)($_POST['test_email'] ?? ($settings['email'] ?? '')));

            if ($testEmail === '' || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                $this->redirectWithStatus('error', 'Enter a valid email address for the test message.');
            }

            if (!class_exists('EmailAlertService')) {
                $this->redirectWithStatus('error', 'Email alert service is unavailable.');
            }

            $companyName = EmailAlertService::getCompanyName($pdo);
            $subject = 'FusionLink Email Test - ' . $companyName;
            $message = 'This is a test email from FusionLink. If you received this, SMTP alerts are working.';
            $html = EmailAlertService::wrapHtml('Email Test Successful', '
                <p>This is a test email from <strong>' . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . '</strong>.</p>
                <p>If you received this message, your SMTP settings are working and email alerts can be sent.</p>
            ', $companyName);

            $sent = EmailAlertService::sendHtml($testEmail, $testEmail, $subject, $html);

            if (!$sent) {
                $smtpHint = MailService::getLastError();
                $message = 'Test email failed. Check SMTP host, username, app password, and sender email in Settings.';
                if ($smtpHint !== '') {
                    $message .= ' Server said: ' . $smtpHint;
                }
                $this->redirectWithStatus('error', $message);
            }

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession('Settings', 'TEST_EMAIL', 'Sent test email to ' . $testEmail);
            }

            $this->redirectWithStatus('success', 'Test email sent successfully to ' . $testEmail . '.');
        } catch (Throwable $e) {
            error_log('SettingController@testEmail error: ' . $e->getMessage());
            $this->redirectWithStatus('error', 'Unable to send test email right now.');
        }
    }

    public function backup(): void
    {
        $this->requireAdmin();

        try {
            if (!class_exists('DatabaseBackupService')) {
                $this->redirectWithStatus('error', 'Backup service is unavailable.');
            }

            $created = DatabaseBackupService::createBackup('isp_billing_backup');

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession('Settings', 'BACKUP', 'Created database backup: ' . $created['name']);
            }

            $this->sendBackupDownload($created['path']);
        } catch (Throwable $e) {
            error_log('SettingController@backup error: ' . $e->getMessage());
            $this->redirectWithStatus('error', $e->getMessage() !== '' ? $e->getMessage() : 'Failed to backup database.');
        }
    }

    public function downloadBackup(): void
    {
        $this->requireAdmin();

        try {
            $fileName = (string)($_GET['file'] ?? $_POST['file'] ?? '');
            $path = DatabaseBackupService::resolveBackupPath($fileName);

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession('Settings', 'BACKUP_DOWNLOAD', 'Downloaded backup: ' . basename($path));
            }

            $this->sendBackupDownload($path);
        } catch (Throwable $e) {
            error_log('SettingController@downloadBackup error: ' . $e->getMessage());
            $this->redirectWithStatus('error', $e->getMessage());
        }
    }

    public function restore(): void
    {
        $this->requireAdmin();

        try {
            $sqlFile = $this->storeUploadedBackupFile($_FILES['backup_file'] ?? []);
            $label = basename($sqlFile);

            $this->importSqlBackup($sqlFile, $label);

            $this->redirectWithStatus('success', 'Backup restored successfully from uploaded file: ' . $label . '.');
        } catch (Throwable $e) {
            error_log('SettingController@restore error: ' . $e->getMessage());
            $this->redirectWithStatus('error', $e->getMessage());
        }
    }

    public function restoreLatest(): void
    {
        $this->requireAdmin();

        try {
            $latestBackupFile = $this->getLatestBackupFile();
            if ($latestBackupFile === null) {
                $this->redirectWithStatus('error', 'No backup file found in storage/backups.');
            }

            $label = basename($latestBackupFile);
            $this->importSqlBackup($latestBackupFile, $label);

            $this->redirectWithStatus('success', 'Backup restored successfully from latest server backup: ' . $label . '.');
        } catch (Throwable $e) {
            error_log('SettingController@restoreLatest error: ' . $e->getMessage());
            $this->redirectWithStatus('error', $e->getMessage());
        }
    }

    public function restoreSelected(): void
    {
        $this->requireAdmin();

        try {
            $fileName = (string)($_POST['file'] ?? '');
            $path = DatabaseBackupService::resolveBackupPath($fileName);
            $label = basename($path);
            $this->importSqlBackup($path, $label);
            $this->redirectWithStatus('success', 'Backup restored successfully from server backup: ' . $label . '.');
        } catch (Throwable $e) {
            error_log('SettingController@restoreSelected error: ' . $e->getMessage());
            $this->redirectWithStatus('error', $e->getMessage());
        }
    }

    public function deleteBackup(): void
    {
        $this->requireAdmin();

        try {
            $fileName = (string)($_POST['file'] ?? '');
            DatabaseBackupService::deleteBackup($fileName);

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession('Settings', 'BACKUP_DELETE', 'Deleted backup: ' . basename($fileName));
            }

            $this->redirectWithStatus('success', 'Backup deleted: ' . basename($fileName) . '.');
        } catch (Throwable $e) {
            error_log('SettingController@deleteBackup error: ' . $e->getMessage());
            $this->redirectWithStatus('error', $e->getMessage());
        }
    }

    public function reset(): void
    {
        $this->requireAdmin();

        try {
            $confirmation = trim((string)($_POST['reset_confirmation'] ?? ''));
            if ($confirmation !== 'RESET') {
                $this->redirectWithStatus('error', 'Reset cancelled. You must type RESET to continue.');
            }

            $pdo = $this->db();
            $this->ensureSettingsRow($pdo);

            $summary = $this->resetBusinessData($pdo);

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession('Settings', 'RESET', 'Cleared billing and application data.');
            }

            $this->redirectWithStatus('success', $this->formatResetSummary($summary));
        } catch (Throwable $e) {
            error_log('SettingController@reset error: ' . $e->getMessage());
            $this->redirectWithStatus('error', 'Failed to reset database records.');
        }
    }

    private function sendBackupDownload(string $path): void
    {
        $name = basename($path);
        $extension = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
        $contentType = $extension === 'zip' ? 'application/zip' : 'application/sql';

        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . (int)filesize($path));
        readfile($path);
        exit;
    }
}
