<?php

class ActivityLogger
{
    private const VISIT_DEBOUNCE_SECONDS = 45;

    /** @var array<string, string> */
    private const MODULE_MAP = [
        '/dashboard' => 'Home',
        '/customers' => 'Customers',
        '/subscriptions' => 'Subscriptions',
        '/invoices' => 'Invoices',
        '/payments' => 'Payments',
        '/inquiries' => 'Inquiries',
        '/bookings' => 'Bookings',
        '/personnel' => 'Personnel',
        '/plans' => 'Plans',
        '/reports' => 'Reports',
        '/users' => 'Users',
        '/activity-logs' => 'Activity Logs',
        '/settings' => 'Settings',
        '/cms' => 'CMS',
        '/page' => 'Public Site',
        '/login' => 'Auth',
        '/verify-otp' => 'Auth',
        '/logout' => 'Auth',
    ];

    /** Paths that should not create visit noise (JSON/API/slot polls). */
    private const SKIP_VISIT_PATHS = [
        '/page/booking-slots',
        '/bookings/available-slots',
        '/bookings/week-slots',
    ];

    private static function db(): ?PDO
    {
        try {
            $config = require __DIR__ . '/../../config/database.php';

            $dbName = $config['db'] ?? ($config['name'] ?? null);
            if (!$dbName) {
                return null;
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
        } catch (Throwable $e) {
            error_log('ActivityLogger@db error: ' . $e->getMessage());
            return null;
        }
    }

    private static function ensureTable(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS activity_logs (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                user_email VARCHAR(191) NULL,
                user_role VARCHAR(100) NULL,
                module VARCHAR(100) NOT NULL,
                action VARCHAR(100) NOT NULL,
                description TEXT NOT NULL,
                ip_address VARCHAR(45) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_activity_logs_created_at (created_at),
                INDEX idx_activity_logs_user_email (user_email),
                INDEX idx_activity_logs_module (module),
                INDEX idx_activity_logs_action (action)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public static function log(
        ?int $userId,
        ?string $userEmail,
        ?string $userRole,
        string $module,
        string $action,
        string $description
    ): void {
        try {
            $pdo = self::db();
            if (!$pdo) {
                return;
            }

            self::ensureTable($pdo);

            $stmt = $pdo->prepare("
                INSERT INTO activity_logs (
                    user_id,
                    user_email,
                    user_role,
                    module,
                    action,
                    description,
                    ip_address
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $userId,
                $userEmail,
                $userRole,
                $module,
                $action,
                $description,
                self::clientIp(),
            ]);
        } catch (Throwable $e) {
            error_log('ActivityLogger@log error: ' . $e->getMessage());
        }
    }

    public static function logSession(string $module, string $action, string $description): void
    {
        $user = $_SESSION['user'] ?? [];

        self::log(
            isset($user['id']) ? (int)$user['id'] : null,
            isset($user['email']) ? (string)$user['email'] : null,
            isset($user['role']) ? (string)$user['role'] : null,
            $module,
            $action,
            $description
        );
    }

    /**
     * Record authenticated page / module visits for GET navigation.
     */
    public static function logAuthenticatedVisit(string $path, string $method = 'GET'): void
    {
        try {
            if (empty($_SESSION['user'])) {
                return;
            }

            $method = strtoupper(trim($method));
            $path = self::normalizePath($path);

            if ($path === '' || $path === '/') {
                return;
            }

            if (!self::shouldLogVisit($path, $method)) {
                return;
            }

            if (self::isDebounced($path, $method)) {
                return;
            }

            $module = self::moduleFromPath($path);
            $label = self::pageLabelFromPath($path);
            $query = self::safeQueryString();

            $description = 'Visited ' . $label . ' (' . $path . ')';
            if ($query !== '') {
                $description .= ' [' . $query . ']';
            }

            self::logSession($module, 'VIEW', $description);
            self::markVisited($path, $method);
        } catch (Throwable $e) {
            error_log('ActivityLogger@logAuthenticatedVisit error: ' . $e->getMessage());
        }
    }

    public static function moduleFromPath(string $path): string
    {
        $path = self::normalizePath($path);

        foreach (self::MODULE_MAP as $prefix => $module) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return $module;
            }
        }

        $segment = trim(explode('/', ltrim($path, '/'))[0] ?? '', '/');
        if ($segment === '') {
            return 'System';
        }

        return ucwords(str_replace(['-', '_'], ' ', $segment));
    }

    private static function pageLabelFromPath(string $path): string
    {
        $path = self::normalizePath($path);
        $parts = array_values(array_filter(explode('/', trim($path, '/'))));

        if ($parts === []) {
            return 'Home';
        }

        $labels = array_map(static function (string $part): string {
            return ucwords(str_replace(['-', '_'], ' ', $part));
        }, $parts);

        return implode(' / ', $labels);
    }

    private static function shouldLogVisit(string $path, string $method): bool
    {
        if ($method !== 'GET') {
            return false;
        }

        if (in_array($path, self::SKIP_VISIT_PATHS, true)) {
            return false;
        }

        // Skip JSON/API-style responses and background polls.
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        if (str_contains($accept, 'application/json') && !str_contains($accept, 'text/html')) {
            return false;
        }

        $xhr = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        if ($xhr === 'xmlhttprequest') {
            return false;
        }

        // Skip obvious asset-like paths if they somehow hit the router.
        if (preg_match('/\.(css|js|png|jpe?g|gif|svg|ico|webp|map|woff2?)$/i', $path)) {
            return false;
        }

        return true;
    }

    private static function isDebounced(string $path, string $method): bool
    {
        $bucket = $_SESSION['_activity_visit_debounce'] ?? null;
        if (!is_array($bucket)) {
            return false;
        }

        $key = $method . ' ' . $path;
        $lastAt = (int)($bucket[$key] ?? 0);
        if ($lastAt <= 0) {
            return false;
        }

        return (time() - $lastAt) < self::VISIT_DEBOUNCE_SECONDS;
    }

    private static function markVisited(string $path, string $method): void
    {
        if (!isset($_SESSION['_activity_visit_debounce']) || !is_array($_SESSION['_activity_visit_debounce'])) {
            $_SESSION['_activity_visit_debounce'] = [];
        }

        $key = $method . ' ' . $path;
        $_SESSION['_activity_visit_debounce'][$key] = time();

        // Keep session payload small.
        if (count($_SESSION['_activity_visit_debounce']) > 40) {
            asort($_SESSION['_activity_visit_debounce']);
            $_SESSION['_activity_visit_debounce'] = array_slice($_SESSION['_activity_visit_debounce'], -25, null, true);
        }
    }

    private static function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '/';
        }

        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        if (strlen($path) > 1) {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    private static function safeQueryString(): string
    {
        $parts = [];
        foreach ($_GET as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $key = (string)$key;
            if (in_array(strtolower($key), ['password', 'token', 'csrf', '_token', 'otp', 'otp_code'], true)) {
                continue;
            }

            $parts[] = $key . '=' . substr((string)$value, 0, 80);
            if (count($parts) >= 6) {
                break;
            }
        }

        return implode('&', $parts);
    }

    private static function clientIp(): ?string
    {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            // X-Forwarded-For may contain a list.
            $ip = trim(explode(',', $candidate)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return null;
    }
}
