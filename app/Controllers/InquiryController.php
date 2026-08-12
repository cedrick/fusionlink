<?php

if (file_exists(__DIR__ . '/../Services/ActivityLogger.php')) {
    require_once __DIR__ . '/../Services/ActivityLogger.php';
}

if (file_exists(__DIR__ . '/../Services/MailService.php')) {
    require_once __DIR__ . '/../Services/MailService.php';
}

if (file_exists(__DIR__ . '/../Services/EmailAlertService.php')) {
    require_once __DIR__ . '/../Services/EmailAlertService.php';
}

if (file_exists(__DIR__ . '/../Services/ReferralService.php')) {
    require_once __DIR__ . '/../Services/ReferralService.php';
}

if (file_exists(__DIR__ . '/../Services/CustomerPortalService.php')) {
    require_once __DIR__ . '/../Services/CustomerPortalService.php';
}

if (file_exists(__DIR__ . '/../Services/ExistingCustomerService.php')) {
    require_once __DIR__ . '/../Services/ExistingCustomerService.php';
}

if (file_exists(__DIR__ . '/../Services/ApplicationWorkflowService.php')) {
    require_once __DIR__ . '/../Services/ApplicationWorkflowService.php';
}

if (file_exists(__DIR__ . '/../Services/BillingCycleService.php')) {
    require_once __DIR__ . '/../Services/BillingCycleService.php';
}

class InquiryController
{
    private function requireLogin(): void
    {
        if (!isset($_SESSION['user'])) {
            redirect('/login');
            exit;
        }
    }

