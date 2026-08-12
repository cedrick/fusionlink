<?php

if (file_exists(__DIR__ . '/../Services/ActivityLogger.php')) {
    require_once __DIR__ . '/../Services/ActivityLogger.php';
}

if (file_exists(__DIR__ . '/../Services/MailService.php')) {
    require_once __DIR__ . '/../Services/MailService.php';
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

if (file_exists(__DIR__ . '/../Services/BillingCycleService.php')) {
    require_once __DIR__ . '/../Services/BillingCycleService.php';
}

class InvoiceController
{
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

    private function requireLogin(): void
    {
        if (!isset($_SESSION['user'])) {
            redirect('/login');
            exit;
        }
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
    }

    private function getBillingDueDay(PDO $pdo): int
    {
        try {
            $this->ensureBillingDueDayColumn($pdo);

            $stmt = $pdo->query("
                SELECT billing_due_day
                FROM settings
                ORDER BY id ASC
                LIMIT 1
            ");
            $row = $stmt->fetch();

            $day = (int)($row['billing_due_day'] ?? 8);
            if ($day < 1 || $day > 31) {
                $day = 8;
            }

            return $day;
        } catch (Throwable $e) {
            error_log('InvoiceController@getBillingDueDay error: ' . $e->getMessage());
            return 8;
        }
    }

    private function getMonthlyDueDate(PDO $pdo, ?string $baseDate = null): string
    {
        if (class_exists('BillingCycleService')) {
            $coverageEnd = $baseDate
                ? date('Y-m-t', strtotime($baseDate) ?: time())
                : date('Y-m-t');

            return BillingCycleService::dueDateForCoverageEnd($pdo, $coverageEnd);
        }

        $billingDueDay = $this->getBillingDueDay($pdo);
        $baseTimestamp = $baseDate ? strtotime($baseDate) : time();
        if ($baseTimestamp === false) {
            $baseTimestamp = time();
        }
        $nextMonthTs = strtotime(date('Y-m-01', $baseTimestamp) . ' +1 month');
        $daysInMonth = (int)date('t', $nextMonthTs);
        $day = min($billingDueDay, $daysInMonth);

        return date('Y-m-', $nextMonthTs) . sprintf('%02d', $day);
    }

    private function renderInvoiceMessagePage(
        string $title,
        string $message,
        string $buttonText = 'Back to Invoice',
        string $buttonLink = '/invoices',
        bool $isSuccess = true
    ): void {
        $accentColor  = $isSuccess ? '#86efac' : '#fca5a5';
        $accentBg     = $isSuccess ? 'rgba(34,197,94,.12)' : 'rgba(239,68,68,.12)';
        $accentBorder = $isSuccess ? 'rgba(34,197,94,.20)' : 'rgba(239,68,68,.20)';
        $icon         = $isSuccess ? '✓' : '⚠';
        $buttonLink = url($buttonLink);

        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . ' - ISP-BILLING-LITE</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(255,255,255,.04), transparent 18%),
                radial-gradient(circle at bottom right, rgba(255,255,255,.03), transparent 18%),
                linear-gradient(180deg, #020202 0%, #050505 55%, #090909 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #fafafa;
        }

        .card {
            width: 100%;
            max-width: 640px;
            background: linear-gradient(180deg, rgba(255,255,255,.04) 0%, rgba(255,255,255,.025) 100%);
            border-radius: 32px;
            padding: 34px;
            box-shadow: 0 18px 50px rgba(0,0,0,.40);
            border: 1px solid rgba(255,255,255,.08);
            color: #fafafa;
        }

        .status-icon {
            width: 88px;
            height: 88px;
            border-radius: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: ' . $accentBg . ';
            border: 1px solid ' . $accentBorder . ';
            color: ' . $accentColor . ';
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 22px;
        }

        .card h2 {
            margin: 0 0 12px;
            font-size: 30px;
            line-height: 1.15;
            color: #ffffff;
        }

        .subtitle {
            margin: 0 0 24px;
            font-size: 16px;
            line-height: 1.7;
            color: #a3a3a3;
        }

        .message-box {
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 18px;
            padding: 18px 20px;
            margin-bottom: 24px;
        }

        .message-label {
            display: block;
            margin-bottom: 10px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: ' . $accentColor . ';
        }

        .message-text {
            margin: 0;
            font-size: 15px;
            line-height: 1.8;
            color: #f5f5f5;
            font-weight: 600;
            word-break: break-word;
        }

        .actions {
            display: flex;
            margin-top: 8px;
        }

        .btn-primary {
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 56px;
            padding: 14px 20px;
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #ededed 100%);
            color: #050505;
            font-size: 16px;
            font-weight: 800;
            box-shadow: 0 10px 24px rgba(0,0,0,.22);
            transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease;
            border: 1px solid rgba(255,255,255,.14);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(0,0,0,.28);
            opacity: 0.98;
        }

        .footer-note {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
            color: #737373;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="status-icon">' . $icon . '</div>
        <h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>
        <p class="subtitle">Invoice email action has been processed by the system.</p>

        <div class="message-box">
            <span class="message-label">Status Details</span>
            <p class="message-text">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>
        </div>

        <div class="actions">
            <a href="' . htmlspecialchars($buttonLink, ENT_QUOTES, 'UTF-8') . '" class="btn-primary">' . htmlspecialchars($buttonText, ENT_QUOTES, 'UTF-8') . '</a>
        </div>

        <p class="footer-note">Secure billing communication via ISP-BILLING-LITE</p>
    </div>
</body>
</html>';
    }

    private function getSystemSettings(PDO $pdo): array
    {
        $defaults = [
            'company_name' => 'FUSIONLINK',
            'business_address' => 'Mabini St, Batangas City',
            'bank_account' => '',
            'gcash_account' => '',
            'contact_number' => '+63 900 123 4567',
            'email' => 'support@fusionlink.com',
            'billing_due_day' => 8,
        ];

        try {
            $this->ensureBillingDueDayColumn($pdo);
            if (class_exists('BillingCycleService')) {
                BillingCycleService::ensureSchema($pdo);
            }

            $stmt = $pdo->query("
                SELECT
                    company_name,
                    business_address,
                    bank_account,
                    gcash_account,
                    contact_number,
                    email,
                    billing_due_day
                FROM settings
                ORDER BY id DESC
                LIMIT 1
            ");
            $row = $stmt->fetch();

            if (!$row) {
                return $defaults;
            }

            $billingDueDay = (int)($row['billing_due_day'] ?? 8);
            if ($billingDueDay < 1 || $billingDueDay > 31) {
                $billingDueDay = 8;
            }

            return [
                'company_name' => trim((string)($row['company_name'] ?? '')) !== '' ? (string)$row['company_name'] : $defaults['company_name'],
                'business_address' => trim((string)($row['business_address'] ?? '')) !== '' ? (string)$row['business_address'] : $defaults['business_address'],
                'bank_account' => (string)($row['bank_account'] ?? ''),
                'gcash_account' => (string)($row['gcash_account'] ?? ''),
                'contact_number' => trim((string)($row['contact_number'] ?? '')) !== '' ? (string)$row['contact_number'] : $defaults['contact_number'],
                'email' => trim((string)($row['email'] ?? '')) !== '' ? (string)$row['email'] : $defaults['email'],
                'billing_due_day' => $billingDueDay,
            ];
        } catch (Throwable $e) {
            error_log('InvoiceController@getSystemSettings error: ' . $e->getMessage());
            return $defaults;
        }
    }

    private function getBillingPeriodForDate(string $date): array
    {
        $ts = strtotime($date);
        if ($ts === false) {
            $ts = time();
        }

        $start = date('Y-m-01', $ts);
        $end = date('Y-m-t', $ts);

        return [$start, $end];
    }

    private function getInvoiceStatementPeriod(array $invoice, ?string $subscriptionStartDate = null): array
    {
        $storedStart = trim((string)($invoice['billing_period_start'] ?? ''));
        $storedEnd = trim((string)($invoice['billing_period_end'] ?? ''));
        if ($storedStart !== '' && $storedEnd !== '') {
            return [$storedStart, $storedEnd];
        }

        $createdAt = (string)($invoice['created_at'] ?? date('Y-m-d H:i:s'));
        [$periodStart, $periodEnd] = $this->getBillingPeriodForDate($createdAt);

        if ($subscriptionStartDate) {
            $subscriptionMonth = date('Y-m', strtotime($subscriptionStartDate));
            $invoiceMonth = date('Y-m', strtotime($createdAt));

            if ($subscriptionMonth === $invoiceMonth) {
                $day = (int)date('d', strtotime($subscriptionStartDate));
                if ($day > 1) {
                    $periodStart = date('Y-m-d', strtotime($subscriptionStartDate));
                }
            }
        }

        return [$periodStart, $periodEnd];
    }

    private function calculateProratedAmount(float $monthlyPrice, string $startDate): float
    {
        if (class_exists('BillingCycleService')) {
            return BillingCycleService::calculateProratedAmount($monthlyPrice, $startDate);
        }

        $timestamp = strtotime($startDate);
        if ($timestamp === false) {
            return round($monthlyPrice, 2);
        }

        $daysInMonth = (int)date('t', $timestamp);
        $currentDay = (int)date('j', $timestamp);
        $remainingDays = ($daysInMonth - $currentDay) + 1;

        if ($remainingDays < 1) {
            $remainingDays = 1;
        }

        $dailyRate = $monthlyPrice / $daysInMonth;
        return round($dailyRate * $remainingDays, 2);
    }

    private function autoGenerateMonthlyInvoices(PDO $pdo): int
    {
        if (class_exists('BillingCycleService')) {
            $result = BillingCycleService::generateMonthlyInvoices($pdo, false);
            return (int)($result['generated'] ?? 0);
        }

        return 0;
    }

    private function sendRenewalReminders(PDO $pdo): void
    {
        $currentYearMonth = date('Y-m');
        $settings = $this->getSystemSettings($pdo);
        $companyName = (string)($settings['company_name'] ?? 'ISP-BILLING-LITE');
        $companyEmail = (string)($settings['email'] ?? '');

        $stmt = $pdo->query("
            SELECT
                c.id AS customer_id,
                c.full_name,
                c.email,
                p.name AS plan_name,
                p.price,
                s.id AS subscription_id
            FROM customers c
            INNER JOIN subscriptions s ON s.customer_id = c.id
            LEFT JOIN plans p ON p.id = s.plan_id
            WHERE s.status = 'ACTIVE'
              AND c.status = 'ACTIVE'
            ORDER BY c.full_name ASC
        ");

        $subscriptions = $stmt->fetchAll();

        foreach ($subscriptions as $subscription) {
            $customerId = (int)($subscription['customer_id'] ?? 0);
            $customerName = trim((string)($subscription['full_name'] ?? 'Customer'));
            $recipientEmail = class_exists('EmailAlertService')
                ? EmailAlertService::resolveCustomerEmail($pdo, $customerId)
                : trim((string)($subscription['email'] ?? ''));
            $planName = trim((string)($subscription['plan_name'] ?? 'Internet Plan'));
            $planPrice = (float)($subscription['price'] ?? 0);

            if ($customerId <= 0 || $recipientEmail === '') {
                continue;
            }

            $check = $pdo->prepare("
                SELECT id
                FROM notifications
                WHERE customer_id = ?
                AND type = 'RENEWAL_REMINDER'
                AND DATE_FORMAT(created_at, '%Y-%m') = ?
                LIMIT 1
            ");
            $check->execute([$customerId, $currentYearMonth]);
            $existing = $check->fetch();

            if ($existing) {
                continue;
            }

            $subject = 'Upcoming Subscription Reminder - ' . $companyName;
            $formattedPrice = number_format($planPrice, 2);

            $message = 'Hello ' . $customerName . ', this is a reminder that your internet subscription for plan "' . $planName . '" will continue for the upcoming month. Monthly fee: ₱' . $formattedPrice . '. Please expect your next billing cycle soon. Thank you for choosing ' . $companyName . '.';

            $insert = $pdo->prepare("
                INSERT INTO notifications (customer_id, invoice_id, type, recipient_email, subject, message, status)
                VALUES (?, NULL, ?, ?, ?, ?, ?)
            ");
            $insert->execute([
                $customerId,
                'RENEWAL_REMINDER',
                $recipientEmail,
                $subject,
                $message,
                'PENDING'
            ]);

            $notificationId = $pdo->lastInsertId();

            if (!class_exists('MailService')) {
                continue;
            }

            $mailSent = false;

            try {
                $mailService = new MailService();

                $emailBody = '
                    <h2>Upcoming Subscription Reminder</h2>
                    <p>Hello ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>
                    <p>This is a reminder that your internet subscription will continue for the upcoming month.</p>
                    <p><strong>Plan:</strong> ' . htmlspecialchars($planName, ENT_QUOTES, 'UTF-8') . '</p>
                    <p><strong>Monthly Fee:</strong> ₱' . htmlspecialchars($formattedPrice, ENT_QUOTES, 'UTF-8') . '</p>
                    <p>Please expect your next billing cycle soon.</p>
                    <br>
                    <p>Thank you for choosing ' . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . '.</p>';

                if ($companyEmail !== '') {
                    $emailBody .= '<p><strong>Company Email:</strong> ' . htmlspecialchars($companyEmail, ENT_QUOTES, 'UTF-8') . '</p>';
                }

                $mailSent = $mailService->send(
                    $recipientEmail,
                    $customerName,
                    $subject,
                    $emailBody
                );
            } catch (Throwable $e) {
                error_log('InvoiceController renewal reminder mail error: ' . $e->getMessage());
                $mailSent = false;
            }

            $update = $pdo->prepare("
                UPDATE notifications
                SET status = ?
                WHERE id = ?
            ");
            $update->execute([
                $mailSent ? 'SENT' : 'FAILED',
                $notificationId
            ]);
        }
    }

    private function sendOverdueReminders(PDO $pdo): void
    {
        $settings = $this->getSystemSettings($pdo);
        $companyName = (string)($settings['company_name'] ?? 'ISP-BILLING-LITE');
        $companyEmail = (string)($settings['email'] ?? '');
        $companyPhone = (string)($settings['contact_number'] ?? '');

        $stmt = $pdo->query("
            SELECT
                i.id,
                i.customer_id,
                i.amount,
                i.due_date,
                c.full_name,
                c.email
            FROM invoices i
            LEFT JOIN customers c ON i.customer_id = c.id
            WHERE i.status = 'OVERDUE'
        ");

        $overdueInvoices = $stmt->fetchAll();

        foreach ($overdueInvoices as $invoice) {
            $invoiceId = (int)($invoice['id'] ?? 0);
            $customerId = (int)($invoice['customer_id'] ?? 0);
            $recipientEmail = class_exists('EmailAlertService')
                ? EmailAlertService::resolveCustomerEmail($pdo, $customerId)
                : trim((string)($invoice['email'] ?? ''));
            $customerName = class_exists('EmailAlertService')
                ? EmailAlertService::resolveCustomerName($pdo, $customerId, (string)($invoice['full_name'] ?? 'Customer'))
                : ((string)($invoice['full_name'] ?? 'Customer'));
            $amount = number_format((float)($invoice['amount'] ?? 0), 2);
            $dueDate = $invoice['due_date'] ?? '';

            if ($invoiceId <= 0 || $customerId <= 0 || $recipientEmail === '') {
                continue;
            }

            $check = $pdo->prepare("
                SELECT id
                FROM notifications
                WHERE invoice_id = ?
                AND type = 'OVERDUE_REMINDER'
                LIMIT 1
            ");
            $check->execute([$invoiceId]);
            $existing = $check->fetch();

            if ($existing) {
                continue;
            }

            $subject = 'Overdue Invoice Reminder - ' . $companyName;
            $message = 'Hello ' . $customerName . ', your invoice #' . $invoiceId . ' is now overdue. Amount due: ₱' . $amount . '. Please settle your payment as soon as possible.';

            $insert = $pdo->prepare("
                INSERT INTO notifications (customer_id, invoice_id, type, recipient_email, subject, message, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insert->execute([
                $customerId,
                $invoiceId,
                'OVERDUE_REMINDER',
                $recipientEmail,
                $subject,
                $message,
                'PENDING'
            ]);

            $notificationId = $pdo->lastInsertId();

            if (!class_exists('MailService')) {
                continue;
            }

            $mailSent = false;

            try {
                $mailService = new MailService();

                $emailBody = '
                    <h2>Overdue Invoice Reminder</h2>
                    <p>Hello ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>
                    <p>Your invoice <strong>#' . htmlspecialchars((string)$invoiceId, ENT_QUOTES, 'UTF-8') . '</strong> is now overdue.</p>
                    <p><strong>Amount Due:</strong> ₱' . htmlspecialchars($amount, ENT_QUOTES, 'UTF-8') . '</p>
                    <p><strong>Due Date:</strong> ' . htmlspecialchars((string)$dueDate, ENT_QUOTES, 'UTF-8') . '</p>
                    <p>Please settle your payment as soon as possible to avoid service interruption.</p>
                    <br>
                    <p>Regards,<br>' . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . '</p>';

                if ($companyEmail !== '') {
                    $emailBody .= '<p><strong>Support Email:</strong> ' . htmlspecialchars($companyEmail, ENT_QUOTES, 'UTF-8') . '</p>';
                }

                if ($companyPhone !== '') {
                    $emailBody .= '<p><strong>Contact Number:</strong> ' . htmlspecialchars($companyPhone, ENT_QUOTES, 'UTF-8') . '</p>';
                }

                $mailSent = $mailService->send(
                    $recipientEmail,
                    $customerName,
                    $subject,
                    $emailBody
                );
            } catch (Throwable $e) {
                error_log('InvoiceController overdue mail error: ' . $e->getMessage());
                $mailSent = false;
            }

            $update = $pdo->prepare("
                UPDATE notifications
                SET status = ?
                WHERE id = ?
            ");
            $update->execute([
                $mailSent ? 'SENT' : 'FAILED',
                $notificationId
            ]);
        }
    }

    private function getInvoicePdfData(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("
            SELECT
                i.*,
                c.full_name AS customer_name,
                c.email,
                c.phone,
                c.address,
                s.start_date AS subscription_start_date,
                p.name AS plan_name,
                p.speed AS plan_speed,
                p.price AS plan_price
            FROM invoices i
            LEFT JOIN customers c ON c.id = i.customer_id
            LEFT JOIN subscriptions s ON s.customer_id = i.customer_id AND s.status = 'ACTIVE'
            LEFT JOIN plans p ON p.id = s.plan_id
            WHERE i.id = ?
            ORDER BY s.id DESC
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $invoice = $stmt->fetch();

        if (!$invoice) {
            return null;
        }

        $settings = $this->getSystemSettings($pdo);
        [$periodStart, $periodEnd] = $this->getInvoiceStatementPeriod(
            $invoice,
            (string)($invoice['subscription_start_date'] ?? '')
        );

        $customerName = htmlspecialchars((string)($invoice['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $phone        = htmlspecialchars((string)($invoice['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
        $addressRaw   = trim((string)($invoice['address'] ?? ''));
        $address      = nl2br(htmlspecialchars($addressRaw, ENT_QUOTES, 'UTF-8'));
        $amountRaw    = (float)($invoice['amount'] ?? 0);
        $referralCreditRaw = (float)($invoice['referral_credit_applied'] ?? 0);
        $vatAmountRaw = (float)($invoice['vat_amount'] ?? 0);
        $vatRateRaw   = (float)($invoice['vat_rate'] ?? 0);
        $subtotalRaw  = (float)($invoice['subtotal'] ?? 0);
        if ($subtotalRaw <= 0 && $vatAmountRaw > 0) {
            $subtotalRaw = round(max(0, $amountRaw + $referralCreditRaw - $vatAmountRaw), 2);
        }
        if ($subtotalRaw <= 0) {
            $subtotalRaw = round($amountRaw + $referralCreditRaw, 2);
        }
        if ($vatRateRaw <= 0 && $vatAmountRaw > 0 && $subtotalRaw > 0) {
            $vatRateRaw = 12.0;
        }
        $amount       = number_format($amountRaw, 2);
        $referralCredit = number_format($referralCreditRaw, 2);
        $subtotal     = number_format($subtotalRaw, 2);
        $vatAmount    = number_format($vatAmountRaw, 2);
        $vatRateLabel = number_format($vatRateRaw, 0);
        $createdAtRaw = (string)($invoice['created_at'] ?? '');
        $invoiceId    = (int)($invoice['id'] ?? 0);

        $issuedDateDisplay = $createdAtRaw !== '' ? date('d M Y', strtotime($createdAtRaw)) : '-';
        $receiptDate       = $createdAtRaw !== '' ? date('d M Y', strtotime($createdAtRaw)) : '-';
        $statementPeriod   = date('n/j/Y', strtotime($periodStart)) . ' - ' . date('n/j/Y', strtotime($periodEnd));

        $planNameRaw  = trim((string)($invoice['plan_name'] ?? 'Internet Subscription'));
        $planSpeedRaw = trim((string)($invoice['plan_speed'] ?? ''));
        $planPriceRaw = $subtotalRaw > 0
            ? $subtotalRaw
            : (isset($invoice['plan_price']) ? (float)$invoice['plan_price'] : ($amountRaw + $referralCreditRaw));

        $planName      = htmlspecialchars($planNameRaw !== '' ? $planNameRaw : 'Internet Subscription', ENT_QUOTES, 'UTF-8');
        $planSpeed     = htmlspecialchars($planSpeedRaw !== '' ? $planSpeedRaw : 'N/A', ENT_QUOTES, 'UTF-8');
        $planValidity  = 'Monthly';
        $planAmount    = number_format($planPriceRaw > 0 ? $planPriceRaw : $amountRaw, 2);

        $providerName       = (string)$settings['company_name'];
        $providerAddress    = (string)$settings['business_address'];
        $providerEmail      = (string)$settings['email'];
        $providerPhone      = (string)$settings['contact_number'];
        $paymentMethod      = 'GCash / Online';
        $accountNumber      = $phone !== '' ? $phone : 'N/A';

        $paymentChannelLines = [];
        if (class_exists('PaymentMethodService')) {
            $paymentMethods = PaymentMethodService::getAll($pdo, true);
            $paymentChannelLines = PaymentMethodService::formatInvoiceLines($paymentMethods);
        }

        if (empty($paymentChannelLines)) {
            $providerBank  = (string)($settings['bank_account'] ?? '');
            $providerGcash = (string)($settings['gcash_account'] ?? '');
            if ($providerGcash !== '') {
                $paymentChannelLines[] = 'GCash: ' . htmlspecialchars($providerGcash, ENT_QUOTES, 'UTF-8');
            }
            if ($providerBank !== '') {
                $paymentChannelLines[] = 'Bank: ' . htmlspecialchars($providerBank, ENT_QUOTES, 'UTF-8');
            }
        }

        if (empty($paymentChannelLines)) {
            $paymentChannelLines[] = htmlspecialchars($paymentMethod, ENT_QUOTES, 'UTF-8');
        }

        $providerContactLine = trim(
            ($providerEmail !== '' ? $providerEmail : '') .
            ($providerEmail !== '' && $providerPhone !== '' ? ' | ' : '') .
            ($providerPhone !== '' ? $providerPhone : '')
        );

        $paymentChannelHtml = implode('<br>', $paymentChannelLines);

        $providerDetailsHtml = htmlspecialchars($providerName, ENT_QUOTES, 'UTF-8') . '<br>';
        $providerDetailsHtml .= htmlspecialchars($providerAddress, ENT_QUOTES, 'UTF-8') . '<br>';

        if ($providerContactLine !== '') {
            $providerDetailsHtml .= htmlspecialchars($providerContactLine, ENT_QUOTES, 'UTF-8') . '<br>';
        }

        $providerDetailsHtml .= 'Statement Period: ' . htmlspecialchars($statementPeriod, ENT_QUOTES, 'UTF-8');

        $html = '
        <!doctype html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Invoice #' . $invoiceId . '</title>
            <style>
                body {
                    font-family: DejaVu Sans, Arial, sans-serif;
                    font-size: 13px;
                    color: #333333;
                    margin: 0;
                    padding: 0;
                }
                .page { padding: 30px 34px 34px; }
                .title {
                    font-size: 18px;
                    font-weight: bold;
                    color: #333333;
                    margin-bottom: 10px;
                }
                .rule {
                    border-top: 1px solid #d9d9d9;
                    margin-bottom: 10px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .top-table td { vertical-align: top; }
                .top-left { width: 58%; padding-right: 10px; }
                .top-right { width: 42%; text-align: right; }
                .logo-text {
                    font-size: 24px;
                    font-weight: bold;
                    color: #0f172a;
                    margin-bottom: 8px;
                }
                .sub-block {
                    margin-top: 16px;
                    line-height: 1.6;
                }
                .label-strong { font-weight: bold; }
                .right-heading {
                    font-size: 13px;
                    font-weight: bold;
                    margin-bottom: 4px;
                }
                .right-block {
                    margin-bottom: 14px;
                    line-height: 1.6;
                }
                .section-head {
                    background: #f3f4f6;
                    border: 1px solid #ececec;
                    padding: 7px 10px;
                    font-size: 13px;
                    font-weight: bold;
                    color: #4b5563;
                    margin-top: 14px;
                    margin-bottom: 0;
                }
                .plan-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 0;
                }
                .plan-table th {
                    text-align: left;
                    padding: 10px 10px;
                    border-bottom: 1px solid #dddddd;
                    color: #444444;
                    font-size: 13px;
                    font-weight: bold;
                }
                .plan-table td {
                    padding: 12px 10px;
                    border-bottom: 1px solid #e6e6e6;
                    font-size: 13px;
                    color: #444444;
                }
                .text-right { text-align: right; }
                .total-row {
                    margin-top: 10px;
                    text-align: right;
                    font-size: 14px;
                    font-weight: bold;
                    color: #444444;
                }
                .bottom-note {
                    margin-top: 26px;
                    text-align: center;
                    font-size: 13px;
                    font-weight: bold;
                    color: #444444;
                }
                .bottom-subnote {
                    margin-top: 14px;
                    text-align: center;
                    font-size: 12px;
                    color: #555555;
                }
            </style>
        </head>
        <body>
            <div class="page">
                <div class="title">Internet Invoice</div>
                <div class="rule"></div>

                <table class="top-table">
                    <tr>
                        <td class="top-left">
                            <div class="logo-text">' . htmlspecialchars($providerName, ENT_QUOTES, 'UTF-8') . '</div>

                            <div class="sub-block">
                                <div class="label-strong">Billed To,</div>
                                <div><span class="label-strong">Customer Name:</span> ' . $customerName . '</div>
                                <div><span class="label-strong">Customer Address:</span> ' . $address . '</div>
                                <div><span class="label-strong">Bill Account Number:</span> ' . htmlspecialchars((string)$accountNumber, ENT_QUOTES, 'UTF-8') . '</div>
                            </div>

                            <div class="sub-block">
                                <div class="label-strong">Payment Method</div>
                                <div>' . $paymentChannelHtml . '</div>
                            </div>
                        </td>

                        <td class="top-right">
                            <div class="right-block">
                                <div class="right-heading">Receipt Details</div>
                                <div><span class="label-strong">Receipt Number:</span> IN' . str_pad((string)$invoiceId, 4, '0', STR_PAD_LEFT) . '</div>
                                <div><span class="label-strong">Date:</span> ' . htmlspecialchars($issuedDateDisplay, ENT_QUOTES, 'UTF-8') . '</div>
                            </div>

                            <div class="right-block">
                                <div class="right-heading">Internet Provider Details</div>
                                <div>' . $providerDetailsHtml . '</div>
                            </div>

                            <div class="right-block">
                                <div class="right-heading">Receipt Date</div>
                                <div>' . htmlspecialchars($receiptDate, ENT_QUOTES, 'UTF-8') . '</div>
                            </div>
                        </td>
                    </tr>
                </table>

                <div class="section-head">Service Plan Summary</div>

                <table class="plan-table">
                    <thead>
                        <tr>
                            <th style="width:23%;">Plan Speed</th>
                            <th style="width:27%;">Plan Package</th>
                            <th style="width:25%;">Plan Validity</th>
                            <th style="width:25%;" class="text-right">Plan Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>' . $planSpeed . '</td>
                            <td>' . $planName . '</td>
                            <td>' . htmlspecialchars($planValidity, ENT_QUOTES, 'UTF-8') . '</td>
                            <td class="text-right">₱ ' . $planAmount . '</td>
                        </tr>
                    </tbody>
                </table>

                <div class="total-row" style="margin-top:8px;">Subtotal: ₱ ' . $subtotal . '</div>
                ' . ($vatAmountRaw > 0 ? '
                <div class="total-row" style="margin-top:4px;">VAT (' . htmlspecialchars($vatRateLabel, ENT_QUOTES, 'UTF-8') . '%): ₱ ' . $vatAmount . '</div>
                ' : '') . '
                ' . ($referralCreditRaw > 0 ? '
                <div class="total-row" style="margin-top:4px;">Referral Credit: -₱ ' . $referralCredit . '</div>
                ' : '') . '
                <div class="total-row">Total' . ($vatAmountRaw > 0 ? ' (VAT inclusive)' : '') . ': ₱ ' . $amount . '</div>

                <div class="bottom-note">ALL PAYMENTS TO BE MADE IN FAVOUR OF ' . htmlspecialchars(strtoupper($providerName), ENT_QUOTES, 'UTF-8') . '</div>
                <div class="bottom-subnote">THIS IS A COMPUTER GENERATED INVOICE AND DOES NOT REQUIRE ANY SIGNATURE</div>
            </div>
        </body>
        </html>';

        return [
            'invoice' => $invoice,
            'invoice_id' => $invoiceId,
            'customer_name_raw' => (string)($invoice['customer_name'] ?? 'Customer'),
            'customer_email_raw' => (string)($invoice['email'] ?? ''),
            'subscription_start_date' => (string)($invoice['subscription_start_date'] ?? ''),
            'statement_period_start' => $periodStart,
            'statement_period_end' => $periodEnd,
            'settings' => $settings,
            'html' => $html,
        ];
    }

    private function sendInvoiceEmail(PDO $pdo, int $invoiceId): bool
    {
        if (!class_exists('MailService')) {
            return false;
        }

        $pdfData = $this->getInvoicePdfData($pdo, $invoiceId);
        if (!$pdfData) {
            return false;
        }

        $invoice = $pdfData['invoice'] ?? [];
        $settings = $pdfData['settings'] ?? [];
        $customerId = (int)($invoice['customer_id'] ?? 0);
        $customerEmail = class_exists('EmailAlertService')
            ? EmailAlertService::resolveCustomerEmail($pdo, $customerId)
            : trim((string)($pdfData['customer_email_raw'] ?? ''));
        if ($customerEmail === '') {
            $customerEmail = trim((string)($pdfData['customer_email_raw'] ?? ''));
        }
        $customerName  = (string)($pdfData['customer_name_raw'] ?? 'Customer');

        if ($customerEmail === '') {
            return false;
        }

        $companyName = (string)($settings['company_name'] ?? 'FUSIONLINK');
        $companyEmail = (string)($settings['email'] ?? 'support@ispbillinglite.com');
        $companyPhone = (string)($settings['contact_number'] ?? '+63 900 123 4567');

        $statementPeriod = date('n/j/Y', strtotime((string)$pdfData['statement_period_start'])) . ' - ' . date('n/j/Y', strtotime((string)$pdfData['statement_period_end']));
        $invoiceStatus = strtoupper((string)($invoice['status'] ?? 'ISSUED'));
        $invoiceAmount = (float)($invoice['amount'] ?? 0);

        $currentBalance = 0.00;
        if ($invoiceStatus !== 'PAID') {
            $currentBalance = $invoiceAmount;
        }

        $currentBalanceFormatted = number_format($currentBalance, 2);
        $loginUrl = absolute_url('/login');

        $subject = 'Automatic Payment Reminder - ' . $companyName;

        $headline = 'Monthly Billing Notice';
        $message = 'Your monthly internet service bill has been generated.';
        if ($invoiceStatus === 'PAID') {
            $message = 'Your billing statement is available and this billing period is already fully paid.';
        }

        $body = '
            <div style="margin:0;padding:24px;background:#f3f4f8;font-family:Arial,Helvetica,sans-serif;color:#111827;">
                <div style="max-width:720px;margin:0 auto;background:#ffffff;padding:32px 28px;border:1px solid #e5e7eb;">
                    <div style="font-size:24px;letter-spacing:8px;color:#4b5563;font-weight:700;margin-bottom:42px;">
                        ' . htmlspecialchars(strtoupper($companyName), ENT_QUOTES, 'UTF-8') . '
                    </div>

                    <div style="font-size:28px;font-weight:800;color:#000000;line-height:1.2;margin-bottom:20px;">
                        ' . htmlspecialchars($headline, ENT_QUOTES, 'UTF-8') . '
                    </div>

                    <div style="font-size:18px;line-height:1.7;color:#111827;margin-bottom:28px;">
                        ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '
                    </div>

                    <table style="width:100%;border-collapse:collapse;margin-bottom:28px;">
                        <tr>
                            <td style="width:50%;border:1px solid #d1d5db;padding:16px;vertical-align:top;">
                                <div style="font-size:17px;font-weight:800;color:#111827;line-height:1.5;">Statement<br>Period</div>
                            </td>
                            <td style="width:50%;border:1px solid #d1d5db;padding:16px;vertical-align:top;">
                                <div style="font-size:17px;font-weight:800;color:#111827;line-height:1.5;">Current<br>Balance</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="border:1px solid #d1d5db;padding:16px;vertical-align:top;font-size:18px;line-height:1.6;color:#111827;">
                                ' . htmlspecialchars($statementPeriod, ENT_QUOTES, 'UTF-8') . '
                            </td>
                            <td style="border:1px solid #d1d5db;padding:16px;vertical-align:top;font-size:18px;line-height:1.6;color:#111827;">
                                PHP<br>' . htmlspecialchars($currentBalanceFormatted, ENT_QUOTES, 'UTF-8') . '
                            </td>
                        </tr>
                    </table>

                    <div style="font-size:18px;line-height:1.7;color:#111827;margin-bottom:34px;">
                        Sign in to your account below to review your payment or update your payment method.
                    </div>

                    <div style="margin-bottom:34px;">
                        <a href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#000000;color:#ffffff;text-decoration:none;font-size:20px;font-weight:700;padding:18px 34px;border-radius:4px;">
                            My Account
                        </a>
                    </div>

                    <div style="font-size:14px;line-height:1.8;color:#6b7280;border-top:1px solid #e5e7eb;padding-top:18px;">
                        <div><strong>Invoice #:</strong> ' . htmlspecialchars((string)$invoiceId, ENT_QUOTES, 'UTF-8') . '</div>
                        <div><strong>Status:</strong> ' . htmlspecialchars($invoiceStatus, ENT_QUOTES, 'UTF-8') . '</div>
                        <div><strong>Support Email:</strong> ' . htmlspecialchars($companyEmail, ENT_QUOTES, 'UTF-8') . '</div>
                        <div><strong>Contact Number:</strong> ' . htmlspecialchars($companyPhone, ENT_QUOTES, 'UTF-8') . '</div>
                    </div>
                </div>
            </div>
        ';

        try {
            $mailService = new MailService();
            return $mailService->send(
                $customerEmail,
                $customerName,
                $subject,
                $body
            );
        } catch (Throwable $e) {
            error_log("InvoiceController@sendInvoiceEmail error: " . $e->getMessage());
            return false;
        }
    }

    public function generateAndSendMonthlyInvoices(): void
    {
        $this->requireLogin();

        try {
            $pdo = $this->db();

            if (class_exists('BillingCycleService')) {
                $gen = BillingCycleService::generateMonthlyInvoices($pdo, true);
                $generatedCount = (int)($gen['generated'] ?? 0);
            } else {
                $generatedCount = $this->autoGenerateMonthlyInvoices($pdo);
            }

            $selectedInvoiceIds = $_POST['invoice_ids'] ?? [];

            if (!is_array($selectedInvoiceIds) || empty($selectedInvoiceIds)) {
                $this->renderInvoiceMessagePage(
                    'No Invoice Selected',
                    'Please check at least one invoice before generating monthly invoice emails.',
                    'Back to Invoices',
                    '/invoices',
                    false
                );
                exit;
            }

            $sentCount = 0;
            $failedCount = 0;

            foreach ($selectedInvoiceIds as $invoiceIdRaw) {
                $invoiceId = (int)$invoiceIdRaw;
                if ($invoiceId <= 0) {
                    continue;
                }

                if ($this->sendInvoiceEmail($pdo, $invoiceId)) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }
            }

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession(
                    'Invoices',
                    'GENERATE_SEND',
                    'Generated ' . $generatedCount . ' invoice(s), sent ' . $sentCount . ' email(s), failed ' . $failedCount . '.'
                );
            }

            $this->renderInvoiceMessagePage(
                'Monthly Invoice Emails Processed',
                'Generated ' . $generatedCount . ' invoice(s). Sent ' . $sentCount . ' selected invoice email(s). Failed: ' . $failedCount . '.',
                'Back to Invoices',
                '/invoices',
                $sentCount > 0
            );
            exit;
        } catch (Throwable $e) {
            error_log("InvoiceController@generateAndSendMonthlyInvoices error: " . $e->getMessage());
            $this->renderInvoiceMessagePage(
                'Invoice Email Processing Failed',
                'Unable to generate and send selected monthly invoice emails right now.',
                'Back to Invoices',
                '/invoices',
                false
            );
            exit;
        }
    }

    public function index(): void
    {
        $this->requireLogin();

        $invoices = [];
        $page = 1;
        $perPage = 20;
        $totalRows = 0;
        $totalPages = 1;
        $search = '';
        $statusFilter = '';
        $sortBy = 'id';
        $sortDir = 'DESC';

        try {
            $pdo = $this->db();

            if (class_exists('BillingCycleService')) {
                BillingCycleService::ensureSchema($pdo);
                // Generates bills and auto-emails customers (+ admin BCC) when new invoices are created.
                BillingCycleService::generateMonthlyInvoices($pdo, false);
                BillingCycleService::markOverdueInvoices($pdo);
                BillingCycleService::sendDueDateReminders($pdo);
                BillingCycleService::sendOverdueNotices($pdo);
            } else {
                $this->autoGenerateMonthlyInvoices($pdo);
                $this->sendRenewalReminders($pdo);
                $pdo->query("
                    UPDATE invoices
                    SET status = 'OVERDUE'
                    WHERE status = 'ISSUED'
                      AND due_date IS NOT NULL
                      AND due_date < CURDATE()
                ");
                $this->sendOverdueReminders($pdo);
            }

            $page = (int)($_GET['page'] ?? 1);
            $search = trim((string)($_GET['search'] ?? ''));
            $statusFilter = strtoupper(trim((string)($_GET['status'] ?? '')));
            $sortBy = trim((string)($_GET['sort_by'] ?? 'id'));
            $sortDir = strtoupper(trim((string)($_GET['sort_dir'] ?? 'DESC')));
            $perPage = 20;

            if ($page < 1) {
                $page = 1;
            }

            if (!in_array($statusFilter, ['', 'DRAFT', 'ISSUED', 'PAID', 'OVERDUE'], true)) {
                $statusFilter = '';
            }

            $allowedSort = [
                'id' => 'i.id',
                'customer_name' => 'c.full_name',
                'amount' => 'i.amount',
                'due_date' => 'i.due_date',
                'status' => 'i.status',
                'created_at' => 'i.created_at',
            ];

            if (!isset($allowedSort[$sortBy])) {
                $sortBy = 'id';
            }

            if (!in_array($sortDir, ['ASC', 'DESC'], true)) {
                $sortDir = 'DESC';
            }

            $where = [];
            $params = [];

            if ($search !== '') {
                $where[] = "(CAST(i.id AS CHAR) LIKE :search OR c.full_name LIKE :search OR CAST(i.amount AS CHAR) LIKE :search OR i.due_date LIKE :search OR i.status LIKE :search OR i.created_at LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }

            if ($statusFilter !== '') {
                $where[] = "i.status = :status";
                $params[':status'] = $statusFilter;
            }

            $whereSql = '';
            if (!empty($where)) {
                $whereSql = 'WHERE ' . implode(' AND ', $where);
            }

            $countSql = "
                SELECT COUNT(*)
                FROM invoices i
                LEFT JOIN customers c ON c.id = i.customer_id
                {$whereSql}
            ";
            $countStmt = $pdo->prepare($countSql);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();

            $totalRows = (int)$countStmt->fetchColumn();
            $totalPages = max(1, (int)ceil($totalRows / $perPage));

            if ($page > $totalPages) {
                $page = $totalPages;
            }

            $offset = ($page - 1) * $perPage;
            $orderBySql = $allowedSort[$sortBy] . ' ' . $sortDir;

            $sql = "
                SELECT
                    i.id,
                    i.customer_id,
                    i.amount,
                    i.subtotal,
                    i.vat_rate,
                    i.vat_amount,
                    i.due_date,
                    i.billing_period_start,
                    i.billing_period_end,
                    i.is_prorated,
                    i.coverage_days,
                    i.status,
                    i.created_at,
                    c.full_name AS customer_name
                FROM invoices i
                LEFT JOIN customers c ON c.id = i.customer_id
                {$whereSql}
                ORDER BY {$orderBySql}
                LIMIT :limit OFFSET :offset
            ";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $invoices = $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log("InvoiceController@index error: " . $e->getMessage());
        }

        View::render('invoices/index', [
            'title' => 'Invoices',
            'invoices' => $invoices,
            'page' => $page,
            'perPage' => $perPage,
            'totalRows' => $totalRows,
            'totalPages' => $totalPages,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();

        $customers = [];
        $defaultDue = date('Y-m-d');

        try {
            $pdo = $this->db();

            $defaultDue = $this->getMonthlyDueDate($pdo);

            $customers = $pdo->query("
                SELECT id, full_name
                FROM customers
                WHERE status = 'ACTIVE'
                ORDER BY full_name ASC
            ")->fetchAll();
        } catch (Throwable $e) {
            error_log("InvoiceController@create error: " . $e->getMessage());
        }

        View::render('invoices/create', [
            'title' => 'Generate Invoice',
            'customers' => $customers,
            'defaultDue' => $defaultDue,
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();

        try {
            $pdo = $this->db();

            $customerId = (int)($_POST['customer_id'] ?? 0);
            $amount     = trim($_POST['amount'] ?? '');
            $dueDate    = trim($_POST['due_date'] ?? '');
            $status     = $_POST['status'] ?? 'ISSUED';

            if ($customerId <= 0) {
                redirect('/invoices/create');
                exit;
            }

            $checkCustomer = $pdo->prepare("
                SELECT id
                FROM customers
                WHERE id = ?
                  AND status = 'ACTIVE'
                LIMIT 1
            ");
            $checkCustomer->execute([$customerId]);

            if (!$checkCustomer->fetch()) {
                redirect('/invoices/create');
                exit;
            }

            if ($amount === '' || !is_numeric($amount)) {
                redirect('/invoices/create');
                exit;
            }

            if ($dueDate === '') {
                $dueDate = $this->getMonthlyDueDate($pdo);
            }

            if (!in_array($status, ['ISSUED', 'PAID'], true)) {
                $status = 'ISSUED';
            }

            if (class_exists('ReferralService')) {
                $invoiceData = ReferralService::insertInvoice($pdo, $customerId, (float)$amount, $dueDate, $status);
                $invoiceId = (int)($invoiceData['id'] ?? 0);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO invoices (customer_id, amount, due_date, status)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$customerId, $amount, $dueDate, $status]);
                $invoiceId = (int)$pdo->lastInsertId();
            }

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession(
                    'Invoices',
                    'CREATE',
                    'Created manual invoice ID ' . $invoiceId . ' for customer ID ' . $customerId . '.'
                );
            }
        } catch (Throwable $e) {
            error_log("InvoiceController@store error: " . $e->getMessage());
        }

        redirect('/invoices');
        exit;
    }

    public function view(): void
    {
        $this->requireLogin();

        try {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                redirect('/invoices');
                exit;
            }

            redirect('/payments/create?invoice_id=' . $id);
            exit;
        } catch (Throwable $e) {
            error_log("InvoiceController@view redirect error: " . $e->getMessage());
            redirect('/invoices');
            exit;
        }
    }

    public function outstandingReport(): void
    {
        redirect('/reports/revenue');
    }

    public function emailInvoice(): void
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            redirect('/invoices');
        }

        try {
            $pdo = $this->db();
            $sent = $this->sendInvoiceEmail($pdo, $id);

            $this->renderInvoiceMessagePage(
                $sent ? 'Invoice Email Sent' : 'Invoice Email Failed',
                $sent
                    ? 'The invoice PDF was emailed to the customer.'
                    : 'Unable to send the invoice email. Check the customer email and mail settings.',
                'Back to Invoices',
                '/invoices',
                $sent
            );
        } catch (Throwable $e) {
            error_log('InvoiceController@emailInvoice error: ' . $e->getMessage());
            $this->renderInvoiceMessagePage(
                'Invoice Email Failed',
                'Unable to send the invoice email right now.',
                'Back to Invoices',
                '/invoices',
                false
            );
        }
    }

    public function delete(): void
    {
        redirect('/invoices');
        exit;
    }

    public function pdf(): void
    {
        $this->requireLogin();

        try {
            if (!file_exists(__DIR__ . '/../../vendor/autoload.php')) {
                header('Content-Type: text/html; charset=utf-8');
                echo '<h2>PDF library not installed.</h2>';
                echo '<p>Run: <code>composer require dompdf/dompdf</code></p>';
                exit;
            }

            require_once __DIR__ . '/../../vendor/autoload.php';

            if (!class_exists('\\Dompdf\\Dompdf')) {
                header('Content-Type: text/html; charset=utf-8');
                echo '<h2>Dompdf class not found.</h2>';
                echo '<p>Run: <code>composer require dompdf/dompdf</code></p>';
                exit;
            }

            $pdo = $this->db();

            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                redirect('/invoices');
                exit;
            }

            $pdfData = $this->getInvoicePdfData($pdo, $id);
            if (!$pdfData) {
                redirect('/invoices');
                exit;
            }

            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($pdfData['html']);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream('invoice-' . $pdfData['invoice_id'] . '.pdf', ['Attachment' => false]);
            exit;
        } catch (Throwable $e) {
            error_log("InvoiceController@pdf error: " . $e->getMessage());
            redirect('/invoices');
            exit;
        }
    }
}
