<?php

if (file_exists(__DIR__ . '/ApplicationWorkflowService.php')) {
    require_once __DIR__ . '/ApplicationWorkflowService.php';
}

class EmailAlertService
{
    public static function resolveCustomerEmail(PDO $pdo, int $customerId): string
    {
        if ($customerId <= 0) {
            return '';
        }

        $stmt = $pdo->prepare('SELECT email FROM customers WHERE id = ? LIMIT 1');
        $stmt->execute([$customerId]);
        $row = $stmt->fetch();
        $email = trim((string)($row['email'] ?? ''));

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        $stmt = $pdo->prepare("
            SELECT email
            FROM users
            WHERE customer_id = ?
              AND role = 'ROLE_CUSTOMER'
            LIMIT 1
        ");
        $stmt->execute([$customerId]);
        $userRow = $stmt->fetch();
        $email = trim((string)($userRow['email'] ?? ''));

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        return '';
    }

    public static function resolveCustomerName(PDO $pdo, int $customerId, string $fallback = 'Customer'): string
    {
        if ($customerId <= 0) {
            return $fallback;
        }

        $stmt = $pdo->prepare('SELECT full_name FROM customers WHERE id = ? LIMIT 1');
        $stmt->execute([$customerId]);
        $row = $stmt->fetch();
        $name = trim((string)($row['full_name'] ?? ''));

        return $name !== '' ? $name : $fallback;
    }

    public static function getStaffRecipients(PDO $pdo): array
    {
        $recipients = [];

        try {
            $stmt = $pdo->query('
                SELECT email, company_name
                FROM settings
                ORDER BY id ASC
                LIMIT 1
            ');
            $settings = $stmt->fetch();
            $companyEmail = trim((string)($settings['email'] ?? ''));
            $companyName = trim((string)($settings['company_name'] ?? 'FusionLink'));

            if ($companyEmail !== '' && filter_var($companyEmail, FILTER_VALIDATE_EMAIL)) {
                $recipients[strtolower($companyEmail)] = $companyName !== '' ? $companyName : 'Admin';
            }
        } catch (Throwable $e) {
            error_log('EmailAlertService@getStaffRecipients settings error: ' . $e->getMessage());
        }

        try {
            $stmt = $pdo->query("
                SELECT email
                FROM users
                WHERE role IN ('ROLE_ADMIN', 'ROLE_STAFF', 'ADMIN', 'STAFF', 'admin', 'staff')
            ");
            foreach ($stmt->fetchAll() as $row) {
                $email = trim((string)($row['email'] ?? ''));
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $recipients[strtolower($email)] = $email;
                }
            }
        } catch (Throwable $e) {
            error_log('EmailAlertService@getStaffRecipients users error: ' . $e->getMessage());
        }

        return $recipients;
    }

    /**
     * Administrator / company inbox used as BCC on customer billing emails.
     * Prefer Settings → company email, then ROLE_ADMIN user emails.
     *
     * @return array<string,string> email => display name
     */
    public static function getAdministratorEmails(PDO $pdo): array
    {
        $recipients = [];

        try {
            $stmt = $pdo->query('
                SELECT email, company_name
                FROM settings
                ORDER BY id ASC
                LIMIT 1
            ');
            $settings = $stmt->fetch();
            $companyEmail = trim((string)($settings['email'] ?? ''));
            $companyName = trim((string)($settings['company_name'] ?? 'Administrator'));

            if ($companyEmail !== '' && filter_var($companyEmail, FILTER_VALIDATE_EMAIL)) {
                $recipients[strtolower($companyEmail)] = $companyName !== '' ? $companyName : 'Administrator';
            }
        } catch (Throwable $e) {
            error_log('EmailAlertService@getAdministratorEmails settings error: ' . $e->getMessage());
        }

        try {
            $stmt = $pdo->query("
                SELECT email
                FROM users
                WHERE role IN ('ROLE_ADMIN', 'ADMIN', 'admin')
            ");
            foreach ($stmt->fetchAll() as $row) {
                $email = trim((string)($row['email'] ?? ''));
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $key = strtolower($email);
                    if (!isset($recipients[$key])) {
                        $recipients[$key] = $email;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('EmailAlertService@getAdministratorEmails users error: ' . $e->getMessage());
        }

        return $recipients;
    }

    /**
     * @return list<string>
     */
    public static function administratorBccList(PDO $pdo, string $excludeEmail = ''): array
    {
        $exclude = strtolower(trim($excludeEmail));
        $list = [];
        foreach (self::getAdministratorEmails($pdo) as $email => $_name) {
            if ($exclude !== '' && strtolower((string)$email) === $exclude) {
                continue;
            }
            $list[] = (string)$email;
        }

        return $list;
    }

    public static function logNotification(
        PDO $pdo,
        int $customerId,
        ?int $invoiceId,
        string $type,
        string $recipientEmail,
        string $subject,
        string $message,
        string $status
    ): int {
        $stmt = $pdo->prepare('
            INSERT INTO notifications (customer_id, invoice_id, type, recipient_email, subject, message, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            max(0, $customerId),
            $invoiceId,
            $type,
            $recipientEmail,
            $subject,
            $message,
            $status,
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function sendHtml(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        array $bccEmails = []
    ): bool {
        if (!class_exists('MailService')) {
            return false;
        }

        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            $mailService = new MailService();
            return $mailService->send($toEmail, $toName, $subject, $htmlBody, $bccEmails);
        } catch (Throwable $e) {
            error_log('EmailAlertService@sendHtml error: ' . $e->getMessage());
            return false;
        }
    }

    public static function notifyCustomer(
        PDO $pdo,
        int $customerId,
        ?int $invoiceId,
        string $type,
        string $subject,
        string $plainMessage,
        string $htmlBody,
        bool $copyAdministrator = false
    ): bool {
        $email = self::resolveCustomerEmail($pdo, $customerId);
        if ($email === '') {
            self::logNotification($pdo, $customerId, $invoiceId, $type, '', $subject, $plainMessage, 'FAILED');
            return false;
        }

        $name = self::resolveCustomerName($pdo, $customerId);
        $notificationId = self::logNotification($pdo, $customerId, $invoiceId, $type, $email, $subject, $plainMessage, 'PENDING');
        $bcc = $copyAdministrator ? self::administratorBccList($pdo, $email) : [];
        $sent = self::sendHtml($email, $name, $subject, $htmlBody, $bcc);

        $stmt = $pdo->prepare('UPDATE notifications SET status = ? WHERE id = ?');
        $stmt->execute([$sent ? 'SENT' : 'FAILED', $notificationId]);

        return $sent;
    }

    public static function notifyStaff(
        PDO $pdo,
        string $type,
        string $subject,
        string $plainMessage,
        string $htmlBody,
        ?int $relatedCustomerId = null,
        ?int $invoiceId = null
    ): int {
        $recipients = self::getStaffRecipients($pdo);
        $sentCount = 0;

        foreach ($recipients as $email => $name) {
            $notificationId = self::logNotification(
                $pdo,
                (int)($relatedCustomerId ?? 0),
                $invoiceId,
                $type,
                $email,
                $subject,
                $plainMessage,
                'PENDING'
            );

            $sent = self::sendHtml($email, (string)$name, $subject, $htmlBody);
            if ($sent) {
                $sentCount++;
            }

            $stmt = $pdo->prepare('UPDATE notifications SET status = ? WHERE id = ?');
            $stmt->execute([$sent ? 'SENT' : 'FAILED', $notificationId]);
        }

        return $sentCount;
    }

    public static function notifyApplicant(
        PDO $pdo,
        string $email,
        string $name,
        string $subject,
        string $plainMessage,
        string $htmlBody
    ): bool {
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::logNotification($pdo, 0, null, 'APPLICATION_RECEIVED', $email, $subject, $plainMessage, 'FAILED');
            return false;
        }

        $notificationId = self::logNotification($pdo, 0, null, 'APPLICATION_CONFIRMATION', $email, $subject, $plainMessage, 'PENDING');
        $sent = self::sendHtml($email, $name, $subject, $htmlBody);

        $stmt = $pdo->prepare('UPDATE notifications SET status = ? WHERE id = ?');
        $stmt->execute([$sent ? 'SENT' : 'FAILED', $notificationId]);

        return $sent;
    }

    public static function wrapHtml(string $heading, string $contentHtml, string $companyName = 'FusionLink'): string
    {
        return '
            <div style="max-width:700px;margin:0 auto;font-family:Arial,sans-serif;background:#ffffff;border:1px solid #dbe4f0;">
                <div style="background:#6d28d9;color:#ffffff;padding:20px 24px;text-align:center;">
                    <h1 style="margin:0;font-size:24px;">' . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . '</h1>
                    <p style="margin:8px 0 0 0;font-size:15px;">' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . '</p>
                </div>
                <div style="padding:28px 24px;color:#1f2937;line-height:1.7;">' . $contentHtml . '</div>
            </div>
        ';
    }

    public static function getCompanyName(PDO $pdo): string
    {
        try {
            $stmt = $pdo->query('SELECT company_name FROM settings ORDER BY id ASC LIMIT 1');
            $row = $stmt->fetch();
            $name = trim((string)($row['company_name'] ?? ''));
            return $name !== '' ? $name : 'FusionLink';
        } catch (Throwable $e) {
            return 'FusionLink';
        }
    }

    public static function getCompanyContact(PDO $pdo): array
    {
        $defaults = [
            'company_name' => 'FusionLink',
            'email' => '',
            'contact_number' => '',
        ];

        try {
            $stmt = $pdo->query('
                SELECT company_name, email, contact_number
                FROM settings
                ORDER BY id ASC
                LIMIT 1
            ');
            $row = $stmt->fetch();
            if (!$row) {
                return $defaults;
            }

            return [
                'company_name' => trim((string)($row['company_name'] ?? '')) ?: $defaults['company_name'],
                'email' => trim((string)($row['email'] ?? '')),
                'contact_number' => trim((string)($row['contact_number'] ?? '')),
            ];
        } catch (Throwable $e) {
            return $defaults;
        }
    }

    public static function notifyApplicationApproved(
        PDO $pdo,
        int $customerId,
        int $invoiceId,
        array $planDetails,
        float $monthlyPrice,
        float $invoiceAmount,
        int $remainingDays,
        string $dueDate,
        ?string $portalLoginEmail = null,
        ?string $portalTemporaryPassword = null,
        ?string $portalLoginUrl = null
    ): bool {
        $company = self::getCompanyContact($pdo);
        $companyName = $company['company_name'];
        $customerName = self::resolveCustomerName($pdo, $customerId);
        $planName = trim((string)($planDetails['name'] ?? ''));
        $planSpeed = trim((string)($planDetails['speed'] ?? ''));
        $supportEmail = $company['email'] !== '' ? $company['email'] : 'support@example.com';
        $supportPhone = $company['contact_number'] !== '' ? $company['contact_number'] : 'Not available';

        $portalHtml = '';
        if (
            $portalLoginEmail !== null && $portalLoginEmail !== ''
            && $portalTemporaryPassword !== null && $portalTemporaryPassword !== ''
            && $portalLoginUrl !== null && $portalLoginUrl !== ''
        ) {
            $portalHtml = '
            <div style="border:1px solid #c4b5fd;background:#f5f3ff;padding:18px 20px;margin:24px 0;">
                <h2 style="margin:0 0 14px 0;font-size:18px;color:#0f172a;">ISP Billing Portal Login</h2>
                <p style="margin:6px 0;">Use these credentials to view invoices and submit payments online:</p>
                <p style="margin:6px 0;"><strong>Login URL:</strong> <a href="' . htmlspecialchars($portalLoginUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($portalLoginUrl, ENT_QUOTES, 'UTF-8') . '</a></p>
                <p style="margin:6px 0;"><strong>Email:</strong> ' . htmlspecialchars($portalLoginEmail, ENT_QUOTES, 'UTF-8') . '</p>
                <p style="margin:6px 0;"><strong>Temporary Password:</strong> ' . htmlspecialchars($portalTemporaryPassword, ENT_QUOTES, 'UTF-8') . '</p>
                <p style="margin:12px 0 0 0;">After signing in, a verification code will be sent to your email. Then open Billing Portal → Password to replace this temporary password.</p>
            </div>';
        }

        $subject = 'Application Approved - ' . $companyName;
        $plainMessage = 'Hello ' . $customerName . ', your internet service application has been approved.';
        if ($portalLoginEmail !== null && $portalTemporaryPassword !== null && $portalLoginUrl !== null) {
            $plainMessage .= ' Portal login — URL: ' . $portalLoginUrl . ' | Email: ' . $portalLoginEmail . ' | Password: ' . $portalTemporaryPassword;
        }
        $html = self::wrapHtml('Application Approved', '
            <p>Hello ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>
            <p>Your internet service application has been approved and you are now registered as a client.</p>
            <div style="border:1px solid #d1d5db;background:#f8fafc;padding:18px 20px;margin:24px 0;">
                <h2 style="margin:0 0 14px 0;font-size:18px;color:#0f172a;">Account Details</h2>
                <p style="margin:6px 0;"><strong>Selected Plan:</strong> ' . htmlspecialchars($planName, ENT_QUOTES, 'UTF-8') . ' - ' . htmlspecialchars($planSpeed, ENT_QUOTES, 'UTF-8') . '</p>
                <p style="margin:6px 0;"><strong>Monthly Plan Rate:</strong> ₱' . htmlspecialchars(number_format($monthlyPrice, 2), ENT_QUOTES, 'UTF-8') . '</p>
                <p style="margin:6px 0;"><strong>Prorated First Bill:</strong> ₱' . htmlspecialchars(number_format($invoiceAmount, 2), ENT_QUOTES, 'UTF-8') . '</p>
                <p style="margin:6px 0;"><strong>Billing Coverage for First Bill:</strong> ' . (int)$remainingDays . ' day(s) remaining in this month</p>
                <p style="margin:6px 0;"><strong>Customer ID:</strong> ' . (int)$customerId . '</p>
                <p style="margin:6px 0;"><strong>Initial Invoice ID:</strong> ' . (int)$invoiceId . '</p>
                <p style="margin:6px 0;"><strong>Due Date:</strong> ' . htmlspecialchars($dueDate, ENT_QUOTES, 'UTF-8') . '</p>
            </div>
            ' . $portalHtml . '
            <p>Our team may contact you for the next steps such as installation schedule and service activation.</p>
            <div style="border-top:1px solid #d1d5db;margin-top:28px;padding-top:18px;color:#374151;">
                <p style="margin:0 0 8px 0;"><strong>' . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . ' Support Team</strong></p>
                <p style="margin:4px 0;">Email: ' . htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') . '</p>
                <p style="margin:4px 0;">Phone: ' . htmlspecialchars($supportPhone, ENT_QUOTES, 'UTF-8') . '</p>
            </div>
            <p style="margin-top:24px;">Welcome to ' . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . '.</p>
        ', $companyName);

        return self::notifyCustomer(
            $pdo,
            $customerId,
            $invoiceId,
            'APPLICATION_APPROVED',
            $subject,
            $plainMessage,
            $html,
            true
        );
    }

    public static function notifyNewApplication(PDO $pdo, array $application): void
    {
        $companyName = self::getCompanyName($pdo);
        $name = trim((string)($application['name'] ?? 'Applicant'));
        $email = trim((string)($application['email'] ?? ''));
        $phone = trim((string)($application['phone'] ?? ''));
        $plan = trim((string)($application['plan'] ?? ''));
        $address = trim((string)($application['address'] ?? ''));
        $referredByPhone = trim((string)($application['referred_by_phone'] ?? ''));

        $staffSubject = 'New Service Application - ' . $companyName;
        $staffMessage = $name . ' submitted a new service application for ' . $plan . '.';
        $staffHtml = self::wrapHtml('New Service Application', '
            <p><strong>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</strong> submitted a new application.</p>
            <p><strong>Plan:</strong> ' . htmlspecialchars($plan, ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Phone:</strong> ' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Address:</strong> ' . nl2br(htmlspecialchars($address, ENT_QUOTES, 'UTF-8')) . '</p>
            <p><strong>Referred by phone:</strong> ' . htmlspecialchars($referredByPhone !== '' ? $referredByPhone : 'Not provided', ENT_QUOTES, 'UTF-8') . '</p>
            <p>Review it in the Inquiries section of FusionLink.</p>
        ', $companyName);

        self::notifyStaff($pdo, 'APPLICATION_RECEIVED', $staffSubject, $staffMessage, $staffHtml);

        if ($email !== '') {
            $applicantSubject = 'Application Received - ' . $companyName;
            $applicantMessage = 'Hello ' . $name . ', we received your service application for ' . $plan . '. Our team will review it and contact you soon.';
            $applicantHtml = self::wrapHtml('Application Received', '
                <p>Hello ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>
                <p>We received your service application and our team will review it soon.</p>
                <p><strong>Selected plan:</strong> ' . htmlspecialchars($plan, ENT_QUOTES, 'UTF-8') . '</p>
                <p>Thank you for choosing ' . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . '.</p>
            ', $companyName);

            self::notifyApplicant($pdo, $email, $name, $applicantSubject, $applicantMessage, $applicantHtml);
        }
    }

    public static function notifyPaymentSubmitted(
        PDO $pdo,
        int $customerId,
        int $invoiceId,
        int $paymentId,
        float $amount,
        string $method
    ): void {
        $companyName = self::getCompanyName($pdo);
        $customerName = self::resolveCustomerName($pdo, $customerId);
        $customerEmail = self::resolveCustomerEmail($pdo, $customerId);
        $amountFormatted = number_format($amount, 2);

        $staffSubject = 'Payment Submitted for Verification - ' . $companyName;
        $staffMessage = $customerName . ' submitted a payment of ₱' . $amountFormatted . ' for Invoice #' . $invoiceId . '.';
        $staffHtml = self::wrapHtml('Payment Awaiting Verification', '
            <p><strong>' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . '</strong> submitted a payment that needs verification.</p>
            <p><strong>Invoice:</strong> #' . (int)$invoiceId . '</p>
            <p><strong>Payment ID:</strong> #' . (int)$paymentId . '</p>
            <p><strong>Amount:</strong> ₱' . htmlspecialchars($amountFormatted, ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Method:</strong> ' . htmlspecialchars($method, ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Customer email:</strong> ' . htmlspecialchars($customerEmail !== '' ? $customerEmail : 'Not available', ENT_QUOTES, 'UTF-8') . '</p>
            <p>Review it in the Payments section of FusionLink.</p>
        ', $companyName);

        self::notifyStaff($pdo, 'PAYMENT_SUBMITTED', $staffSubject, $staffMessage, $staffHtml, $customerId, $invoiceId);

        if ($customerEmail !== '') {
            $subject = 'Payment Received - ' . $companyName;
            $message = 'Hello ' . $customerName . ', we received your payment submission for Invoice #' . $invoiceId . '. Our team will verify it soon.';
            $html = self::wrapHtml('Payment Submission Received', '
                <p>Hello ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>
                <p>We received your payment submission and our team will verify it soon.</p>
                <p><strong>Invoice:</strong> #' . (int)$invoiceId . '</p>
                <p><strong>Amount:</strong> ₱' . htmlspecialchars($amountFormatted, ENT_QUOTES, 'UTF-8') . '</p>
                <p><strong>Method:</strong> ' . htmlspecialchars($method, ENT_QUOTES, 'UTF-8') . '</p>
            ', $companyName);

            self::notifyCustomer($pdo, $customerId, $invoiceId, 'PAYMENT_SUBMITTED', $subject, $message, $html);
        }
    }

    public static function notifyReferralCreditEarned(
        PDO $pdo,
        int $referrerCustomerId,
        string $referredCustomerName,
        float $amount
    ): void {
        if ($referrerCustomerId <= 0) {
            return;
        }

        $companyName = self::getCompanyName($pdo);
        $referrerName = self::resolveCustomerName($pdo, $referrerCustomerId);
        $amountFormatted = number_format($amount, 2);

        $subject = 'Referral Credit Earned - ' . $companyName;
        $message = 'Hello ' . $referrerName . ', you earned a referral credit of ₱' . $amountFormatted . ' because ' . $referredCustomerName . ' was approved.';
        $html = self::wrapHtml('Referral Credit Earned', '
            <p>Hello ' . htmlspecialchars($referrerName, ENT_QUOTES, 'UTF-8') . ',</p>
            <p>Thank you for referring <strong>' . htmlspecialchars($referredCustomerName, ENT_QUOTES, 'UTF-8') . '</strong>.</p>
            <p>Your referral credit of <strong>₱' . htmlspecialchars($amountFormatted, ENT_QUOTES, 'UTF-8') . '</strong> will be applied to your next bill automatically.</p>
        ', $companyName);

        self::notifyCustomer($pdo, $referrerCustomerId, null, 'REFERRAL_CREDIT', $subject, $message, $html);
    }

    public static function notifyPortalCredentials(
        PDO $pdo,
        int $customerId,
        string $customerName,
        string $loginEmail,
        string $temporaryPassword,
        string $loginUrl
    ): bool {
        $companyName = self::getCompanyName($pdo);
        $subject = 'Your Billing Portal Login - ' . $companyName;
        $plainMessage = 'Hello ' . $customerName . ', your billing portal login is ready. URL: ' . $loginUrl . ' | Email: ' . $loginEmail . ' | Password: ' . $temporaryPassword;
        $html = self::wrapHtml('Billing Portal Access', '
            <p>Hello ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>
            <p>Your ISP billing portal account is ready. Use it to view invoices and submit payments online.</p>
            <div style="border:1px solid #c4b5fd;background:#f5f3ff;padding:18px 20px;margin:24px 0;">
                <p style="margin:6px 0;"><strong>Login URL:</strong> <a href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '</a></p>
                <p style="margin:6px 0;"><strong>Email:</strong> ' . htmlspecialchars($loginEmail, ENT_QUOTES, 'UTF-8') . '</p>
                <p style="margin:6px 0;"><strong>Temporary Password:</strong> ' . htmlspecialchars($temporaryPassword, ENT_QUOTES, 'UTF-8') . '</p>
            </div>
            <p>After signing in, a verification code will be sent to your email. Then open Billing Portal → Password to replace this temporary password.</p>
        ', $companyName);

        return self::notifyCustomer($pdo, $customerId, null, 'PORTAL_CREDENTIALS', $subject, $plainMessage, $html);
    }

    public static function notifyPortalPasswordReset(
        PDO $pdo,
        int $customerId,
        string $customerName,
        string $loginEmail,
        string $temporaryPassword,
        string $loginUrl
    ): bool {
        $companyName = self::getCompanyName($pdo);
        $subject = 'Your Billing Portal Password Was Reset - ' . $companyName;
        $plainMessage = 'Hello ' . $customerName . ', your billing portal password was reset. URL: ' . $loginUrl . ' | Email: ' . $loginEmail . ' | Temporary password: ' . $temporaryPassword;
        $html = self::wrapHtml('Portal Password Reset', '
            <p>Hello ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>
            <p>FusionLink reset your billing portal password. Use this temporary password to sign in, then change it under Billing Portal → Password.</p>
            <div style="border:1px solid #c4b5fd;background:#f5f3ff;padding:18px 20px;margin:24px 0;">
                <p style="margin:6px 0;"><strong>Login URL:</strong> <a href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '</a></p>
                <p style="margin:6px 0;"><strong>Email:</strong> ' . htmlspecialchars($loginEmail, ENT_QUOTES, 'UTF-8') . '</p>
                <p style="margin:6px 0;"><strong>Temporary Password:</strong> ' . htmlspecialchars($temporaryPassword, ENT_QUOTES, 'UTF-8') . '</p>
            </div>
        ', $companyName);

        return self::notifyCustomer($pdo, $customerId, null, 'PORTAL_PASSWORD_RESET', $subject, $plainMessage, $html);
    }

    public static function notifyExistingCustomerPortalReady(
        PDO $pdo,
        int $customerId,
        string $customerName,
        string $loginEmail,
        string $temporaryPassword,
        string $loginUrl,
        bool $credentialsEmailed
    ): void {
        $companyName = self::getCompanyName($pdo);
        $subject = 'Your Billing Portal Is Ready - ' . $companyName;
        $plainMessage = 'Hello ' . $customerName . ', your billing portal is ready. URL: ' . $loginUrl;
        if ($credentialsEmailed) {
            $plainMessage .= ' Login details were sent to ' . $loginEmail . '.';
        } else {
            $plainMessage .= ' Email: ' . $loginEmail . ' | Password: ' . $temporaryPassword;
        }

        $credentialBlock = $credentialsEmailed
            ? '<p>We sent your login details to <strong>' . htmlspecialchars($loginEmail, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
            : '<div style="border:1px solid #c4b5fd;background:#f5f3ff;padding:18px 20px;margin:24px 0;">
                <p style="margin:6px 0;"><strong>Login URL:</strong> <a href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '</a></p>
                <p style="margin:6px 0;"><strong>Email:</strong> ' . htmlspecialchars($loginEmail, ENT_QUOTES, 'UTF-8') . '</p>
                <p style="margin:6px 0;"><strong>Temporary Password:</strong> ' . htmlspecialchars($temporaryPassword, ENT_QUOTES, 'UTF-8') . '</p>
            </div>';

        $html = self::wrapHtml('Billing Portal Ready', '
            <p>Hello ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>
            <p>Thanks for confirming your account. Your online billing portal is ready.</p>
            ' . $credentialBlock . '
            <p><a href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '">Open Billing Portal</a></p>
        ', $companyName);

        self::notifyCustomer($pdo, $customerId, null, 'EXISTING_CUSTOMER_PORTAL', $subject, $plainMessage, $html);
    }

    public static function notifyExistingCustomerPortalExists(
        PDO $pdo,
        int $customerId,
        string $customerName,
        string $email,
        string $loginUrl
    ): void {
        $companyName = self::getCompanyName($pdo);
        $subject = 'Billing Portal Login Reminder - ' . $companyName;
        $plainMessage = 'Hello ' . $customerName . ', you already have billing portal access. Login: ' . $loginUrl;
        $html = self::wrapHtml('Portal Login Reminder', '
            <p>Hello ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>
            <p>You already have billing portal access linked to <strong>' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</strong>.</p>
            <p><a href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '">Open Billing Portal</a></p>
            <p>If you forgot your password, contact our office and we can reset it for you.</p>
        ', $companyName);

        self::notifyCustomer($pdo, $customerId, null, 'EXISTING_CUSTOMER_PORTAL', $subject, $plainMessage, $html);
    }

    public static function notifyExistingCustomerPendingReview(PDO $pdo, string $name, string $email): void
    {
        if ($email === '') {
            return;
        }

        $companyName = self::getCompanyName($pdo);
        $subject = 'We Received Your Details - ' . $companyName;
        $plainMessage = 'Hello ' . $name . ', we received your billing portal setup request and will verify your account soon.';
        $html = self::wrapHtml('Details Received', '
            <p>Hello ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>
            <p>Thank you for submitting your details. Our team will verify your account and email your billing portal login within 1 business day.</p>
            <p>No further action is needed right now.</p>
        ', $companyName);

        self::notifyApplicant($pdo, $email, $name, $subject, $plainMessage, $html);
    }

    public static function notifyStaffExistingCustomerRegistered(PDO $pdo, array $payload): void
    {
        $companyName = self::getCompanyName($pdo);
        $name = trim((string)($payload['name'] ?? 'Customer'));
        $email = trim((string)($payload['email'] ?? ''));
        $phone = trim((string)($payload['phone'] ?? ''));
        $address = trim((string)($payload['address'] ?? ''));
        $matched = !empty($payload['matched']);
        $portalCreated = !empty($payload['portal_created']);

        if ($matched && $portalCreated) {
            $staffSubject = 'Existing Customer Portal Activated - ' . $companyName;
            $staffMessage = $name . ' confirmed existing customer details and portal access was created automatically.';
        } elseif ($matched) {
            $staffSubject = 'Existing Customer Portal Request - ' . $companyName;
            $staffMessage = $name . ' submitted the existing customer portal form (account already had portal access).';
        } else {
            $staffSubject = 'Existing Customer Needs Review - ' . $companyName;
            $staffMessage = $name . ' submitted the existing customer form but no customer record matched their phone.';
        }

        $staffHtml = self::wrapHtml('Existing Customer Registration', '
            <p><strong>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</strong></p>
            <p><strong>Phone:</strong> ' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Address:</strong> ' . nl2br(htmlspecialchars($address !== '' ? $address : 'Not provided', ENT_QUOTES, 'UTF-8')) . '</p>
            <p><strong>Phone matched customer record:</strong> ' . ($matched ? 'Yes' : 'No') . '</p>
            <p><strong>Portal auto-created:</strong> ' . ($portalCreated ? 'Yes' : 'No') . '</p>
            <p>Review in FusionLink Inquiries if manual follow-up is needed.</p>
        ', $companyName);

        self::notifyStaff($pdo, 'EXISTING_CUSTOMER_REGISTRATION', $staffSubject, $staffMessage, $staffHtml);
    }

    public static function notifyServiceBookingCreated(PDO $pdo, array $booking, array $customer, bool $isApplicationVisit = false): void
    {
        $companyName = self::getCompanyName($pdo);
        $customerName = trim((string)($customer['customer_name'] ?? 'Customer'));
        $customerPhone = trim((string)($customer['customer_phone'] ?? ''));
        $customerEmail = trim((string)($customer['customer_email'] ?? ''));
        $address = trim((string)($customer['address'] ?? ''));
        $serviceName = trim((string)($booking['service_name'] ?? 'Service'));
        $personnelName = trim((string)($booking['personnel_name'] ?? 'Personnel'));
        $bookingDate = (string)($booking['booking_date'] ?? '');
        $startTime = (string)($booking['start_time'] ?? '');
        $endTime = (string)($booking['end_time'] ?? '');
        $dateLabel = $bookingDate !== '' ? date('F j, Y', strtotime($bookingDate)) : 'TBD';
        $timeLabel = $startTime !== ''
            ? date('g:i A', strtotime($startTime))
            : 'TBD';

        $isOcular = class_exists('ApplicationWorkflowService')
            && ApplicationWorkflowService::isOcularServiceName($serviceName);
        $isInstallation = class_exists('ApplicationWorkflowService')
            && ApplicationWorkflowService::isInstallationServiceName($serviceName);

        $visitLabel = $isOcular ? 'Ocular visit' : ($isInstallation ? 'Installation visit' : 'Service visit');
        $staffSubject = ($isApplicationVisit ? 'Application ' : '') . $visitLabel . ' Scheduled - ' . $companyName;
        $staffMessage = $customerName . ' has a scheduled ' . strtolower($visitLabel) . ' on ' . $dateLabel . ' at ' . $timeLabel . '.';
        $staffHtml = self::wrapHtml('Visit Scheduled', '
            <p><strong>' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . '</strong> has a scheduled visit.</p>
            <p><strong>Visit type:</strong> ' . htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Date:</strong> ' . htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Time:</strong> ' . htmlspecialchars($timeLabel, ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Assigned technician:</strong> ' . htmlspecialchars($personnelName, ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Phone:</strong> ' . htmlspecialchars($customerPhone, ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($customerEmail !== '' ? $customerEmail : 'Not provided', ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Address:</strong> ' . nl2br(htmlspecialchars($address !== '' ? $address : 'Not provided', ENT_QUOTES, 'UTF-8')) . '</p>
            <p>Review it in FusionLink Bookings.</p>
        ', $companyName);

        self::notifyStaff($pdo, 'SERVICE_BOOKING', $staffSubject, $staffMessage, $staffHtml);

        if ($customerEmail !== '') {
            $subject = $visitLabel . ' Scheduled - ' . $companyName;
            $plainMessage = 'Hello ' . $customerName . ', your ' . strtolower($visitLabel) . ' is scheduled on ' . $dateLabel . ' at ' . $timeLabel . '. Assigned technician: ' . $personnelName . '.';
            $html = self::wrapHtml('Visit Scheduled', '
                <p>Hello ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>
                <p>Your <strong>' . htmlspecialchars(strtolower($visitLabel), ENT_QUOTES, 'UTF-8') . '</strong> has been scheduled.</p>
                <p><strong>Visit type:</strong> ' . htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8') . '</p>
                <p><strong>Date:</strong> ' . htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8') . '</p>
                <p><strong>Time:</strong> ' . htmlspecialchars($timeLabel, ENT_QUOTES, 'UTF-8') . '</p>
                <p><strong>Assigned technician:</strong> ' . htmlspecialchars($personnelName, ENT_QUOTES, 'UTF-8') . '</p>
                <p><strong>Service address:</strong> ' . nl2br(htmlspecialchars($address !== '' ? $address : 'Not provided', ENT_QUOTES, 'UTF-8')) . '</p>
                ' . ($isInstallation
                    ? '<p>Please be available at the service address during the scheduled time so our team can complete the installation.</p>'
                    : '<p>Please be available at the service address during the scheduled time so our team can complete the ocular/site assessment.</p>') . '
            ', $companyName);

            self::notifyApplicant($pdo, $customerEmail, $customerName, $subject, $plainMessage, $html);
        }
    }

    public static function notifyVisitCompleted(PDO $pdo, array $booking): void
    {
        $companyName = self::getCompanyName($pdo);
        $customerName = trim((string)($booking['customer_name'] ?? 'Customer'));
        $customerEmail = trim((string)($booking['customer_email'] ?? ''));
        $serviceName = trim((string)($booking['service_name'] ?? 'Service'));
        $isOcular = class_exists('ApplicationWorkflowService')
            && ApplicationWorkflowService::isOcularServiceName($serviceName);
        $isInstallation = class_exists('ApplicationWorkflowService')
            && ApplicationWorkflowService::isInstallationServiceName($serviceName);

        if ($customerEmail === '') {
            return;
        }

        if ($isOcular) {
            $subject = 'Ocular Visit Completed - ' . $companyName;
            $plainMessage = 'Hello ' . $customerName . ', your ocular/site assessment has been completed. Our team will contact you to schedule installation.';
            $html = self::wrapHtml('Ocular Completed', '
                <p>Hello ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>
                <p>Your <strong>ocular / site assessment</strong> has been marked as completed.</p>
                <p>Our team will contact you soon to schedule the installation visit.</p>
            ', $companyName);
        } elseif ($isInstallation) {
            $subject = 'Installation Completed - ' . $companyName;
            $plainMessage = 'Hello ' . $customerName . ', your installation has been completed. Your account setup will follow shortly.';
            $html = self::wrapHtml('Installation Completed', '
                <p>Hello ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>
                <p>Your <strong>installation visit</strong> has been marked as completed.</p>
                <p>We are now finalizing your customer account and will email your billing portal login details shortly.</p>
            ', $companyName);
        } else {
            $subject = 'Service Visit Completed - ' . $companyName;
            $plainMessage = 'Hello ' . $customerName . ', your scheduled service visit has been completed.';
            $html = self::wrapHtml('Visit Completed', '
                <p>Hello ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>
                <p>Your scheduled <strong>' . htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8') . '</strong> visit has been marked as completed.</p>
            ', $companyName);
        }

        self::notifyApplicant($pdo, $customerEmail, $customerName, $subject, $plainMessage, $html);
    }
}
