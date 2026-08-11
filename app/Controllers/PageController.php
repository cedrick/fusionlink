<?php

require_once __DIR__ . '/../Services/CmsService.php';

if (file_exists(__DIR__ . '/../Services/EmailAlertService.php')) {
    require_once __DIR__ . '/../Services/EmailAlertService.php';
}

if (file_exists(__DIR__ . '/../Services/ReferralService.php')) {
    require_once __DIR__ . '/../Services/ReferralService.php';
}

if (file_exists(__DIR__ . '/../Services/CustomerPortalService.php')) {
    require_once __DIR__ . '/../Services/CustomerPortalService.php';
}

if (file_exists(__DIR__ . '/../Services/ExistingCustomerService.php')) {
    require_once __DIR__ . '/../Services/ExistingCustomerService.php';
}

if (file_exists(__DIR__ . '/../Services/FieldService.php')) {
    require_once __DIR__ . '/../Services/FieldService.php';
}

if (file_exists(__DIR__ . '/../Services/PlanService.php')) {
    require_once __DIR__ . '/../Services/PlanService.php';
}

class PageController
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

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', trim($phone));
    }

    private function isValidPhone(string $phone): bool
    {
        return (bool)preg_match('/^09\d{9}$/', $phone);
    }

    private function getPlans(PDO $pdo): array
    {
        if (class_exists('PlanService')) {
            PlanService::ensureSchema($pdo);
        }

        return $pdo->query('
            SELECT id, name, speed, price
            FROM plans
            WHERE is_legacy = 0
            ORDER BY price ASC, id ASC
        ')->fetchAll();
    }

    private function formatPlanLabel(array $plan): string
    {
        $name = trim((string)($plan['name'] ?? ''));
        $speed = trim((string)($plan['speed'] ?? ''));
        $price = number_format((float)($plan['price'] ?? 0), 2);

        return $name . ' - ' . $speed . ' - ₱' . $price;
    }

    public function index(): void
    {
        $cms = CmsService::get();
        $plans = [];

        try {
            $plans = $this->getPlans($this->db());
        } catch (Throwable $e) {
            error_log('PageController@index plans error: ' . $e->getMessage());
        }

        $applied = isset($_GET['applied']) && $_GET['applied'] === '1';
        $error = trim((string)($_GET['error'] ?? ''));
        $selectedPlanId = (int)($_GET['plan'] ?? 0);

        if ($selectedPlanId > 0) {
            $planIds = array_map(static fn(array $plan): int => (int)($plan['id'] ?? 0), $plans);
            if (!in_array($selectedPlanId, $planIds, true)) {
                $selectedPlanId = 0;
            }
        }

        View::renderPublic('page/index', [
            'title' => (string)($cms['company_name'] ?? 'FusionLink'),
            'cms' => $cms,
            'plans' => $plans,
            'applied' => $applied,
            'error' => $error,
            'selectedPlanId' => $selectedPlanId,
        ]);
    }

    private function redirectApply(string $query): void
    {
        redirect('/page?' . $query . '#apply');
    }

    public function submitApply(): void
    {
        if (!rate_limit('page_apply', 5, 600)) {
            $this->redirectApply('error=' . urlencode('Too many submissions. Please wait a few minutes and try again.'));
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = $this->normalizePhone((string)($_POST['phone'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $planId = (int)($_POST['plan_id'] ?? 0);
        $referredByPhone = ReferralService::normalizePhone((string)($_POST['referred_by_phone'] ?? ''));
        $planQuery = $planId > 0 ? '&plan=' . $planId : '';

        if (mb_strlen($name) > 120) {
            $this->redirectApply('error=' . urlencode('Full name is too long.') . $planQuery);
        }

        if ($name === '') {
            $this->redirectApply('error=' . urlencode('Full name is required.') . $planQuery);
        }

        if ($email === '') {
            $this->redirectApply('error=' . urlencode('Email is required.') . $planQuery);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirectApply('error=' . urlencode('Please enter a valid email address.') . $planQuery);
        }

        if (mb_strlen($email) > 190) {
            $this->redirectApply('error=' . urlencode('Email is too long.') . $planQuery);
        }

        if (!$this->isValidPhone($phone)) {
            $this->redirectApply('error=' . urlencode('Phone must be 11 digits and start with 09.') . $planQuery);
        }

        if ($referredByPhone !== '' && $referredByPhone === $phone) {
            $this->redirectApply('error=' . urlencode('Referrer phone cannot be the same as your phone number.') . $planQuery);
        }

        if ($referredByPhone !== '' && !preg_match('/^09\d{9}$/', $referredByPhone)) {
            $this->redirectApply('error=' . urlencode('Referrer phone must be 11 digits and start with 09.') . $planQuery);
        }

        if ($address === '') {
            $this->redirectApply('error=' . urlencode('Address is required.') . $planQuery);
        }

        if (mb_strlen($address) > 500) {
            $this->redirectApply('error=' . urlencode('Address is too long.') . $planQuery);
        }

        if ($planId <= 0) {
            $this->redirectApply('error=' . urlencode('Please select a plan.'));
        }

        try {
            $pdo = $this->db();
            if (class_exists('PlanService')) {
                PlanService::ensureSchema($pdo);
            }
            ReferralService::ensureSchema($pdo);

            if ($referredByPhone !== '') {
                $referrer = ReferralService::findReferrerByPhone($pdo, $referredByPhone);
                if (!$referrer) {
                    $this->redirectApply('error=' . urlencode('Referrer phone was not found among active customers.') . $planQuery);
                }
            }

            $stmt = $pdo->prepare('SELECT id, name, speed, price, is_legacy FROM plans WHERE id = ? LIMIT 1');
            $stmt->execute([$planId]);
            $plan = $stmt->fetch();

            if (!$plan) {
                $this->redirectApply('error=' . urlencode('Selected plan is invalid.'));
            }

            if (class_exists('PlanService') && PlanService::isLegacyPlan($plan)) {
                $this->redirectApply('error=' . urlencode('That plan is for existing customers only. Please choose another plan.') . $planQuery);
            }

            $planLabel = $this->formatPlanLabel($plan);

            $insert = $pdo->prepare('
                INSERT INTO service_requests (name, email, phone, address, plan, referred_by_phone, status, email_sent)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0)
            ');
            $insert->execute([
                $name,
                $email,
                $phone,
                $address,
                $planLabel,
                $referredByPhone !== '' ? $referredByPhone : null,
                'PENDING',
            ]);

            if (class_exists('EmailAlertService')) {
                EmailAlertService::notifyNewApplication($pdo, [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'plan' => $planLabel,
                    'referred_by_phone' => $referredByPhone,
                ]);
            }

            redirect('/page?applied=1#apply');
        } catch (Throwable $e) {
            error_log('PageController@submitApply error: ' . $e->getMessage());
            $this->redirectApply('error=' . urlencode('Unable to submit your request right now. Please try again.') . $planQuery);
        }
    }

    public function existingCustomerForm(): void
    {
        $cms = CmsService::get();
        $done = isset($_GET['done']) && $_GET['done'] === '1';
        $outcome = trim((string)($_GET['outcome'] ?? ''));
        $message = trim((string)($_GET['message'] ?? ''));
        $error = trim((string)($_GET['error'] ?? ''));

        View::renderPublic('page/existing', [
            'title' => 'Set Up Billing Portal - ' . (string)($cms['company_name'] ?? 'FusionLink'),
            'cms' => $cms,
            'done' => $done,
            'outcome' => $outcome,
            'message' => $message,
            'error' => $error,
        ]);
    }

    private function redirectExisting(string $query): void
    {
        redirect('/page/existing?' . $query);
    }

    public function submitExistingCustomer(): void
    {
        if (!rate_limit('page_existing_customer', 5, 600)) {
            $this->redirectExisting('error=' . urlencode('Too many submissions. Please wait a few minutes and try again.'));
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = $this->normalizePhone((string)($_POST['phone'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));

        try {
            if (!class_exists('ExistingCustomerService')) {
                throw new RuntimeException('This form is temporarily unavailable. Please contact our office.');
            }

            $result = ExistingCustomerService::processRegistration($this->db(), [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
            ]);

            $this->redirectExisting(
                'done=1'
                . '&outcome=' . urlencode((string)($result['outcome'] ?? 'pending_review'))
                . '&message=' . urlencode((string)($result['message'] ?? 'Thank you. Our team will contact you soon.'))
            );
        } catch (Throwable $e) {
            error_log('PageController@submitExistingCustomer error: ' . $e->getMessage());
            $this->redirectExisting('error=' . urlencode($e->getMessage()));
        }
    }

    public function bookServiceForm(): void
    {
        $cms = CmsService::get();
        $serviceTypes = [];

        try {
            $serviceTypes = FieldService::getActiveServiceTypes($this->db());
        } catch (Throwable $e) {
            error_log('PageController@bookServiceForm error: ' . $e->getMessage());
        }

        View::renderPublic('page/book', [
            'title' => 'Book a Service - ' . (string)($cms['company_name'] ?? 'FusionLink'),
            'cms' => $cms,
            'serviceTypes' => $serviceTypes,
            'done' => isset($_GET['done']) && $_GET['done'] === '1',
            'message' => trim((string)($_GET['message'] ?? '')),
            'error' => trim((string)($_GET['error'] ?? '')),
        ]);
    }

    private function redirectBook(string $query): void
    {
        redirect('/page/book?' . $query);
    }

    public function submitBookService(): void
    {
        if (!rate_limit('page_book_service', 5, 600)) {
            $this->redirectBook('error=' . urlencode('Too many booking attempts. Please wait a few minutes and try again.'));
        }

        try {
            if (!class_exists('FieldService')) {
                throw new RuntimeException('Booking is temporarily unavailable.');
            }

            $pdo = $this->db();
            $result = FieldService::createBooking($pdo, [
                'service_type_id' => (int)($_POST['service_type_id'] ?? 0),
                'personnel_id' => (int)($_POST['personnel_id'] ?? 0),
                'booking_date' => (string)($_POST['booking_date'] ?? ''),
                'start_time' => (string)($_POST['start_time'] ?? ''),
                'customer_name' => (string)($_POST['customer_name'] ?? ''),
                'customer_phone' => (string)($_POST['customer_phone'] ?? ''),
                'customer_email' => (string)($_POST['customer_email'] ?? ''),
                'address' => (string)($_POST['address'] ?? ''),
                'notes' => (string)($_POST['notes'] ?? ''),
            ]);

            if (class_exists('EmailAlertService')) {
                EmailAlertService::notifyServiceBookingCreated($pdo, $result, [
                    'customer_name' => trim((string)($_POST['customer_name'] ?? '')),
                    'customer_phone' => trim((string)($_POST['customer_phone'] ?? '')),
                    'customer_email' => trim((string)($_POST['customer_email'] ?? '')),
                    'address' => trim((string)($_POST['address'] ?? '')),
                ]);
            }

            $message = 'Booked for '
                . date('M j, Y', strtotime($result['booking_date']))
                . ' at '
                . date('g:i A', strtotime($result['start_time']))
                . ' with '
                . $result['personnel_name']
                . '.';

            $this->redirectBook('done=1&message=' . urlencode($message));
        } catch (Throwable $e) {
            error_log('PageController@submitBookService error: ' . $e->getMessage());
            $this->redirectBook('error=' . urlencode($e->getMessage()));
        }
    }

    public function publicBookingSlots(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $pdo = $this->db();
            $serviceTypeId = (int)($_GET['service_type_id'] ?? 0);
            $date = trim((string)($_GET['date'] ?? date('Y-m-d')));
            $slots = FieldService::getAvailableSlots($pdo, $serviceTypeId, $date);

            echo json_encode([
                'ok' => true,
                'date' => $date,
                'same_day_available' => $date === date('Y-m-d') && $slots !== [],
                'slots' => array_map(static function (array $slot): array {
                    return [
                        'personnel_id' => (int)($slot['personnel_id'] ?? 0),
                        'personnel_name' => (string)($slot['personnel_name'] ?? ''),
                        'start_time' => (string)($slot['start_time'] ?? ''),
                        'end_time' => (string)($slot['end_time'] ?? ''),
                        'label' => (string)($slot['label'] ?? ''),
                        'same_day' => !empty($slot['same_day']),
                    ];
                }, $slots),
            ]);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => $e->getMessage(), 'slots' => []]);
        }
        exit;
    }
}
