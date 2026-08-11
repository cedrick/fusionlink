<?php

/**
 * Canonical ISP billing cycle rules:
 * - Coverage: 1st through last day of the service month
 * - Due date: billing_due_day of the FOLLOWING month (default 8)
 * - Still on-time on the due date; overdue starting the next day
 * - Mid-month activations: prorated first bill for remaining days
 */
class BillingCycleService
{
    public const DEFAULT_DUE_DAY = 8;

    public static function ensureSchema(PDO $pdo): void
    {
        if (self::tableExists($pdo, 'settings') && !self::columnExists($pdo, 'settings', 'billing_due_day')) {
            $pdo->exec('ALTER TABLE settings ADD COLUMN billing_due_day INT NOT NULL DEFAULT 8');
        }

        if (!self::tableExists($pdo, 'invoices')) {
            return;
        }

        if (!self::columnExists($pdo, 'invoices', 'billing_period_start')) {
            $pdo->exec('ALTER TABLE invoices ADD COLUMN billing_period_start DATE NULL AFTER due_date');
        }
        if (!self::columnExists($pdo, 'invoices', 'billing_period_end')) {
            $pdo->exec('ALTER TABLE invoices ADD COLUMN billing_period_end DATE NULL AFTER billing_period_start');
        }
        if (!self::columnExists($pdo, 'invoices', 'is_prorated')) {
            $pdo->exec('ALTER TABLE invoices ADD COLUMN is_prorated TINYINT(1) NOT NULL DEFAULT 0 AFTER billing_period_end');
        }
        if (!self::columnExists($pdo, 'invoices', 'coverage_days')) {
            $pdo->exec('ALTER TABLE invoices ADD COLUMN coverage_days INT UNSIGNED NULL AFTER is_prorated');
        }
    }

    public static function getBillingDueDay(PDO $pdo): int
    {
        self::ensureSchema($pdo);

        try {
            $stmt = $pdo->query('SELECT billing_due_day FROM settings ORDER BY id ASC LIMIT 1');
            $row = $stmt ? $stmt->fetch() : false;
            $day = (int)($row['billing_due_day'] ?? self::DEFAULT_DUE_DAY);
            if ($day < 1 || $day > 31) {
                return self::DEFAULT_DUE_DAY;
            }

            return $day;
        } catch (Throwable $e) {
            return self::DEFAULT_DUE_DAY;
        }
    }

    public static function today(): string
    {
        if (function_exists('app_now')) {
            return app_now()->format('Y-m-d');
        }

        return date('Y-m-d');
    }

    public static function fullMonthPeriod(?string $anyDateInMonth = null): array
    {
        $ts = strtotime($anyDateInMonth ?: self::today());
        if ($ts === false) {
            $ts = time();
        }

        return [
            'start' => date('Y-m-01', $ts),
            'end' => date('Y-m-t', $ts),
            'year_month' => date('Y-m', $ts),
        ];
    }

    public static function isMonthEnd(?string $date = null): bool
    {
        $date = $date ?: self::today();
        $ts = strtotime($date);
        if ($ts === false) {
            return false;
        }

        return date('Y-m-d', $ts) === date('Y-m-t', $ts);
    }

    /**
     * Due date for a coverage month = due day of the NEXT calendar month.
     */
    public static function dueDateForCoverageEnd(PDO $pdo, string $coverageEndDate): string
    {
        $ts = strtotime($coverageEndDate);
        if ($ts === false) {
            $ts = time();
        }

        $nextMonthTs = strtotime(date('Y-m-01', $ts) . ' +1 month');
        $dueDay = self::getBillingDueDay($pdo);
        $daysInNext = (int)date('t', $nextMonthTs);
        $day = min($dueDay, $daysInNext);

        return date('Y-m-', $nextMonthTs) . sprintf('%02d', $day);
    }

