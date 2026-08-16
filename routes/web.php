<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (file_exists(__DIR__ . '/../app/Services/ActivityLogger.php')) {
    require_once __DIR__ . '/../app/Services/ActivityLogger.php';
}

if (file_exists(__DIR__ . '/../app/Controllers/ActivityLogController.php')) {
    require_once __DIR__ . '/../app/Controllers/ActivityLogController.php';
}

if (file_exists(__DIR__ . '/../app/Controllers/CronController.php')) {
    require_once __DIR__ . '/../app/Controllers/CronController.php';
}

if (file_exists(__DIR__ . '/../app/Controllers/InquiryController.php')) {
    require_once __DIR__ . '/../app/Controllers/InquiryController.php';
}

if (file_exists(__DIR__ . '/../app/Controllers/CmsController.php')) {
    require_once __DIR__ . '/../app/Controllers/CmsController.php';
}

/**
 * Simple auth guard (NO Auth class needed)
 */
$requireLogin = function () {
    if (empty($_SESSION['user'])) {
        redirect('/login');
    }
};

/**
 * CUSTOMER restriction
 */
if (isset($_SESSION['user']) && (($_SESSION['user']['role'] ?? '') === 'ROLE_CUSTOMER')) {
    $allowed = [
        '/payments/create',
        '/payments/store',
        '/invoices/official-receipt',
        '/invoices/pdf',
        '/account/password',
        '/logout',
        '/page',
        '/page/apply',
        '/page/existing',
        '/page/book',
        '/page/booking-slots',
    ];

    $uri = request_path();

    if (!in_array($uri, $allowed, true)) {
        redirect('/payments/create');
    }
}

// -------------------- PUBLIC ROUTES --------------------

$router->get('/reports/outstanding', function () {
    (new InvoiceController)->outstandingReport();
});

$router->get('/reports/revenue', function () {
    (new PaymentController)->revenueReport();
});

$router->get('/', function () {
    redirect('/login');
});

$router->get('/page', function () {
    (new PageController())->index();
});

$router->post('/page/apply', function () {
    (new PageController())->submitApply();
});

$router->get('/page/existing', function () {
    (new PageController())->existingCustomerForm();
});

$router->post('/page/existing', function () {
    (new PageController())->submitExistingCustomer();
});

$router->get('/page/book', function () {
    (new PageController())->bookServiceForm();
});

$router->post('/page/book', function () {
    (new PageController())->submitBookService();
});

$router->get('/page/booking-slots', function () {
    (new PageController())->publicBookingSlots();
});

$router->get('/login', function () {
    (new AuthController())->loginForm();
});

$router->post('/login', function () {
    (new AuthController())->login();
});

$router->get('/verify-otp', function () {
    (new AuthController())->verifyOtpForm();
});

$router->post('/verify-otp', function () {
    (new AuthController())->verifyOtp();
});

$router->post('/verify-otp/resend', function () {
    (new AuthController())->resendOtp();
});

$router->get('/account/password', function () use ($requireLogin) {
    $requireLogin();
    (new AuthController())->changePasswordForm();
});

$router->post('/account/password', function () use ($requireLogin) {
    $requireLogin();
    (new AuthController())->changePassword();
});

$router->get('/logout', function () {
    if (class_exists('ActivityLogger') && !empty($_SESSION['user'])) {
        ActivityLogger::logSession('Auth', 'LOGOUT', 'User logged out.');
    }

    if (class_exists('RememberMeService') && class_exists('Database')) {
        try {
            RememberMeService::revokeCurrent(Database::connect());
        } catch (Throwable $e) {
            error_log('logout remember-me revoke error: ' . $e->getMessage());
        }
    }

    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
    redirect('/login');
    exit;
});

$router->get('/users', function () use ($requireLogin) {
    $requireLogin();
    (new UserController())->index();
});

$router->get('/users/create', function () use ($requireLogin) {
    $requireLogin();
    (new UserController())->create();
});

$router->post('/users/store', function () use ($requireLogin) {
    $requireLogin();
    (new UserController())->store();
});

$router->get('/users/edit', function () use ($requireLogin) {
    $requireLogin();
    (new UserController())->edit();
});

