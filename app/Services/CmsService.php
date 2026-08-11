<?php

class CmsService
{
    public static function defaults(): array
    {
        return [
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

    public static function get(): array
    {
        $cms = self::defaults();

        try {
            $config = require __DIR__ . '/../../config/database.php';
            $dbName = $config['db'] ?? ($config['name'] ?? null);
            if (!$dbName) {
                return $cms;
            }

            $host = $config['host'] ?? '127.0.0.1';
            $user = $config['user'] ?? '';
            $pass = $config['pass'] ?? '';
            $charset = $config['charset'] ?? 'utf8mb4';
            $dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";

            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $stmt = $pdo->query('SELECT * FROM cms_settings ORDER BY id ASC LIMIT 1');
            $row = $stmt->fetch();
            if ($row) {
                $cms = array_merge($cms, $row);
            }
        } catch (Throwable $e) {
            error_log('CmsService@get error: ' . $e->getMessage());
        }

        return $cms;
    }
}
