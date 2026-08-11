<?php

if (file_exists(__DIR__ . '/../Services/ActivityLogger.php')) {
    require_once __DIR__ . '/../Services/ActivityLogger.php';
}

class ActivityLogController
{
    private function requireLogin(): void
    {
        if (!isset($_SESSION['user'])) {
            redirect('/login');
            exit;
        }
    }

    private function requireAdmin(): void
    {
        $this->requireLogin();

        $role = $_SESSION['user']['role'] ?? '';
        if (!in_array($role, ['ROLE_ADMIN', 'ADMIN', 'admin'], true)) {
            http_response_code(403);
            echo '<h1>Access Denied</h1>';
            echo '<p>This page is restricted to administrators only.</p>';
            echo "<a href='" . htmlspecialchars(url('/dashboard'), ENT_QUOTES, 'UTF-8') . "'>Back to Dashboard</a>";
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
        $this->requireAdmin();

        $logs = [];
        $page = (int)($_GET['page'] ?? 1);
        $search = trim((string)($_GET['search'] ?? ''));
        $moduleFilter = trim((string)($_GET['module'] ?? ''));
        $actionFilter = trim((string)($_GET['action'] ?? ''));
        $sortBy = trim((string)($_GET['sort_by'] ?? 'created_at'));
        $sortDir = strtoupper(trim((string)($_GET['sort_dir'] ?? 'DESC')));
        $perPage = 30;
        $totalRows = 0;
        $totalPages = 1;
        $modules = [];
        $actions = [];

        if ($page < 1) {
            $page = 1;
        }

        $allowedSort = [
            'id' => 'id',
            'user_email' => 'user_email',
            'user_role' => 'user_role',
            'module' => 'module',
            'action' => 'action',
            'created_at' => 'created_at',
        ];

        if (!isset($allowedSort[$sortBy])) {
            $sortBy = 'created_at';
        }

        if (!in_array($sortDir, ['ASC', 'DESC'], true)) {
            $sortDir = 'DESC';
        }

        try {
            $pdo = $this->db();

            $modules = $pdo->query("
                SELECT DISTINCT module
                FROM activity_logs
                WHERE module IS NOT NULL AND module <> ''
                ORDER BY module ASC
            ")->fetchAll(PDO::FETCH_COLUMN) ?: [];

            $actions = $pdo->query("
                SELECT DISTINCT action
                FROM activity_logs
                WHERE action IS NOT NULL AND action <> ''
                ORDER BY action ASC
            ")->fetchAll(PDO::FETCH_COLUMN) ?: [];

            $where = [];
            $params = [];

            if ($search !== '') {
                $where[] = "(
                    CAST(id AS CHAR) LIKE :search
                    OR COALESCE(user_email, '') LIKE :search
                    OR COALESCE(user_role, '') LIKE :search
                    OR module LIKE :search
                    OR action LIKE :search
                    OR description LIKE :search
                    OR COALESCE(ip_address, '') LIKE :search
                    OR CAST(created_at AS CHAR) LIKE :search
                )";
                $params[':search'] = '%' . $search . '%';
            }

            if ($moduleFilter !== '') {
                $where[] = "module = :module";
                $params[':module'] = $moduleFilter;
            }

            if ($actionFilter !== '') {
                $where[] = "action = :action";
                $params[':action'] = $actionFilter;
            }

            $whereSql = '';
            if (!empty($where)) {
                $whereSql = 'WHERE ' . implode(' AND ', $where);
            }

            $countStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM activity_logs
                {$whereSql}
            ");
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

            $stmt = $pdo->prepare("
                SELECT *
                FROM activity_logs
                {$whereSql}
                ORDER BY {$orderBySql}
                LIMIT :limit OFFSET :offset
            ");
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $logs = $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log('ActivityLogController@index error: ' . $e->getMessage());
        }

        View::render('activity_logs/index', [
            'title' => 'Activity Logs',
            'logs' => $logs,
            'page' => $page,
            'perPage' => $perPage,
            'totalRows' => $totalRows,
            'totalPages' => $totalPages,
            'search' => $search,
            'moduleFilter' => $moduleFilter,
            'actionFilter' => $actionFilter,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'modules' => $modules,
            'actions' => $actions,
        ]);
    }
}