$router->post('/users/update', function () use ($requireLogin) {
    $requireLogin();
    (new UserController())->update();
});

$router->post('/users/delete', function () use ($requireLogin) {
    $requireLogin();
    (new UserController())->delete();
});

// -------------------- PROTECTED ROUTES --------------------

$router->get('/dashboard', function () use ($requireLogin) {
    $requireLogin();
    (new DashboardController())->index();
});

// ---------------- Activity Logs ----------------

$router->get('/activity-logs', function () use ($requireLogin) {
    $requireLogin();
    (new ActivityLogController())->index();
});

// ---------------- Billing Cron (token protected, no login) ----------------

$router->get('/cron/billing', function () {
    (new CronController())->billing();
});

// ---------------- Settings ----------------

$router->get('/settings', function () use ($requireLogin) {
    $requireLogin();
    (new SettingController())->index();
});

$router->post('/settings/update', function () use ($requireLogin) {
    $requireLogin();
    (new SettingController())->update();
});

$router->post('/settings/test-omada', function () use ($requireLogin) {
    $requireLogin();
    (new SettingController())->testOmada();
});

$router->post('/settings/test-email', function () use ($requireLogin) {
    $requireLogin();
    (new SettingController())->testEmail();
});

$router->post('/settings/backup', function () use ($requireLogin) {
    $requireLogin();
    (new SettingController())->backup();
});

$router->get('/settings/backup/download', function () use ($requireLogin) {
    $requireLogin();
    (new SettingController())->downloadBackup();
});

$router->post('/settings/restore', function () use ($requireLogin) {
    $requireLogin();
    (new SettingController())->restore();
});

$router->post('/settings/restore-latest', function () use ($requireLogin) {
    $requireLogin();
    (new SettingController())->restoreLatest();
});

$router->post('/settings/restore-selected', function () use ($requireLogin) {
    $requireLogin();
    (new SettingController())->restoreSelected();
});

$router->post('/settings/backup/delete', function () use ($requireLogin) {
    $requireLogin();
    (new SettingController())->deleteBackup();
});

$router->post('/settings/reset', function () use ($requireLogin) {
    $requireLogin();
    (new SettingController())->reset();
});

// ---------------- CMS ----------------

$router->get('/cms', function () use ($requireLogin) {
    $requireLogin();
    (new CmsController())->index();
});

$router->get('/cms/dashboard', function () use ($requireLogin) {
    $requireLogin();
    (new CmsController())->dashboard();
});

$router->get('/cms/content', function () use ($requireLogin) {
    $requireLogin();
    (new CmsController())->content();
});

$router->post('/cms/content/update', function () use ($requireLogin) {
    $requireLogin();
    (new CmsController())->updateContent();
});

$router->get('/cms/design', function () use ($requireLogin) {
    $requireLogin();
    (new CmsController())->design();
});

$router->post('/cms/design/update', function () use ($requireLogin) {
    $requireLogin();
    (new CmsController())->updateDesign();
});

$router->get('/cms/media', function () use ($requireLogin) {
    $requireLogin();
    (new CmsController())->media();
});

$router->post('/cms/media/update', function () use ($requireLogin) {
    $requireLogin();
    (new CmsController())->updateMedia();
});

$router->get('/cms/navigation', function () use ($requireLogin) {
    $requireLogin();
    (new CmsController())->navigation();
});

$router->post('/cms/navigation/update', function () use ($requireLogin) {
    $requireLogin();
    (new CmsController())->updateNavigation();
});

$router->get('/cms/settings', function () use ($requireLogin) {
    $requireLogin();
    (new CmsController())->settings();
});

$router->post('/cms/settings/update', function () use ($requireLogin) {
    $requireLogin();
    (new CmsController())->updateSettings();
});

// Backward compatibility
$router->post('/cms/update', function () use ($requireLogin) {
    $requireLogin();
    (new CmsController())->updateContent();
});
// ---------------- Customers ----------------

$router->get('/customers', function () {
    if (!isset($_SESSION['user'])) { redirect('/login'); }
    (new CustomerController())->index();
});

