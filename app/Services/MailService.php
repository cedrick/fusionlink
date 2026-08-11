<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private array $config = [];
    private ?array $settings = null;
    private static string $lastError = '';

    public static function getLastError(): string
    {
        return self::$lastError;
    }

    private static function setLastError(string $message): void
    {
        self::$lastError = trim($message);
    }

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/mail.php';
    }

    private function db(): PDO
    {
        $config = require __DIR__ . '/../../config/database.php';

        $dbName = $config['db'] ?? ($config['name'] ?? null);
        if (!$dbName) {
            throw new RuntimeException("Database config error: missing 'db' (or 'name') key in config/database.php");
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

    private function getSystemSettings(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        $defaults = [
            'company_name'    => $this->config['from_name'] ?? 'FUSIONLINK',
            'email'           => $this->config['from_email'] ?? 'no-reply@example.com',
            'contact_number'  => '',
            'business_address'=> '',
            'smtp_host'       => $this->config['host'] ?? 'smtp.gmail.com',
            'smtp_port'       => (int)($this->config['port'] ?? 587),
            'smtp_username'   => $this->config['username'] ?? '',
            'smtp_password'   => $this->config['password'] ?? '',
            'smtp_encryption' => $this->config['encryption'] ?? 'tls',
        ];

        try {
            $pdo = $this->db();

            if (!$this->tableExists($pdo, 'settings')) {
                $this->settings = $defaults;
                return $this->settings;
            }

            $hasSmtpColumns =
                $this->columnExists($pdo, 'settings', 'smtp_host') &&
                $this->columnExists($pdo, 'settings', 'smtp_port') &&
                $this->columnExists($pdo, 'settings', 'smtp_username') &&
                $this->columnExists($pdo, 'settings', 'smtp_password') &&
                $this->columnExists($pdo, 'settings', 'smtp_encryption');

            if ($hasSmtpColumns) {
                $stmt = $pdo->query("
                    SELECT
                        company_name,
                        email,
                        contact_number,
                        business_address,
                        smtp_host,
                        smtp_port,
                        smtp_username,
                        smtp_password,
                        smtp_encryption
                    FROM settings
                    ORDER BY id DESC
                    LIMIT 1
                ");
            } else {
                $stmt = $pdo->query("
                    SELECT
                        company_name,
                        email,
                        contact_number,
                        business_address
                    FROM settings
                    ORDER BY id DESC
                    LIMIT 1
                ");
            }

            $row = $stmt->fetch();

            if (!$row) {
                $this->settings = $defaults;
                return $this->settings;
            }

            $companyName = trim((string)($row['company_name'] ?? ''));
            $email = trim((string)($row['email'] ?? ''));
            $contactNumber = trim((string)($row['contact_number'] ?? ''));
            $businessAddress = trim((string)($row['business_address'] ?? ''));
            $smtpHost = trim((string)($row['smtp_host'] ?? ''));
            $smtpPort = isset($row['smtp_port']) && $row['smtp_port'] !== null ? (int)$row['smtp_port'] : 0;
            $smtpUsername = trim((string)($row['smtp_username'] ?? ''));
            $smtpPassword = trim((string)($row['smtp_password'] ?? ''));
            $smtpEncryption = strtolower(trim((string)($row['smtp_encryption'] ?? '')));

            $this->settings = [
                'company_name' => $companyName !== '' ? $companyName : $defaults['company_name'],
                'email' => $email !== '' ? $email : $defaults['email'],
                'contact_number' => $contactNumber,
                'business_address' => $businessAddress,
                'smtp_host' => $smtpHost !== '' ? $smtpHost : $defaults['smtp_host'],
                'smtp_port' => $smtpPort > 0 ? $smtpPort : $defaults['smtp_port'],
                'smtp_username' => $smtpUsername !== '' ? $smtpUsername : $defaults['smtp_username'],
                'smtp_password' => $smtpPassword !== '' ? $smtpPassword : $defaults['smtp_password'],
                'smtp_encryption' => $smtpEncryption !== '' ? $smtpEncryption : $defaults['smtp_encryption'],
            ];

            return $this->settings;
        } catch (Throwable $e) {
            error_log('MailService@getSystemSettings error: ' . $e->getMessage());
            $this->settings = $defaults;
            return $this->settings;
        }
    }

    private function normalizeEncryption(string $value): string
    {
        $value = strtolower(trim($value));

        if ($value === 'ssl') {
            return PHPMailer::ENCRYPTION_SMTPS;
        }

        if ($value === 'tls' || $value === 'starttls') {
            return PHPMailer::ENCRYPTION_STARTTLS;
        }

        return '';
    }

    private function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $settings = $this->getSystemSettings();

        $smtpHost = trim((string)($settings['smtp_host'] ?? 'smtp.gmail.com'));
        $smtpPort = (int)($settings['smtp_port'] ?? 587);
        $smtpUsername = trim((string)($settings['smtp_username'] ?? ''));
        $smtpPassword = trim((string)($settings['smtp_password'] ?? ''));
        $smtpEncryption = $this->normalizeEncryption((string)($settings['smtp_encryption'] ?? 'tls'));

        $fromEmail = trim((string)($settings['email'] ?? ''));
        $fromName  = trim((string)($settings['company_name'] ?? ''));

        if ($fromEmail === '') {
            $fromEmail = $this->config['from_email'] ?? 'no-reply@example.com';
        }

        if ($fromName === '') {
            $fromName = $this->config['from_name'] ?? 'FUSIONLINK';
        }

        if ($smtpHost === '') {
            $smtpHost = $this->config['host'] ?? 'smtp.gmail.com';
        }

        if ($smtpPort <= 0) {
            $smtpPort = (int)($this->config['port'] ?? 587);
        }

        if ($smtpUsername === '') {
            $smtpUsername = trim((string)($this->config['username'] ?? ''));
        }

        if ($smtpPassword === '') {
            $smtpPassword = trim((string)($this->config['password'] ?? ''));
        }

        if ($smtpUsername === '') {
            throw new RuntimeException('SMTP username is missing. Please update SMTP settings.');
        }

        if ($smtpPassword === '') {
            throw new RuntimeException('SMTP password is missing. Please update SMTP settings.');
        }

        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->Port = $smtpPort;
        $mail->CharSet = 'UTF-8';

        if ($smtpEncryption !== '') {
            $mail->SMTPSecure = $smtpEncryption;
        }

        $mail->setFrom($fromEmail, $fromName);

        if (strcasecmp($smtpUsername, $fromEmail) !== 0) {
            $mail->addReplyTo($fromEmail, $fromName);
        }

        return $mail;
    }

    public function send(string $toEmail, string $toName, string $subject, string $body, array $bccEmails = []): bool
    {
        self::setLastError('');

        try {
            $mail = $this->createMailer();

            $mail->addAddress($toEmail, $toName);

            $seen = [strtolower(trim($toEmail)) => true];
            foreach ($bccEmails as $bccEmail) {
                $bcc = strtolower(trim((string)$bccEmail));
                if ($bcc === '' || !filter_var($bcc, FILTER_VALIDATE_EMAIL) || isset($seen[$bcc])) {
                    continue;
                }
                $mail->addBCC($bcc);
                $seen[$bcc] = true;
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            self::setLastError($e->getMessage());
            error_log('MailService error: ' . $e->getMessage());
            return false;
        } catch (Throwable $e) {
            self::setLastError($e->getMessage());
            error_log('MailService throwable error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendWithAttachment(
        string $toEmail,
        string $toName,
        string $subject,
        string $body,
        string $attachmentName,
        string $attachmentData,
        string $attachmentMime = 'application/pdf'
    ): bool {
        try {
            $mail = $this->createMailer();

            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->addStringAttachment(
                $attachmentData,
                $attachmentName,
                'base64',
                $attachmentMime
            );

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('MailService attachment error: ' . $e->getMessage());
            return false;
        } catch (Throwable $e) {
            error_log('MailService attachment throwable error: ' . $e->getMessage());
            return false;
        }
    }
}
