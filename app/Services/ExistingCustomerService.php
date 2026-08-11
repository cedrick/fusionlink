<?php

class ExistingCustomerService
{
    public const PLAN_LABEL = 'Existing Customer - Portal Setup';

    public static function processRegistration(PDO $pdo, array $input): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $email = trim((string)($input['email'] ?? ''));
        $phone = self::normalizePhone((string)($input['phone'] ?? ''));
        $address = trim((string)($input['address'] ?? ''));

        if ($name === '') {
            throw new RuntimeException('Full name is required.');
        }

        if (mb_strlen($name) > 120) {
            throw new RuntimeException('Full name is too long.');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Please enter a valid email address.');
        }

        if (mb_strlen($email) > 190) {
            throw new RuntimeException('Email is too long.');
        }

        if (!self::isValidPhone($phone)) {
            throw new RuntimeException('Phone must be 11 digits and start with 09.');
        }

        if ($address !== '' && mb_strlen($address) > 500) {
            throw new RuntimeException('Address is too long.');
        }

        $customer = self::findCustomerByPhone($pdo, $phone);

        if ($customer) {
            return self::processMatchedCustomer($pdo, $customer, $name, $email, $address);
        }

        return self::processUnmatchedSubmission($pdo, $name, $email, $phone, $address);
    }

    private static function processMatchedCustomer(
        PDO $pdo,
        array $customer,
        string $name,
        string $email,
        string $address
    ): array {
        $customerId = (int)($customer['id'] ?? 0);
        $status = strtoupper((string)($customer['status'] ?? ''));

        if ($status === 'DISCONNECTED') {
            throw new RuntimeException('This account is marked disconnected. Please contact our office for assistance.');
        }

        self::updateCustomerDetails($pdo, $customerId, $name, $email, $address);

        if (!class_exists('CustomerPortalService')) {
            throw new RuntimeException('Portal service is unavailable. Please contact our office.');
        }

        $portalStatus = CustomerPortalService::getPortalStatus($pdo, $customerId);
        if ($portalStatus['has_portal']) {
            if (class_exists('EmailAlertService')) {
                EmailAlertService::notifyExistingCustomerPortalExists(
                    $pdo,
                    $customerId,
                    $name,
                    $email,
                    function_exists('absolute_url') ? absolute_url('/login') : url('/login')
                );
            }

            return [
                'outcome' => 'portal_exists',
                'customer_id' => $customerId,
                'message' => 'You already have billing portal access. Check your email for a login reminder, or use the ISP Billing Login button below.',
            ];
        }

        $portalResult = CustomerPortalService::activatePortal($pdo, $customerId, true);

        if (class_exists('EmailAlertService')) {
            EmailAlertService::notifyExistingCustomerPortalReady(
                $pdo,
                $customerId,
                $portalResult['customer_name'],
                $portalResult['email'],
                $portalResult['password'],
                function_exists('absolute_url') ? absolute_url('/login') : url('/login'),
                $portalResult['mail_sent']
            );

            EmailAlertService::notifyStaffExistingCustomerRegistered($pdo, [
                'name' => $portalResult['customer_name'],
                'email' => $portalResult['email'],
                'phone' => (string)($customer['phone'] ?? ''),
                'matched' => true,
                'portal_created' => true,
            ]);
        }

        $message = 'Your billing portal is ready. ';
        if ($portalResult['mail_sent']) {
            $message .= 'We emailed your login details to ' . $portalResult['email'] . '.';
        } else {
            $message .= 'Our team will send your login details shortly.';
        }

        return [
            'outcome' => 'portal_ready',
            'customer_id' => $customerId,
            'message' => $message,
        ];
    }

    private static function processUnmatchedSubmission(
        PDO $pdo,
        string $name,
        string $email,
        string $phone,
        string $address
    ): array {
        if (self::hasPendingRequest($pdo, $phone)) {
            return [
                'outcome' => 'pending_review',
                'customer_id' => 0,
                'message' => 'We already received your details and our team is reviewing them. We will contact you soon.',
            ];
        }

        if (self::customerExistsByEmail($pdo, $email)) {
            throw new RuntimeException('This email is already linked to another customer account. Please contact our office.');
        }

        $insert = $pdo->prepare('
            INSERT INTO service_requests (name, email, phone, address, plan, status, email_sent)
            VALUES (?, ?, ?, ?, ?, ?, 0)
        ');
        $insert->execute([
            $name,
            $email,
            $phone,
            $address !== '' ? $address : null,
            self::PLAN_LABEL,
            'PENDING',
        ]);

        if (class_exists('EmailAlertService')) {
            EmailAlertService::notifyStaffExistingCustomerRegistered($pdo, [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'matched' => false,
                'portal_created' => false,
            ]);

            EmailAlertService::notifyExistingCustomerPendingReview($pdo, $name, $email);
        }

        return [
            'outcome' => 'pending_review',
            'customer_id' => 0,
            'message' => 'Thank you. We could not auto-match your phone number, so our team will verify your account and email your portal login within 1 business day.',
        ];
    }

    private static function updateCustomerDetails(
        PDO $pdo,
        int $customerId,
        string $name,
        string $email,
        string $address
    ): void {
        $current = $pdo->prepare('SELECT full_name, email, address FROM customers WHERE id = ? LIMIT 1');
        $current->execute([$customerId]);
        $row = $current->fetch();

        if (!$row) {
            throw new RuntimeException('Customer record not found.');
        }

        $currentEmail = trim((string)($row['email'] ?? ''));
        if ($email !== '' && strcasecmp($email, $currentEmail) !== 0) {
            $emailCheck = $pdo->prepare('
                SELECT id
                FROM customers
                WHERE email = ?
                  AND id <> ?
                LIMIT 1
            ');
            $emailCheck->execute([$email, $customerId]);
            if ($emailCheck->fetch()) {
                throw new RuntimeException('This email is already used by another customer account.');
            }

            $userEmailCheck = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $userEmailCheck->execute([$email]);
            if ($userEmailCheck->fetch()) {
                throw new RuntimeException('This email is already used by another login account.');
            }
        }

        $update = $pdo->prepare('
            UPDATE customers
            SET full_name = ?, email = ?, address = COALESCE(NULLIF(?, ""), address)
            WHERE id = ?
        ');
        $update->execute([$name, $email, $address, $customerId]);
    }

    private static function findCustomerByPhone(PDO $pdo, string $phone): ?array
    {
        $stmt = $pdo->prepare('
            SELECT id, full_name, email, phone, address, status
            FROM customers
            WHERE phone = ?
            ORDER BY id DESC
            LIMIT 1
        ');
        $stmt->execute([$phone]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    private static function hasPendingRequest(PDO $pdo, string $phone): bool
    {
        $stmt = $pdo->prepare("
            SELECT id
            FROM service_requests
            WHERE phone = ?
              AND plan = ?
              AND status = 'PENDING'
            LIMIT 1
        ");
        $stmt->execute([$phone, self::PLAN_LABEL]);

        return (bool)$stmt->fetch();
    }

    private static function customerExistsByEmail(PDO $pdo, string $email): bool
    {
        $stmt = $pdo->prepare('SELECT id FROM customers WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);

        return (bool)$stmt->fetch();
    }

    private static function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', trim($phone));
    }

    private static function isValidPhone(string $phone): bool
    {
        return (bool)preg_match('/^09\d{9}$/', $phone);
    }
}