$router->get('/customers/create', function () {
    if (!isset($_SESSION['user'])) { redirect('/login'); }
    (new CustomerController())->create();
});

$router->post('/customers/store', function () {
    if (!isset($_SESSION['user'])) { redirect('/login'); }
    (new CustomerController())->store();
});

$router->get('/customers/edit', function () {
    if (!isset($_SESSION['user'])) { redirect('/login'); }
    (new CustomerController())->edit();
});

$router->post('/customers/update', function () {
    if (!isset($_SESSION['user'])) { redirect('/login'); }
    (new CustomerController())->update();
});

$router->post('/customers/delete', function () {
    if (!isset($_SESSION['user'])) { redirect('/login'); }
    (new CustomerController())->delete();
});

$router->post('/customers/activate-portal', function () {
    if (!isset($_SESSION['user'])) { redirect('/login'); }
    (new CustomerController())->activatePortal();
});

$router->post('/customers/reset-portal-password', function () {
    if (!isset($_SESSION['user'])) { redirect('/login'); }
    (new CustomerController())->resetPortalPassword();
});

$router->get('/customers/import', function () {
    if (!isset($_SESSION['user'])) { redirect('/login'); }
    (new CustomerController())->import();
});

$router->post('/customers/import', function () {
    if (!isset($_SESSION['user'])) { redirect('/login'); }
    (new CustomerController())->processImport();
});

$router->post('/customers/installment/save', function () {
    if (!isset($_SESSION['user'])) { redirect('/login'); }
    (new CustomerController())->saveInstallment();
});

$router->post('/customers/installment/cancel', function () {
    if (!isset($_SESSION['user'])) { redirect('/login'); }
    (new CustomerController())->cancelInstallment();
});

$router->post('/customers/network/suspend', function () {
    if (!isset($_SESSION['user'])) { redirect('/login'); }
    (new CustomerController())->suspendNetwork();
});

$router->post('/customers/network/restore', function () {
    if (!isset($_SESSION['user'])) { redirect('/login'); }
    (new CustomerController())->restoreNetwork();
});

// ---------------- Bookings ----------------

$router->get('/bookings', function () use ($requireLogin) {
    $requireLogin();
    (new BookingController())->index();
});

$router->get('/bookings/create', function () use ($requireLogin) {
    $requireLogin();
    (new BookingController())->create();
});

$router->post('/bookings/store', function () use ($requireLogin) {
    $requireLogin();
    (new BookingController())->store();
});

$router->post('/bookings/cancel', function () use ($requireLogin) {
    $requireLogin();
    (new BookingController())->cancel();
});

$router->post('/bookings/complete', function () use ($requireLogin) {
    $requireLogin();
    (new BookingController())->complete();
});

$router->get('/bookings/available-slots', function () use ($requireLogin) {
    $requireLogin();
    (new BookingController())->availableSlots();
});

$router->get('/bookings/week-slots', function () use ($requireLogin) {
    $requireLogin();
    (new BookingController())->weekSlots();
});

$router->get('/bookings/edit', function () use ($requireLogin) {
    $requireLogin();
    (new BookingController())->edit();
});

$router->post('/bookings/update', function () use ($requireLogin) {
    $requireLogin();
    (new BookingController())->update();
});

$router->get('/personnel', function () use ($requireLogin) {
    $requireLogin();
    (new PersonnelController())->index();
});

$router->post('/personnel/store', function () use ($requireLogin) {
    $requireLogin();
    (new PersonnelController())->storePersonnel();
});

$router->post('/personnel/update', function () use ($requireLogin) {
    $requireLogin();
    (new PersonnelController())->updatePersonnel();
});

$router->post('/personnel/service-types/store', function () use ($requireLogin) {
    $requireLogin();
    (new PersonnelController())->storeServiceType();
});

// ---------------- Plans ----------------

$router->get('/plans', function () {
    if (!isset($_SESSION['user'])) { redirect('/login'); }
    (new PlanController())->index();
});

$router->get('/plans/create', function () {
    if (!isset($_SESSION['user'])) { redirect('/login'); }
    (new PlanController())->create();
});

