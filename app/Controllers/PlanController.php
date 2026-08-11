<?php

if (file_exists(__DIR__ . '/../Services/ActivityLogger.php')) {
    require_once __DIR__ . '/../Services/ActivityLogger.php';
}

if (file_exists(__DIR__ . '/../Services/PlanService.php')) {
    require_once __DIR__ . '/../Services/PlanService.php';
}

class PlanController
{
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
        $pdo = $this->db();
        PlanService::ensureSchema($pdo);

        $plans = $pdo->query("
            SELECT id, name, speed, price, is_legacy
            FROM plans
            ORDER BY is_legacy ASC, id DESC
        ")->fetchAll();

        View::render('plans/index', [
            'title' => 'Plans',
            'plans' => $plans,
        ]);
    }

    public function create(): void
    {
        $pdo = $this->db();
        PlanService::ensureSchema($pdo);

        View::render('plans/create', [
            'title' => 'Add Plan',
        ]);
    }

    public function store(): void
    {
        $pdo = $this->db();

        $name  = trim($_POST['name'] ?? '');
        $speed = trim($_POST['speed'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $isLegacy = !empty($_POST['is_legacy']) ? 1 : 0;

        if ($name === '' || $speed === '' || $price === '') {
            die("Invalid input: name, speed, price are required.");
        }

        if (!is_numeric($price)) {
            die("Invalid input: price must be numeric.");
        }

        PlanService::ensureSchema($pdo);

        $stmt = $pdo->prepare("
            INSERT INTO plans (name, speed, price, is_legacy)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$name, $speed, $price, $isLegacy]);

        if (class_exists('ActivityLogger')) {
            ActivityLogger::logSession('Plans', 'CREATE', 'Added plan: ' . $name . ' (' . $speed . ')');
        }

        redirect("/plans");
        exit;
    }

    public function edit(): void
    {
        $pdo = $this->db();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            redirect("/plans");
            exit;
        }

        PlanService::ensureSchema($pdo);

        $stmt = $pdo->prepare("SELECT id, name, speed, price, is_legacy FROM plans WHERE id = ?");
        $stmt->execute([$id]);
        $plan = $stmt->fetch();

        if (!$plan) {
            redirect("/plans");
            exit;
        }

        View::render('plans/edit', [
            'title' => 'Edit Plan',
            'plan'  => $plan,
        ]);
    }

    public function update(): void
    {
        $pdo = $this->db();

        $id    = (int)($_POST['id'] ?? 0);
        $name  = trim($_POST['name'] ?? '');
        $speed = trim($_POST['speed'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $isLegacy = !empty($_POST['is_legacy']) ? 1 : 0;

        if ($id <= 0 || $name === '' || $speed === '' || $price === '') {
            die("Invalid input: id, name, speed, price are required.");
        }

        if (!is_numeric($price)) {
            die("Invalid input: price must be numeric.");
        }

        PlanService::ensureSchema($pdo);

        $stmt = $pdo->prepare("
            UPDATE plans
            SET name = ?, speed = ?, price = ?, is_legacy = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $speed, $price, $isLegacy, $id]);

        if (class_exists('ActivityLogger')) {
            ActivityLogger::logSession('Plans', 'UPDATE', 'Updated plan ID ' . $id . ': ' . $name);
        }

        redirect("/plans");
        exit;
    }

    public function delete(): void
    {
        $pdo = $this->db();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            redirect("/plans");
            exit;
        }

        $stmt = $pdo->prepare("SELECT name FROM plans WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $plan = $stmt->fetch();
        $planName = (string)($plan['name'] ?? ('ID ' . $id));

        $stmt = $pdo->prepare("DELETE FROM plans WHERE id = ?");
        $stmt->execute([$id]);

        if (class_exists('ActivityLogger')) {
            ActivityLogger::logSession('Plans', 'DELETE', 'Deleted plan: ' . $planName);
        }

        redirect("/plans");
        exit;
    }
}
