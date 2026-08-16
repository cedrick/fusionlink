<?php

class CustomerPortalService
{
    public static function generateTemporaryPassword(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $password = '';

        for ($i = 0; $i < 10; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $password;
    }

    public static function getPortalStatus(PDO $pdo, int $customerId): array
    {
        if ($customerId <= 0 || !self::tableExists($pdo, 'users')) {
            return [
                'has_portal' => false,
                'user_id' => 0,
                'email' => '',
            ];
        }

        $stmt = $pdo->prepare("
            SELECT id, email
            FROM users
            WHERE role = 'ROLE_CUSTOMER'
              AND customer_id = ?
            LIMIT 1
        ");
        $stmt->execute([$customerId]);
        $row = $stmt->fetch();

        if (!$row) {
            return [
                'has_portal' => false,
                'user_id' => 0,
                'email' => '',
            ];
        }

        return [
            'has_portal' => true,
            'user_id' => (int)($row['id'] ?? 0),
            'email' => trim((string)($row['email'] ?? '')),
        ];
    }

    public static function getPortalStatuses(PDO $pdo, array $customerIds): array
    {
        $customerIds = array_values(array_filter(array_map('intval', $customerIds), static fn (int $id): bool => $id > 0));
        if ($customerIds === [] || !self::tableExists($pdo, 'users')) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($customerIds), '?'));
        $stmt = $pdo->prepare("
            SELECT customer_id, id, email
            FROM users
            WHERE role = 'ROLE_CUSTOMER'
              AND customer_id IN ($placeholders)
        ");
        $stmt->execute($customerIds);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $customerId = (int)($row['customer_id'] ?? 0);
            if ($customerId <= 0) {
                continue;
            }

            $map[$customerId] = [
                'has_portal' => true,
                'user_id' => (int)($row['id'] ?? 0),
                'email' => trim((string)($row['email'] ?? '')),
            ];
        }

        return $map;
    }

    public static function createPortalUser(PDO $pdo, int $customerId, string $email): array
    {
        if ($customerId <= 0) {
            throw new RuntimeException('Invalid customer ID for portal user.');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('A valid email is required to create customer portal access.');
        }

        if (!self::tableExists($pdo, 'users')) {
            throw new RuntimeException('Users table is missing. Cannot create portal login.');
        }

        $checkEmail = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $checkEmail->execute([$email]);
        if ($checkEmail->fetch()) {
            throw new RuntimeException('This email is already used by another login account.');
        }

        $checkCustomerUser = $pdo->prepare("
            SELECT id
            FROM users
            WHERE role = 'ROLE_CUSTOMER'
              AND customer_id = ?
            LIMIT 1
        ");
        $checkCustomerUser->execute([$customerId]);
        if ($checkCustomerUser->fetch()) {
            throw new RuntimeException('This customer already has a portal login account.');
        }

        $temporaryPassword = self::generateTemporaryPassword();
        $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);

        $insertUser = $pdo->prepare("
            INSERT INTO users (customer_id, email, password_hash, role)
            VALUES (?, ?, ?, 'ROLE_CUSTOMER')
        ");
        $insertUser->execute([$customerId, $email, $passwordHash]);

        $userId = (int)$pdo->lastInsertId();
        if ($userId <= 0) {
            throw new RuntimeException('Failed to create customer portal login.');
        }

        return [
            'user_id' => $userId,
            'email' => $email,
            'password' => $temporaryPassword,
        ];
    }

    public static function activatePortal(PDO $pdo, int $customerId, bool $sendEmail = true): array
    {
        $stmt = $pdo->prepare('SELECT id, full_name, email, status FROM customers WHERE id = ? LIMIT 1');
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch();

        if (!$customer) {
            throw new RuntimeException('Customer not found.');
        }

        if (strtoupper((string)($customer['status'] ?? '')) === 'DISCONNECTED') {
            throw new RuntimeException('Cannot activate portal access for a disconnected customer.');
        }

        $email = trim((string)($customer['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Add a valid email address on the customer record before activating portal access.');
        }

        $portalUser = self::createPortalUser($pdo, $customerId, $email);
        $mailSent = false;

        if ($sendEmail && class_exists('EmailAlertService')) {
            $loginUrl = function_exists('absolute_url') ? absolute_url('/login') : url('/login');
            $mailSent = EmailAlertService::notifyPortalCredentials(
                $pdo,
                $customerId,
                trim((string)($customer['full_name'] ?? 'Customer')),
                $portalUser['email'],
                $portalUser['password'],
                $loginUrl
            );
        }

        return [
            'customer_name' => trim((string)($customer['full_name'] ?? 'Customer')),
            'email' => $portalUser['email'],
            'password' => $portalUser['password'],
            'mail_sent' => $mailSent,
        ];
    }

    public static function resetPortalPassword(PDO $pdo, int $customerId, bool $sendEmail = true): array
    {
        $stmt = $pdo->prepare('SELECT id, full_name, email, status FROM customers WHERE id = ? LIMIT 1');
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch();

        if (!$customer) {
            throw new RuntimeException('Customer not found.');
        }

        $portal = self::getPortalStatus($pdo, $customerId);
        $userId = (int)($portal['user_id'] ?? 0);
        if (empty($portal['has_portal']) || $userId <= 0) {
            throw new RuntimeException('This customer does not have a portal login yet. Create portal access first.');
        }

        $email = trim((string)($portal['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Portal login email is missing or invalid.');
        }

        $temporaryPassword = self::generateTemporaryPassword();
        $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);

        $update = $pdo->prepare("
            UPDATE users
            SET password_hash = ?
            WHERE id = ?
              AND role = 'ROLE_CUSTOMER'
            LIMIT 1
        ");
        $update->execute([$passwordHash, $userId]);

        $mailSent = false;
        if ($sendEmail && class_exists('EmailAlertService')) {
            $loginUrl = function_exists('absolute_url') ? absolute_url('/login') : url('/login');
            $mailSent = EmailAlertService::notifyPortalPasswordReset(
                $pdo,
                $customerId,
                trim((string)($customer['full_name'] ?? 'Customer')),
                $email,
                $temporaryPassword,
                $loginUrl
            );
        }

        return [
            'customer_name' => trim((string)($customer['full_name'] ?? 'Customer')),
            'email' => $email,
            'password' => $temporaryPassword,
            'mail_sent' => $mailSent,
        ];
    }

    private static function tableExists(PDO $pdo, string $tableName): bool
    {
        $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$tableName]);
        return (bool)$stmt->fetchColumn();
    }
}
