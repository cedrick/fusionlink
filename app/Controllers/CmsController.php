<?php

if (file_exists(__DIR__ . '/../Services/ActivityLogger.php')) {
    require_once __DIR__ . '/../Services/ActivityLogger.php';
}

if (file_exists(__DIR__ . '/../Services/CmsImageService.php')) {
    require_once __DIR__ . '/../Services/CmsImageService.php';
}

class CmsController
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
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$tableName, $columnName]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function ensureCmsSettingsTable(PDO $pdo): void
    {
        if (!$this->tableExists($pdo, 'cms_settings')) {
            $pdo->exec("
                CREATE TABLE cms_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    hero_title TEXT NULL,
                    hero_subtitle TEXT NULL,
                    hero_image TEXT NULL,

                    about_title TEXT NULL,
                    about_text TEXT NULL,
                    about_image TEXT NULL,

                    services_title TEXT NULL,
                    services_text TEXT NULL,

                    plans_title TEXT NULL,
                    plans_text TEXT NULL,

                    cta_title TEXT NULL,
                    cta_text TEXT NULL,
                    cta_button_text VARCHAR(255) NULL,
                    cta_button_link VARCHAR(255) NULL,

                    company_name VARCHAR(255) NULL,
                    company_email VARCHAR(255) NULL,
                    company_phone VARCHAR(50) NULL,
                    company_address TEXT NULL,

                    apply_title TEXT NULL,
                    apply_subtitle TEXT NULL,
                    apply_form_title TEXT NULL,
                    apply_form_text TEXT NULL,
                    apply_success_message TEXT NULL,

                    footer_text TEXT NULL,

                    nav_home_label VARCHAR(100) NULL,
                    nav_about_label VARCHAR(100) NULL,
                    nav_plans_label VARCHAR(100) NULL,
                    nav_contact_label VARCHAR(100) NULL,
                    nav_apply_label VARCHAR(100) NULL,

                    primary_color VARCHAR(30) NULL,
                    secondary_color VARCHAR(30) NULL,
                    accent_color VARCHAR(30) NULL,
                    text_color VARCHAR(30) NULL,
                    header_background VARCHAR(30) NULL,
                    section_background VARCHAR(30) NULL,
                    footer_background VARCHAR(30) NULL,
                    button_radius VARCHAR(20) NULL,

                    website_logo TEXT NULL,
                    website_favicon TEXT NULL,

                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            return;
        }

        $columnsToAdd = [
            'about_title'        => "ALTER TABLE cms_settings ADD COLUMN about_title TEXT NULL AFTER hero_image",
            'about_text'         => "ALTER TABLE cms_settings ADD COLUMN about_text TEXT NULL AFTER about_title",
            'about_image'        => "ALTER TABLE cms_settings ADD COLUMN about_image TEXT NULL AFTER about_text",

            'services_title'     => "ALTER TABLE cms_settings ADD COLUMN services_title TEXT NULL AFTER about_image",
            'services_text'      => "ALTER TABLE cms_settings ADD COLUMN services_text TEXT NULL AFTER services_title",

            'plans_title'        => "ALTER TABLE cms_settings ADD COLUMN plans_title TEXT NULL AFTER services_text",
            'plans_text'         => "ALTER TABLE cms_settings ADD COLUMN plans_text TEXT NULL AFTER plans_title",

            'cta_title'          => "ALTER TABLE cms_settings ADD COLUMN cta_title TEXT NULL AFTER plans_text",
            'cta_text'           => "ALTER TABLE cms_settings ADD COLUMN cta_text TEXT NULL AFTER cta_title",
            'cta_button_text'    => "ALTER TABLE cms_settings ADD COLUMN cta_button_text VARCHAR(255) NULL AFTER cta_text",
            'cta_button_link'    => "ALTER TABLE cms_settings ADD COLUMN cta_button_link VARCHAR(255) NULL AFTER cta_button_text",

            'nav_home_label'     => "ALTER TABLE cms_settings ADD COLUMN nav_home_label VARCHAR(100) NULL AFTER footer_text",
            'nav_about_label'    => "ALTER TABLE cms_settings ADD COLUMN nav_about_label VARCHAR(100) NULL AFTER nav_home_label",
            'nav_plans_label'    => "ALTER TABLE cms_settings ADD COLUMN nav_plans_label VARCHAR(100) NULL AFTER nav_about_label",
            'nav_contact_label'  => "ALTER TABLE cms_settings ADD COLUMN nav_contact_label VARCHAR(100) NULL AFTER nav_plans_label",
            'nav_apply_label'    => "ALTER TABLE cms_settings ADD COLUMN nav_apply_label VARCHAR(100) NULL AFTER nav_contact_label",

            'primary_color'      => "ALTER TABLE cms_settings ADD COLUMN primary_color VARCHAR(30) NULL AFTER nav_apply_label",
            'secondary_color'    => "ALTER TABLE cms_settings ADD COLUMN secondary_color VARCHAR(30) NULL AFTER primary_color",
            'accent_color'       => "ALTER TABLE cms_settings ADD COLUMN accent_color VARCHAR(30) NULL AFTER secondary_color",
            'text_color'         => "ALTER TABLE cms_settings ADD COLUMN text_color VARCHAR(30) NULL AFTER accent_color",
            'header_background'  => "ALTER TABLE cms_settings ADD COLUMN header_background VARCHAR(30) NULL AFTER text_color",
            'section_background' => "ALTER TABLE cms_settings ADD COLUMN section_background VARCHAR(30) NULL AFTER header_background",
            'footer_background'  => "ALTER TABLE cms_settings ADD COLUMN footer_background VARCHAR(30) NULL AFTER section_background",
            'button_radius'      => "ALTER TABLE cms_settings ADD COLUMN button_radius VARCHAR(20) NULL AFTER footer_background",

            'website_logo'       => "ALTER TABLE cms_settings ADD COLUMN website_logo TEXT NULL AFTER button_radius",
            'website_favicon'    => "ALTER TABLE cms_settings ADD COLUMN website_favicon TEXT NULL AFTER website_logo",
        ];

        foreach ($columnsToAdd as $column => $sql) {
            if (!$this->columnExists($pdo, 'cms_settings', $column)) {
                $pdo->exec($sql);
            }
        }
    }

    private function getDefaultCms(): array
    {
        return [
            'id' => 0,

            'hero_title' => 'Fast, simple, and reliable internet for homes and businesses.',
            'hero_subtitle' => 'Browse available plans, submit your service request online, and enjoy a cleaner customer journey from application to billing access.',
            'hero_image' => '',

            'about_title' => 'About FusionLink',
            'about_text' => 'FusionLink provides dependable internet solutions with a simpler application experience and connected billing management.',
            'about_image' => '',

            'services_title' => 'Our Services',
            'services_text' => 'We provide internet subscription plans, online applications, customer billing, payment verification, and support-ready service operations.',

            'plans_title' => 'Featured Plans',
            'plans_text' => 'Choose a plan that matches your home or business connectivity needs.',

            'cta_title' => 'Ready to get connected?',
            'cta_text' => 'Submit your application online and let the team assist you with plan activation.',
            'cta_button_text' => 'Apply Now',
            'cta_button_link' => '/page#apply',

            'company_name' => 'FusionLink',
            'company_email' => 'support@fusionlink.local',
            'company_phone' => '+63 900 123 4567',
            'company_address' => 'Fusionlink Service Center',

            'apply_title' => 'Apply for Fusionlink internet service',
            'apply_subtitle' => 'Choose your preferred plan, provide your contact details, and submit your service request in a cleaner and faster online flow.',
            'apply_form_title' => 'Enter your customer details',
            'apply_form_text' => 'Complete the required information below so the team can review your request and contact you.',
            'apply_success_message' => 'Your request has been recorded and will be reviewed by the Fusionlink team.',

            'footer_text' => 'Reliable internet solutions with a cleaner application flow, connected billing access, and flexible plan options.',

            'nav_home_label' => 'Home',
            'nav_about_label' => 'About',
            'nav_plans_label' => 'Plans',
            'nav_contact_label' => 'Contact',
            'nav_apply_label' => 'Apply Now',

            'primary_color' => '#6d28d9',
            'secondary_color' => '#8b5cf6',
            'accent_color' => '#a78bfa',
            'text_color' => '#ffffff',
            'header_background' => '#0f0f10',
            'section_background' => '#111113',
            'footer_background' => '#0a0a0a',
            'button_radius' => '16',

            'website_logo' => '',
            'website_favicon' => '',
        ];
    }

    private function ensureCmsRow(PDO $pdo): array
    {
        $this->ensureCmsSettingsTable($pdo);

        $stmt = $pdo->query("
            SELECT *
            FROM cms_settings
            ORDER BY id ASC
            LIMIT 1
        ");
        $row = $stmt->fetch();

        if ($row) {
            return array_merge($this->getDefaultCms(), $row);
        }

        $defaults = $this->getDefaultCms();

        $insert = $pdo->prepare("
            INSERT INTO cms_settings (
                hero_title, hero_subtitle, hero_image,
                about_title, about_text, about_image,
                services_title, services_text,
                plans_title, plans_text,
                cta_title, cta_text, cta_button_text, cta_button_link,
                company_name, company_email, company_phone, company_address,
                apply_title, apply_subtitle, apply_form_title, apply_form_text, apply_success_message,
                footer_text,
                nav_home_label, nav_about_label, nav_plans_label, nav_contact_label, nav_apply_label,
                primary_color, secondary_color, accent_color, text_color, header_background, section_background, footer_background, button_radius,
                website_logo, website_favicon
            ) VALUES (
                ?, ?, ?,
                ?, ?, ?,
                ?, ?,
                ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?
            )
        ");

        $insert->execute([
            $defaults['hero_title'],
            $defaults['hero_subtitle'],
            $defaults['hero_image'],

            $defaults['about_title'],
            $defaults['about_text'],
            $defaults['about_image'],

            $defaults['services_title'],
            $defaults['services_text'],

            $defaults['plans_title'],
            $defaults['plans_text'],

            $defaults['cta_title'],
            $defaults['cta_text'],
            $defaults['cta_button_text'],
            $defaults['cta_button_link'],

            $defaults['company_name'],
            $defaults['company_email'],
            $defaults['company_phone'],
            $defaults['company_address'],

            $defaults['apply_title'],
            $defaults['apply_subtitle'],
            $defaults['apply_form_title'],
            $defaults['apply_form_text'],
            $defaults['apply_success_message'],

            $defaults['footer_text'],

            $defaults['nav_home_label'],
            $defaults['nav_about_label'],
            $defaults['nav_plans_label'],
            $defaults['nav_contact_label'],
            $defaults['nav_apply_label'],

            $defaults['primary_color'],
            $defaults['secondary_color'],
            $defaults['accent_color'],
            $defaults['text_color'],
            $defaults['header_background'],
            $defaults['section_background'],
            $defaults['footer_background'],
            $defaults['button_radius'],

            $defaults['website_logo'],
            $defaults['website_favicon'],
        ]);

        $defaults['id'] = (int)$pdo->lastInsertId();

        return $defaults;
    }

    private function getCms(): array
    {
        $cms = $this->getDefaultCms();

        try {
            $pdo = $this->db();
            $cms = $this->ensureCmsRow($pdo);
        } catch (Throwable $e) {
            error_log('CmsController@getCms error: ' . $e->getMessage());
        }

        return $cms;
    }

    private function renderPage(string $view, string $title): void
    {
        $this->requireAdmin();

        View::render($view, [
            'title' => $title,
            'cms' => $this->getCms(),
            'success' => isset($_GET['success']) && $_GET['success'] === '1',
            'error' => isset($_GET['error']) && $_GET['error'] === '1',
            'message' => trim((string)($_GET['message'] ?? '')),
        ]);
    }

    private function redirectWithStatus(string $path, string $type, string $message): void
    {
        header('Location: ' . url($path) . '?' . http_build_query([
            $type => '1',
            'message' => $message,
        ]));
        exit;
    }

    public function index(): void
    {
        $this->dashboard();
    }

    public function dashboard(): void
    {
        $this->renderPage('cms/dashboard', 'CMS Dashboard');
    }

    public function content(): void
    {
        $this->renderPage('cms/content', 'CMS Content');
    }

    public function design(): void
    {
        $this->renderPage('cms/design', 'CMS Design');
    }

    public function media(): void
    {
        $this->renderPage('cms/media', 'CMS Media');
    }

    public function navigation(): void
    {
        $this->renderPage('cms/navigation', 'CMS Navigation');
    }

    public function settings(): void
    {
        $this->renderPage('cms/settings', 'CMS Website Settings');
    }

    private function persist(array $fields, string $redirectPath, string $activityMessage): void
    {
        $this->requireAdmin();

        try {
            $pdo = $this->db();
            $cms = $this->ensureCmsRow($pdo);
            $defaults = $this->getDefaultCms();
            $id = (int)($cms['id'] ?? 0);

            foreach ($fields as $key => $value) {
                $fields[$key] = trim((string)$value);

                if ($fields[$key] === '' && array_key_exists($key, $defaults)) {
                    $fields[$key] = $defaults[$key];
                }
            }

            if (isset($fields['company_email']) && $fields['company_email'] !== '' && !filter_var($fields['company_email'], FILTER_VALIDATE_EMAIL)) {
                $this->redirectWithStatus($redirectPath, 'error', 'Invalid company email address.');
            }

            $setParts = [];
            $values = [];

            foreach ($fields as $column => $value) {
                $setParts[] = "{$column} = ?";
                $values[] = $value;
            }

            $values[] = $id;

            $sql = "UPDATE cms_settings SET " . implode(', ', $setParts) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession('CMS', 'UPDATE', $activityMessage);
            }

            $this->redirectWithStatus($redirectPath, 'success', 'CMS updated successfully.');
        } catch (Throwable $e) {
            error_log('CmsController@persist error: ' . $e->getMessage());
            $this->redirectWithStatus($redirectPath, 'error', 'Failed to update CMS.');
        }
    }

    public function updateContent(): void
    {
        $this->persist([
            'hero_title' => $_POST['hero_title'] ?? '',
            'hero_subtitle' => $_POST['hero_subtitle'] ?? '',
            'about_title' => $_POST['about_title'] ?? '',
            'about_text' => $_POST['about_text'] ?? '',
            'services_title' => $_POST['services_title'] ?? '',
            'services_text' => $_POST['services_text'] ?? '',
            'plans_title' => $_POST['plans_title'] ?? '',
            'plans_text' => $_POST['plans_text'] ?? '',
            'cta_title' => $_POST['cta_title'] ?? '',
            'cta_text' => $_POST['cta_text'] ?? '',
            'cta_button_text' => $_POST['cta_button_text'] ?? '',
            'cta_button_link' => $_POST['cta_button_link'] ?? '',
            'apply_title' => $_POST['apply_title'] ?? '',
            'apply_subtitle' => $_POST['apply_subtitle'] ?? '',
            'apply_form_title' => $_POST['apply_form_title'] ?? '',
            'apply_form_text' => $_POST['apply_form_text'] ?? '',
            'apply_success_message' => $_POST['apply_success_message'] ?? '',
            'footer_text' => $_POST['footer_text'] ?? '',
        ], '/cms/content', 'Updated CMS content sections.');
    }

    public function updateDesign(): void
    {
        $this->persist([
            'primary_color' => $_POST['primary_color'] ?? '',
            'secondary_color' => $_POST['secondary_color'] ?? '',
            'accent_color' => $_POST['accent_color'] ?? '',
            'text_color' => $_POST['text_color'] ?? '',
            'header_background' => $_POST['header_background'] ?? '',
            'section_background' => $_POST['section_background'] ?? '',
            'footer_background' => $_POST['footer_background'] ?? '',
            'button_radius' => $_POST['button_radius'] ?? '',
        ], '/cms/design', 'Updated CMS design settings.');
    }

    public function updateMedia(): void
    {
        $this->requireAdmin();

        try {
            $pdo = $this->db();
            $cms = $this->ensureCmsRow($pdo);
            $imageService = new CmsImageService();

            $fields = [
                'website_logo' => trim((string)($cms['website_logo'] ?? '')),
                'website_favicon' => trim((string)($cms['website_favicon'] ?? '')),
                'hero_image' => trim((string)($cms['hero_image'] ?? '')),
                'about_image' => trim((string)($cms['about_image'] ?? '')),
            ];

            foreach (array_keys(CmsImageService::PRESETS) as $field) {
                if (!empty($_POST['remove_' . $field])) {
                    $imageService->deleteStoredFile($fields[$field]);
                    $fields[$field] = '';
                    continue;
                }

                $upload = $_FILES[$field] ?? null;
                if (!is_array($upload) || (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $imageService->deleteStoredFile($fields[$field]);
                $fields[$field] = $imageService->processUpload($upload, $field);
            }

            $stmt = $pdo->prepare('
                UPDATE cms_settings
                SET website_logo = ?, website_favicon = ?, hero_image = ?, about_image = ?
                WHERE id = ?
            ');
            $stmt->execute([
                $fields['website_logo'],
                $fields['website_favicon'],
                $fields['hero_image'],
                $fields['about_image'],
                (int)($cms['id'] ?? 0),
            ]);

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession('CMS', 'UPDATE', 'Updated CMS media uploads.');
            }

            $this->redirectWithStatus('/cms/media', 'success', 'Media uploaded and resized successfully.');
        } catch (Throwable $e) {
            error_log('CmsController@updateMedia error: ' . $e->getMessage());
            $this->redirectWithStatus('/cms/media', 'error', $e->getMessage());
        }
    }

    public function updateNavigation(): void
    {
        $this->persist([
            'nav_home_label' => $_POST['nav_home_label'] ?? '',
            'nav_about_label' => $_POST['nav_about_label'] ?? '',
            'nav_plans_label' => $_POST['nav_plans_label'] ?? '',
            'nav_contact_label' => $_POST['nav_contact_label'] ?? '',
            'nav_apply_label' => $_POST['nav_apply_label'] ?? '',
        ], '/cms/navigation', 'Updated CMS navigation labels.');
    }

    public function updateSettings(): void
    {
        $this->persist([
            'company_name' => $_POST['company_name'] ?? '',
            'company_email' => $_POST['company_email'] ?? '',
            'company_phone' => $_POST['company_phone'] ?? '',
            'company_address' => $_POST['company_address'] ?? '',
        ], '/cms/settings', 'Updated CMS website settings.');
    }

    public function update(): void
    {
        $this->updateContent();
    }
}
