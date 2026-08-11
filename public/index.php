<?php

require_once __DIR__ . '/../core/helpers.php';

configure_session_cookie();
session_start();
attempt_remember_me_login();
send_security_headers();

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = base_path();

if ($basePath !== '' && str_starts_with($requestUri, $basePath)) {
    $requestUri = substr($requestUri, strlen($basePath)) ?: '/';
}

// Allow the PHP built-in server to serve real static files directly
$staticFile = realpath(__DIR__ . $requestUri);
$publicDir = realpath(__DIR__);

if (
    $requestUri !== '/' &&
    $staticFile &&
    $publicDir &&
    str_starts_with($staticFile, $publicDir) &&
    is_file($staticFile)
) {
    return false;
}

require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Database.php';
if (file_exists(__DIR__ . '/../app/Services/ActivityLogger.php')) {
    require_once __DIR__ . '/../app/Services/ActivityLogger.php';
}
require_once __DIR__ . '/../app/Controllers/HomeController.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/DashboardController.php';
require_once __DIR__ . '/../app/Controllers/CustomerController.php';
require_once __DIR__ . '/../app/Controllers/UserController.php';
require_once __DIR__ . '/../app/Controllers/PlanController.php';
require_once __DIR__ . '/../app/Controllers/PaymentController.php';
require_once __DIR__ . '/../app/Controllers/SubscriptionController.php';
require_once __DIR__ . '/../app/Controllers/InvoiceController.php';
require_once __DIR__ . '/../app/Controllers/SettingController.php';
require_once __DIR__ . '/../app/Controllers/PageController.php';
require_once __DIR__ . '/../app/Controllers/BookingController.php';
require_once __DIR__ . '/../app/Controllers/PersonnelController.php';
require_once __DIR__ . '/../app/Controllers/InquiryController.php';
require_once __DIR__ . '/../app/Services/RememberMeService.php';
require_once __DIR__ . '/../vendor/autoload.php';

$router = new Router();

require_once __DIR__ . '/../routes/web.php';

$router->resolve($_SERVER['REQUEST_URI']);
