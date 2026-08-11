<?php

if (file_exists(__DIR__ . '/../Services/ActivityLogger.php')) {
    require_once __DIR__ . '/../Services/ActivityLogger.php';
}

class UserController
{
    private function requireLogin(): void
    {
        if (!isset($_SESSION['user'])) {
            redirect("/login");
            exit;
        }
    }

    private function requireAdmin(): void
    {
        $this->requireLogin();

        $role = $_SESSION['user']['role'] ?? '';

        if (!in_array($role, ['ROLE_ADMIN', 'ADMIN', 'admin'], true)) {
            http_response_code(403);
            echo "<h1>Access Denied</h1>";
            echo "<p>This page is restricted to administrators only.</p>";
            echo "<a href='" . htmlspecialchars(url('/dashboard'), ENT_QUOTES, 'UTF-8') . "'>Back to Dashboard</a>";
            exit;
        }
    }

    private function getCustomers(): array
    {
        try {
            $pdo = Database::pdo();

            $stmt = $pdo->query("
                SELECT id, full_name, email
                FROM customers
                ORDER BY full_name ASC, id DESC
            ");

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('UserController@getCustomers error: ' . $e->getMessage());
            return [];
        }
    }

    public function index()
    {
        $this->requireAdmin();

        $users = [];
        $page = (int)($_GET['page'] ?? 1);
        $search = trim((string)($_GET['search'] ?? ''));
        $roleFilter = strtoupper(trim((string)($_GET['role'] ?? '')));
        $sortBy = trim((string)($_GET['sort_by'] ?? 'id'));
        $sortDir = strtoupper(trim((string)($_GET['sort_dir'] ?? 'DESC')));
        $perPage = 20;
        $totalRows = 0;
        $totalPages = 1;

        if ($page < 1) {
            $page = 1;
        }

        $allowedRoles = ['', 'ROLE_ADMIN', 'ROLE_STAFF', 'ROLE_CUSTOMER'];
        if (!in_array($roleFilter, $allowedRoles, true)) {
            $roleFilter = '';
        }

        $allowedSort = [
            'id' => 'id',
            'email' => 'email',
            'role' => 'role',
            'created_at' => 'created_at',
        ];

        if (!isset($allowedSort[$sortBy])) {
            $sortBy = 'id';
        }

        if (!in_array($sortDir, ['ASC', 'DESC'], true)) {
            $sortDir = 'DESC';
        }

        try {
            $pdo = Database::pdo();

            $where = [];
            $params = [];

            if ($search !== '') {
                $where[] = "(CAST(id AS CHAR) LIKE :search OR email LIKE :search OR role LIKE :search OR created_at LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }

            if ($roleFilter !== '') {
                $where[] = "role = :role";
                $params[':role'] = $roleFilter;
            }

            $whereSql = '';
            if (!empty($where)) {
                $whereSql = 'WHERE ' . implode(' AND ', $where);
            }

            $countSql = "
                SELECT COUNT(*)
                FROM users
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
                SELECT id, customer_id, email, role, created_at
                FROM users
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

            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('UserController@index error: ' . $e->getMessage());
            $users = [];
        }

        View::render("users/index", [
            "title" => "Users",
            "users" => $users,
            "page" => $page,
            "perPage" => $perPage,
            "totalRows" => $totalRows,
            "totalPages" => $totalPages,
            "search" => $search,
            "roleFilter" => $roleFilter,
            "sortBy" => $sortBy,
            "sortDir" => $sortDir,
        ]);
    }

    public function create()
    {
        $this->requireAdmin();

        View::render("users/create", [
            "title" => "Create User",
            "customers" => $this->getCustomers(),
        ]);
    }

    public function store()
    {
        $this->requireAdmin();

        try {
            $pdo = Database::pdo();

            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = strtoupper(trim((string)($_POST['role'] ?? 'ROLE_STAFF')));
            $customerId = (int)($_POST['customer_id'] ?? 0);

            if ($email === '' || $password === '') {
                die('Email and password are required.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                die('Invalid email address.');
            }

            if (!in_array($role, ['ROLE_ADMIN', 'ROLE_STAFF', 'ROLE_CUSTOMER'], true)) {
                $role = 'ROLE_STAFF';
            }

            if ($role === 'ROLE_CUSTOMER') {
                if ($customerId <= 0) {
                    die('Please select a customer for ROLE_CUSTOMER.');
                }

                $checkCustomer = $pdo->prepare("
                    SELECT id
                    FROM customers
                    WHERE id = ?
                    LIMIT 1
                ");
                $checkCustomer->execute([$customerId]);

                if (!$checkCustomer->fetch(PDO::FETCH_ASSOC)) {
                    die('Selected customer not found.');
                }

                $checkAssigned = $pdo->prepare("
                    SELECT id
                    FROM users
                    WHERE role = 'ROLE_CUSTOMER'
                      AND customer_id = ?
                    LIMIT 1
                ");
                $checkAssigned->execute([$customerId]);

                if ($checkAssigned->fetch(PDO::FETCH_ASSOC)) {
                    die('That customer already has a user account.');
                }
            } else {
                $customerId = null;
            }

            $checkEmail = $pdo->prepare("
                SELECT id
                FROM users
                WHERE email = ?
                LIMIT 1
            ");
            $checkEmail->execute([$email]);

            if ($checkEmail->fetch(PDO::FETCH_ASSOC)) {
                die('Email already exists.');
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO users (customer_id, email, password_hash, role)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$customerId, $email, $passwordHash, $role]);

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession('Users', 'CREATE', 'Created user: ' . $email . ' (' . $role . ')');
            }

            redirect("/users");
            exit;
        } catch (Throwable $e) {
            error_log('UserController@store error: ' . $e->getMessage());
            die('Failed to create user.');
        }
    }

    public function edit()
    {
        $this->requireAdmin();

        try {
            $pdo = Database::pdo();

            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                redirect("/users");
                exit;
            }

            $stmt = $pdo->prepare("
                SELECT id, customer_id, email, role, created_at
                FROM users
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                redirect("/users");
                exit;
            }

            View::render("users/edit", [
                "title" => "Edit User",
                "user" => $user,
                "customers" => $this->getCustomers(),
            ]);
        } catch (Throwable $e) {
            error_log('UserController@edit error: ' . $e->getMessage());
            redirect("/users");
            exit;
        }
    }

    public function update()
    {
        $this->requireAdmin();

        try {
            $pdo = Database::pdo();

            $id = (int)($_POST['id'] ?? 0);
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = strtoupper(trim((string)($_POST['role'] ?? 'ROLE_STAFF')));
            $customerId = (int)($_POST['customer_id'] ?? 0);

            if ($id <= 0 || $email === '') {
                die('Invalid input.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                die('Invalid email address.');
            }

            if (!in_array($role, ['ROLE_ADMIN', 'ROLE_STAFF', 'ROLE_CUSTOMER'], true)) {
                $role = 'ROLE_STAFF';
            }

            $currentStmt = $pdo->prepare("
                SELECT id, customer_id, email, role
                FROM users
                WHERE id = ?
                LIMIT 1
            ");
            $currentStmt->execute([$id]);
            $currentUser = $currentStmt->fetch(PDO::FETCH_ASSOC);

            if (!$currentUser) {
                die('User not found.');
            }

            $checkEmail = $pdo->prepare("
                SELECT id
                FROM users
                WHERE email = ?
                  AND id <> ?
                LIMIT 1
            ");
            $checkEmail->execute([$email, $id]);

            if ($checkEmail->fetch(PDO::FETCH_ASSOC)) {
                die('Email already exists.');
            }

            if ($role === 'ROLE_CUSTOMER') {
                if ($customerId <= 0) {
                    die('Please select a customer for ROLE_CUSTOMER.');
                }

                $checkCustomer = $pdo->prepare("
                    SELECT id
                    FROM customers
                    WHERE id = ?
                    LIMIT 1
                ");
                $checkCustomer->execute([$customerId]);

                if (!$checkCustomer->fetch(PDO::FETCH_ASSOC)) {
                    die('Selected customer not found.');
                }

                $checkAssigned = $pdo->prepare("
                    SELECT id
                    FROM users
                    WHERE role = 'ROLE_CUSTOMER'
                      AND customer_id = ?
                      AND id <> ?
                    LIMIT 1
                ");
                $checkAssigned->execute([$customerId, $id]);

                if ($checkAssigned->fetch(PDO::FETCH_ASSOC)) {
                    die('That customer already has a user account.');
                }
            } else {
                $customerId = null;
            }

            if ($password !== '') {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    UPDATE users
                    SET customer_id = ?, email = ?, password_hash = ?, role = ?
                    WHERE id = ?
                ");
                $stmt->execute([$customerId, $email, $passwordHash, $role, $id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET customer_id = ?, email = ?, role = ?
                    WHERE id = ?
                ");
                $stmt->execute([$customerId, $email, $role, $id]);
            }

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession('Users', 'UPDATE', 'Updated user ID ' . $id . ': ' . $email);
            }

            redirect("/users");
            exit;
        } catch (Throwable $e) {
            error_log('UserController@update error: ' . $e->getMessage());
            die('Failed to update user.');
        }
    }

    public function delete()
    {
        $this->requireAdmin();

        try {
            $pdo = Database::pdo();

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                redirect("/users");
                exit;
            }

            $currentEmail = $_SESSION['user']['email'] ?? '';

            $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                redirect("/users");
                exit;
            }

            if (($user['email'] ?? '') === $currentEmail) {
                die('You cannot delete your own logged-in account.');
            }

            $deletedEmail = (string)($user['email'] ?? ('ID ' . $id));

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession('Users', 'DELETE', 'Deleted user: ' . $deletedEmail);
            }

            redirect("/users");
            exit;
        } catch (Throwable $e) {
            error_log('UserController@delete error: ' . $e->getMessage());
            die('Failed to delete user.');
        }
    }
}