$router->post('/plans/store', function () {
    if (!isset($_SESSION['user'])) { redirect('/login'); }
    (new PlanController())->store();
});

$router->get('/plans/edit', function () {
    if (!isset($_SESSION['user'])) { redirect('/login'); }
    (new PlanController())->edit();
});

$router->post('/plans/update', function () {
    if (!isset($_SESSION['user'])) { redirect('/login'); }
    (new PlanController())->update();
});

$router->get('/plans/delete', function () {
    if (!isset($_SESSION['user'])) { redirect('/login'); }
    (new PlanController())->delete();
});

// ---------------- Subscriptions ----------------

$router->get('/subscriptions', function () use ($requireLogin) {
    $requireLogin();
    (new SubscriptionController())->index();
});

$router->get('/subscriptions/create', function () use ($requireLogin) {
    $requireLogin();
    (new SubscriptionController())->create();
});

$router->post('/subscriptions', function () use ($requireLogin) {
    $requireLogin();
    (new SubscriptionController())->store();
});

$router->get('/subscriptions/edit', function () use ($requireLogin) {
    $requireLogin();
    (new SubscriptionController())->edit();
});

$router->post('/subscriptions/update', function () use ($requireLogin) {
    $requireLogin();
    (new SubscriptionController())->update();
});

$router->get('/subscriptions/delete', function () use ($requireLogin) {
    $requireLogin();
    (new SubscriptionController())->delete();
});

// ---------------- Invoices ----------------

$router->get('/invoices', function () use ($requireLogin) {
    $requireLogin();
    (new InvoiceController())->index();
});

$router->get('/invoices/create', function () use ($requireLogin) {
    $requireLogin();
    (new InvoiceController())->create();
});

$router->post('/invoices/generate-send', function () use ($requireLogin) {
    $requireLogin();
    (new InvoiceController())->generateAndSendMonthlyInvoices();
});

$router->post('/invoices/store', function () use ($requireLogin) {
    $requireLogin();
    (new InvoiceController())->store();
});

$router->get('/invoices/view', function () use ($requireLogin) {
    $requireLogin();
    (new InvoiceController())->view();
});

$router->get('/invoices/delete', function () use ($requireLogin) {
    $requireLogin();
    (new InvoiceController())->delete();
});

$router->get('/invoices/pdf', function () use ($requireLogin) {
    $requireLogin();
    (new InvoiceController())->pdf();
});

$router->get('/invoices/email', function () use ($requireLogin) {
    $requireLogin();
    (new InvoiceController())->emailInvoice();
});

$router->post('/invoices/official-receipt', function () use ($requireLogin) {
    $requireLogin();
    (new InvoiceController())->attachOfficialReceipt();
});

$router->get('/invoices/official-receipt', function () use ($requireLogin) {
    $requireLogin();
    (new InvoiceController())->downloadOfficialReceipt();
});

// ---------------- Payments ----------------

$router->get('/payments', function () use ($requireLogin) {
    $requireLogin();
    (new PaymentController())->index();
});

$router->get('/payments/create', function () use ($requireLogin) {
    $requireLogin();
    (new PaymentController())->create();
});

$router->post('/payments/store', function () use ($requireLogin) {
    $requireLogin();
    (new PaymentController())->store();
});

$router->post('/payments/verify', function () {
    (new PaymentController)->verify();
});

$router->post('/payments/reject', function () {
    (new PaymentController)->reject();
});

// ---------------- Inquiries ----------------

$router->get('/inquiries', function () use ($requireLogin) {
    $requireLogin();
    (new InquiryController())->index();
});

$router->post('/inquiries/register-customer', function () use ($requireLogin) {
    $requireLogin();
    (new InquiryController())->registerCustomer();
});

$router->post('/inquiries/reject', function () use ($requireLogin) {
    $requireLogin();
    (new InquiryController())->reject();
});

$router->post('/inquiries/delete', function () use ($requireLogin) {
    $requireLogin();
    (new InquiryController())->delete();
});

$router->post('/inquiries/clear-processed', function () use ($requireLogin) {
    $requireLogin();
    (new InquiryController())->clearProcessed();
});
