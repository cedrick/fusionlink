<?php

if (file_exists(__DIR__ . '/../Services/BillingCycleService.php')) {
    require_once __DIR__ . '/../Services/BillingCycleService.php';
}

if (file_exists(__DIR__ . '/../Services/ReferralService.php')) {
    require_once __DIR__ . '/../Services/ReferralService.php';
}

if (file_exists(__DIR__ . '/../Services/MailService.php')) {
    require_once __DIR__ . '/../Services/MailService.php';
}

if (file_exists(__DIR__ . '/../Services/EmailAlertService.php')) {
    require_once __DIR__ . '/../Services/EmailAlertService.php';
}

if (file_exists(__DIR__ . '/../Services/ActivityLogger.php')) {
    require_once __DIR__ . '/../Services/ActivityLogger.php';
}

class CronController
{
    private function db(): PDO
    {
        $config = require __DIR__ . '/../../config/database.php';
        $dbName = $config['db'] ?? ($config['name'] ?? null);
        if (!$dbName) {
            throw new RuntimeException('Database config error.');
        }

        $host = $config['host'] ?? '127.0.0.1';
        $user = $config['user'] ?? '';
        $pass = $config['pass'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';
        $dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    private function expectedToken(): string
    {
        $app = require __DIR__ . '/../../config/app.php';
        return trim((string)($app['billing_cron_token'] ?? ''));
    }

    public function billing(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $expected = $this->expectedToken();
            $provided = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));

            if ($expected === '' || !hash_equals($expected, $provided)) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'message' => 'Invalid cron token.']);
                return;
            }

            if (!class_exists('BillingCycleService')) {
                throw new RuntimeException('BillingCycleService is unavailable.');
            }

            $task = trim((string)($_GET['task'] ?? $_POST['task'] ?? 'all'));
            $pdo = $this->db();
            $result = BillingCycleService::runScheduledJobs($pdo, $task);

            if (class_exists('ActivityLogger')) {
                ActivityLogger::log(
                    null,
                    'cron@system',
                    'SYSTEM',
                    'Billing',
                    'CRON',
                    'Billing cron task="' . $task . '" result=' . json_encode($result)
                );
            }

            echo json_encode([
                'ok' => true,
                'task' => $task,
                'date' => class_exists('BillingCycleService') ? BillingCycleService::today() : date('Y-m-d'),
                'result' => $result,
            ]);
        } catch (Throwable $e) {
            error_log('CronController@billing error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
    }
}
