<?php

if (file_exists(__DIR__ . '/../Services/ActivityLogger.php')) {
    require_once __DIR__ . '/../Services/ActivityLogger.php';
}

if (file_exists(__DIR__ . '/../Services/MailService.php')) {
    require_once __DIR__ . '/../Services/MailService.php';
}

if (file_exists(__DIR__ . '/../Services/RememberMeService.php')) {
    require_once __DIR__ . '/../Services/RememberMeService.php';
}

class AuthController
{
    public function loginForm()
    {
        if (!empty($_SESSION['otp_pending'])) {
            unset($_SESSION['otp_pending']);
        }

        View::render('auth/login', [
            'title' => 'Login'
        ]);
    }

    private function renderMessagePage(
        string $title,
        string $message,
        string $buttonText = 'Back to login',
        string $buttonLink = '',
        bool $isError = true
    ): void {
        $buttonLink = url($buttonLink === '' ? '/login' : $buttonLink);

        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . ' - FUSIONLINK</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(59, 130, 246, 0.12), transparent 30%),
                radial-gradient(circle at bottom right, rgba(37, 99, 235, 0.10), transparent 28%),
                linear-gradient(90deg, #06122b 0%, #081632 45%, #0d1f45 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
            color: #0f172a;
        }

        .wrapper {
            width: 100%;
            max-width: 1180px;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 44px;
            align-items: center;
        }

        .hero {
            color: #ffffff;
            padding: 12px 4px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            padding: 10px 16px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.16);
            background: rgba(255,255,255,0.06);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #dbeafe;
            margin-bottom: 24px;
        }

        .hero h1 {
            margin: 0 0 18px;
            font-size: 72px;
            line-height: 0.95;
            font-weight: 800;
            letter-spacing: -2px;
            color: #ffffff;
        }

        .hero p {
            margin: 0 0 26px;
            max-width: 640px;
            font-size: 17px;
            line-height: 1.75;
            color: rgba(255,255,255,0.90);
        }

        .tips {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .tips li {
            position: relative;
            padding-left: 26px;
            margin-bottom: 14px;
            font-size: 15px;
            line-height: 1.6;
            color: rgba(255,255,255,0.92);
        }

        .tips li::before {
            content: "";
            position: absolute;
            left: 0;
            top: 9px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.16);
        }

