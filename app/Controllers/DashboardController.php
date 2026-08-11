<?php

class DashboardController
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

    public function index()
    {
        $this->requireLogin();

        $user = $_SESSION['user'] ?? [];
        $role = (string)($user['role'] ?? '');
        $isAdmin = in_array($role, ['ROLE_ADMIN', 'ADMIN', 'admin'], true);
        $isStaff = in_array($role, ['ROLE_ADMIN', 'ROLE_STAFF', 'ADMIN', 'STAFF', 'admin', 'staff'], true);
        $displayName = trim((string)($user['full_name'] ?? $user['name'] ?? ''));
        if ($displayName === '') {
            $email = trim((string)($user['email'] ?? 'User'));
            $displayName = strstr($email, '@', true) ?: $email;
        }
        $companyName = 'FusionLink';

        $totalCustomers = 0;
        $activeSubscriptions = 0;
        $outstandingInvoices = 0;
        $verifiedRevenue = 0;
        $paymentsThisMonth = 0;
        $chartLabels = [];
        $chartValues = [];
        $recentInvoices = [];
        $recentPayments = [];
        $activityAlerts = [
            'items' => [],
            'pendingApplications' => 0,
            'pendingPayments' => 0,
            'overdueInvoices' => 0,
        ];

        try {
            $pdo = $this->db();

            try {
                $settingsStmt = $pdo->query('SELECT company_name FROM settings ORDER BY id ASC LIMIT 1');
                $settingsRow = $settingsStmt ? $settingsStmt->fetch() : false;
                if (!empty($settingsRow['company_name'])) {
                    $companyName = trim((string)$settingsRow['company_name']);
                }
            } catch (Throwable $e) {
                // Keep default company name.
            }

            $stmt = $pdo->query("SELECT COUNT(*) AS total_customers FROM customers");
            $row = $stmt->fetch();
            $totalCustomers = (int)($row['total_customers'] ?? 0);

            $stmt = $pdo->query("SELECT COUNT(*) AS active_subscriptions FROM subscriptions WHERE status = 'ACTIVE'");
            $row = $stmt->fetch();
            $activeSubscriptions = (int)($row['active_subscriptions'] ?? 0);

            $stmt = $pdo->query("SELECT COUNT(*) AS outstanding_invoices FROM invoices WHERE status IN ('ISSUED', 'OVERDUE')");
            $row = $stmt->fetch();
            $outstandingInvoices = (int)($row['outstanding_invoices'] ?? 0);

            $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) AS verified_revenue FROM payments WHERE status = 'VERIFIED'");
            $row = $stmt->fetch();
            $verifiedRevenue = (float)($row['verified_revenue'] ?? 0);

            $stmt = $pdo->query("
                SELECT COALESCE(SUM(amount), 0) AS payments_this_month
                FROM payments
                WHERE YEAR(payment_date) = YEAR(CURDATE())
                  AND MONTH(payment_date) = MONTH(CURDATE())
            ");
            $row = $stmt->fetch();
            $paymentsThisMonth = (float)($row['payments_this_month'] ?? 0);

            $monthMap = [
                1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0,
                7 => 0, 8 => 0, 9 => 0, 10 => 0, 11 => 0, 12 => 0
            ];

            $stmt = $pdo->query("
                SELECT MONTH(payment_date) AS payment_month, COALESCE(SUM(amount), 0) AS total_amount
                FROM payments
                WHERE YEAR(payment_date) = YEAR(CURDATE())
                GROUP BY MONTH(payment_date)
                ORDER BY payment_month ASC
            ");

            foreach ($stmt->fetchAll() as $row) {
                $month = (int)($row['payment_month'] ?? 0);
                if ($month >= 1 && $month <= 12) {
                    $monthMap[$month] = (float)($row['total_amount'] ?? 0);
                }
            }

            $monthLabels = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
            ];

            foreach ($monthMap as $monthNumber => $amount) {
                $chartLabels[] = $monthLabels[$monthNumber];
                $chartValues[] = (float)$amount;
            }

            $stmt = $pdo->query("
                SELECT
                    invoices.id,
                    invoices.amount,
                    invoices.due_date,
                    invoices.status,
                    customers.full_name AS customer_name
                FROM invoices
                LEFT JOIN customers ON customers.id = invoices.customer_id
                ORDER BY invoices.id DESC
                LIMIT 5
            ");
            $recentInvoices = $stmt->fetchAll();

            $stmt = $pdo->query("
                SELECT
                    payments.id,
                    payments.amount,
                    payments.payment_date,
                    payments.method,
                    payments.status,
                    invoices.id AS invoice_number,
                    customers.full_name AS customer_name
                FROM payments
                LEFT JOIN invoices ON invoices.id = payments.invoice_id
                LEFT JOIN customers ON customers.id = invoices.customer_id
                ORDER BY payments.id DESC
                LIMIT 5
            ");
            $recentPayments = $stmt->fetchAll();

            $activityAlerts = $this->buildActivityAlerts($pdo);

        } catch (Throwable $e) {
            error_log("DashboardController@index error: " . $e->getMessage());
            $activityAlerts = [
                'items' => [],
                'pendingApplications' => 0,
                'pendingPayments' => 0,
                'overdueInvoices' => 0,
            ];
        }

        View::render('dashboard/index', [
            'title' => 'Home',
            'user' => $user,
            'role' => $role,
            'isAdmin' => $isAdmin,
            'isStaff' => $isStaff,
            'displayName' => $displayName,
            'companyName' => $companyName,
            'totalCustomers' => $totalCustomers,
            'activeSubscriptions' => $activeSubscriptions,
            'outstandingInvoices' => $outstandingInvoices,
            'verifiedRevenue' => $verifiedRevenue,
            'paymentsThisMonth' => $paymentsThisMonth,
            'chartLabels' => $chartLabels,
            'chartValues' => $chartValues,
            'recentInvoices' => $recentInvoices,
            'recentPayments' => $recentPayments,
            'activityAlerts' => $activityAlerts,
        ]);
    }

    private function buildActivityAlerts(PDO $pdo): array
    {
        $items = [];

        $stmt = $pdo->query("
            SELECT COUNT(*) AS total
            FROM service_requests
            WHERE status = 'PENDING'
        ");
        $pendingApplications = (int)($stmt->fetch()['total'] ?? 0);

        $stmt = $pdo->query("
            SELECT id, name, email, phone, plan, created_at
            FROM service_requests
            WHERE status = 'PENDING'
            ORDER BY created_at DESC, id DESC
            LIMIT 8
        ");
        foreach ($stmt->fetchAll() as $row) {
            $items[] = [
                'type' => 'application',
                'icon' => '📨',
                'title' => 'New service application',
                'message' => trim((string)($row['name'] ?? 'Applicant')) . ' applied for ' . trim((string)($row['plan'] ?? 'a plan')) . '.',
                'meta' => trim((string)($row['email'] ?? '')) !== '' ? (string)$row['email'] : (string)($row['phone'] ?? ''),
                'time' => (string)($row['created_at'] ?? ''),
                'url' => url('/inquiries'),
                'badge' => 'PENDING',
                'badgeClass' => 'badge-warning',
                'sort' => strtotime((string)($row['created_at'] ?? '')) ?: (int)($row['id'] ?? 0),
            ];
        }

        $stmt = $pdo->query("
            SELECT COUNT(*) AS total
            FROM payments
            WHERE status = 'PENDING'
        ");
        $pendingPayments = (int)($stmt->fetch()['total'] ?? 0);

        $stmt = $pdo->query("
            SELECT
                payments.id,
                payments.amount,
                payments.payment_date,
                payments.method,
                payments.status,
                invoices.id AS invoice_number,
                customers.full_name AS customer_name
            FROM payments
            LEFT JOIN invoices ON invoices.id = payments.invoice_id
            LEFT JOIN customers ON customers.id = invoices.customer_id
            WHERE payments.status = 'PENDING'
            ORDER BY payments.id DESC
            LIMIT 8
        ");
        foreach ($stmt->fetchAll() as $row) {
            $customer = trim((string)($row['customer_name'] ?? 'Customer'));
            $items[] = [
                'type' => 'payment',
                'icon' => '💳',
                'title' => 'Payment awaiting verification',
                'message' => $customer . ' submitted ₱' . number_format((float)($row['amount'] ?? 0), 2) . ' for Invoice #' . (int)($row['invoice_number'] ?? 0) . '.',
                'meta' => trim((string)($row['method'] ?? '')) !== '' ? (string)$row['method'] : 'Pending review',
                'time' => (string)($row['payment_date'] ?? ''),
                'url' => url('/payments'),
                'badge' => 'PENDING',
                'badgeClass' => 'badge-warning',
                'sort' => strtotime((string)($row['payment_date'] ?? '')) ?: (int)($row['id'] ?? 0),
            ];
        }

        $stmt = $pdo->query("
            SELECT COUNT(*) AS total
            FROM invoices
            WHERE status = 'OVERDUE'
        ");
        $overdueInvoices = (int)($stmt->fetch()['total'] ?? 0);

        $stmt = $pdo->query("
            SELECT
                invoices.id,
                invoices.amount,
                invoices.due_date,
                customers.full_name AS customer_name
            FROM invoices
            LEFT JOIN customers ON customers.id = invoices.customer_id
            WHERE invoices.status = 'OVERDUE'
            ORDER BY invoices.due_date ASC, invoices.id DESC
            LIMIT 8
        ");
        foreach ($stmt->fetchAll() as $row) {
            $customer = trim((string)($row['customer_name'] ?? 'Customer'));
            $items[] = [
                'type' => 'overdue',
                'icon' => '⚠️',
                'title' => 'Overdue invoice reminder',
                'message' => $customer . ' has overdue Invoice #' . (int)($row['id'] ?? 0) . ' for ₱' . number_format((float)($row['amount'] ?? 0), 2) . '.',
                'meta' => 'Due ' . (string)($row['due_date'] ?? ''),
                'time' => (string)($row['due_date'] ?? ''),
                'url' => url('/reports/outstanding'),
                'badge' => 'OVERDUE',
                'badgeClass' => 'badge-danger',
                'sort' => strtotime((string)($row['due_date'] ?? '')) ?: (int)($row['id'] ?? 0),
            ];
        }

        $stmt = $pdo->query("
            SELECT
                notifications.id,
                notifications.type,
                notifications.subject,
                notifications.message,
                notifications.status,
                notifications.invoice_id,
                notifications.recipient_email,
                notifications.created_at,
                customers.full_name AS customer_name
            FROM notifications
            LEFT JOIN customers ON customers.id = notifications.customer_id
            ORDER BY notifications.created_at DESC, notifications.id DESC
            LIMIT 10
        ");
        foreach ($stmt->fetchAll() as $row) {
            $type = strtoupper((string)($row['type'] ?? 'NOTIFICATION'));
            $items[] = [
                'type' => 'email',
                'icon' => $this->notificationIcon($type),
                'title' => $this->notificationTitle($type),
                'message' => (string)($row['message'] ?? $row['subject'] ?? 'Email notification sent.'),
                'meta' => trim((string)($row['customer_name'] ?? '')) !== ''
                    ? (string)$row['customer_name'] . ' · ' . (string)($row['recipient_email'] ?? '')
                    : (string)($row['recipient_email'] ?? ''),
                'time' => (string)($row['created_at'] ?? ''),
                'url' => $this->notificationUrl($type, (int)($row['invoice_id'] ?? 0)),
                'badge' => strtoupper((string)($row['status'] ?? 'SENT')),
                'badgeClass' => strtoupper((string)($row['status'] ?? '')) === 'SENT' ? 'badge-success' : 'badge-warning',
                'sort' => strtotime((string)($row['created_at'] ?? '')) ?: (int)($row['id'] ?? 0),
            ];
        }

        usort($items, static function (array $a, array $b): int {
            return ($b['sort'] ?? 0) <=> ($a['sort'] ?? 0);
        });

        $items = array_slice($items, 0, 15);
        foreach ($items as &$item) {
            unset($item['sort']);
        }
        unset($item);

        return [
            'items' => $items,
            'pendingApplications' => $pendingApplications,
            'pendingPayments' => $pendingPayments,
            'overdueInvoices' => $overdueInvoices,
        ];
    }

    private function notificationIcon(string $type): string
    {
        return match ($type) {
            'PAYMENT_CONFIRMED' => '✅',
            'PAYMENT_REJECTED' => '❌',
            'PAYMENT_SUBMITTED' => '💳',
            'OVERDUE_REMINDER' => '⏰',
            'RENEWAL_REMINDER' => '🔔',
            'APPLICATION_RECEIVED' => '📨',
            'APPLICATION_CONFIRMATION' => '✅',
            default => '📧',
        };
    }

    private function notificationTitle(string $type): string
    {
        return match ($type) {
            'PAYMENT_CONFIRMED' => 'Payment confirmation email sent',
            'PAYMENT_REJECTED' => 'Payment rejection email sent',
            'PAYMENT_SUBMITTED' => 'Payment submission email sent',
            'OVERDUE_REMINDER' => 'Overdue invoice email sent',
            'RENEWAL_REMINDER' => 'Renewal reminder email sent',
            'APPLICATION_RECEIVED' => 'Application alert email sent',
            'APPLICATION_CONFIRMATION' => 'Application confirmation email sent',
            default => 'Email notification sent',
        };
    }

    private function notificationUrl(string $type, int $invoiceId): string
    {
        return match ($type) {
            'PAYMENT_CONFIRMED', 'PAYMENT_REJECTED', 'PAYMENT_SUBMITTED' => url('/payments'),
            'APPLICATION_RECEIVED' => url('/inquiries'),
            'OVERDUE_REMINDER' => url('/reports/outstanding'),
            'RENEWAL_REMINDER' => url('/subscriptions'),
            default => $invoiceId > 0 ? url('/invoices') : url('/dashboard'),
        };
    }
}
