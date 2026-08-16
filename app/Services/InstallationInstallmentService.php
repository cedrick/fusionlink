<?php

/**
 * Installation fee installment plans attached to a customer.
 * Monthly bills may include min(monthly_amount, remaining_balance) until paid off.
 */
class InstallationInstallmentService
{
    public static function ensureSchema(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'installation_installments')) {
            $pdo->exec("
                CREATE TABLE installation_installments (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    customer_id INT UNSIGNED NOT NULL,
                    total_amount DECIMAL(12,2) NOT NULL,
                    monthly_amount DECIMAL(12,2) NOT NULL,
                    remaining_balance DECIMAL(12,2) NOT NULL,
                    months_completed INT UNSIGNED NOT NULL DEFAULT 0,
                    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
                    notes VARCHAR(255) NULL,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_installments_customer_status (customer_id, status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }

        if (self::tableExists($pdo, 'invoices')) {
            if (!self::columnExists($pdo, 'invoices', 'plan_amount')) {
                $pdo->exec('ALTER TABLE invoices ADD COLUMN plan_amount DECIMAL(12,2) NULL AFTER coverage_days');
            }
            if (!self::columnExists($pdo, 'invoices', 'installment_amount')) {
                $pdo->exec('ALTER TABLE invoices ADD COLUMN installment_amount DECIMAL(12,2) NULL AFTER plan_amount');
            }
            if (!self::columnExists($pdo, 'invoices', 'installment_id')) {
                $pdo->exec('ALTER TABLE invoices ADD COLUMN installment_id INT UNSIGNED NULL AFTER installment_amount');
            }
        }
    }

    public static function getActiveForCustomer(PDO $pdo, int $customerId): ?array
    {
        self::ensureSchema($pdo);
        if ($customerId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare("
            SELECT *
            FROM installation_installments
            WHERE customer_id = ?
              AND status = 'ACTIVE'
              AND remaining_balance > 0
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$customerId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function listForCustomer(PDO $pdo, int $customerId): array
    {
        self::ensureSchema($pdo);
        if ($customerId <= 0) {
            return [];
        }

        $stmt = $pdo->prepare("
            SELECT *
            FROM installation_installments
            WHERE customer_id = ?
            ORDER BY id DESC
        ");
        $stmt->execute([$customerId]);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array{id:int,amount:float}|null
     */
    public static function peekCharge(PDO $pdo, int $customerId): ?array
    {
        $plan = self::getActiveForCustomer($pdo, $customerId);
        if (!$plan) {
            return null;
        }

        $monthly = round((float)($plan['monthly_amount'] ?? 0), 2);
        $remaining = round((float)($plan['remaining_balance'] ?? 0), 2);
        $charge = round(min($monthly, $remaining), 2);
        if ($charge <= 0) {
            return null;
        }

        return [
            'id' => (int)$plan['id'],
            'amount' => $charge,
            'remaining_before' => $remaining,
            'months_completed' => (int)($plan['months_completed'] ?? 0),
        ];
    }

    /**
     * Apply a billed installment charge: reduce remaining balance and bump months_completed.
     */
    public static function applyCharge(PDO $pdo, int $installmentId, float $chargeAmount): bool
    {
        self::ensureSchema($pdo);
        $chargeAmount = round(max(0, $chargeAmount), 2);
        if ($installmentId <= 0 || $chargeAmount <= 0) {
            return false;
        }

        $stmt = $pdo->prepare("
            SELECT id, remaining_balance, months_completed, status
            FROM installation_installments
            WHERE id = ?
            LIMIT 1
            FOR UPDATE
        ");

        $startedTx = false;
        try {
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
                $startedTx = true;
            }

            $stmt->execute([$installmentId]);
            $row = $stmt->fetch();
            if (!$row || strtoupper((string)$row['status']) !== 'ACTIVE') {
                if ($startedTx) {
                    $pdo->rollBack();
                }
                return false;
            }

            $remaining = round((float)$row['remaining_balance'], 2);
            $charge = round(min($chargeAmount, $remaining), 2);
            $newRemaining = round(max(0, $remaining - $charge), 2);
            $months = (int)$row['months_completed'] + 1;
            $status = $newRemaining <= 0.009 ? 'PAID_OFF' : 'ACTIVE';

            $upd = $pdo->prepare("
                UPDATE installation_installments
                SET remaining_balance = ?,
                    months_completed = ?,
                    status = ?
                WHERE id = ?
            ");
            $upd->execute([$newRemaining, $months, $status, $installmentId]);

            if ($startedTx) {
                $pdo->commit();
            }

            return true;
        } catch (Throwable $e) {
            if ($startedTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('InstallationInstallmentService@applyCharge: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create or replace the active installment plan for a customer.
     * months_already_completed reduces remaining_balance for mid-plan migrations.
     *
     * @return array{ok:bool,id?:int,error?:string}
     */
    public static function savePlan(
        PDO $pdo,
        int $customerId,
        float $totalAmount,
        float $monthlyAmount,
        int $monthsAlreadyCompleted = 0,
        ?string $notes = null,
        ?int $existingId = null
    ): array {
        self::ensureSchema($pdo);

        if ($customerId <= 0) {
            return ['ok' => false, 'error' => 'Invalid customer.'];
        }

        $totalAmount = round($totalAmount, 2);
        $monthlyAmount = round($monthlyAmount, 2);
        $monthsAlreadyCompleted = max(0, $monthsAlreadyCompleted);

        if ($totalAmount <= 0 || $monthlyAmount <= 0) {
            return ['ok' => false, 'error' => 'Total and monthly installment amounts must be greater than zero.'];
        }
        if ($monthlyAmount > $totalAmount) {
            return ['ok' => false, 'error' => 'Monthly amount cannot exceed the total installation fee.'];
        }

        $alreadyPaid = round(min($totalAmount, $monthsAlreadyCompleted * $monthlyAmount), 2);
        $remaining = round(max(0, $totalAmount - $alreadyPaid), 2);
        $status = $remaining <= 0.009 ? 'PAID_OFF' : 'ACTIVE';
        $notes = $notes !== null ? trim($notes) : null;
        if ($notes === '') {
            $notes = null;
        }

        try {
            if ($existingId && $existingId > 0) {
                $upd = $pdo->prepare("
                    UPDATE installation_installments
                    SET total_amount = ?,
                        monthly_amount = ?,
                        remaining_balance = ?,
                        months_completed = ?,
                        status = ?,
                        notes = ?
                    WHERE id = ? AND customer_id = ?
                ");
                $upd->execute([
                    $totalAmount,
                    $monthlyAmount,
                    $remaining,
                    $monthsAlreadyCompleted,
                    $status,
                    $notes,
                    $existingId,
                    $customerId,
                ]);

                return ['ok' => true, 'id' => $existingId];
            }

            // Close any other active plans for this customer before opening a new one.
            $pdo->prepare("
                UPDATE installation_installments
                SET status = 'CANCELLED'
                WHERE customer_id = ? AND status = 'ACTIVE'
            ")->execute([$customerId]);

            $ins = $pdo->prepare("
                INSERT INTO installation_installments (
                    customer_id, total_amount, monthly_amount, remaining_balance,
                    months_completed, status, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([
                $customerId,
                $totalAmount,
                $monthlyAmount,
                $remaining,
                $monthsAlreadyCompleted,
                $status,
                $notes,
            ]);

            return ['ok' => true, 'id' => (int)$pdo->lastInsertId()];
        } catch (Throwable $e) {
            error_log('InstallationInstallmentService@savePlan: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Could not save installment plan.'];
        }
    }

    public static function cancelActive(PDO $pdo, int $customerId, int $installmentId): bool
    {
        self::ensureSchema($pdo);
        $stmt = $pdo->prepare("
            UPDATE installation_installments
            SET status = 'CANCELLED'
            WHERE id = ? AND customer_id = ? AND status = 'ACTIVE'
        ");
        $stmt->execute([$installmentId, $customerId]);

        return $stmt->rowCount() > 0;
    }

    private static function tableExists(PDO $pdo, string $tableName): bool
    {
        $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$tableName]);
        return (bool)$stmt->fetchColumn();
    }

    private static function columnExists(PDO $pdo, string $tableName, string $columnName): bool
    {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$tableName}` LIKE ?");
        $stmt->execute([$columnName]);
        return (bool)$stmt->fetch();
    }
}