        .card {
            background: #f8fafc;
            border-radius: 34px;
            padding: 34px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.28);
            border: 1px solid rgba(255,255,255,0.4);
        }

        .status-icon {
            width: 72px;
            height: 72px;
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #dbeafe;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            font-size: 34px;
            margin-bottom: 18px;
        }

        .card h2 {
            margin: 0 0 10px;
            font-size: 28px;
            line-height: 1.15;
            color: #0f172a;
        }

        .subtitle {
            margin: 0 0 22px;
            font-size: 16px;
            line-height: 1.7;
            color: #64748b;
        }

        .message-box {
            background: #eef4ff;
            border: 1px solid #dbeafe;
            border-radius: 18px;
            padding: 18px;
            margin-bottom: 22px;
        }

        .message-label {
            display: block;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #2563eb;
        }

        .message-text {
            margin: 0;
            font-size: 15px;
            line-height: 1.7;
            color: #0f172a;
            font-weight: 600;
        }

        .card-tips {
            margin: 0 0 24px;
            padding-left: 20px;
            color: #475569;
            font-size: 14px;
            line-height: 1.8;
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
            min-height: 54px;
            padding: 14px 20px;
            border-radius: 16px;
            background: linear-gradient(90deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.14);
            transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.18);
            opacity: 0.98;
        }

        .footer-note {
            margin-top: 18px;
            text-align: center;
            font-size: 14px;
            color: #94a3b8;
        }

        @media (max-width: 980px) {
            .wrapper {
                grid-template-columns: 1fr;
                gap: 28px;
            }

            .hero h1 {
                font-size: 54px;
            }

            .card {
                max-width: 620px;
                width: 100%;
                margin: 0 auto;
            }
        }

        @media (max-width: 640px) {
            body {
                padding: 18px;
            }

            .card {
                padding: 24px;
                border-radius: 24px;
            }

            .hero h1 {
                font-size: 42px;
                letter-spacing: -1px;
            }

            .hero p {
                font-size: 15px;
            }

            .card h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="hero">
            <div class="eyebrow">Fusionlink Billing Platform</div>
            <h1>Manage billing,<br>customers, and<br>payments in one<br>place.</h1>
            <p>FUSIONLINK helps you manage subscribers, generate invoices, verify payments, and track outstanding balances with a clean and modern workflow.</p>

            <ul class="tips">
                <li>Customer and subscription management</li>
                <li>Invoice generation and PDF export</li>
                <li>Payment verification and email notifications</li>
                <li>Outstanding and revenue reporting</li>
            </ul>
        </div>

        <div class="card">
            <div class="status-icon">' . ($isError ? '⚠' : 'ℹ') . '</div>

            <h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>
            <p class="subtitle">We were unable to complete your request at this time.</p>

            <div class="message-box">
                <span class="message-label">Details</span>
                <p class="message-text">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>
            </div>

            <ul class="card-tips">
                <li>Double-check the email address you entered.</li>
                <li>Make sure your password is typed correctly.</li>
                <li>Check whether Caps Lock is turned on.</li>
            </ul>

            <div class="actions">
                <a href="' . htmlspecialchars($buttonLink, ENT_QUOTES, 'UTF-8') . '" class="btn-primary">' . htmlspecialchars($buttonText, ENT_QUOTES, 'UTF-8') . '</a>
            </div>

            <p class="footer-note">Secure access to FUSIONLINK</p>
        </div>
    </div>
</body>
</html>';
    }

    private function generateOtpCode(): string
    {
        return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function createOtpTable(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS login_otps (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                email VARCHAR(255) NOT NULL,
                otp_code VARCHAR(10) NOT NULL,
                expires_at DATETIME NOT NULL,
                is_used TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function clearOtpRecords(PDO $pdo, int $userId): void
    {
        $this->createOtpTable($pdo);

        $stmt = $pdo->prepare("
            DELETE FROM login_otps
            WHERE user_id = :user_id
        ");
        $stmt->execute([
            'user_id' => $userId
        ]);
    }

    private function createOtpRecord(PDO $pdo, array $user, string $otpCode): void
    {
        $this->createOtpTable($pdo);
        $this->clearOtpRecords($pdo, (int)$user['id']);

        $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $stmt = $pdo->prepare("
            INSERT INTO login_otps (user_id, email, otp_code, expires_at, is_used)
            VALUES (:user_id, :email, :otp_code, :expires_at, 0)
        ");
        $stmt->execute([
            'user_id' => (int)$user['id'],
            'email' => (string)$user['email'],
            'otp_code' => $otpCode,
            'expires_at' => $expiresAt,
        ]);
    }

    private function setOtpSession(array $user, bool $rememberMe = false): void
    {
        $_SESSION['otp_pending'] = [
            'id' => (int)$user['id'],
            'customer_id' => $user['customer_id'] ?? null,
            'email' => (string)$user['email'],
            'role' => (string)$user['role'],
            'remember_me' => $rememberMe,
        ];
    }

    private function clearOtpSession(): void
    {
        unset($_SESSION['otp_pending']);
    }

    private function getPendingOtpUser(): ?array
    {
        $pending = $_SESSION['otp_pending'] ?? null;
        return is_array($pending) ? $pending : null;
    }

    private function sendOtpEmail(string $toEmail, string $toName, string $otpCode): bool
    {
        if (!class_exists('MailService')) {
            return false;
        }

        try {
            $mailService = new MailService();

            $subject = 'FUSIONLINK Login OTP';
            $body = '
                <div style="margin:0;padding:24px;background:#f3f4f8;font-family:Arial,Helvetica,sans-serif;color:#111827;">
                    <div style="max-width:640px;margin:0 auto;background:#ffffff;padding:32px 28px;border:1px solid #e5e7eb;border-radius:12px;">
                        <div style="font-size:24px;letter-spacing:6px;color:#4b5563;font-weight:700;margin-bottom:24px;">
                            FUSIONLINK
                        </div>

                        <div style="font-size:28px;font-weight:800;color:#000000;line-height:1.2;margin-bottom:18px;">
                            Your One-Time Password
                        </div>

                        <div style="font-size:16px;line-height:1.8;color:#111827;margin-bottom:20px;">
                            Use the verification code below to complete your sign in.
                        </div>

                        <div style="margin:24px 0;padding:22px;border:1px dashed #cbd5e1;border-radius:12px;text-align:center;background:#f8fafc;">
                            <div style="font-size:36px;font-weight:800;letter-spacing:10px;color:#111827;">' . htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8') . '</div>
                        </div>

                        <div style="font-size:14px;line-height:1.8;color:#475569;">
                            This code will expire in 5 minutes. If you did not try to sign in, you can ignore this email.
                        </div>

                        <div style="margin-top:24px;font-size:13px;color:#64748b;border-top:1px solid #e5e7eb;padding-top:16px;">
                            FUSIONLINK secure login verification
                        </div>
                    </div>
                </div>
            ';

            return $mailService->send(
                $toEmail,
                $toName !== '' ? $toName : $toEmail,
                $subject,
                $body
            );
        } catch (Throwable $e) {
            error_log('AuthController@sendOtpEmail error: ' . $e->getMessage());
            return false;
        }
    }

    private function completeLogin(array $user, string $logMessage, bool $rememberMe = false): void
    {
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'customer_id' => $user['customer_id'] ?? null,
            'email' => (string)$user['email'],
            'role' => (string)$user['role'],
        ];

        if ($rememberMe && class_exists('RememberMeService')) {
            try {
                RememberMeService::issue(Database::connect(), (int)$user['id']);
                $logMessage .= ' Remember-me enabled for 30 days.';
            } catch (Throwable $e) {
                error_log('AuthController@completeLogin remember-me error: ' . $e->getMessage());
            }
        }

        if (class_exists('ActivityLogger')) {
            ActivityLogger::log(
                (int)$user['id'],
                (string)$user['email'],
                (string)$user['role'],
                'Auth',
                'LOGIN',
                $logMessage
            );
        }

        if (($user['role'] ?? '') === 'ROLE_CUSTOMER') {
            redirect('/payments/create');
        }

        redirect('/dashboard');
    }

    public function login()
    {
        if (!rate_limit('login', 8, 900)) {
            $this->renderMessagePage('Too Many Attempts', 'Too many login attempts. Please wait and try again.');
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $this->renderMessagePage('Invalid Login', 'Email and password are required.');
            return;
        }

        try {
            $pdo = Database::connect();

            $stmt = $pdo->prepare("
                SELECT id, customer_id, email, password_hash, role
                FROM users
                WHERE email = :email
                LIMIT 1
            ");
            $stmt->execute(['email' => $email]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $this->renderMessagePage('Invalid Login', 'Email or password is incorrect.');
                return;
            }

            $storedHash = $user['password_hash'] ?? '';
            $loginOk = false;

            if ($storedHash !== '' && password_verify($password, $storedHash)) {
                $loginOk = true;

                if (password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);

                    $update = $pdo->prepare("
                        UPDATE users
                        SET password_hash = :password_hash
                        WHERE id = :id
                    ");
                    $update->execute([
                        'password_hash' => $newHash,
                        'id' => $user['id']
                    ]);
                }
            } else {
                $incomingSha256 = hash('sha256', $password);

                if ($storedHash !== '' && hash_equals($storedHash, $incomingSha256)) {
                    $loginOk = true;

                    $newHash = password_hash($password, PASSWORD_DEFAULT);

                    $update = $pdo->prepare("
                        UPDATE users
                        SET password_hash = :password_hash
                        WHERE id = :id
                    ");
                    $update->execute([
                        'password_hash' => $newHash,
                        'id' => $user['id']
                    ]);
                }
            }

            if (!$loginOk) {
                $this->renderMessagePage('Invalid Login', 'Email or password is incorrect.');
                return;
            }

            if (!otp_login_required($user)) {
                $this->completeLogin($user, 'User logged in successfully without OTP.', remember_me_requested());
                return;
            }

            $otpCode = $this->generateOtpCode();

            $this->createOtpRecord($pdo, $user, $otpCode);
            $this->setOtpSession($user, remember_me_requested());

            $sent = $this->sendOtpEmail(
                (string)$user['email'],
                (string)$user['email'],
                $otpCode
            );

            if (!$sent) {
                if (is_local_request()) {
                    redirect('/verify-otp?success=' . urlencode('Email is unavailable on localhost. Use the OTP code shown below.'));
                    return;
                }

                $this->clearOtpSession();
                $this->clearOtpRecords($pdo, (int)$user['id']);
                $this->renderMessagePage('OTP Send Failed', 'Unable to send the verification code right now. Please try again.');
                return;
            }

            redirect('/verify-otp?success=' . urlencode('A verification code has been sent to your email.'));
        } catch (Throwable $e) {
            error_log('AuthController@login error: ' . $e->getMessage());
            $this->renderMessagePage('System Error', 'Unable to process login right now.', 'Back to login', '/login', false);
        }
    }

    public function verifyOtpForm(): void
    {
        $pendingUser = $this->getPendingOtpUser();

        if (!$pendingUser) {
            redirect('/login');
            exit;
        }

        View::render('auth/verify_otp', [
            'title' => 'Verify OTP',
            'email' => (string)($pendingUser['email'] ?? ''),
            'rememberMe' => !empty($pendingUser['remember_me']),
            'success' => trim((string)($_GET['success'] ?? '')),
            'error' => trim((string)($_GET['error'] ?? '')),
        ]);
    }

    public function verifyOtp(): void
    {
        if (!rate_limit('verify_otp', 10, 600)) {
            redirect('/verify-otp?error=' . urlencode('Too many attempts. Please wait and try again.'));
        }

        $pendingUser = $this->getPendingOtpUser();

        if (!$pendingUser) {
            redirect('/login');
            exit;
        }

        $otpCode = trim((string)($_POST['otp_code'] ?? ''));

        if ($otpCode === '') {
            redirect('/verify-otp?error=' . urlencode('OTP code is required.'));
        }

        try {
            $pdo = Database::connect();
            $this->createOtpTable($pdo);

            $stmt = $pdo->prepare("
                SELECT id, otp_code, expires_at, is_used
                FROM login_otps
                WHERE user_id = :user_id
                ORDER BY id DESC
                LIMIT 1
            ");
            $stmt->execute([
                'user_id' => (int)$pendingUser['id']
            ]);

            $otpRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$otpRow) {
                redirect('/verify-otp?error=' . urlencode('No OTP request found. Please sign in again.'));
            }

            if ((int)($otpRow['is_used'] ?? 0) === 1) {
                redirect('/verify-otp?error=' . urlencode('This OTP was already used. Please sign in again.'));
            }

            $expiresAt = strtotime((string)($otpRow['expires_at'] ?? ''));
            if ($expiresAt === false || $expiresAt < time()) {
                redirect('/verify-otp?error=' . urlencode('OTP code already expired. Please resend a new code.'));
            }

            if (!hash_equals((string)$otpRow['otp_code'], $otpCode)) {
                redirect('/verify-otp?error=' . urlencode('Invalid OTP code.'));
            }

            $update = $pdo->prepare("
                UPDATE login_otps
                SET is_used = 1
                WHERE id = :id
            ");
            $update->execute([
                'id' => (int)$otpRow['id']
            ]);

            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id' => (int)$pendingUser['id'],
                'customer_id' => $pendingUser['customer_id'] ?? null,
                'email' => (string)$pendingUser['email'],
                'role' => (string)$pendingUser['role']
            ];

            $rememberMe = !empty($pendingUser['remember_me']);
            $this->clearOtpSession();

            if ($rememberMe && class_exists('RememberMeService')) {
                try {
                    RememberMeService::issue($pdo, (int)$pendingUser['id']);
                } catch (Throwable $e) {
                    error_log('AuthController@verifyOtp remember-me error: ' . $e->getMessage());
                }
            }

            if (class_exists('ActivityLogger')) {
                ActivityLogger::log(
                    (int)$pendingUser['id'],
                    (string)$pendingUser['email'],
                    (string)$pendingUser['role'],
                    'Auth',
                    'LOGIN_OTP',
                    'User logged in successfully via OTP verification.'
                        . ($rememberMe ? ' Remember-me enabled for 30 days.' : '')
                );
            }

            if (($pendingUser['role'] ?? '') === 'ROLE_CUSTOMER') {
                redirect('/payments/create');
            } else {
                redirect('/dashboard');
            }
            exit;
        } catch (Throwable $e) {
            error_log('AuthController@verifyOtp error: ' . $e->getMessage());
            redirect('/verify-otp?error=' . urlencode('Unable to verify OTP right now.'));
        }
    }

    public function resendOtp(): void
    {
        if (!rate_limit('resend_otp', 5, 600)) {
            redirect('/verify-otp?error=' . urlencode('Too many resend attempts. Please wait and try again.'));
        }

        $pendingUser = $this->getPendingOtpUser();

        if (!$pendingUser) {
            redirect('/login');
            exit;
        }

        try {
            $pdo = Database::connect();

            $otpCode = $this->generateOtpCode();
            $this->createOtpRecord($pdo, $pendingUser, $otpCode);

            $sent = $this->sendOtpEmail(
                (string)$pendingUser['email'],
                (string)$pendingUser['email'],
                $otpCode
            );

            if (!$sent) {
                redirect('/verify-otp?error=' . urlencode('Unable to resend OTP right now.'));
            }

            redirect('/verify-otp?success=' . urlencode('A new OTP code has been sent to your email.'));
        } catch (Throwable $e) {
            error_log('AuthController@resendOtp error: ' . $e->getMessage());
            redirect('/verify-otp?error=' . urlencode('Unable to resend OTP right now.'));
        }
    }
}
