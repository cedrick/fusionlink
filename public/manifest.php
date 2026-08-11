<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';

if (!class_exists('CmsService', false)) {
    require_once __DIR__ . '/../app/Services/CmsService.php';
}

header('Content-Type: application/manifest+json; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

$base = base_path();
$scope = $base === '' ? '/' : $base . '/';
$cms = CmsService::get();
$appName = trim((string)($cms['company_name'] ?? 'FusionLink'));
$shortName = mb_strlen($appName) > 12 ? 'FusionLink' : $appName;

echo json_encode([
    'id' => $scope,
    'name' => $appName . ' ISP Billing',
    'short_name' => $shortName,
    'description' => 'Install the FusionLink ISP billing app for invoices, payments, and customer management.',
    'start_url' => url('/login'),
    'scope' => $scope,
    'display' => 'standalone',
    'orientation' => 'any',
    'background_color' => '#050505',
    'theme_color' => '#6d28d9',
    'icons' => [
        [
            'src' => url('/icon-192.png'),
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
        [
            'src' => url('/icon-512.png'),
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