    public static function buildPeriodForActivation(string $activationDate): array
    {
        $ts = strtotime($activationDate);
        if ($ts === false) {
            $ts = time();
            $activationDate = date('Y-m-d', $ts);
        } else {
            $activationDate = date('Y-m-d', $ts);
        }

        $monthStart = date('Y-m-01', $ts);
        $monthEnd = date('Y-m-t', $ts);
        $startDay = (int)date('j', $ts);
        $daysInMonth = (int)date('t', $ts);
        $isProrated = $startDay > 1;
        $periodStart = $isProrated ? $activationDate : $monthStart;
        $coverageDays = ((int)date('j', strtotime($monthEnd)) - (int)date('j', strtotime($periodStart))) + 1;
        if ($coverageDays < 1) {
            $coverageDays = 1;
        }

        return [
            'start' => $periodStart,
            'end' => $monthEnd,
            'year_month' => date('Y-m', $ts),
            'is_prorated' => $isProrated,
            'coverage_days' => $coverageDays,
            'days_in_month' => $daysInMonth,
        ];
    }

    public static function calculateProratedAmount(float $monthlyPrice, string $startDate): float
    {
        $period = self::buildPeriodForActivation($startDate);
        if (empty($period['is_prorated'])) {
            return round($monthlyPrice, 2);
        }

        $daysInMonth = max(1, (int)$period['days_in_month']);
        $coverageDays = max(1, (int)$period['coverage_days']);
        $dailyRate = $monthlyPrice / $daysInMonth;

        return round($dailyRate * $coverageDays, 2);
    }

