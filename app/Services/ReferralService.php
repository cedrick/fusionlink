<?php

class ReferralService
{
    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', trim($phone));
    }

    public static function ensureSchema(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'referral_rewards')) {
            $pdo->exec("
                CREATE TABLE referral_rewards (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    referrer_customer_id INT UNSIGNED NOT NULL,
                    referred_customer_id INT UNSIGNED NOT NULL,
                    amount DECIMAL(10,2) NOT NULL DEFAULT 500.00,
                    status ENUM('PENDING','APPLIED') NOT NULL DEFAULT 'PENDING',
                    applied_invoice_id INT UNSIGNED NULL,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    applied_at TIMESTAMP NULL DEFAULT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY uniq_referred_customer (referred_customer_id),
                    KEY idx_referrer_status (referrer_customer_id, status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (self::tableExists($pdo, 'settings') && !self::columnExists($pdo, 'settings', 'referral_reward_amount')) {
            $pdo->exec("ALTER TABLE settings ADD COLUMN referral_reward_amount DECIMAL(10,2) NOT NULL DEFAULT 500.00 AFTER billing_due_day");
        }

        if (self::tableExists($pdo, 'service_requests') && !self::columnExists($pdo, 'service_requests', 'referred_by_phone')) {
            $pdo->exec("ALTER TABLE service_requests ADD COLUMN referred_by_phone VARCHAR(50) NULL AFTER plan");
        }

        if (self::tableExists($pdo, 'customers') && !self::columnExists($pdo, 'customers', 'referred_by_customer_id')) {
            $pdo->exec("ALTER TABLE customers ADD COLUMN referred_by_customer_id INT UNSIGNED NULL AFTER status");
        }

        if (self::tableExists($pdo, 'invoices') && !self::columnExists($pdo, 'invoices', 'referral_credit_applied')) {
            $pdo->exec("ALTER TABLE invoices ADD COLUMN referral_credit_applied DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER amount");
        }

        if (file_exists(__DIR__ . '/BillingCycleService.php')) {
            require_once __DIR__ . '/BillingCycleService.php';
            if (class_exists('BillingCycleService')) {
                BillingCycleService::ensureSchema($pdo);
            }
        }
    }

    public static function getRewardAmount(PDO $pdo): float
    {
        self::ensureSchema($pdo);

        try {
            $stmt = $pdo->query('SELECT referral_reward_amount FROM settings ORDER BY id ASC LIMIT 1');
            $row = $stmt->fetch();
            $amount = (float)($row['referral_reward_amount'] ?? 500);
            return $amount > 0 ? round($amount, 2) : 500.0;
        } catch (Throwable $e) {
            return 500.0;
        }
    }

    public static function findReferrerByPhone(PDO $pdo, string $phone, int $excludeCustomerId = 0): ?array
    {
        self::ensureSchema($pdo);

        $phone = self::normalizePhone($phone);
        if ($phone === '' || !preg_match('/^09\d{9}$/', $phone)) {
            return null;
        }

        $sql = "
            SELECT id, full_name, phone, status
            FROM customers
            WHERE phone = ?
              AND status = 'ACTIVE'
        ";
        $params = [$phone];

        if ($excludeCustomerId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeCustomerId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function createPendingReward(
        PDO $pdo,
        int $referrerCustomerId,
        int $referredCustomerId,
        ?float $amount = null
    ): ?int {
        self::ensureSchema($pdo);

        if ($referrerCustomerId <= 0 || $referredCustomerId <= 0 || $referrerCustomerId === $referredCustomerId) {
            return null;
        }

        $rewardAmount = $amount ?? self::getRewardAmount($pdo);

        $check = $pdo->prepare('SELECT id FROM referral_rewards WHERE referred_customer_id = ? LIMIT 1');
        $check->execute([$referredCustomerId]);
        if ($check->fetch()) {
            return null;
        }

        $stmt = $pdo->prepare("
            INSERT INTO referral_rewards (referrer_customer_id, referred_customer_id, amount, status)
            VALUES (?, ?, ?, 'PENDING')
        ");
        $stmt->execute([$referrerCustomerId, $referredCustomerId, $rewardAmount]);

        return (int)$pdo->lastInsertId();
    }

    public static function processInquiryReferral(PDO $pdo, array $inquiry, int $newCustomerId, string $newCustomerPhone): ?array
    {
        self::ensureSchema($pdo);

        $referrerPhone = self::normalizePhone((string)($inquiry['referred_by_phone'] ?? ''));
        if ($referrerPhone === '') {
            return null;
        }

        if ($referrerPhone === self::normalizePhone($newCustomerPhone)) {
            return null;
        }

        $referrer = self::findReferrerByPhone($pdo, $referrerPhone, $newCustomerId);
        if (!$referrer) {
            return null;
        }

        $referrerId = (int)($referrer['id'] ?? 0);
        if ($referrerId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare('UPDATE customers SET referred_by_customer_id = ? WHERE id = ?');
        $stmt->execute([$referrerId, $newCustomerId]);

        $rewardAmount = self::getRewardAmount($pdo);
        $rewardId = self::createPendingReward($pdo, $referrerId, $newCustomerId, $rewardAmount);
        if ($rewardId === null) {
            return null;
        }

        return [
            'reward_id' => $rewardId,
            'referrer_customer_id' => $referrerId,
            'referrer_name' => trim((string)($referrer['full_name'] ?? 'Customer')),
            'amount' => $rewardAmount,
        ];
    }

    public static function calculatePendingCredit(PDO $pdo, int $customerId, float $invoiceAmount): array
    {
        self::ensureSchema($pdo);

        if ($customerId <= 0 || $invoiceAmount <= 0) {
            return [
                'credit' => 0.0,
                'reward_ids' => [],
            ];
        }

        $stmt = $pdo->prepare("
            SELECT id, amount
            FROM referral_rewards
            WHERE referrer_customer_id = ?
              AND status = 'PENDING'
            ORDER BY id ASC
        ");
        $stmt->execute([$customerId]);

        $remaining = round($invoiceAmount, 2);
        $totalCredit = 0.0;
        $rewardIds = [];

        foreach ($stmt->fetchAll() as $row) {
            if ($remaining <= 0) {
                break;
            }

            $rewardId = (int)($row['id'] ?? 0);
            $rewardAmount = round((float)($row['amount'] ?? 0), 2);
            if ($rewardId <= 0 || $rewardAmount <= 0) {
                continue;
            }

            $applied = min($remaining, $rewardAmount);
            $totalCredit += $applied;
            $remaining -= $applied;
            $rewardIds[] = $rewardId;
        }

        return [
            'credit' => round($totalCredit, 2),
            'reward_ids' => $rewardIds,
        ];
    }

    public static function markRewardsApplied(PDO $pdo, array $rewardIds, int $invoiceId): void
    {
        if ($invoiceId <= 0 || $rewardIds === []) {
            return;
        }

        self::ensureSchema($pdo);

        $placeholders = implode(',', array_fill(0, count($rewardIds), '?'));
        $params = array_merge([$invoiceId], $rewardIds);

        $stmt = $pdo->prepare("
            UPDATE referral_rewards
            SET status = 'APPLIED',
                applied_invoice_id = ?,
                applied_at = NOW()
            WHERE id IN ($placeholders)
              AND status = 'PENDING'
        ");
        $stmt->execute($params);
    }

    public static function insertInvoice(
        PDO $pdo,
        int $customerId,
        float $amount,
        string $dueDate,
        string $status = 'ISSUED',
        array $meta = []
    ): array {
        self::ensureSchema($pdo);

        $amount = round(max(0, $amount), 2);
        $creditInfo = self::calculatePendingCredit($pdo, $customerId, $amount);
        $credit = (float)($creditInfo['credit'] ?? 0);
        $finalAmount = round(max(0, $amount - $credit), 2);

        $periodStart = $meta['billing_period_start'] ?? null;
        $periodEnd = $meta['billing_period_end'] ?? null;
        $isProrated = !empty($meta['is_prorated']) ? 1 : 0;
        $coverageDays = array_key_exists('coverage_days', $meta) ? $meta['coverage_days'] : null;
        $subtotal = array_key_exists('subtotal', $meta) ? round((float)$meta['subtotal'], 2) : null;
        $vatRate = array_key_exists('vat_rate', $meta) ? round((float)$meta['vat_rate'], 2) : null;
        $vatAmount = array_key_exists('vat_amount', $meta) ? round((float)$meta['vat_amount'], 2) : null;
        $planAmount = array_key_exists('plan_amount', $meta) ? round((float)$meta['plan_amount'], 2) : null;
        $installmentAmount = array_key_exists('installment_amount', $meta) ? round((float)$meta['installment_amount'], 2) : null;
        $installmentId = array_key_exists('installment_id', $meta) && $meta['installment_id']
            ? (int)$meta['installment_id']
            : null;

        $hasPeriodColumns = self::columnExists($pdo, 'invoices', 'billing_period_start');
        $hasVatColumns = self::columnExists($pdo, 'invoices', 'vat_amount');
        $hasInstallmentColumns = self::columnExists($pdo, 'invoices', 'installment_amount');

        if ($hasPeriodColumns && $hasVatColumns && $hasInstallmentColumns) {
            $stmt = $pdo->prepare('
                INSERT INTO invoices (
                    customer_id, amount, subtotal, vat_rate, vat_amount, referral_credit_applied, due_date, status,
                    billing_period_start, billing_period_end, is_prorated, coverage_days,
                    plan_amount, installment_amount, installment_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $customerId,
                $finalAmount,
                $subtotal,
                $vatRate,
                $vatAmount,
                $credit,
                $dueDate,
                $status,
                $periodStart,
                $periodEnd,
                $isProrated,
                $coverageDays,
                $planAmount,
                $installmentAmount,
                $installmentId,
            ]);
        } elseif ($hasPeriodColumns && $hasVatColumns) {
            $stmt = $pdo->prepare('
                INSERT INTO invoices (
                    customer_id, amount, subtotal, vat_rate, vat_amount, referral_credit_applied, due_date, status,
                    billing_period_start, billing_period_end, is_prorated, coverage_days
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $customerId,
                $finalAmount,
                $subtotal,
                $vatRate,
                $vatAmount,
                $credit,
                $dueDate,
                $status,
                $periodStart,
                $periodEnd,
                $isProrated,
                $coverageDays,
            ]);
        } elseif ($hasPeriodColumns) {
            $stmt = $pdo->prepare('
                INSERT INTO invoices (
                    customer_id, amount, referral_credit_applied, due_date, status,
                    billing_period_start, billing_period_end, is_prorated, coverage_days
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $customerId,
                $finalAmount,
                $credit,
                $dueDate,
                $status,
                $periodStart,
                $periodEnd,
                $isProrated,
                $coverageDays,
            ]);
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO invoices (customer_id, amount, referral_credit_applied, due_date, status)
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([$customerId, $finalAmount, $credit, $dueDate, $status]);
        }

        $invoiceId = (int)$pdo->lastInsertId();
        if ($invoiceId > 0 && $credit > 0) {
            self::markRewardsApplied($pdo, $creditInfo['reward_ids'] ?? [], $invoiceId);
        }

        return [
            'id' => $invoiceId,
            'amount' => $finalAmount,
            'referral_credit_applied' => $credit,
            'original_amount' => $amount,
            'due_date' => $dueDate,
            'billing_period_start' => $periodStart,
            'billing_period_end' => $periodEnd,
            'is_prorated' => $isProrated,
            'coverage_days' => $coverageDays,
            'subtotal' => $subtotal,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'plan_amount' => $planAmount,
            'installment_amount' => $installmentAmount,
            'installment_id' => $installmentId,
        ];
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
