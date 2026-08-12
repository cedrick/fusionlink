<?php

if (file_exists(__DIR__ . '/../Services/ActivityLogger.php')) {
    require_once __DIR__ . '/../Services/ActivityLogger.php';
}

if (file_exists(__DIR__ . '/../Services/CustomerPortalService.php')) {
    require_once __DIR__ . '/../Services/CustomerPortalService.php';
}

if (file_exists(__DIR__ . '/../Services/BillingCycleService.php')) {
    require_once __DIR__ . '/../Services/BillingCycleService.php';
}

class CustomerController
{
    private function requireLogin(): void
    {
        if (!isset($_SESSION['user'])) {
            redirect('/login');
            exit;
        }
    }

    private function redirectWithError(string $url, string $message): void
    {
        $_SESSION['error'] = $message;
        header('Location: ' . url($url));
        exit;
    }

    private function redirectWithSuccess(string $url, string $message): void
    {
        $_SESSION['success'] = $message;
        header('Location: ' . url($url));
        exit;
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

    private function computeCustomerStatus(array $customer): string
    {
        $status = strtoupper((string)($customer['status'] ?? 'ACTIVE'));

        if ($status === 'DISCONNECTED') {
            return 'DISCONNECTED';
        }

        $createdAt = strtotime((string)($customer['created_at'] ?? 'now'));
        $daysOld = (time() - $createdAt) / 86400;

        if ($daysOld < 30) {
            return 'NEW';
        }

        return 'ACTIVE';
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', trim($phone));
    }

    private function isValidPhone(string $phone): bool
    {
        return (bool) preg_match('/^09\d{9}$/', $phone);
    }

    private function tableExists(PDO $pdo, string $tableName): bool
    {
        $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$tableName]);
        return (bool)$stmt->fetchColumn();
    }

    private function deleteCustomerRelatedRecords(PDO $pdo, int $customerId): void
    {
        $invoiceIds = [];
        $stmt = $pdo->prepare('SELECT id FROM invoices WHERE customer_id = ?');
        $stmt->execute([$customerId]);
        foreach ($stmt->fetchAll() as $row) {
            $invoiceIds[] = (int)($row['id'] ?? 0);
        }
        $invoiceIds = array_values(array_filter($invoiceIds, static fn (int $id): bool => $id > 0));

        if ($invoiceIds !== [] && $this->tableExists($pdo, 'payments')) {
            $placeholders = implode(',', array_fill(0, count($invoiceIds), '?'));
            $stmt = $pdo->prepare("DELETE FROM payments WHERE invoice_id IN ($placeholders)");
            $stmt->execute($invoiceIds);
        }

        if ($this->tableExists($pdo, 'notifications')) {
            $stmt = $pdo->prepare('DELETE FROM notifications WHERE customer_id = ?');
            $stmt->execute([$customerId]);
        }

        $stmt = $pdo->prepare('DELETE FROM invoices WHERE customer_id = ?');
        $stmt->execute([$customerId]);

        $stmt = $pdo->prepare('DELETE FROM subscriptions WHERE customer_id = ?');
        $stmt->execute([$customerId]);

        if ($this->tableExists($pdo, 'referral_rewards')) {
            $stmt = $pdo->prepare('
                DELETE FROM referral_rewards
                WHERE referrer_customer_id = ?
                   OR referred_customer_id = ?
            ');
            $stmt->execute([$customerId, $customerId]);
        }

        if ($this->tableExists($pdo, 'users')) {
            $stmt = $pdo->prepare("
                DELETE FROM users
                WHERE customer_id = ?
                  AND role IN ('ROLE_CUSTOMER', 'CUSTOMER', 'customer')
            ");
            $stmt->execute([$customerId]);
        }
    }

    public function index()
    {
        $this->requireLogin();

        try {
            $pdo = $this->db();

            $page = (int)($_GET['page'] ?? 1);
            $search = trim((string)($_GET['search'] ?? ''));
            $statusFilter = strtoupper(trim((string)($_GET['status'] ?? '')));
            $sortBy = trim((string)($_GET['sort_by'] ?? 'id'));
            $sortDir = strtoupper(trim((string)($_GET['sort_dir'] ?? 'DESC')));
            $perPage = 20;

            if ($page < 1) {
                $page = 1;
            }

            if (!in_array($statusFilter, ['', 'NEW', 'ACTIVE', 'DISCONNECTED'], true)) {
                $statusFilter = '';
            }

            $allowedSort = [
                'id' => 'id',
                'full_name' => 'full_name',
                'email' => 'email',
                'phone' => 'phone',
                'created_at' => 'created_at',
            ];

            if (!isset($allowedSort[$sortBy])) {
                $sortBy = 'id';
            }

            if (!in_array($sortDir, ['ASC', 'DESC'], true)) {
                $sortDir = 'DESC';
            }

            $where = [];
            $params = [];

            if ($search !== '') {
                $where[] = "(full_name LIKE :search OR email LIKE :search OR phone LIKE :search OR address LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }

            if ($statusFilter === 'DISCONNECTED') {
                $where[] = "status = 'DISCONNECTED'";
            } elseif ($statusFilter === 'NEW') {
                $where[] = "status <> 'DISCONNECTED' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            } elseif ($statusFilter === 'ACTIVE') {
                $where[] = "status <> 'DISCONNECTED' AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
            }

            $whereSql = '';
            if (!empty($where)) {
                $whereSql = 'WHERE ' . implode(' AND ', $where);
            }

            $countSql = "SELECT COUNT(*) FROM customers {$whereSql}";
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
                SELECT *
                FROM customers
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

            $customers = $stmt->fetchAll();

            foreach ($customers as &$customer) {
                $customer['display_status'] = $this->computeCustomerStatus($customer);
            }
            unset($customer);

            $portalStatuses = [];
            if (class_exists('CustomerPortalService')) {
                $portalStatuses = CustomerPortalService::getPortalStatuses(
                    $pdo,
                    array_map(static fn (array $row): int => (int)($row['id'] ?? 0), $customers)
                );
            }

            View::render('customers/index', [
                'title' => 'Customers',
                'customers' => $customers,
                'portalStatuses' => $portalStatuses,
                'existingCustomerLink' => function_exists('absolute_url')
                    ? absolute_url('/page/existing')
                    : url('/page/existing'),
                'error' => $_SESSION['error'] ?? null,
                'success' => $_SESSION['success'] ?? null,
                'page' => $page,
                'perPage' => $perPage,
                'totalRows' => $totalRows,
                'totalPages' => $totalPages,
                'search' => $search,
                'statusFilter' => $statusFilter,
                'sortBy' => $sortBy,
                'sortDir' => $sortDir,
            ]);
            unset($_SESSION['error'], $_SESSION['success']);
        } catch (Throwable $e) {
            $this->redirectWithError('/dashboard', 'Customers load failed: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $this->requireLogin();

        View::render('customers/create', [
            'title' => 'Add Customer',
            'error' => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['error']);
    }

    public function store()
    {
        $this->requireLogin();

        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = $this->normalizePhone((string)($_POST['phone'] ?? ''));
        $address   = trim($_POST['address'] ?? '');
        $status    = $_POST['status'] ?? 'ACTIVE';

        if ($full_name === '') {
            $this->redirectWithError('/customers/create', 'Full name is required.');
        }

        if (!$this->isValidPhone($phone)) {
            $this->redirectWithError('/customers/create', 'Phone number must be exactly 11 digits and must start with 09.');
        }

        if (!in_array($status, ['ACTIVE', 'DISCONNECTED'], true)) {
            $status = 'ACTIVE';
        }

        try {
            $pdo = $this->db();
            $sql = "INSERT INTO customers (full_name, email, phone, address, status)
                    VALUES (:full_name, :email, :phone, :address, :status)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':full_name' => $full_name,
                ':email'     => $email !== '' ? $email : null,
                ':phone'     => $phone,
                ':address'   => $address !== '' ? $address : null,
                ':status'    => $status,
            ]);

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession('Customers', 'CREATE', 'Added customer: ' . $full_name);
            }

            redirect('/customers');
            exit;
        } catch (Throwable $e) {
            $this->redirectWithError('/customers/create', 'Add customer failed: ' . $e->getMessage());
        }
    }

    public function edit()
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->redirectWithError('/customers', 'Invalid customer ID.');
        }

        try {
            $pdo = $this->db();
            if (class_exists('BillingCycleService')) {
                BillingCycleService::ensureSchema($pdo);
            }
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $customer = $stmt->fetch();

            if (!$customer) {
                $this->redirectWithError('/customers', 'Customer not found.');
            }

            $portalStatus = class_exists('CustomerPortalService')
                ? CustomerPortalService::getPortalStatus($pdo, $id)
                : ['has_portal' => false, 'user_id' => 0, 'email' => ''];

            View::render('customers/edit', [
                'title' => 'Edit Customer',
                'customer' => $customer,
                'portalStatus' => $portalStatus,
                'error' => $_SESSION['error'] ?? null,
                'success' => $_SESSION['success'] ?? null,
            ]);
            unset($_SESSION['error'], $_SESSION['success']);
        } catch (Throwable $e) {
            $this->redirectWithError('/customers', 'Edit load failed: ' . $e->getMessage());
        }
    }

    public function update()
    {
        $this->requireLogin();

        $id        = (int)($_POST['id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = $this->normalizePhone((string)($_POST['phone'] ?? ''));
        $address   = trim($_POST['address'] ?? '');
        $status    = $_POST['status'] ?? 'ACTIVE';
        $vatInclusive = isset($_POST['vat_inclusive']) && (string)$_POST['vat_inclusive'] === '1' ? 1 : 0;

        if ($id <= 0) {
            $this->redirectWithError('/customers', 'Invalid customer ID.');
        }

        if ($full_name === '') {
            $this->redirectWithError('/customers/edit?id=' . $id, 'Full name is required.');
        }

        if (!$this->isValidPhone($phone)) {
            $this->redirectWithError('/customers/edit?id=' . $id, 'Phone number must be exactly 11 digits and must start with 09.');
        }

        if (!in_array($status, ['ACTIVE', 'DISCONNECTED'], true)) {
            $status = 'ACTIVE';
        }

        try {
            $pdo = $this->db();
            if (class_exists('BillingCycleService')) {
                BillingCycleService::ensureSchema($pdo);
            }

            $sql = "UPDATE customers
                    SET full_name = :full_name,
                        email = :email,
                        phone = :phone,
                        address = :address,
                        status = :status,
                        vat_inclusive = :vat_inclusive
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':full_name' => $full_name,
                ':email'     => $email !== '' ? $email : null,
                ':phone'     => $phone,
                ':address'   => $address !== '' ? $address : null,
                ':status'    => $status,
                ':vat_inclusive' => $vatInclusive,
                ':id'        => $id,
            ]);

            $syncedInvoices = 0;
            if (class_exists('BillingCycleService')) {
                $syncedInvoices = BillingCycleService::syncOpenInvoicesVatForCustomer($pdo, $id);
            }

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession(
                    'Customers',
                    'UPDATE',
                    'Updated customer ID ' . $id . ': ' . $full_name
                        . ($syncedInvoices > 0 ? ' (synced VAT on ' . $syncedInvoices . ' open invoice(s))' : '')
                );
            }

            if ($syncedInvoices > 0) {
                $_SESSION['success'] = 'Customer updated. VAT was recalculated on '
                    . $syncedInvoices . ' open invoice' . ($syncedInvoices === 1 ? '' : 's') . '.';
                redirect('/customers/edit?id=' . $id);
                exit;
            }

            redirect('/customers');
            exit;
        } catch (Throwable $e) {
            $this->redirectWithError('/customers/edit?id=' . $id, 'Update failed: ' . $e->getMessage());
        }
    }

    public function delete()
    {
        $this->requireLogin();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->redirectWithError('/customers', 'Invalid customer ID.');
        }

        try {
            $pdo = $this->db();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT full_name FROM customers WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $customer = $stmt->fetch();
            if (!$customer) {
                throw new RuntimeException('Customer not found.');
            }

            $customerName = (string)($customer['full_name'] ?? ('ID ' . $id));

            $this->deleteCustomerRelatedRecords($pdo, $id);

            $stmt = $pdo->prepare("DELETE FROM customers WHERE id = :id");
            $stmt->execute([':id' => $id]);

            $pdo->commit();

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession('Customers', 'DELETE', 'Deleted customer: ' . $customerName);
            }

            redirect('/customers');
            exit;
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('CustomerController@delete error: ' . $e->getMessage());
            $this->redirectWithError('/customers', 'Delete failed: ' . $e->getMessage());
        }
    }

    public function activatePortal(): void
    {
        $this->requireLogin();

        $id = (int)($_POST['id'] ?? 0);
        $returnTo = trim((string)($_POST['return_to'] ?? '/customers'));
        if (!in_array($returnTo, ['/customers', '/customers/edit'], true)) {
            $returnTo = '/customers';
        }

        if ($id <= 0) {
            $this->redirectWithError($returnTo, 'Invalid customer ID.');
        }

        if ($returnTo === '/customers/edit') {
            $returnTo = '/customers/edit?id=' . $id;
        }

        try {
            if (!class_exists('CustomerPortalService')) {
                throw new RuntimeException('Portal service is unavailable.');
            }

            $pdo = $this->db();
            $result = CustomerPortalService::activatePortal($pdo, $id, true);

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession(
                    'Customers',
                    'ACTIVATE_PORTAL',
                    'Activated billing portal for customer ID ' . $id . ' (' . $result['email'] . ').'
                );
            }

            $message = 'Portal login created for ' . $result['customer_name'] . '. Email: ' . $result['email'] . ' | Password: ' . $result['password'];
            if ($result['mail_sent']) {
                $message .= ' Credentials were emailed to the customer.';
            } else {
                $message .= ' Email was not sent — share the password manually.';
            }

            $this->redirectWithSuccess($returnTo, $message);
        } catch (Throwable $e) {
            error_log('CustomerController@activatePortal error: ' . $e->getMessage());
            $this->redirectWithError($returnTo, $e->getMessage());
        }
    }

    public function import(): void
    {
        $this->requireLogin();

        View::render('customers/import', [
            'title' => 'Import Customers',
            'error' => $_SESSION['error'] ?? null,
            'success' => $_SESSION['success'] ?? null,
        ]);
        unset($_SESSION['error'], $_SESSION['success']);
    }

    public function processImport(): void
    {
        $this->requireLogin();

        $createSubscriptions = isset($_POST['create_subscriptions']) && $_POST['create_subscriptions'] === '1';
        $activatePortal = isset($_POST['activate_portal']) && $_POST['activate_portal'] === '1';

        if (!isset($_FILES['csv_file']) || (int)($_FILES['csv_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->redirectWithError('/customers/import', 'Please choose a CSV file to upload.');
        }

        $tmpPath = (string)($_FILES['csv_file']['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            $this->redirectWithError('/customers/import', 'Invalid CSV upload.');
        }

        try {
            $pdo = $this->db();
            $handle = fopen($tmpPath, 'r');
            if ($handle === false) {
                throw new RuntimeException('Unable to read the uploaded CSV file.');
            }

            $header = fgetcsv($handle);
            if (!$header) {
                fclose($handle);
                throw new RuntimeException('CSV file is empty.');
            }

            $headerMap = $this->mapCsvHeader($header);
            $required = ['full_name', 'phone'];
            foreach ($required as $column) {
                if (!isset($headerMap[$column])) {
                    fclose($handle);
                    throw new RuntimeException('CSV must include columns: full_name, phone. Optional: email, address, status, plan_name, start_date.');
                }
            }

            $plansByName = $this->getPlansByName($pdo);
            $created = 0;
            $skipped = 0;
            $portalActivated = 0;
            $subscriptionsCreated = 0;
            $errors = [];
            $lineNumber = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;

                if ($row === [null] || $row === false || trim(implode('', $row)) === '') {
                    continue;
                }

                try {
                    $data = $this->parseCsvRow($row, $headerMap);
                    $result = $this->importCustomerRow($pdo, $data, $plansByName, $createSubscriptions, $activatePortal);
                    if ($result['created']) {
                        $created++;
                    } else {
                        $skipped++;
                    }
                    if ($result['subscription_created']) {
                        $subscriptionsCreated++;
                    }
                    if ($result['portal_activated']) {
                        $portalActivated++;
                    }
                } catch (Throwable $e) {
                    $errors[] = 'Row ' . $lineNumber . ': ' . $e->getMessage();
                }
            }

            fclose($handle);

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession(
                    'Customers',
                    'IMPORT',
                    'Imported customers from CSV. Created: ' . $created . ', skipped: ' . $skipped . '.'
                );
            }

            $message = 'Import finished. Created ' . $created . ' customer(s)';
            if ($subscriptionsCreated > 0) {
                $message .= ', subscriptions ' . $subscriptionsCreated;
            }
            if ($portalActivated > 0) {
                $message .= ', portal logins ' . $portalActivated;
            }
            if ($skipped > 0) {
                $message .= ', skipped ' . $skipped;
            }
            $message .= '.';
            if ($errors !== []) {
                $message .= ' Issues: ' . implode(' | ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= ' | ...and ' . (count($errors) - 5) . ' more.';
                }
            }

            $this->redirectWithSuccess('/customers', $message);
        } catch (Throwable $e) {
            error_log('CustomerController@processImport error: ' . $e->getMessage());
            $this->redirectWithError('/customers/import', $e->getMessage());
        }
    }

    private function mapCsvHeader(array $header): array
    {
        $map = [];
        foreach ($header as $index => $label) {
            $key = strtolower(trim((string)$label));
            $key = str_replace([' ', '-'], '_', $key);
            if ($key !== '') {
                $map[$key] = (int)$index;
            }
        }

        return $map;
    }

    private function parseCsvRow(array $row, array $headerMap): array
    {
        $get = static function (string $key) use ($row, $headerMap): string {
            if (!isset($headerMap[$key])) {
                return '';
            }

            return trim((string)($row[$headerMap[$key]] ?? ''));
        };

        return [
            'full_name' => $get('full_name'),
            'phone' => $this->normalizePhone($get('phone')),
            'email' => $get('email'),
            'address' => $get('address'),
            'status' => strtoupper($get('status') !== '' ? $get('status') : 'ACTIVE'),
            'plan_name' => $get('plan_name'),
            'start_date' => $get('start_date'),
        ];
    }

    private function getPlansByName(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT id, name FROM plans ORDER BY id ASC');
        $plans = [];
        foreach ($stmt->fetchAll() as $row) {
            $name = strtolower(trim((string)($row['name'] ?? '')));
            if ($name !== '') {
                $plans[$name] = (int)($row['id'] ?? 0);
            }
        }

        return $plans;
    }

    private function importCustomerRow(
        PDO $pdo,
        array $data,
        array $plansByName,
        bool $createSubscriptions,
        bool $activatePortal
    ): array {
        $fullName = trim((string)($data['full_name'] ?? ''));
        $phone = (string)($data['phone'] ?? '');
        $email = trim((string)($data['email'] ?? ''));
        $address = trim((string)($data['address'] ?? ''));
        $status = strtoupper((string)($data['status'] ?? 'ACTIVE'));
        $planName = strtolower(trim((string)($data['plan_name'] ?? '')));
        $startDate = trim((string)($data['start_date'] ?? ''));

        if ($fullName === '') {
            throw new RuntimeException('full_name is required.');
        }

        if (!$this->isValidPhone($phone)) {
            throw new RuntimeException('phone must be 11 digits starting with 09.');
        }

        if (!in_array($status, ['ACTIVE', 'DISCONNECTED'], true)) {
            $status = 'ACTIVE';
        }

        $check = $pdo->prepare('
            SELECT id
            FROM customers
            WHERE phone = ?
               OR (email IS NOT NULL AND email <> "" AND email = ?)
            LIMIT 1
        ');
        $check->execute([$phone, $email]);
        if ($check->fetch()) {
            return [
                'created' => false,
                'subscription_created' => false,
                'portal_activated' => false,
            ];
        }

        $stmt = $pdo->prepare('
            INSERT INTO customers (full_name, email, phone, address, status)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $fullName,
            $email !== '' ? $email : null,
            $phone,
            $address !== '' ? $address : null,
            $status,
        ]);

        $customerId = (int)$pdo->lastInsertId();
        $subscriptionCreated = false;
        $portalActivated = false;

        if ($createSubscriptions && $planName !== '' && isset($plansByName[$planName])) {
            if ($startDate === '' || strtotime($startDate) === false) {
                $startDate = date('Y-m-d');
            }

            $sub = $pdo->prepare('
                INSERT INTO subscriptions (customer_id, plan_id, start_date, billing_type, status)
                VALUES (?, ?, ?, ?, "ACTIVE")
            ');
            $billingType = class_exists('BillingCycleService')
                ? BillingCycleService::BILLING_TYPE_EXISTING
                : 'EXISTING_MIGRATE';
            if (class_exists('BillingCycleService')) {
                BillingCycleService::ensureSchema($pdo);
            }
            $sub->execute([$customerId, $plansByName[$planName], $startDate, $billingType]);
            $subscriptionCreated = true;

            // Existing imports get a regular full-month bill for the enrollment month (never prorate).
            if (class_exists('BillingCycleService')) {
                $planPriceStmt = $pdo->prepare('SELECT price FROM plans WHERE id = ? LIMIT 1');
                $planPriceStmt->execute([(int)$plansByName[$planName]]);
                $planPrice = (float)($planPriceStmt->fetchColumn() ?: 0);
                if ($planPrice > 0) {
                    BillingCycleService::createRegularMonthBillForExisting(
                        $pdo,
                        $customerId,
                        $planPrice,
                        (string)$startDate
                    );
                }
            }
        }

        if ($activatePortal && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && class_exists('CustomerPortalService')) {
            try {
                CustomerPortalService::activatePortal($pdo, $customerId, true);
                $portalActivated = true;
            } catch (Throwable $e) {
                error_log('CustomerController@import portal error for customer ' . $customerId . ': ' . $e->getMessage());
            }
        }

        return [
            'created' => true,
            'subscription_created' => $subscriptionCreated,
            'portal_activated' => $portalActivated,
        ];
    }
}
