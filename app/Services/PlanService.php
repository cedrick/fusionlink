<?php

class PlanService
{
    public static function ensureSchema(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'plans')) {
            return;
        }

        if (!self::columnExists($pdo, 'plans', 'is_legacy')) {
            $pdo->exec('ALTER TABLE plans ADD COLUMN is_legacy TINYINT(1) NOT NULL DEFAULT 0 AFTER price');
            $pdo->exec("UPDATE plans SET is_legacy = 1 WHERE LOWER(name) LIKE '%legacy%'");
        }
    }

    public static function isLegacyPlan(array $plan): bool
    {
        return (int)($plan['is_legacy'] ?? 0) === 1;
    }

    private static function tableExists(PDO $pdo, string $tableName): bool
    {
        $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$tableName]);

        return (bool)$stmt->fetchColumn();
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
