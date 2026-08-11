<?php

if (file_exists(__DIR__ . '/../Services/ActivityLogger.php')) {
    require_once __DIR__ . '/../Services/ActivityLogger.php';
}

if (file_exists(__DIR__ . '/../Services/FieldService.php')) {
    require_once __DIR__ . '/../Services/FieldService.php';
}

if (file_exists(__DIR__ . '/../Services/EmailAlertService.php')) {
    require_once __DIR__ . '/../Services/EmailAlertService.php';
}

if (file_exists(__DIR__ . '/../Services/ApplicationWorkflowService.php')) {
    require_once __DIR__ . '/../Services/ApplicationWorkflowService.php';
}

if (file_exists(__DIR__ . '/InquiryController.php')) {
    require_once __DIR__ . '/InquiryController.php';
}

class BookingController
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
        $_SESSION['booking_flash'] = ['type' => $type, 'message' => $message];
        redirect($url);
        exit;
    }

    public function index(): void
    {
        $this->requireLogin();

        try {
            $pdo = $this->db();
            FieldService::ensureSchema($pdo);

            $defaultDate = FieldService::nextOpenDate();
            $dateFilter = trim((string)($_GET['date'] ?? $defaultDate));
            if (strtotime($dateFilter) === false) {
                $dateFilter = $defaultDate;
            }

            $statusFilter = strtoupper(trim((string)($_GET['status'] ?? '')));
            $allowedStatuses = ['BOOKED', 'COMPLETED', 'CANCELLED'];
            if ($statusFilter !== '' && !in_array($statusFilter, $allowedStatuses, true)) {
                $statusFilter = '';
            }

            $sql = "
                SELECT
                    b.*,
                    st.name AS service_name,
                    p.full_name AS personnel_name
                FROM service_bookings b
                INNER JOIN field_service_types st ON st.id = b.service_type_id
                INNER JOIN field_personnel p ON p.id = b.personnel_id
                WHERE b.booking_date = ?
            ";
            $params = [$dateFilter];

            if ($statusFilter !== '') {
                $sql .= ' AND b.status = ?';
                $params[] = $statusFilter;
            }

            $sql .= ' ORDER BY b.start_time ASC, b.id ASC';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $bookings = $stmt->fetchAll();

            View::render('bookings/index', [
                'title' => 'Service Bookings',
                'bookings' => $bookings,
                'dateFilter' => $dateFilter,
                'statusFilter' => $statusFilter,
                'flash' => $_SESSION['booking_flash'] ?? null,
            ]);
            unset($_SESSION['booking_flash']);
        } catch (Throwable $e) {
            $this->redirectWithFlash('/dashboard', 'error', 'Bookings load failed: ' . $e->getMessage());
        }
    }

    public function create(): void
    {
        $this->requireLogin();

        try {
            $pdo = $this->db();
            FieldService::ensureSchema($pdo);

            $serviceTypes = FieldService::getActiveServiceTypes($pdo);
            $customers = $pdo->query("
                SELECT id, full_name, phone, email, address
                FROM customers
                ORDER BY full_name ASC
            ")->fetchAll();

            $prefill = [
                'service_type_id' => (int)($_GET['service_type_id'] ?? 0),
                'customer_id' => (int)($_GET['customer_id'] ?? 0),
                'service_request_id' => (int)($_GET['inquiry_id'] ?? $_GET['service_request_id'] ?? 0),
                'booking_date' => FieldService::nextOpenDate(trim((string)($_GET['date'] ?? '')) ?: null),
                'customer_name' => '',
                'customer_phone' => '',
                'customer_email' => '',
                'address' => '',
            ];

            if ($prefill['customer_id'] > 0) {
                foreach ($customers as $customer) {
                    if ((int)($customer['id'] ?? 0) === $prefill['customer_id']) {
                        $prefill['customer_name'] = (string)($customer['full_name'] ?? '');
                        $prefill['customer_phone'] = (string)($customer['phone'] ?? '');
                        $prefill['customer_email'] = (string)($customer['email'] ?? '');
                        $prefill['address'] = (string)($customer['address'] ?? '');
                        break;
                    }
                }
            }

            if ($prefill['service_request_id'] > 0) {
                $stmt = $pdo->prepare('SELECT name, phone, email, address FROM service_requests WHERE id = ? LIMIT 1');
                $stmt->execute([$prefill['service_request_id']]);
                $inquiry = $stmt->fetch();
                if ($inquiry) {
                    $prefill['customer_name'] = (string)($inquiry['name'] ?? $prefill['customer_name']);
                    $prefill['customer_phone'] = (string)($inquiry['phone'] ?? $prefill['customer_phone']);
                    $prefill['customer_email'] = (string)($inquiry['email'] ?? $prefill['customer_email']);
                    $prefill['address'] = (string)($inquiry['address'] ?? $prefill['address']);
                }
            }

            $visitType = strtolower(trim((string)($_GET['visit_type'] ?? '')));
            if (class_exists('ApplicationWorkflowService')) {
                ApplicationWorkflowService::ensureVisitServiceTypes($pdo);
            }

            if ($visitType !== '' && class_exists('ApplicationWorkflowService')) {
                $visitServiceTypeId = ApplicationWorkflowService::resolveVisitServiceTypeId($pdo, $visitType);
                if ($visitServiceTypeId > 0) {
                    $prefill['service_type_id'] = $visitServiceTypeId;
                }
                $prefill['visit_type'] = $visitType;
            }

            $useSimpleScheduler = $prefill['service_request_id'] > 0 || $visitType !== '';
            $personnel = FieldService::getActivePersonnel($pdo);
            if ((int)($prefill['service_type_id'] ?? 0) > 0) {
                $assignedPersonnel = FieldService::getPersonnelForService($pdo, (int)$prefill['service_type_id'], true);
                if ($assignedPersonnel !== []) {
                    $personnel = $assignedPersonnel;
                }
            }

            $visitServiceLabel = '';
            if ($visitType === ApplicationWorkflowService::VISIT_OCULAR) {
                $visitServiceLabel = 'Ocular';
            } elseif ($visitType === ApplicationWorkflowService::VISIT_INSTALLATION) {
                $visitServiceLabel = 'Installation';
            }

            View::render('bookings/create', [
                'title' => 'Book Service',
                'serviceTypes' => $serviceTypes,
                'customers' => $customers,
                'prefill' => $prefill,
                'visitType' => $visitType,
                'visitServiceLabel' => $visitServiceLabel,
                'useSimpleScheduler' => $useSimpleScheduler,
                'personnel' => $personnel,
                'flash' => $_SESSION['booking_flash'] ?? null,
            ]);
            unset($_SESSION['booking_flash']);
        } catch (Throwable $e) {
            $this->redirectWithFlash('/bookings', 'error', $e->getMessage());
        }
    }

    public function store(): void
    {
        $this->requireLogin();

        try {
            $pdo = $this->db();
            $serviceRequestId = (int)($_POST['service_request_id'] ?? 0);

            if (class_exists('ApplicationWorkflowService')) {
                ApplicationWorkflowService::ensureVisitServiceTypes($pdo);

                if ($serviceRequestId > 0) {
                    $serviceTypeId = (int)($_POST['service_type_id'] ?? 0);
                    $visitType = trim((string)($_POST['visit_type'] ?? ''));
                    if ($visitType === '') {
                        $stmt = $pdo->prepare('SELECT name FROM field_service_types WHERE id = ? LIMIT 1');
                        $stmt->execute([$serviceTypeId]);
                        $serviceName = (string)$stmt->fetchColumn();
                        if (ApplicationWorkflowService::isInstallationServiceName($serviceName)) {
                            $visitType = ApplicationWorkflowService::VISIT_INSTALLATION;
                        } elseif (ApplicationWorkflowService::isOcularServiceName($serviceName)) {
                            $visitType = ApplicationWorkflowService::VISIT_OCULAR;
                        }
                    }
                    if ($visitType !== '') {
                        ApplicationWorkflowService::assertCanScheduleVisit($pdo, $serviceRequestId, $visitType);
                    }
                }
            }

            $result = FieldService::createBooking($pdo, [
                'service_type_id' => (int)($_POST['service_type_id'] ?? 0),
                'personnel_id' => (int)($_POST['personnel_id'] ?? 0),
                'booking_date' => (string)($_POST['booking_date'] ?? ''),
                'start_time' => (string)($_POST['start_time'] ?? ''),
                'customer_id' => (int)($_POST['customer_id'] ?? 0),
                'service_request_id' => $serviceRequestId,
                'customer_name' => (string)($_POST['customer_name'] ?? ''),
                'customer_phone' => (string)($_POST['customer_phone'] ?? ''),
                'customer_email' => (string)($_POST['customer_email'] ?? ''),
                'address' => (string)($_POST['address'] ?? ''),
                'notes' => (string)($_POST['notes'] ?? ''),
                'is_admin' => true,
            ]);

            if (class_exists('EmailAlertService')) {
                EmailAlertService::notifyServiceBookingCreated($pdo, $result, [
                    'customer_name' => trim((string)($_POST['customer_name'] ?? '')),
                    'customer_phone' => trim((string)($_POST['customer_phone'] ?? '')),
                    'customer_email' => trim((string)($_POST['customer_email'] ?? '')),
                    'address' => trim((string)($_POST['address'] ?? '')),
                ], $serviceRequestId > 0);
            }

            if ($serviceRequestId > 0 && class_exists('ApplicationWorkflowService')) {
                ApplicationWorkflowService::afterBookingCreated($pdo, $serviceRequestId);
            }

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession(
                    'Bookings',
                    'CREATE',
                    'Booked ' . $result['service_name'] . ' on ' . $result['booking_date'] . ' at ' . $result['start_time']
                );
            }

            $successMessage = 'Visit scheduled for ' . date('M j, Y', strtotime($result['booking_date']))
                . ' at ' . date('g:i A', strtotime($result['start_time']))
                . ' with ' . $result['personnel_name'] . '. Applicant was emailed the visit details.';

            if ($serviceRequestId > 0) {
                $this->redirectWithFlash('/inquiries', 'success', $successMessage);
            }

            $this->redirectWithFlash(
                '/bookings?date=' . urlencode($result['booking_date']),
                'success',
                $successMessage
            );
        } catch (Throwable $e) {
            $serviceRequestId = (int)($_POST['service_request_id'] ?? 0);
            $visitType = trim((string)($_POST['visit_type'] ?? ''));
            $redirect = '/bookings/create';
            $query = [];
            if ($serviceRequestId > 0) {
                $query['inquiry_id'] = $serviceRequestId;
            }
            if ($visitType !== '') {
                $query['visit_type'] = $visitType;
            }
            if ($query !== []) {
                $redirect .= '?' . http_build_query($query);
            }

            $_SESSION['booking_flash'] = ['type' => 'error', 'message' => $e->getMessage()];
            redirect($redirect);
            exit;
        }
    }

    public function cancel(): void
    {
        $this->requireLogin();

        $id = (int)($_POST['id'] ?? 0);
        $date = trim((string)($_POST['date'] ?? date('Y-m-d')));

        try {
            FieldService::cancelBooking($this->db(), $id);

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession('Bookings', 'CANCEL', 'Cancelled booking ID ' . $id);
            }

            $this->redirectWithFlash('/bookings?date=' . urlencode($date), 'success', 'Booking cancelled.');
        } catch (Throwable $e) {
            $this->redirectWithFlash('/bookings?date=' . urlencode($date), 'error', $e->getMessage());
        }
    }

    public function complete(): void
    {
        $this->requireLogin();

        $id = (int)($_POST['id'] ?? 0);
        $date = trim((string)($_POST['date'] ?? date('Y-m-d')));

        try {
            $pdo = $this->db();
            $booking = FieldService::completeBooking($pdo, $id);

            if (class_exists('EmailAlertService')) {
                EmailAlertService::notifyVisitCompleted($pdo, $booking);
            }

            $workflowResult = class_exists('ApplicationWorkflowService')
                ? ApplicationWorkflowService::afterBookingCompleted($pdo, $id)
                : null;

            $successMessage = 'Job marked as done for '
                . (string)($booking['customer_name'] ?? 'customer')
                . ' (' . (string)($booking['service_name'] ?? 'visit') . ').';

            if (is_array($workflowResult) && ($workflowResult['action'] ?? '') === 'installation_completed') {
                $inquiryId = (int)($workflowResult['inquiry_id'] ?? 0);
                if ($inquiryId > 0) {
                    $inquiryController = new InquiryController();
                    $convertMessage = $inquiryController->autoConvertAfterInstallation($pdo, $inquiryId);
                    $successMessage .= ' ' . $convertMessage;
                }
            } elseif (is_array($workflowResult) && ($workflowResult['action'] ?? '') === 'ocular_completed') {
                $successMessage .= ' Applicant was emailed that the ocular visit is complete.';
            }

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession('Bookings', 'COMPLETE', 'Completed booking ID ' . $id);
            }

            $redirectUrl = ((int)($booking['service_request_id'] ?? 0) > 0)
                ? '/inquiries'
                : '/bookings?date=' . urlencode($date);

            $this->redirectWithFlash($redirectUrl, 'success', $successMessage);
        } catch (Throwable $e) {
            $this->redirectWithFlash('/bookings?date=' . urlencode($date), 'error', $e->getMessage());
        }
    }

    public function availableSlots(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $pdo = $this->db();
            $serviceTypeId = (int)($_GET['service_type_id'] ?? 0);
            $date = trim((string)($_GET['date'] ?? (function_exists('app_now') ? app_now()->format('Y-m-d') : date('Y-m-d'))));
            $isAdminBooking = true;

            $slots = FieldService::getAvailableSlots($pdo, $serviceTypeId, $date, $isAdminBooking);
            $meta = FieldService::getAvailabilityMeta($pdo, $serviceTypeId, $date, $isAdminBooking);

            echo json_encode([
                'ok' => true,
                'date' => $date,
                'same_day_available' => !empty($meta['same_day_available']),
                'personnel_count' => (int)($meta['personnel_count'] ?? 0),
                'is_admin_booking' => true,
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
            echo json_encode([
                'ok' => false,
                'message' => $e->getMessage(),
                'personnel_count' => 0,
                'slots' => [],
            ]);
        }
        exit;
    }

    public function weekSlots(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $pdo = $this->db();
            $serviceTypeId = (int)($_GET['service_type_id'] ?? 0);
            $weekStart = trim((string)($_GET['week_start'] ?? (function_exists('app_now') ? app_now()->format('Y-m-d') : date('Y-m-d'))));
            $excludeBookingId = (int)($_GET['exclude_booking_id'] ?? 0);

            $week = FieldService::getWeekSlots($pdo, $serviceTypeId, $weekStart, true);

            if ($excludeBookingId > 0) {
                $existing = FieldService::getBookingById($pdo, $excludeBookingId);
                if ($existing && strtoupper((string)($existing['status'] ?? '')) === 'BOOKED') {
                    $existingDate = (string)($existing['booking_date'] ?? '');
                    $existingStart = substr((string)($existing['start_time'] ?? ''), 0, 5);
                    foreach ($week['days'] as &$day) {
                        if (($day['date'] ?? '') !== $existingDate) {
                            continue;
                        }

                        $day['slots'][] = [
                            'personnel_id' => (int)($existing['personnel_id'] ?? 0),
                            'personnel_name' => (string)($existing['personnel_name'] ?? 'Assigned'),
                            'booking_date' => $existingDate,
                            'start_time' => (string)($existing['start_time'] ?? ''),
                            'end_time' => (string)($existing['end_time'] ?? ''),
                            'label' => date('g:i A', strtotime((string)$existing['start_time']))
                                . ' - '
                                . date('g:i A', strtotime((string)$existing['end_time']))
                                . ' • '
                                . (string)($existing['personnel_name'] ?? 'Assigned'),
                            'same_day' => $existingDate === ($week['today'] ?? ''),
                            'current' => true,
                        ];
                        $day['slot_map'][$existingStart] = end($day['slots']);
                    }
                    unset($day);
                }
            }

            echo json_encode([
                'ok' => true,
                'week' => $week,
            ]);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'message' => $e->getMessage(),
            ]);
        }
        exit;
    }

    public function edit(): void
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->redirectWithFlash('/bookings', 'error', 'Invalid booking ID.');
        }

        try {
            $pdo = $this->db();
            FieldService::ensureSchema($pdo);

            $booking = FieldService::getBookingById($pdo, $id);
            if (!$booking) {
                $this->redirectWithFlash('/bookings', 'error', 'Booking not found.');
            }

            $serviceTypes = FieldService::getActiveServiceTypes($pdo);

            View::render('bookings/edit', [
                'title' => 'Edit Booking #' . $id,
                'booking' => $booking,
                'serviceTypes' => $serviceTypes,
                'flash' => $_SESSION['booking_flash'] ?? null,
            ]);
            unset($_SESSION['booking_flash']);
        } catch (Throwable $e) {
            $this->redirectWithFlash('/bookings', 'error', $e->getMessage());
        }
    }

    public function update(): void
    {
        $this->requireLogin();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->redirectWithFlash('/bookings', 'error', 'Invalid booking ID.');
        }

        try {
            $pdo = $this->db();
            $result = FieldService::updateBooking($pdo, $id, [
                'service_type_id' => (int)($_POST['service_type_id'] ?? 0),
                'personnel_id' => (int)($_POST['personnel_id'] ?? 0),
                'booking_date' => (string)($_POST['booking_date'] ?? ''),
                'start_time' => (string)($_POST['start_time'] ?? ''),
                'customer_name' => (string)($_POST['customer_name'] ?? ''),
                'customer_phone' => (string)($_POST['customer_phone'] ?? ''),
                'customer_email' => (string)($_POST['customer_email'] ?? ''),
                'address' => (string)($_POST['address'] ?? ''),
                'notes' => (string)($_POST['notes'] ?? ''),
                'is_admin' => true,
            ]);

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSession(
                    'Bookings',
                    'UPDATE',
                    'Rescheduled booking ID ' . $id . ' to ' . $result['booking_date'] . ' ' . $result['start_time']
                );
            }

            $this->redirectWithFlash(
                '/bookings?date=' . urlencode($result['booking_date']),
                'success',
                'Booking updated for ' . date('M j, Y', strtotime($result['booking_date']))
                    . ' at ' . date('g:i A', strtotime($result['start_time'])) . '.'
            );
        } catch (Throwable $e) {
            $_SESSION['booking_flash'] = ['type' => 'error', 'message' => $e->getMessage()];
            redirect('/bookings/edit?id=' . $id);
            exit;
        }
    }
}
