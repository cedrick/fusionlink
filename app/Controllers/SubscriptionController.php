<?php

if (file_exists(__DIR__ . '/../Services/ActivityLogger.php')) {
    require_once __DIR__ . '/../Services/ActivityLogger.php';
}

if (file_exists(__DIR__ . '/../Services/PlanService.php')) {
    require_once __DIR__ . '/../Services/PlanService.php';
}

class SubscriptionController
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
            die("Database config error: missing 'db' (or 'name') key in config/database.php");
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

    public function index(): void
    {
        $this->requireLogin();

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

        if (!in_array($statusFilter, ['', 'ACTIVE', 'SUSPENDED', 'CANCELLED'], true)) {
            $statusFilter = '';
        }

        $allowedSort = [
            'id' => 's.id',
            'customer_name' => 'c.full_name',
            'plan_name' => 'p.name',
            'speed' => 'p.speed',
            'price' => 'p.price',
            'start_date' => 's.start_date',
            'status' => 's.status',
            'created_at' => 's.created_at',
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
            $where[] = "(c.full_name LIKE :search OR p.name LIKE :search OR p.speed LIKE :search OR CAST(p.price AS CHAR) LIKE :search OR s.status LIKE :search OR s.start_date LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        if ($statusFilter !== '') {
            $where[] = "s.status = :status";
            $params[':status'] = $statusFilter;
        }

        $whereSql = '';
        if (!empty($where)) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }

        $countSql = "
            SELECT COUNT(*)
            FROM subscriptions s
            JOIN customers c ON c.id = s.customer_id
            JOIN plans p ON p.id = s.plan_id
            {$whereSql}
        ";
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
            SELECT
                s.id,
                c.full_name AS customer_name,
                p.name AS plan_name,
                p.speed AS speed,
                p.price AS price,
                s.start_date,
                s.status,
                s.created_at
            FROM subscriptions s
            JOIN customers c ON c.id = s.customer_id
            JOIN plans p ON p.id = s.plan_id
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

        $subscriptions = $stmt->fetchAll();

        View::render('subscriptions/index', [
            'title' => 'Subscriptions',
            'subscriptions' => $subscriptions,
            'page' => $page,
            'perPage' => $perPage,
            'totalRows' => $totalRows,
            'totalPages' => $totalPages,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();

        $pdo = $this->db();
        PlanService::ensureSchema($pdo);

        $customers = $pdo->query("
            SELECT id, full_name
            FROM customers
            WHERE status = 'ACTIVE'
            ORDER BY full_name ASC
        ")->fetchAll();

        $plans = $pdo->query("
            SELECT id, name, speed, price, is_legacy
            FROM plans
            ORDER BY is_legacy ASC, id DESC
        ")->fetchAll();

        View::render('subscriptions/create', [
            'title' => 'Add Subscription',
            'customers' => $customers,
            'plans' => $plans,
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();

        $pdo = $this->db();

        $customerId = (int)($_POST['customer_id'] ?? 0);
        $planId     = (int)($_POST['plan_id'] ?? 0);
        $startDate  = $_POST['start_date'] ?? date('Y-m-d');
        $status     = $_POST['status'] ?? 'ACTIVE';

        $allowedStatus = ['ACTIVE', 'SUSPENDED', 'CANCELLED'];
        if (!in_array($status, $allowedStatus, true)) {
            $status = 'ACTIVE';
        }

        if ($customerId <= 0 || $planId <= 0) {
            die("Invalid input: customer_id and plan_id are required.");
        }

        $checkCustomer = $pdo->prepare("
            SELECT id
            FROM customers
            WHERE id = ?
              AND status = 'ACTIVE'
            LIMIT 1
        ");
        $checkCustomer->execute([$customerId]);

        if (!$checkCustomer->fetch()) {
            die("Selected customer is not active.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO subscriptions (customer_id, plan_id, start_date, status)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$customerId, $planId, $startDate, $status]);

        if (class_exists('ActivityLogger')) {
            ActivityLogger::logSession('Subscriptions', 'CREATE', 'Created subscription for customer ID ' . $customerId . ' with plan ID ' . $planId);
        }

        redirect("/subscriptions");
        exit;
    }

    public function edit(): void
    {
        $this->requireLogin();

        $pdo = $this->db();
        PlanService::ensureSchema($pdo);

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            redirect("/subscriptions");
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE id = ?");
        $stmt->execute([$id]);
        $subscription = $stmt->fetch();

        if (!$subscription) {
            redirect("/subscriptions");
            exit;
        }

        $currentCustomerId = (int)($subscription['customer_id'] ?? 0);

        $customersStmt = $pdo->prepare("
            SELECT id, full_name
            FROM customers
            WHERE status = 'ACTIVE' OR id = ?
            ORDER BY full_name ASC
        ");
        $customersStmt->execute([$currentCustomerId]);
        $customers = $customersStmt->fetchAll();

        $plans = $pdo->query("
            SELECT id, name, speed, price, is_legacy
            FROM plans
            ORDER BY is_legacy ASC, id DESC
        ")->fetchAll();

        View::render('subscriptions/edit', [
            'title' => 'Edit Subscription',
            'subscription' => $subscription,
            'customers' => $customers,
            'plans' => $plans,
        ]);
    }

    public function update(): void
    {
        $this->requireLogin();

        $pdo = $this->db();

        $id         = (int)($_POST['id'] ?? 0);
        $customerId = (int)($_POST['customer_id'] ?? 0);
        $planId     = (int)($_POST['plan_id'] ?? 0);
        $startDate  = $_POST['start_date'] ?? date('Y-m-d');
        $status     = $_POST['status'] ?? 'ACTIVE';

        $allowedStatus = ['ACTIVE', 'SUSPENDED', 'CANCELLED'];
        if (!in_array($status, $allowedStatus, true)) {
            $status = 'ACTIVE';
        }

        if ($id <= 0 || $customerId <= 0 || $planId <= 0) {
            die("Invalid input.");
        }

        $existingStmt = $pdo->prepare("
            SELECT customer_id
            FROM subscriptions
            WHERE id = ?
            LIMIT 1
        ");
        $existingStmt->execute([$id]);
        $existingSubscription = $existingStmt->fetch();

        if (!$existingSubscription) {
            die("Subscription not found.");
        }

        $originalCustomerId = (int)($existingSubscription['customer_id'] ?? 0);

        if ($customerId !== $originalCustomerId) {
            $checkCustomer = $pdo->prepare("
                SELECT id
                FROM customers
                WHERE id = ?
                  AND status = 'ACTIVE'
                LIMIT 1
            ");
            $checkCustomer->execute([$customerId]);

            if (!$checkCustomer->fetch()) {
                die("Selected customer is not active.");
            }
        }

        $stmt = $pdo->prepare("
            UPDATE subscriptions
            SET customer_id = ?, plan_id = ?, start_date = ?, status = ?
            WHERE id = ?
        ");
        $stmt->execute([$customerId, $planId, $startDate, $status, $id]);

        if (class_exists('ActivityLogger')) {
            ActivityLogger::logSession('Subscriptions', 'UPDATE', 'Updated subscription ID ' . $id);
        }

        redirect("/subscriptions");
        exit;
    }

    public function delete(): void
    {
        $this->requireLogin();

        $pdo = $this->db();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            redirect("/subscriptions");
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE id = ?");
        $stmt->execute([$id]);

        if (class_exists('ActivityLogger')) {
            ActivityLogger::logSession('Subscriptions', 'DELETE', 'Deleted subscription ID ' . $id);
        }

        redirect("/subscriptions");
        exit;
    }
}