    public static function invoiceExistsForPeriod(PDO $pdo, int $customerId, string $periodStart, string $periodEnd): bool
    {
        self::ensureSchema($pdo);

        $stmt = $pdo->prepare("
            SELECT id
            FROM invoices
            WHERE customer_id = ?
              AND (
                    (billing_period_start IS NOT NULL AND billing_period_end IS NOT NULL
                        AND billing_period_start = ? AND billing_period_end = ?)
                 OR (billing_period_start IS NULL
                        AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(?, '%Y-%m'))
              )
            LIMIT 1
        ");
        $stmt->execute([$customerId, $periodStart, $periodEnd, $periodEnd]);

        return (bool)$stmt->fetchColumn();
    }

    /**
     * @return array{generated:int,invoices:array<int,array>}
     */
    public static function generateMonthlyInvoices(PDO $pdo, bool $forceMonthEnd = false): array
    {
        self::ensureSchema($pdo);
        if (class_exists('ReferralService')) {
            ReferralService::ensureSchema($pdo);
        }

        $today = self::today();
        $period = self::fullMonthPeriod($today);
        $isMonthEnd = $forceMonthEnd || self::isMonthEnd($today);
        $generated = [];

        $stmt = $pdo->query("
            SELECT
                s.customer_id,
                s.plan_id,
                s.start_date,
                p.price,
                p.name AS plan_name,
                c.full_name,
                c.email
            FROM subscriptions s
            INNER JOIN customers c ON c.id = s.customer_id
            INNER JOIN plans p ON p.id = s.plan_id
            WHERE s.status = 'ACTIVE'
              AND c.status = 'ACTIVE'
        ");

        foreach ($stmt->fetchAll() as $subscription) {
            $customerId = (int)($subscription['customer_id'] ?? 0);
            $startDate = (string)($subscription['start_date'] ?? '');
            $planPrice = (float)($subscription['price'] ?? 0);

            if ($customerId <= 0 || $planPrice <= 0 || $startDate === '') {
                continue;
            }

            $startTs = strtotime($startDate);
            if ($startTs === false) {
                continue;
            }

            // Not active yet for this coverage month.
            if (date('Y-m-d', $startTs) > $period['end']) {
                continue;
            }

            $startedThisMonth = date('Y-m', $startTs) === $period['year_month'];

            // Full-cycle customers wait until month-end (unless forced).
            if (!$isMonthEnd && !$startedThisMonth) {
                continue;
            }

            if ($startedThisMonth) {
                $coverage = self::buildPeriodForActivation($startDate);
            } else {
                $coverage = [
                    'start' => $period['start'],
                    'end' => $period['end'],
                    'year_month' => $period['year_month'],
                    'is_prorated' => false,
                    'coverage_days' => (int)date('t', strtotime($period['start'])),
                ];
            }

            if (self::invoiceExistsForPeriod($pdo, $customerId, $coverage['start'], $coverage['end'])) {
                continue;
            }

            $amount = !empty($coverage['is_prorated'])
                ? self::calculateProratedAmount($planPrice, $startDate)
                : round($planPrice, 2);
            $dueDate = self::dueDateForCoverageEnd($pdo, $coverage['end']);

            $invoice = self::createInvoice($pdo, [
                'customer_id' => $customerId,
                'amount' => $amount,
                'due_date' => $dueDate,
                'status' => 'ISSUED',
                'billing_period_start' => $coverage['start'],
                'billing_period_end' => $coverage['end'],
                'is_prorated' => !empty($coverage['is_prorated']) ? 1 : 0,
                'coverage_days' => (int)($coverage['coverage_days'] ?? 0),
            ]);

            if (($invoice['id'] ?? 0) > 0) {
                $row = $invoice + [
                    'customer_id' => $customerId,
                    'plan_name' => (string)($subscription['plan_name'] ?? ''),
                    'due_date' => $dueDate,
                    'billing_period_start' => $coverage['start'],
                    'billing_period_end' => $coverage['end'],
                    'is_prorated' => !empty($coverage['is_prorated']),
                    'full_name' => (string)($subscription['full_name'] ?? 'Customer'),
                    'email' => (string)($subscription['email'] ?? ''),
                    'amount' => $amount,
                ];
                $generated[] = $row;

                // Auto-email customer (and BCC administrator) as soon as the bill is created.
                if (!self::notificationExists($pdo, $customerId, (int)$row['id'], 'MONTHLY_BILL')) {
                    self::sendInvoiceLifecycleEmail($pdo, $row, 'MONTHLY_BILL');
                }
            }
        }

        return [
            'generated' => count($generated),
            'invoices' => $generated,
        ];
    }

    public static function createInvoice(PDO $pdo, array $input): array
    {
        self::ensureSchema($pdo);

        $customerId = (int)($input['customer_id'] ?? 0);
        $amount = round((float)($input['amount'] ?? 0), 2);
        $dueDate = (string)($input['due_date'] ?? '');
        $status = strtoupper((string)($input['status'] ?? 'ISSUED'));
        $periodStart = (string)($input['billing_period_start'] ?? '');
        $periodEnd = (string)($input['billing_period_end'] ?? '');
        $isProrated = !empty($input['is_prorated']) ? 1 : 0;
        $coverageDays = isset($input['coverage_days']) ? (int)$input['coverage_days'] : null;

        if ($customerId <= 0 || $dueDate === '') {
            return ['id' => 0, 'amount' => 0.0, 'referral_credit_applied' => 0.0];
        }

        if (class_exists('ReferralService')) {
            $created = ReferralService::insertInvoice($pdo, $customerId, $amount, $dueDate, $status, [
                'billing_period_start' => $periodStart !== '' ? $periodStart : null,
                'billing_period_end' => $periodEnd !== '' ? $periodEnd : null,
                'is_prorated' => $isProrated,
                'coverage_days' => $coverageDays,
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO invoices (
                    customer_id, amount, due_date, status,
                    billing_period_start, billing_period_end, is_prorated, coverage_days
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $customerId,
                $amount,
                $dueDate,
                $status,
                $periodStart !== '' ? $periodStart : null,
                $periodEnd !== '' ? $periodEnd : null,
                $isProrated,
                $coverageDays,
            ]);
            $created = [
                'id' => (int)$pdo->lastInsertId(),
                'amount' => $amount,
                'referral_credit_applied' => 0.0,
            ];
        }

        return $created;
    }

    public static function createFirstBillForActivation(
        PDO $pdo,
        int $customerId,
        float $monthlyPrice,
        string $activationDate
    ): array {
        $coverage = self::buildPeriodForActivation($activationDate);
        if (self::invoiceExistsForPeriod($pdo, $customerId, $coverage['start'], $coverage['end'])) {
            return ['id' => 0, 'amount' => 0.0, 'skipped' => true];
        }

        $amount = !empty($coverage['is_prorated'])
            ? self::calculateProratedAmount($monthlyPrice, $activationDate)
            : round($monthlyPrice, 2);
        $dueDate = self::dueDateForCoverageEnd($pdo, $coverage['end']);

        $invoice = self::createInvoice($pdo, [
            'customer_id' => $customerId,
            'amount' => $amount,
            'due_date' => $dueDate,
            'status' => 'ISSUED',
            'billing_period_start' => $coverage['start'],
            'billing_period_end' => $coverage['end'],
            'is_prorated' => !empty($coverage['is_prorated']) ? 1 : 0,
            'coverage_days' => (int)$coverage['coverage_days'],
        ]);

        return $invoice + [
            'due_date' => $dueDate,
            'billing_period_start' => $coverage['start'],
            'billing_period_end' => $coverage['end'],
            'is_prorated' => !empty($coverage['is_prorated']),
            'coverage_days' => (int)$coverage['coverage_days'],
            'skipped' => false,
        ];
    }

    public static function markOverdueInvoices(PDO $pdo): int
    {
        self::ensureSchema($pdo);

        // On-time through due date; overdue starting the next calendar day.
        $stmt = $pdo->prepare("
            UPDATE invoices
            SET status = 'OVERDUE'
            WHERE status = 'ISSUED'
              AND due_date IS NOT NULL
              AND due_date < ?
        ");
        $stmt->execute([self::today()]);

        return $stmt->rowCount();
    }

    /**
     * Due-date reminder for invoices due today (still ISSUED).
     */
    public static function sendDueDateReminders(PDO $pdo): int
    {
        self::ensureSchema($pdo);
        $today = self::today();
        $sent = 0;

        $stmt = $pdo->prepare("
            SELECT
                i.id,
                i.customer_id,
                i.amount,
                i.due_date,
                i.billing_period_start,
                i.billing_period_end,
                i.is_prorated,
                i.coverage_days,
                c.full_name,
                c.email
            FROM invoices i
            INNER JOIN customers c ON c.id = i.customer_id
            WHERE i.status = 'ISSUED'
              AND i.due_date = ?
        ");
        $stmt->execute([$today]);

        foreach ($stmt->fetchAll() as $invoice) {
            if (self::notificationExists($pdo, (int)$invoice['customer_id'], (int)$invoice['id'], 'DUE_REMINDER')) {
                continue;
            }

            $ok = self::sendInvoiceLifecycleEmail($pdo, $invoice, 'DUE_REMINDER');
            if ($ok) {
                $sent++;
            }
        }

        return $sent;
    }

    public static function sendOverdueNotices(PDO $pdo): int
    {
        self::ensureSchema($pdo);
        $sent = 0;

        $stmt = $pdo->query("
            SELECT
                i.id,
                i.customer_id,
                i.amount,
                i.due_date,
                i.billing_period_start,
                i.billing_period_end,
                i.is_prorated,
                i.coverage_days,
                c.full_name,
                c.email
            FROM invoices i
            INNER JOIN customers c ON c.id = i.customer_id
            WHERE i.status = 'OVERDUE'
        ");

        foreach ($stmt->fetchAll() as $invoice) {
            // One overdue notice per invoice.
            if (self::notificationExists($pdo, (int)$invoice['customer_id'], (int)$invoice['id'], 'OVERDUE_REMINDER')) {
                continue;
            }

            $ok = self::sendInvoiceLifecycleEmail($pdo, $invoice, 'OVERDUE_REMINDER');
            if ($ok) {
                $sent++;
            }
        }

        return $sent;
    }

    public static function sendMonthEndBillEmails(PDO $pdo, array $invoiceRows = []): int
    {
        self::ensureSchema($pdo);
        $sent = 0;

        if ($invoiceRows === []) {
            $period = self::fullMonthPeriod();
            $stmt = $pdo->prepare("
                SELECT
                    i.id,
                    i.customer_id,
                    i.amount,
                    i.due_date,
                    i.billing_period_start,
                    i.billing_period_end,
                    i.is_prorated,
                    i.coverage_days,
                    c.full_name,
                    c.email
                FROM invoices i
                INNER JOIN customers c ON c.id = i.customer_id
                WHERE i.status IN ('ISSUED', 'OVERDUE')
                  AND (
                        (i.billing_period_end IS NOT NULL AND DATE_FORMAT(i.billing_period_end, '%Y-%m') = ?)
                     OR (i.billing_period_end IS NULL AND DATE_FORMAT(i.created_at, '%Y-%m') = ?)
                  )
            ");
            $stmt->execute([$period['year_month'], $period['year_month']]);
            $invoiceRows = $stmt->fetchAll();
        }

        foreach ($invoiceRows as $invoice) {
            $customerId = (int)($invoice['customer_id'] ?? 0);
            $invoiceId = (int)($invoice['id'] ?? 0);
            if ($customerId <= 0 || $invoiceId <= 0) {
                continue;
            }
            if (self::notificationExists($pdo, $customerId, $invoiceId, 'MONTHLY_BILL')) {
                continue;
            }

            $ok = self::sendInvoiceLifecycleEmail($pdo, $invoice, 'MONTHLY_BILL');
            if ($ok) {
                $sent++;
            }
        }

        return $sent;
    }

    public static function formatPeriodLabel(?string $start, ?string $end, bool $prorated = false, ?int $days = null): string
    {
        $start = trim((string)$start);
        $end = trim((string)$end);
        if ($start === '' || $end === '') {
            return '—';
        }

        $label = date('M j', strtotime($start)) . ' – ' . date('M j, Y', strtotime($end));
        if ($prorated) {
            $label .= ' (prorated' . ($days ? ', ' . (int)$days . ' day(s)' : '') . ')';
        }

        return $label;
    }

    /**
     * @return array{generated:int,overdue:int,due_reminders:int,overdue_notices:int,bills_emailed:int}
     */
    public static function runScheduledJobs(PDO $pdo, string $task = 'all'): array
    {
        $result = [
            'generated' => 0,
            'overdue' => 0,
            'due_reminders' => 0,
            'overdue_notices' => 0,
            'bills_emailed' => 0,
        ];

        $task = strtolower(trim($task));
        $runAll = ($task === '' || $task === 'all');

        if ($runAll || $task === 'generate') {
            $force = $runAll ? self::isMonthEnd() : true;
            $generated = self::generateMonthlyInvoices($pdo, $force);
            $result['generated'] = (int)($generated['generated'] ?? 0);

            // Newly generated invoices are emailed inside generateMonthlyInvoices.
            // Also catch any unsent bills for the current month (e.g. prior failed sends).
            if ($result['generated'] > 0 || ($runAll && self::isMonthEnd()) || $task === 'generate') {
                $result['bills_emailed'] = self::sendMonthEndBillEmails($pdo, $generated['invoices'] ?? []);
            }
        }

        if ($runAll || $task === 'overdue') {
            $result['overdue'] = self::markOverdueInvoices($pdo);
            $result['overdue_notices'] = self::sendOverdueNotices($pdo);
        }

        if ($runAll || $task === 'reminders' || $task === 'due') {
            $result['due_reminders'] = self::sendDueDateReminders($pdo);
        }

        return $result;
    }

    private static function sendInvoiceLifecycleEmail(PDO $pdo, array $invoice, string $type): bool
    {
        $customerId = (int)($invoice['customer_id'] ?? 0);
        $invoiceId = (int)($invoice['id'] ?? 0);
        $customerName = trim((string)($invoice['full_name'] ?? 'Customer'));
        $amount = number_format((float)($invoice['amount'] ?? 0), 2);
        $dueDate = (string)($invoice['due_date'] ?? '');
        $periodLabel = self::formatPeriodLabel(
            $invoice['billing_period_start'] ?? null,
            $invoice['billing_period_end'] ?? null,
            !empty($invoice['is_prorated']),
            isset($invoice['coverage_days']) ? (int)$invoice['coverage_days'] : null
        );

        $email = class_exists('EmailAlertService')
            ? EmailAlertService::resolveCustomerEmail($pdo, $customerId)
            : trim((string)($invoice['email'] ?? ''));

        if ($email === '') {
            return false;
        }

        $companyName = 'FusionLink';
        try {
            $settings = $pdo->query('SELECT company_name, email FROM settings ORDER BY id ASC LIMIT 1')->fetch();
            if (!empty($settings['company_name'])) {
                $companyName = (string)$settings['company_name'];
            }
        } catch (Throwable $e) {
            // keep default
        }

        if ($type === 'MONTHLY_BILL') {
            $subject = 'Your monthly bill is ready - ' . $companyName;
            $headline = 'Monthly Bill';
            $message = 'Hello ' . $customerName . ', your bill for ' . $periodLabel
                . ' is ready. Amount due: ₱' . $amount . '. Due date: ' . $dueDate
                . ' (still on time on this date). Please pay via your billing portal.';
        } elseif ($type === 'DUE_REMINDER') {
            $subject = 'Payment due today - ' . $companyName;
            $headline = 'Payment Due Today';
            $message = 'Hello ' . $customerName . ', this is a reminder that Invoice #' . $invoiceId
                . ' for ₱' . $amount . ' is due today (' . $dueDate . '). Billing period: '
                . $periodLabel . '. Please pay today to avoid overdue status tomorrow.';
        } else {
            $subject = 'Overdue invoice notice - ' . $companyName;
            $headline = 'Overdue Invoice';
            $message = 'Hello ' . $customerName . ', Invoice #' . $invoiceId
                . ' for ₱' . $amount . ' is now overdue (due date was ' . $dueDate
                . '). Billing period: ' . $periodLabel . '. Please settle your balance as soon as possible.';
        }

        $html = '
            <h2>' . htmlspecialchars($headline, ENT_QUOTES, 'UTF-8') . '</h2>
            <p>Hello ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>
            <p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Invoice:</strong> #' . (int)$invoiceId . '</p>
            <p><strong>Billing period:</strong> ' . htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Amount:</strong> ₱' . htmlspecialchars($amount, ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Due date:</strong> ' . htmlspecialchars($dueDate, ENT_QUOTES, 'UTF-8') . '</p>
            <p>Thank you,<br>' . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . '</p>
        ';

        $insert = $pdo->prepare("
            INSERT INTO notifications (customer_id, invoice_id, type, recipient_email, subject, message, status)
            VALUES (?, ?, ?, ?, ?, ?, 'PENDING')
        ");
        $insert->execute([$customerId, $invoiceId, $type, $email, $subject, $message]);
        $notificationId = (int)$pdo->lastInsertId();

        $mailSent = false;
        if (class_exists('MailService')) {
            try {
                $bcc = class_exists('EmailAlertService')
                    ? EmailAlertService::administratorBccList($pdo, $email)
                    : [];
                $mailService = new MailService();
                $mailSent = (bool)$mailService->send($email, $customerName, $subject, $html, $bcc);
            } catch (Throwable $e) {
                error_log('BillingCycleService@sendInvoiceLifecycleEmail error: ' . $e->getMessage());
            }
        }

        if ($notificationId > 0) {
            $update = $pdo->prepare("UPDATE notifications SET status = ? WHERE id = ?");
            $update->execute([$mailSent ? 'SENT' : 'FAILED', $notificationId]);
        }

        return $mailSent;
    }

    private static function notificationExists(PDO $pdo, int $customerId, int $invoiceId, string $type): bool
    {
        $stmt = $pdo->prepare("
            SELECT id
            FROM notifications
            WHERE customer_id = ?
              AND invoice_id = ?
              AND type = ?
            LIMIT 1
        ");
        $stmt->execute([$customerId, $invoiceId, $type]);

        return (bool)$stmt->fetchColumn();
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