    private function db(): PDO
    {
        $config = require __DIR__ . '/../../config/database.php';

        $dbName = $config['db'] ?? ($config['name'] ?? null);
        if (!$dbName) {
            die("Database config error: missing 'db' (or 'name') in config/database.php");
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

    private function setFlash(string $type, string $message): void
    {
        $_SESSION['inquiry_flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    private function getFlash(): ?array
    {
        $flash = $_SESSION['inquiry_flash'] ?? null;
        unset($_SESSION['inquiry_flash']);
        return is_array($flash) ? $flash : null;
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', trim($phone));
    }

    private function isValidPhone(string $phone): bool
    {
        return (bool)preg_match('/^09\d{9}$/', $phone);
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
            $pdo->exec("ALTER TABLE settings ADD COLUMN billing_due_day INT NOT NULL DEFAULT 1");
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
            error_log('InquiryController@getBillingDueDay error: ' . $e->getMessage());
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

    private function parsePlanDetails(array $row): ?array
    {
        $rawPlan = trim((string)($row['plan'] ?? ''));
        if ($rawPlan === '' || $this->isPortalSetupInquiry($rawPlan)) {
            return null;
        }

        $normalizedRaw = $this->normalizePlanMatchKey($rawPlan);

        $stmt = $this->db()->query("
            SELECT id, name, speed, price
            FROM plans
            ORDER BY id DESC
        ");
        $plans = $stmt->fetchAll();

        foreach ($plans as $plan) {
            $name = trim((string)($plan['name'] ?? ''));
            $speed = trim((string)($plan['speed'] ?? ''));
            $price = number_format((float)($plan['price'] ?? 0), 2);

            $candidateA = $name . ' - ' . $speed . ' - ₱' . $price;
            $candidateB = $name . ' - ' . $speed . ' - PHP ' . $price;
            $candidateC = $name;

            if (
                strcasecmp($rawPlan, $candidateA) === 0 ||
                strcasecmp($rawPlan, $candidateB) === 0 ||
                strcasecmp($rawPlan, $candidateC) === 0 ||
                stripos($rawPlan, $name) !== false ||
                $this->normalizePlanMatchKey($candidateA) === $normalizedRaw ||
                $this->normalizePlanMatchKey($candidateB) === $normalizedRaw
            ) {
                return [
                    'id' => (int)$plan['id'],
                    'name' => $name,
                    'speed' => $speed,
                    'price' => (float)$plan['price'],
                ];
            }
        }

        return null;
    }

    private function normalizePlanMatchKey(string $value): string
    {
        $value = strtolower($value);
        $value = str_replace(['₱', 'php', ',', ' '], '', $value);

        return $value;
    }

    private function isPortalSetupInquiry(string $planLabel): bool
    {
        $planLabel = trim($planLabel);
        if ($planLabel === '') {
            return false;
        }

        if (class_exists('ExistingCustomerService')
            && strcasecmp($planLabel, ExistingCustomerService::PLAN_LABEL) === 0) {
            return true;
        }

        return stripos($planLabel, 'existing customer') !== false
            && stripos($planLabel, 'portal') !== false;
    }

    private function convertPortalSetupInquiry(PDO $pdo, int $inquiryId, array $row): string
    {
        if (!class_exists('CustomerPortalService')) {
            throw new RuntimeException('Portal service is unavailable.');
        }

        $fullName = trim((string)($row['name'] ?? ''));
        $email = trim((string)($row['email'] ?? ''));
        $phone = $this->normalizePhone((string)($row['phone'] ?? ''));
        $address = trim((string)($row['address'] ?? ''));

        if ($fullName === '') {
            throw new RuntimeException('Inquiry full name is missing.');
        }

        if (!$this->isValidPhone($phone)) {
            throw new RuntimeException('Inquiry phone number is invalid. It must start with 09 and contain 11 digits.');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('A valid email is required to create the customer and portal login.');
        }

        $existingStmt = $pdo->prepare('
            SELECT id, full_name, email, phone
            FROM customers
            WHERE phone = ?
            LIMIT 1
        ');
        $existingStmt->execute([$phone]);
        $existingCustomer = $existingStmt->fetch();

        if ($existingCustomer) {
            $customerId = (int)($existingCustomer['id'] ?? 0);
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

            $updateCustomer = $pdo->prepare('
                UPDATE customers
                SET full_name = ?, email = ?, address = COALESCE(NULLIF(?, ""), address), status = "ACTIVE"
                WHERE id = ?
            ');
            $updateCustomer->execute([$fullName, $email, $address, $customerId]);
        } else {
            $emailCheck = $pdo->prepare('
                SELECT id
                FROM customers
                WHERE email = ?
                LIMIT 1
            ');
            $emailCheck->execute([$email]);
            if ($emailCheck->fetch()) {
                throw new RuntimeException('A customer with the same email already exists.');
            }

            $insertCustomer = $pdo->prepare('
                INSERT INTO customers (full_name, email, phone, address, status)
                VALUES (?, ?, ?, ?, "ACTIVE")
            ');
            $insertCustomer->execute([
                $fullName,
                $email,
                $phone,
                $address !== '' ? $address : null,
            ]);

            $customerId = (int)$pdo->lastInsertId();
            if ($customerId <= 0) {
                throw new RuntimeException('Failed to create customer.');
            }
        }

        $portalStatus = CustomerPortalService::getPortalStatus($pdo, $customerId);
        $portalMessage = '';

        if ($portalStatus['has_portal']) {
            $portalMessage = ' Portal login already exists for this customer.';
            if (class_exists('EmailAlertService')) {
                $loginUrl = function_exists('absolute_url') ? absolute_url('/login') : url('/login');
                EmailAlertService::notifyExistingCustomerPortalExists($pdo, $customerId, $fullName, $email, $loginUrl);
            }
        } else {
            $portalResult = CustomerPortalService::activatePortal($pdo, $customerId, true);
            if ($portalResult['mail_sent']) {
                $portalMessage = ' Portal login created and emailed to ' . $portalResult['email'] . '.';
            } else {
                $portalMessage = ' Portal login created. Email: ' . $portalResult['email'] . ' | Password: ' . $portalResult['password'];
            }
        }

        $updateInquiry = $pdo->prepare("
            UPDATE service_requests
            SET status = 'CONVERTED',
                email_sent = 1,
                converted_at = NOW()
            WHERE id = ?
        ");
        $updateInquiry->execute([$inquiryId]);

        if (class_exists('ActivityLogger')) {
            ActivityLogger::logSession(
                'Inquiries',
                'REGISTER_CUSTOMER',
                'Converted existing-customer inquiry ID ' . $inquiryId . ' into customer ID ' . $customerId . ' with portal access.'
            );
        }

        return 'Existing customer inquiry converted successfully.' . $portalMessage;
    }

    public function index(): void
    {
        $this->requireLogin();

        try {
            $pdo = $this->db();

            $page = (int)($_GET['page'] ?? 1);
            $search = trim((string)($_GET['search'] ?? ''));
            $statusFilter = strtoupper(trim((string)($_GET['status'] ?? '')));
            $sortBy = trim((string)($_GET['sort_by'] ?? 'created_at'));
            $sortDir = strtoupper(trim((string)($_GET['sort_dir'] ?? 'DESC')));
            $perPage = 20;

            if ($page < 1) {
                $page = 1;
            }

            $allowedStatus = ['', 'PENDING', 'CONVERTED', 'REJECTED'];
            if (!in_array($statusFilter, $allowedStatus, true)) {
                $statusFilter = '';
            }

            $allowedSort = [
                'id' => 'id',
                'name' => 'name',
                'email' => 'email',
                'phone' => 'phone',
                'plan' => 'plan',
                'status' => 'status',
                'created_at' => 'created_at',
            ];

            if (!isset($allowedSort[$sortBy])) {
                $sortBy = 'created_at';
            }

            if (!in_array($sortDir, ['ASC', 'DESC'], true)) {
                $sortDir = 'DESC';
            }

            $where = [];
            $params = [];

            if ($search !== '') {
                $where[] = "(name LIKE :search OR email LIKE :search OR phone LIKE :search OR address LIKE :search OR plan LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }

            if ($statusFilter !== '') {
                $where[] = "status = :status";
                $params[':status'] = $statusFilter;
            }

            $whereSql = '';
            if (!empty($where)) {
                $whereSql = 'WHERE ' . implode(' AND ', $where);
            }

            $countSql = "SELECT COUNT(*) FROM service_requests {$whereSql}";
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
                SELECT id, name, email, phone, address, plan, status, email_sent, converted_at, created_at
                FROM service_requests
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

            $rows = $stmt->fetchAll();

            $workflowStates = [];
            if (class_exists('ApplicationWorkflowService') && $rows !== []) {
                $workflowStates = ApplicationWorkflowService::getWorkflowStatesForInquiries(
                    $pdo,
                    array_column($rows, 'id')
                );
            }

            View::render('inquiries/index', [
                'title' => 'Inquiries',
                'rows' => $rows,
                'workflowStates' => $workflowStates,
                'page' => $page,
                'perPage' => $perPage,
                'totalRows' => $totalRows,
                'totalPages' => $totalPages,
                'search' => $search,
                'statusFilter' => $statusFilter,
                'sortBy' => $sortBy,
                'sortDir' => $sortDir,
                'flash' => $this->getFlash(),
            ]);
        } catch (Throwable $e) {
            error_log('InquiryController@index error: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to load inquiries.');
            redirect('/dashboard');
            exit;
        }
    }

    public function registerCustomer(): void
    {
        $this->requireLogin();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->setFlash('error', 'Invalid inquiry ID.');
            redirect('/inquiries');
            exit;
        }

        $pdo = $this->db();

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT *
                FROM service_requests
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([$id]);
            $row = $stmt->fetch();

            if (!$row) {
                throw new RuntimeException('Inquiry not found.');
            }

            $status = strtoupper((string)($row['status'] ?? 'PENDING'));
            if ($status === 'CONVERTED') {
                throw new RuntimeException('This inquiry is already converted.');
            }

            $fullName = trim((string)($row['name'] ?? ''));
            $email    = trim((string)($row['email'] ?? ''));
            $phone    = $this->normalizePhone((string)($row['phone'] ?? ''));
            $address  = trim((string)($row['address'] ?? ''));

            if ($fullName === '') {
                throw new RuntimeException('Inquiry full name is missing.');
            }

            if (!$this->isValidPhone($phone)) {
                throw new RuntimeException('Inquiry phone number is invalid. It must start with 09 and contain 11 digits.');
            }

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('A valid email is required to create the customer and portal login.');
            }

            $rawPlan = trim((string)($row['plan'] ?? ''));
            if ($this->isPortalSetupInquiry($rawPlan)) {
                $successMessage = $this->convertPortalSetupInquiry($pdo, $id, $row);
                $pdo->commit();
                $this->setFlash('success', $successMessage);
                redirect('/inquiries');
                exit;
            }

            if (class_exists('ApplicationWorkflowService')
                && !ApplicationWorkflowService::canConvertPlanApplication($pdo, $id)) {
                throw new RuntimeException(
                    'Complete the installation visit before converting this applicant to a customer. '
                    . 'Schedule installation, assign a technician, then mark the job as done.'
                );
            }

            $successMessage = $this->convertPlanApplicationRecord($pdo, $id, $row);
            $pdo->commit();

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession(
                    'Inquiries',
                    'REGISTER_CUSTOMER',
                    'Converted inquiry ID ' . $id . ' into a customer account after installation was completed.'
                );
            }

            $this->setFlash('success', $successMessage);
            redirect('/inquiries');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('InquiryController@registerCustomer error: ' . $e->getMessage());
            $this->setFlash('error', 'Register customer failed: ' . $e->getMessage());
            redirect('/inquiries');
            exit;
        }
    }

    public function autoConvertAfterInstallation(PDO $pdo, int $inquiryId): string
    {
        if ($inquiryId <= 0) {
            throw new RuntimeException('Invalid inquiry ID.');
        }

        if (class_exists('ApplicationWorkflowService')
            && !ApplicationWorkflowService::canConvertPlanApplication($pdo, $inquiryId)) {
            throw new RuntimeException('Installation must be completed before converting this applicant.');
        }

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM service_requests WHERE id = ? LIMIT 1');
            $stmt->execute([$inquiryId]);
            $row = $stmt->fetch();

            if (!$row) {
                throw new RuntimeException('Inquiry not found.');
            }

            $status = strtoupper((string)($row['status'] ?? 'PENDING'));
            if ($status === 'CONVERTED') {
                throw new RuntimeException('This inquiry is already converted.');
            }

            if ($status === 'REJECTED') {
                throw new RuntimeException('Rejected inquiries cannot be converted.');
            }

            $rawPlan = trim((string)($row['plan'] ?? ''));
            if ($this->isPortalSetupInquiry($rawPlan)) {
                throw new RuntimeException('Portal setup inquiries use a different conversion flow.');
            }

            $message = $this->convertPlanApplicationRecord($pdo, $inquiryId, $row);
            $pdo->commit();

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession(
                    'Inquiries',
                    'AUTO_CONVERT',
                    'Auto-converted inquiry ID ' . $inquiryId . ' after installation was marked done.'
                );
            }

            return $message;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    private function convertPlanApplicationRecord(PDO $pdo, int $id, array $row): string
    {
        $fullName = trim((string)($row['name'] ?? ''));
        $email    = trim((string)($row['email'] ?? ''));
        $phone    = $this->normalizePhone((string)($row['phone'] ?? ''));
        $address  = trim((string)($row['address'] ?? ''));

        if ($fullName === '') {
            throw new RuntimeException('Inquiry full name is missing.');
        }

        if (!$this->isValidPhone($phone)) {
            throw new RuntimeException('Inquiry phone number is invalid. It must start with 09 and contain 11 digits.');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('A valid email is required to create the customer and portal login.');
        }

        $planDetails = $this->parsePlanDetails($row);
        if (!$planDetails || (int)$planDetails['id'] <= 0) {
            $rawPlan = trim((string)($row['plan'] ?? ''));
            throw new RuntimeException('Could not match the inquiry plan to an existing plan. Plan on file: "' . $rawPlan . '". Edit the inquiry plan text or add the plan in Plans first.');
        }

        $checkExistingCustomer = $pdo->prepare("
            SELECT id
            FROM customers
            WHERE (
                email IS NOT NULL AND email <> '' AND email = :email
            ) OR phone = :phone
            LIMIT 1
        ");
        $checkExistingCustomer->execute([
            ':email' => $email,
            ':phone' => $phone,
        ]);
        $existingCustomer = $checkExistingCustomer->fetch();

        if ($existingCustomer) {
            throw new RuntimeException('A customer with the same email or phone already exists.');
        }

        $insertCustomer = $pdo->prepare("
            INSERT INTO customers (full_name, email, phone, address, status)
            VALUES (?, ?, ?, ?, 'ACTIVE')
        ");
        $insertCustomer->execute([
            $fullName,
            $email !== '' ? $email : null,
            $phone,
            $address !== '' ? $address : null,
        ]);

        $customerId = (int)$pdo->lastInsertId();
        if ($customerId <= 0) {
            throw new RuntimeException('Failed to create customer.');
        }

        $referralResult = null;
        if (class_exists('ReferralService')) {
            ReferralService::ensureSchema($pdo);
            $referralResult = ReferralService::processInquiryReferral($pdo, $row, $customerId, $phone);
        }

        $startDate = date('Y-m-d');

        if (class_exists('BillingCycleService')) {
            BillingCycleService::ensureSchema($pdo);
        }

        $insertSubscription = $pdo->prepare("
            INSERT INTO subscriptions (customer_id, plan_id, start_date, billing_type, status)
            VALUES (?, ?, ?, ?, 'ACTIVE')
        ");
        $insertSubscription->execute([
            $customerId,
            (int)$planDetails['id'],
            $startDate,
            class_exists('BillingCycleService')
                ? BillingCycleService::BILLING_TYPE_NEW
                : 'NEW_ACTIVATION',
        ]);

        $monthlyPrice = (float)$planDetails['price'];

        if (!class_exists('ReferralService')) {
            throw new RuntimeException('Referral service is unavailable.');
        }

        ReferralService::ensureSchema($pdo);

        if (class_exists('BillingCycleService')) {
            $invoiceData = BillingCycleService::createFirstBillForActivation(
                $pdo,
                $customerId,
                $monthlyPrice,
                $startDate
            );
            $invoiceAmount = (float)($invoiceData['amount'] ?? 0);
            $dueDate = (string)($invoiceData['due_date'] ?? '');
            $remainingDays = (int)($invoiceData['coverage_days'] ?? 1);
        } else {
            $invoiceAmount = $this->calculateProratedAmount($monthlyPrice, $startDate);
            $dueDate = $this->getMonthlyDueDate($pdo, $startDate);
            $invoiceData = ReferralService::insertInvoice(
                $pdo,
                $customerId,
                $invoiceAmount,
                $dueDate,
                'ISSUED'
            );
            $daysInMonth = (int)date('t', strtotime($startDate));
            $currentDay = (int)date('j', strtotime($startDate));
            $remainingDays = ($daysInMonth - $currentDay) + 1;
            if ($remainingDays < 1) {
                $remainingDays = 1;
            }
        }
        $invoiceId = (int)($invoiceData['id'] ?? 0);

        $portalUser = CustomerPortalService::createPortalUser($pdo, $customerId, $email);

        $updateInquiry = $pdo->prepare("
            UPDATE service_requests
            SET status = 'CONVERTED',
                email_sent = 0,
                converted_at = NOW()
            WHERE id = ?
        ");
        $updateInquiry->execute([$id]);

        $mailSent = false;
        $mailSkipReason = '';

        if (class_exists('EmailAlertService')) {
            try {
                $loginUrl = function_exists('absolute_url') ? absolute_url('/login') : url('/login');
                $mailSent = EmailAlertService::notifyApplicationApproved(
                    $pdo,
                    $customerId,
                    $invoiceId,
                    $planDetails,
                    $monthlyPrice,
                    $invoiceAmount,
                    $remainingDays,
                    $dueDate,
                    $portalUser['email'],
                    $portalUser['password'],
                    $loginUrl
                );
                if (!$mailSent) {
                    $mailSkipReason = 'SMTP delivery failed — check Settings → Email / SMTP';
                }
            } catch (Throwable $e) {
                error_log('InquiryController@convertPlanApplicationRecord mail error: ' . $e->getMessage());
                $mailSent = false;
                $mailSkipReason = 'email send error — check Settings → Email / SMTP';
            }
        } else {
            $mailSkipReason = 'email service unavailable';
        }

        $updateEmailSent = $pdo->prepare("
            UPDATE service_requests
            SET email_sent = ?
            WHERE id = ?
        ");
        $updateEmailSent->execute([
            $mailSent ? 1 : 0,
            $id
        ]);

        if ($mailSent) {
            $successMessage = 'Applicant converted to customer. Subscription, prorated invoice, and portal login were created. Welcome email with login details was sent.';
        } elseif ($mailSkipReason !== '') {
            $successMessage = 'Applicant converted to customer. Subscription, prorated invoice, and portal login were created. Email was not sent (' . $mailSkipReason . '). Portal login — Email: ' . $portalUser['email'] . ' | Password: ' . $portalUser['password'];
        } else {
            $successMessage = 'Applicant converted to customer. Subscription, prorated invoice, and portal login were created. Portal login — Email: ' . $portalUser['email'] . ' | Password: ' . $portalUser['password'];
        }

        if (is_array($referralResult)) {
            $rewardAmount = number_format((float)($referralResult['amount'] ?? 0), 2);
            $successMessage .= ' Referral credit of ₱' . $rewardAmount . ' was queued for ' . (string)($referralResult['referrer_name'] ?? 'referrer') . ' on their next bill.';

            if (class_exists('EmailAlertService')) {
                EmailAlertService::notifyReferralCreditEarned(
                    $pdo,
                    (int)($referralResult['referrer_customer_id'] ?? 0),
                    $fullName,
                    (float)($referralResult['amount'] ?? 0)
                );
            }
        }

        return $successMessage;
    }

    public function reject(): void
    {
        $this->requireLogin();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->setFlash('error', 'Invalid inquiry ID.');
            redirect('/inquiries');
            exit;
        }

        try {
            $pdo = $this->db();

            $stmt = $pdo->prepare("
                UPDATE service_requests
                SET status = 'REJECTED'
                WHERE id = ?
            ");
            $stmt->execute([$id]);

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession(
                    'Inquiries',
                    'REJECT',
                    'Rejected inquiry ID ' . $id . '.'
                );
            }

            $this->setFlash('success', 'Inquiry marked as rejected.');
            redirect('/inquiries');
            exit;
        } catch (Throwable $e) {
            error_log('InquiryController@reject error: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to reject inquiry.');
            redirect('/inquiries');
            exit;
        }
    }

    public function delete(): void
    {
        $this->requireLogin();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->setFlash('error', 'Invalid inquiry ID.');
            redirect('/inquiries');
            exit;
        }

        try {
            $pdo = $this->db();

            $stmt = $pdo->prepare('SELECT name FROM service_requests WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) {
                throw new RuntimeException('Inquiry not found.');
            }

            $name = trim((string)($row['name'] ?? ('ID ' . $id)));

            $stmt = $pdo->prepare('DELETE FROM service_requests WHERE id = ?');
            $stmt->execute([$id]);

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession('Inquiries', 'DELETE', 'Deleted inquiry ID ' . $id . ': ' . $name);
            }

            $this->setFlash('success', 'Inquiry deleted.');
            redirect('/inquiries');
            exit;
        } catch (Throwable $e) {
            error_log('InquiryController@delete error: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to delete inquiry: ' . $e->getMessage());
            redirect('/inquiries');
            exit;
        }
    }

    public function clearProcessed(): void
    {
        $this->requireLogin();

        try {
            $pdo = $this->db();

            $stmt = $pdo->prepare("
                DELETE FROM service_requests
                WHERE status IN ('CONVERTED', 'REJECTED')
            ");
            $stmt->execute();
            $deleted = $stmt->rowCount();

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession('Inquiries', 'CLEAR_PROCESSED', 'Deleted ' . $deleted . ' processed inquiry record(s).');
            }

            $this->setFlash('success', $deleted . ' processed inquiry record(s) removed.');
            redirect('/inquiries');
            exit;
        } catch (Throwable $e) {
            error_log('InquiryController@clearProcessed error: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to clear processed inquiries: ' . $e->getMessage());
            redirect('/inquiries');
            exit;
        }
    }
}
