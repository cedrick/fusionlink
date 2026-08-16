<?php

/**
 * Canonical ISP billing cycle rules:
 * - Coverage: 1st through last day of the service month
 * - Due date: billing_due_day of the FOLLOWING month (default 8)
 * - Still on-time on the due date; overdue starting the next day
 * - NEW_ACTIVATION: mid-month activations may be prorated for remaining days
 * - EXISTING_MIGRATE: already on service; never prorate. First (and ongoing) bills are
 *   full calendar months starting the enrollment month (1st–last).
 * - VAT: only customers with vat_inclusive=1 get plan price + vat_rate (default 12%).
 *   All other customers are billed VAT-excluded (plan price only).
 */
class BillingCycleService
{
    public const DEFAULT_DUE_DAY = 8;
    public const DEFAULT_VAT_RATE = 12.0;
    public const BILLING_TYPE_NEW = 'NEW_ACTIVATION';
    public const BILLING_TYPE_EXISTING = 'EXISTING_MIGRATE';

    public static function ensureSchema(PDO $pdo): void
    {
        if (self::tableExists($pdo, 'settings') && !self::columnExists($pdo, 'settings', 'billing_due_day')) {
            $pdo->exec('ALTER TABLE settings ADD COLUMN billing_due_day INT NOT NULL DEFAULT 8');
        }

        if (self::tableExists($pdo, 'settings') && !self::columnExists($pdo, 'settings', 'vat_rate')) {
            $pdo->exec('ALTER TABLE settings ADD COLUMN vat_rate DECIMAL(5,2) NOT NULL DEFAULT 12.00');
        }

        if (self::tableExists($pdo, 'customers') && !self::columnExists($pdo, 'customers', 'vat_inclusive')) {
            $pdo->exec('ALTER TABLE customers ADD COLUMN vat_inclusive TINYINT(1) NOT NULL DEFAULT 0 AFTER status');
        }

        if (self::tableExists($pdo, 'subscriptions') && !self::columnExists($pdo, 'subscriptions', 'billing_type')) {
            $pdo->exec("
                ALTER TABLE subscriptions
                ADD COLUMN billing_type VARCHAR(32) NOT NULL DEFAULT 'NEW_ACTIVATION'
                AFTER start_date
            ");
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
        if (!self::columnExists($pdo, 'invoices', 'subtotal')) {
            $pdo->exec('ALTER TABLE invoices ADD COLUMN subtotal DECIMAL(12,2) NULL AFTER amount');
        }
        if (!self::columnExists($pdo, 'invoices', 'vat_rate')) {
            $pdo->exec('ALTER TABLE invoices ADD COLUMN vat_rate DECIMAL(5,2) NULL AFTER subtotal');
        }
        if (!self::columnExists($pdo, 'invoices', 'vat_amount')) {
            $pdo->exec('ALTER TABLE invoices ADD COLUMN vat_amount DECIMAL(12,2) NULL AFTER vat_rate');
        }
        if (!self::columnExists($pdo, 'invoices', 'plan_amount')) {
            $pdo->exec('ALTER TABLE invoices ADD COLUMN plan_amount DECIMAL(12,2) NULL AFTER coverage_days');
        }
        if (!self::columnExists($pdo, 'invoices', 'installment_amount')) {
            $pdo->exec('ALTER TABLE invoices ADD COLUMN installment_amount DECIMAL(12,2) NULL AFTER plan_amount');
        }
        if (!self::columnExists($pdo, 'invoices', 'installment_id')) {
            $pdo->exec('ALTER TABLE invoices ADD COLUMN installment_id INT UNSIGNED NULL AFTER installment_amount');
        }

        if (file_exists(__DIR__ . '/OfficialReceiptService.php')) {
            require_once __DIR__ . '/OfficialReceiptService.php';
            OfficialReceiptService::ensureSchema($pdo);
        }

        if (file_exists(__DIR__ . '/InstallationInstallmentService.php')) {
            require_once __DIR__ . '/InstallationInstallmentService.php';
            InstallationInstallmentService::ensureSchema($pdo);
        }
    }

    /**
     * Compose plan + optional installation installment into one net bill amount.
     *
     * @return array{plan_amount:float,installment_amount:float,installment_id:int,net:float}
     */
    public static function composeBillAmounts(PDO $pdo, int $customerId, float $planAmount): array
    {
        self::ensureSchema($pdo);
        $planAmount = round(max(0, $planAmount), 2);
        $installmentAmount = 0.0;
        $installmentId = 0;

        if (class_exists('InstallationInstallmentService')) {
            $peek = InstallationInstallmentService::peekCharge($pdo, $customerId);
            if ($peek) {
                $installmentAmount = (float)$peek['amount'];
                $installmentId = (int)$peek['id'];
            }
        }

        return [
            'plan_amount' => $planAmount,
            'installment_amount' => $installmentAmount,
            'installment_id' => $installmentId,
            'net' => round($planAmount + $installmentAmount, 2),
        ];
    }

    public static function getVatRate(PDO $pdo): float
    {
        self::ensureSchema($pdo);

        try {
            $stmt = $pdo->query('SELECT vat_rate FROM settings ORDER BY id ASC LIMIT 1');
            $row = $stmt ? $stmt->fetch() : false;
            $rate = round((float)($row['vat_rate'] ?? self::DEFAULT_VAT_RATE), 2);
            if ($rate < 0 || $rate > 100) {
                return self::DEFAULT_VAT_RATE;
            }

            return $rate;
        } catch (Throwable $e) {
            return self::DEFAULT_VAT_RATE;
        }
    }

    /**
     * @return array{subtotal:float,vat_rate:float,vat_amount:float,amount:float}
     */
    public static function applyVat(PDO $pdo, float $netAmount, ?float $vatRate = null): array
    {
        $subtotal = round(max(0, $netAmount), 2);
        $rate = $vatRate !== null ? round((float)$vatRate, 2) : self::getVatRate($pdo);
        if ($rate < 0) {
            $rate = 0.0;
        }

        $vatAmount = round($subtotal * ($rate / 100), 2);
        $total = round($subtotal + $vatAmount, 2);

        return [
            'subtotal' => $subtotal,
            'vat_rate' => $rate,
            'vat_amount' => $vatAmount,
            'amount' => $total,
        ];
    }

    /**
     * VAT is opt-in per customer (vat_inclusive). Default is VAT-excluded.
     */
    public static function customerAppliesVat(PDO $pdo, int $customerId): bool
    {
        self::ensureSchema($pdo);
        if ($customerId <= 0 || !self::columnExists($pdo, 'customers', 'vat_inclusive')) {
            return false;
        }

        try {
            $stmt = $pdo->prepare('SELECT vat_inclusive FROM customers WHERE id = ? LIMIT 1');
            $stmt->execute([$customerId]);
            $row = $stmt->fetch();

            return !empty($row['vat_inclusive']);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Recalculate open (unpaid) invoices when a customer's VAT-inclusive flag changes.
     * Does not modify PAID or VOID invoices.
     *
     * @return int Number of invoices updated
     */
    public static function syncOpenInvoicesVatForCustomer(PDO $pdo, int $customerId): int
    {
        self::ensureSchema($pdo);
        if ($customerId <= 0) {
            return 0;
        }

        $applyVat = self::customerAppliesVat($pdo, $customerId);
        $stmt = $pdo->prepare("
            SELECT id, amount, subtotal, vat_amount, referral_credit_applied
            FROM invoices
            WHERE customer_id = ?
              AND UPPER(COALESCE(status, '')) IN ('ISSUED', 'OVERDUE', 'DRAFT', 'UNPAID')
            ORDER BY id ASC
        ");
        $stmt->execute([$customerId]);
        $rows = $stmt->fetchAll();
        if (!$rows) {
            return 0;
        }

        $update = $pdo->prepare("
            UPDATE invoices
            SET amount = ?, subtotal = ?, vat_rate = ?, vat_amount = ?
            WHERE id = ?
        ");
        $updated = 0;

        foreach ($rows as $row) {
            $invoiceId = (int)($row['id'] ?? 0);
            $currentVat = (float)($row['vat_amount'] ?? 0);
            $credit = (float)($row['referral_credit_applied'] ?? 0);
            $amount = (float)($row['amount'] ?? 0);
            $subtotal = (float)($row['subtotal'] ?? 0);

            // Recover net plan amount before VAT / after credit adjustments.
            if ($currentVat > 0 && $subtotal > 0) {
                $net = $subtotal;
            } elseif ($currentVat > 0) {
                $net = round(max(0, $amount + $credit - $currentVat), 2);
            } elseif ($subtotal > 0) {
                $net = $subtotal;
            } else {
                $net = round(max(0, $amount + $credit), 2);
            }

            if ($applyVat) {
                $parts = self::applyVat($pdo, $net);
                $finalAmount = round(max(0, $parts['amount'] - $credit), 2);
                if (
                    abs($finalAmount - $amount) < 0.005
                    && abs($parts['vat_amount'] - $currentVat) < 0.005
                ) {
                    continue;
                }
                $update->execute([
                    $finalAmount,
                    $parts['subtotal'],
                    $parts['vat_rate'],
                    $parts['vat_amount'],
                    $invoiceId,
                ]);
            } else {
                $finalAmount = round(max(0, $net - $credit), 2);
                if ($currentVat <= 0 && abs($finalAmount - $amount) < 0.005) {
                    continue;
                }
                $update->execute([
                    $finalAmount,
                    $net,
                    null,
                    null,
                    $invoiceId,
                ]);
            }
            $updated++;
        }

        return $updated;
    }

    public static function normalizeBillingType(?string $type): string
    {
        $type = strtoupper(trim((string)$type));
        if ($type === self::BILLING_TYPE_EXISTING || $type === 'EXISTING' || $type === 'MIGRATE') {
            return self::BILLING_TYPE_EXISTING;
        }

        return self::BILLING_TYPE_NEW;
    }

    /**
     * First full calendar month EXISTING_MIGRATE may be billed for =
     * the enrollment month itself (never prorate; they are already on service).
     * Enrollment Aug 12 → first billable month starts 2026-08-01.
     */
    public static function firstFullBillMonthStart(string $enrollmentDate): string
    {
        $ts = strtotime($enrollmentDate);
        if ($ts === false) {
            $ts = time();
        }

        return date('Y-m-01', $ts);
    }

    /**
     * Create the current (or given) full-month regular bill for an existing customer.
     * Never prorates. Applies VAT.
     *
     * @return array{id:int,amount:float,skipped?:bool}
     */
    public static function createRegularMonthBillForExisting(
        PDO $pdo,
        int $customerId,
        float $monthlyPrice,
        ?string $asOfDate = null
    ): array {
        self::ensureSchema($pdo);

        $period = self::fullMonthPeriod($asOfDate ?: self::today());
        if (self::invoiceExistsForPeriod($pdo, $customerId, $period['start'], $period['end'])) {
            return ['id' => 0, 'amount' => 0.0, 'skipped' => true];
        }

        $net = round(max(0, $monthlyPrice), 2);
        $composed = self::composeBillAmounts($pdo, $customerId, $net);
        $dueDate = self::dueDateForCoverageEnd($pdo, $period['end']);
        $days = (int)date('t', strtotime($period['start']));

        $invoice = self::createInvoice($pdo, [
            'customer_id' => $customerId,
            'amount' => $composed['net'],
            'plan_amount' => $composed['plan_amount'],
            'installment_amount' => $composed['installment_amount'],
            'installment_id' => $composed['installment_id'],
            'due_date' => $dueDate,
            'status' => 'ISSUED',
            'billing_period_start' => $period['start'],
            'billing_period_end' => $period['end'],
            'is_prorated' => 0,
            'coverage_days' => $days,
        ]);

        if (($invoice['id'] ?? 0) > 0 && $composed['installment_id'] > 0 && $composed['installment_amount'] > 0
            && class_exists('InstallationInstallmentService')
        ) {
            InstallationInstallmentService::applyCharge(
                $pdo,
                $composed['installment_id'],
                $composed['installment_amount']
            );
        }

        return $invoice + [
            'due_date' => $dueDate,
            'billing_period_start' => $period['start'],
            'billing_period_end' => $period['end'],
            'is_prorated' => false,
            'plan_amount' => $composed['plan_amount'],
            'installment_amount' => $composed['installment_amount'],
            'skipped' => false,
        ];
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
              AND UPPER(COALESCE(status, '')) NOT IN ('VOID', 'CANCELLED')
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
                s.billing_type,
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
            $billingType = self::normalizeBillingType($subscription['billing_type'] ?? null);

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

            // Mid-month: only EXISTING customers get a regular full-month bill if missing.
            // NEW_ACTIVATION first bills use createFirstBillForActivation() (convert / explicit opt-in).
            if (!$isMonthEnd && $billingType !== self::BILLING_TYPE_EXISTING) {
                continue;
            }

            $coverage = [
                'start' => $period['start'],
                'end' => $period['end'],
                'year_month' => $period['year_month'],
                'is_prorated' => false,
                'coverage_days' => (int)date('t', strtotime($period['start'])),
            ];

            if ($billingType === self::BILLING_TYPE_EXISTING) {
                // Already on service: full-month bills from the enrollment month onward. Never prorate.
                $firstFullStart = self::firstFullBillMonthStart($startDate);
                if ($period['start'] < $firstFullStart) {
                    continue;
                }
            } else {
                // Genuine new activation: prorate remaining days of the activation month at month-end
                // if no earlier first bill was created.
                $startedThisMonth = date('Y-m', $startTs) === $period['year_month'];
                if ($startedThisMonth && (int)date('j', $startTs) > 1) {
                    $coverage = self::buildPeriodForActivation($startDate);
                }
            }

            if (self::invoiceExistsForPeriod($pdo, $customerId, $coverage['start'], $coverage['end'])) {
                continue;
            }

            $netAmount = !empty($coverage['is_prorated'])
                ? self::calculateProratedAmount($planPrice, $startDate)
                : round($planPrice, 2);
            $composed = self::composeBillAmounts($pdo, $customerId, $netAmount);
            $dueDate = self::dueDateForCoverageEnd($pdo, $coverage['end']);

            $invoice = self::createInvoice($pdo, [
                'customer_id' => $customerId,
                'amount' => $composed['net'],
                'plan_amount' => $composed['plan_amount'],
                'installment_amount' => $composed['installment_amount'],
                'installment_id' => $composed['installment_id'],
                'due_date' => $dueDate,
                'status' => 'ISSUED',
                'billing_period_start' => $coverage['start'],
                'billing_period_end' => $coverage['end'],
                'is_prorated' => !empty($coverage['is_prorated']) ? 1 : 0,
                'coverage_days' => (int)($coverage['coverage_days'] ?? 0),
            ]);

            if (($invoice['id'] ?? 0) > 0 && $composed['installment_id'] > 0 && $composed['installment_amount'] > 0
                && class_exists('InstallationInstallmentService')
            ) {
                InstallationInstallmentService::applyCharge(
                    $pdo,
                    $composed['installment_id'],
                    $composed['installment_amount']
                );
            }

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
                    'amount' => (float)($invoice['amount'] ?? 0),
                    'subtotal' => (float)($invoice['subtotal'] ?? $composed['net']),
                    'vat_rate' => (float)($invoice['vat_rate'] ?? 0),
                    'vat_amount' => (float)($invoice['vat_amount'] ?? 0),
                    'plan_amount' => $composed['plan_amount'],
                    'installment_amount' => $composed['installment_amount'],
                ];
                $generated[] = $row;

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
        $netAmount = round((float)($input['amount'] ?? 0), 2);
        $dueDate = (string)($input['due_date'] ?? '');
        $status = strtoupper((string)($input['status'] ?? 'ISSUED'));
        $periodStart = (string)($input['billing_period_start'] ?? '');
        $periodEnd = (string)($input['billing_period_end'] ?? '');
        $isProrated = !empty($input['is_prorated']) ? 1 : 0;
        $coverageDays = isset($input['coverage_days']) ? (int)$input['coverage_days'] : null;
        $skipVat = !empty($input['skip_vat']);
        $planAmount = array_key_exists('plan_amount', $input)
            ? round((float)$input['plan_amount'], 2)
            : $netAmount;
        $installmentAmount = array_key_exists('installment_amount', $input)
            ? round((float)$input['installment_amount'], 2)
            : 0.0;
        $installmentId = isset($input['installment_id']) ? (int)$input['installment_id'] : null;
        if ($installmentId !== null && $installmentId <= 0) {
            $installmentId = null;
        }

        if ($customerId <= 0 || $dueDate === '') {
            return ['id' => 0, 'amount' => 0.0, 'referral_credit_applied' => 0.0];
        }

        $vat = ($skipVat || !self::customerAppliesVat($pdo, $customerId))
            ? [
                'subtotal' => $netAmount,
                'vat_rate' => 0.0,
                'vat_amount' => 0.0,
                'amount' => $netAmount,
            ]
            : self::applyVat($pdo, $netAmount);

        if (class_exists('ReferralService')) {
            $created = ReferralService::insertInvoice($pdo, $customerId, (float)$vat['amount'], $dueDate, $status, [
                'billing_period_start' => $periodStart !== '' ? $periodStart : null,
                'billing_period_end' => $periodEnd !== '' ? $periodEnd : null,
                'is_prorated' => $isProrated,
                'coverage_days' => $coverageDays,
                'subtotal' => $vat['subtotal'],
                'vat_rate' => $vat['vat_rate'],
                'vat_amount' => $vat['vat_amount'],
                'plan_amount' => $planAmount,
                'installment_amount' => $installmentAmount,
                'installment_id' => $installmentId,
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO invoices (
                    customer_id, amount, subtotal, vat_rate, vat_amount, due_date, status,
                    billing_period_start, billing_period_end, is_prorated, coverage_days,
                    plan_amount, installment_amount, installment_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $customerId,
                $vat['amount'],
                $vat['subtotal'],
                $vat['vat_rate'],
                $vat['vat_amount'],
                $dueDate,
                $status,
                $periodStart !== '' ? $periodStart : null,
                $periodEnd !== '' ? $periodEnd : null,
                $isProrated,
                $coverageDays,
                $planAmount,
                $installmentAmount,
                $installmentId,
            ]);
            $created = [
                'id' => (int)$pdo->lastInsertId(),
                'amount' => $vat['amount'],
                'referral_credit_applied' => 0.0,
            ];
        }

        return $created + [
            'subtotal' => $vat['subtotal'],
            'vat_rate' => $vat['vat_rate'],
            'vat_amount' => $vat['vat_amount'],
            'plan_amount' => $planAmount,
            'installment_amount' => $installmentAmount,
            'installment_id' => $installmentId,
        ];
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

        $netAmount = !empty($coverage['is_prorated'])
            ? self::calculateProratedAmount($monthlyPrice, $activationDate)
            : round($monthlyPrice, 2);
        $composed = self::composeBillAmounts($pdo, $customerId, $netAmount);
        $dueDate = self::dueDateForCoverageEnd($pdo, $coverage['end']);

        $invoice = self::createInvoice($pdo, [
            'customer_id' => $customerId,
            'amount' => $composed['net'],
            'plan_amount' => $composed['plan_amount'],
            'installment_amount' => $composed['installment_amount'],
            'installment_id' => $composed['installment_id'],
            'due_date' => $dueDate,
            'status' => 'ISSUED',
            'billing_period_start' => $coverage['start'],
            'billing_period_end' => $coverage['end'],
            'is_prorated' => !empty($coverage['is_prorated']) ? 1 : 0,
            'coverage_days' => (int)$coverage['coverage_days'],
        ]);

        if (($invoice['id'] ?? 0) > 0 && $composed['installment_id'] > 0 && $composed['installment_amount'] > 0
            && class_exists('InstallationInstallmentService')
        ) {
            InstallationInstallmentService::applyCharge(
                $pdo,
                $composed['installment_id'],
                $composed['installment_amount']
            );
        }

        return $invoice + [
            'due_date' => $dueDate,
            'billing_period_start' => $coverage['start'],
            'billing_period_end' => $coverage['end'],
            'is_prorated' => !empty($coverage['is_prorated']),
            'coverage_days' => (int)$coverage['coverage_days'],
            'plan_amount' => $composed['plan_amount'],
            'installment_amount' => $composed['installment_amount'],
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
        $count = $stmt->rowCount();

        if (file_exists(__DIR__ . '/OmadaNetworkAccessService.php')) {
            require_once __DIR__ . '/OmadaNetworkAccessService.php';
        }
        if (class_exists('OmadaNetworkAccessService')) {
            try {
                OmadaNetworkAccessService::suspendOverdueCustomers($pdo);
            } catch (Throwable $e) {
                error_log('BillingCycleService@markOverdue Omada suspend: ' . $e->getMessage());
            }
        }

        return $count;
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
                i.subtotal,
                i.vat_rate,
                i.vat_amount,
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
                i.subtotal,
                i.vat_rate,
                i.vat_amount,
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
                    i.subtotal,
                    i.vat_rate,
                    i.vat_amount,
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

        $vatRate = (float)($invoice['vat_rate'] ?? 0);
        $vatAmount = (float)($invoice['vat_amount'] ?? 0);
        $subtotal = (float)($invoice['subtotal'] ?? 0);
        $planAmt = (float)($invoice['plan_amount'] ?? 0);
        $installAmt = (float)($invoice['installment_amount'] ?? 0);
        $vatNote = '';
        if ($vatAmount > 0) {
            $vatNote = ' (incl. ' . number_format($vatRate, 0) . '% VAT ₱' . number_format($vatAmount, 2) . ')';
        }
        $installNote = '';
        if ($installAmt > 0) {
            $installNote = ' (plan ₱' . number_format($planAmt > 0 ? $planAmt : max(0, (float)$invoice['amount'] - $installAmt), 2)
                . ' + install ₱' . number_format($installAmt, 2) . ')';
        }

        if ($type === 'MONTHLY_BILL') {
            $subject = 'Your monthly bill is ready - ' . $companyName;
            $headline = 'Monthly Bill';
            $message = 'Hello ' . $customerName . ', your bill for ' . $periodLabel
                . ' is ready. Amount due: ₱' . $amount . $installNote . $vatNote . '. Due date: ' . $dueDate
                . ' (still on time on this date). Please pay via your billing portal.';
        } elseif ($type === 'DUE_REMINDER') {
            $subject = 'Payment due today - ' . $companyName;
            $headline = 'Payment Due Today';
            $message = 'Hello ' . $customerName . ', this is a reminder that Invoice #' . $invoiceId
                . ' for ₱' . $amount . $installNote . $vatNote . ' is due today (' . $dueDate . '). Billing period: '
                . $periodLabel . '. Please pay today to avoid overdue status tomorrow.';
        } else {
            $subject = 'Overdue invoice notice - ' . $companyName;
            $headline = 'Overdue Invoice';
            $message = 'Hello ' . $customerName . ', Invoice #' . $invoiceId
                . ' for ₱' . $amount . $installNote . $vatNote . ' is now overdue (due date was ' . $dueDate
                . '). Billing period: ' . $periodLabel . '. Please settle your balance as soon as possible.';
        }

        $vatHtml = '';
        if ($installAmt > 0) {
            $vatHtml .= '
            <p><strong>Plan:</strong> ₱' . htmlspecialchars(number_format($planAmt > 0 ? $planAmt : max(0, (float)$invoice['amount'] - $installAmt), 2), ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Installation installment:</strong> ₱' . htmlspecialchars(number_format($installAmt, 2), ENT_QUOTES, 'UTF-8') . '</p>
            ';
        }
        if ($vatAmount > 0) {
            $vatHtml .= '
            <p><strong>Subtotal:</strong> ₱' . htmlspecialchars(number_format($subtotal > 0 ? $subtotal : ((float)$invoice['amount'] - $vatAmount), 2), ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>VAT (' . htmlspecialchars(number_format($vatRate, 0), ENT_QUOTES, 'UTF-8') . '%):</strong> ₱' . htmlspecialchars(number_format($vatAmount, 2), ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Total (VAT inclusive):</strong> ₱' . htmlspecialchars($amount, ENT_QUOTES, 'UTF-8') . '</p>
            ';
        } elseif ($installAmt <= 0) {
            $vatHtml = '<p><strong>Amount:</strong> ₱' . htmlspecialchars($amount, ENT_QUOTES, 'UTF-8') . '</p>';
        } else {
            $vatHtml .= '<p><strong>Total:</strong> ₱' . htmlspecialchars($amount, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        $html = '
            <h2>' . htmlspecialchars($headline, ENT_QUOTES, 'UTF-8') . '</h2>
            <p>Hello ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>
            <p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Invoice:</strong> #' . (int)$invoiceId . '</p>
            <p><strong>Billing period:</strong> ' . htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') . '</p>
            ' . $vatHtml . '
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
