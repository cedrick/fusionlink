<?php

if (file_exists(__DIR__ . '/../Services/ActivityLogger.php')) {
    require_once __DIR__ . '/../Services/ActivityLogger.php';
}

if (file_exists(__DIR__ . '/../Services/FieldService.php')) {
    require_once __DIR__ . '/../Services/FieldService.php';
}

class PersonnelController
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

    private function requireLogin(): void
    {
        if (!isset($_SESSION['user'])) {
            redirect('/login');
            exit;
        }
    }

    private function redirectWithFlash(string $url, string $type, string $message): void
    {
        $_SESSION['personnel_flash'] = ['type' => $type, 'message' => $message];
        redirect($url);
        exit;
    }

    public function index(): void
    {
        $this->requireLogin();

        try {
            $pdo = $this->db();
            FieldService::ensureSchema($pdo);

            $personnel = $pdo->query("
                SELECT id, full_name, phone, work_start_time, work_end_time, is_active
                FROM field_personnel
                ORDER BY full_name ASC
            ")->fetchAll();

            $serviceTypes = $pdo->query("
                SELECT id, name, duration_minutes, is_active
                FROM field_service_types
                ORDER BY name ASC
            ")->fetchAll();

            $assignments = [];
            $stmt = $pdo->query('SELECT personnel_id, service_type_id FROM field_personnel_services');
            foreach ($stmt->fetchAll() as $row) {
                $personnelId = (int)($row['personnel_id'] ?? 0);
                $assignments[$personnelId][] = (int)($row['service_type_id'] ?? 0);
            }

            View::render('personnel/index', [
                'title' => 'Field Personnel',
                'personnel' => $personnel,
                'serviceTypes' => $serviceTypes,
                'assignments' => $assignments,
                'flash' => $_SESSION['personnel_flash'] ?? null,
            ]);
            unset($_SESSION['personnel_flash']);
        } catch (Throwable $e) {
            $this->redirectWithFlash('/dashboard', 'error', $e->getMessage());
        }
    }

    public function storePersonnel(): void
    {
        $this->requireLogin();

        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $phone = preg_replace('/\D+/', '', trim((string)($_POST['phone'] ?? '')));
        $workStart = trim((string)($_POST['work_start_time'] ?? '08:00'));
        $workEnd = trim((string)($_POST['work_end_time'] ?? '17:00'));
        $serviceTypeIds = array_map('intval', (array)($_POST['service_type_ids'] ?? []));

        if ($fullName === '') {
            $this->redirectWithFlash('/personnel', 'error', 'Personnel name is required.');
        }

        try {
            $pdo = $this->db();
            FieldService::ensureSchema($pdo);

            $stmt = $pdo->prepare("
                INSERT INTO field_personnel (full_name, phone, work_start_time, work_end_time, is_active)
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $fullName,
                $phone !== '' ? $phone : null,
                date('H:i:s', strtotime($workStart)),
                date('H:i:s', strtotime($workEnd)),
            ]);

            $personnelId = (int)$pdo->lastInsertId();
            FieldService::syncPersonnelServices($pdo, $personnelId, $serviceTypeIds);

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession('Personnel', 'CREATE', 'Added field personnel: ' . $fullName);
            }

            $this->redirectWithFlash('/personnel', 'success', 'Personnel added.');
        } catch (Throwable $e) {
            $this->redirectWithFlash('/personnel', 'error', $e->getMessage());
        }
    }

    public function updatePersonnel(): void
    {
        $this->requireLogin();

        $id = (int)($_POST['id'] ?? 0);
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $phone = preg_replace('/\D+/', '', trim((string)($_POST['phone'] ?? '')));
        $workStart = trim((string)($_POST['work_start_time'] ?? '08:00'));
        $workEnd = trim((string)($_POST['work_end_time'] ?? '17:00'));
        $isActive = isset($_POST['is_active']) && $_POST['is_active'] === '1' ? 1 : 0;
        $serviceTypeIds = array_map('intval', (array)($_POST['service_type_ids'] ?? []));

        if ($id <= 0 || $fullName === '') {
            $this->redirectWithFlash('/personnel', 'error', 'Invalid personnel update.');
        }

        try {
            $pdo = $this->db();
            FieldService::ensureSchema($pdo);

            $stmt = $pdo->prepare("
                UPDATE field_personnel
                SET full_name = ?, phone = ?, work_start_time = ?, work_end_time = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $fullName,
                $phone !== '' ? $phone : null,
                date('H:i:s', strtotime($workStart)),
                date('H:i:s', strtotime($workEnd)),
                $isActive,
                $id,
            ]);

            FieldService::syncPersonnelServices($pdo, $id, $serviceTypeIds);

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession('Personnel', 'UPDATE', 'Updated field personnel ID ' . $id);
            }

            $this->redirectWithFlash('/personnel', 'success', 'Personnel updated.');
        } catch (Throwable $e) {
            $this->redirectWithFlash('/personnel', 'error', $e->getMessage());
        }
    }

    public function storeServiceType(): void
    {
        $this->requireLogin();

        $name = trim((string)($_POST['name'] ?? ''));
        $duration = (int)($_POST['duration_minutes'] ?? 60);

        if ($name === '' || $duration < 15) {
            $this->redirectWithFlash('/personnel', 'error', 'Service name and duration (min 15 minutes) are required.');
        }

        try {
            $pdo = $this->db();
            FieldService::ensureSchema($pdo);

            $stmt = $pdo->prepare('INSERT INTO field_service_types (name, duration_minutes, is_active) VALUES (?, ?, 1)');
            $stmt->execute([$name, $duration]);

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession('Personnel', 'CREATE_SERVICE', 'Added service type: ' . $name);
            }

            $this->redirectWithFlash('/personnel', 'success', 'Service type added.');
        } catch (Throwable $e) {
            $this->redirectWithFlash('/personnel', 'error', $e->getMessage());
        }
    }
}
