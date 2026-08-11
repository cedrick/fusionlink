<?php

class FieldService
{
    public const DEFAULT_WORK_START = '08:00:00';
    public const DEFAULT_WORK_END = '17:00:00';
    public const DEFAULT_SLOT_INTERVAL = 60;
    public const DEFAULT_SAME_DAY_LEAD_MINUTES = 120;
    public const ADMIN_SAME_DAY_LEAD_MINUTES = 15;

    private static function ensureTimezone(): void
    {
        static $configured = false;
        if ($configured) {
            return;
        }

        $timezone = 'Asia/Manila';
        $configFile = __DIR__ . '/../../config/app.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            $configuredTimezone = trim((string)($config['timezone'] ?? ''));
            if ($configuredTimezone !== '') {
                $timezone = $configuredTimezone;
            }
        }

        date_default_timezone_set($timezone);
        $configured = true;
    }

    private static function todayDate(): string
    {
        self::ensureTimezone();
        $now = function_exists('app_now') ? app_now() : new DateTimeImmutable('now');

        return $now->format('Y-m-d');
    }

    public static function isSundayClosed(string $date): bool
    {
        self::ensureTimezone();
        $normalized = self::normalizeDate($date);
        if ($normalized === null) {
            return false;
        }

        return (int)date('w', strtotime($normalized)) === 0;
    }

    public static function nextOpenDate(?string $fromDate = null): string
    {
        self::ensureTimezone();
        $date = self::normalizeDate($fromDate ?? self::todayDate()) ?? self::todayDate();

        while (self::isSundayClosed($date)) {
            $date = date('Y-m-d', strtotime($date . ' +1 day'));
        }

        return $date;
    }

    public static function assertOperationsDay(string $date): void
    {
        if (self::isSundayClosed($date)) {
            throw new RuntimeException('Sunday is closed. Please choose a date from Monday to Saturday.');
        }
    }

    private static function sameDayLeadMinutes(bool $isAdminBooking): int
    {
        return $isAdminBooking ? self::ADMIN_SAME_DAY_LEAD_MINUTES : self::DEFAULT_SAME_DAY_LEAD_MINUTES;
    }

    public static function ensureSchema(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'field_service_types')) {
            $pdo->exec("
                CREATE TABLE field_service_types (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    name VARCHAR(120) NOT NULL,
                    duration_minutes INT UNSIGNED NOT NULL DEFAULT 60,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!self::tableExists($pdo, 'field_personnel')) {
            $pdo->exec("
                CREATE TABLE field_personnel (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    full_name VARCHAR(120) NOT NULL,
                    phone VARCHAR(20) NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    work_start_time TIME NOT NULL DEFAULT '08:00:00',
                    work_end_time TIME NOT NULL DEFAULT '17:00:00',
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!self::tableExists($pdo, 'field_personnel_services')) {
            $pdo->exec("
                CREATE TABLE field_personnel_services (
                    personnel_id INT UNSIGNED NOT NULL,
                    service_type_id INT UNSIGNED NOT NULL,
                    PRIMARY KEY (personnel_id, service_type_id),
                    KEY idx_service_type (service_type_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!self::tableExists($pdo, 'service_bookings')) {
            $pdo->exec("
                CREATE TABLE service_bookings (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    service_type_id INT UNSIGNED NOT NULL,
                    personnel_id INT UNSIGNED NOT NULL,
                    customer_id INT UNSIGNED NULL,
                    service_request_id INT UNSIGNED NULL,
                    customer_name VARCHAR(150) NOT NULL,
                    customer_phone VARCHAR(20) NOT NULL,
                    customer_email VARCHAR(150) NULL,
                    address TEXT NULL,
                    booking_date DATE NOT NULL,
                    start_time TIME NOT NULL,
                    end_time TIME NOT NULL,
                    status ENUM('BOOKED','COMPLETED','CANCELLED') NOT NULL DEFAULT 'BOOKED',
                    notes TEXT NULL,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_personnel_date (personnel_id, booking_date, status),
                    KEY idx_booking_date (booking_date, status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        self::seedDefaults($pdo);
        self::normalizeServiceTypeNames($pdo);
    }

    public static function displayServiceTypeName(string $name): string
    {
        $clean = trim($name);
        if ($clean === '') {
            return $name;
        }

        $clean = preg_replace('/\s*[\(\-–—]\s*\d+\s*(?:min(?:ute)?s?)\s*\)?\s*$/iu', '', $clean);
        $clean = preg_replace('/\s+\d+\s*(?:min(?:ute)?s?)\s*$/iu', '', $clean);

        $clean = trim($clean);

        return $clean !== '' ? $clean : trim($name);
    }

    public static function getActiveServiceTypes(PDO $pdo): array
    {
        self::ensureSchema($pdo);

        $rows = $pdo->query("
            SELECT id, name, duration_minutes
            FROM field_service_types
            WHERE is_active = 1
            ORDER BY name ASC
        ")->fetchAll();

        foreach ($rows as &$row) {
            $row['name'] = self::displayServiceTypeName((string)($row['name'] ?? ''));
        }
        unset($row);

        return $rows;
    }

    public static function getActivePersonnel(PDO $pdo): array
    {
        self::ensureSchema($pdo);

        return $pdo->query("
            SELECT id, full_name, phone, work_start_time, work_end_time
            FROM field_personnel
            WHERE is_active = 1
            ORDER BY full_name ASC
        ")->fetchAll();
    }

    public static function getPersonnelForService(PDO $pdo, int $serviceTypeId, bool $allowAllActiveFallback = false): array
    {
        self::ensureSchema($pdo);

        if ($serviceTypeId <= 0) {
            return $allowAllActiveFallback ? self::getActivePersonnel($pdo) : [];
        }

        $stmt = $pdo->prepare("
            SELECT p.id, p.full_name, p.phone, p.work_start_time, p.work_end_time
            FROM field_personnel p
            INNER JOIN field_personnel_services ps ON ps.personnel_id = p.id
            WHERE ps.service_type_id = ?
              AND p.is_active = 1
            ORDER BY p.full_name ASC
        ");
        $stmt->execute([$serviceTypeId]);
        $personnel = $stmt->fetchAll();

        if ($personnel === [] && $allowAllActiveFallback) {
            return self::getActivePersonnel($pdo);
        }

        return $personnel;
    }

    public static function getAvailableSlots(PDO $pdo, int $serviceTypeId, string $date, bool $isAdminBooking = false): array
    {
        self::ensureSchema($pdo);
        self::ensureTimezone();

        $serviceType = self::getServiceType($pdo, $serviceTypeId);
        if (!$serviceType) {
            return [];
        }

        $bookingDate = self::normalizeDate($date);
        if ($bookingDate === null) {
            return [];
        }

        if (self::isSundayClosed($bookingDate)) {
            return [];
        }

        $durationMinutes = max(15, (int)($serviceType['duration_minutes'] ?? 60));
        $personnel = self::getPersonnelForService($pdo, $serviceTypeId, $isAdminBooking);
        $slots = [];
        $leadMinutes = self::sameDayLeadMinutes($isAdminBooking);

        foreach ($personnel as $person) {
            $personnelId = (int)($person['id'] ?? 0);
            if ($personnelId <= 0) {
                continue;
            }

            $daySlots = self::buildSlotsForPersonnel(
                $pdo,
                $personnelId,
                (string)($person['full_name'] ?? 'Personnel'),
                $bookingDate,
                (string)($person['work_start_time'] ?? self::DEFAULT_WORK_START),
                (string)($person['work_end_time'] ?? self::DEFAULT_WORK_END),
                $durationMinutes,
                $leadMinutes
            );

            $slots = array_merge($slots, $daySlots);
        }

        usort($slots, static function (array $a, array $b): int {
            $startCompare = strcmp((string)$a['start_time'], (string)$b['start_time']);
            if ($startCompare !== 0) {
                return $startCompare;
            }

            return strcmp((string)$a['personnel_name'], (string)$b['personnel_name']);
        });

        return $slots;
    }

    public static function hasSameDayAvailability(PDO $pdo, int $serviceTypeId, bool $isAdminBooking = false): bool
    {
        $today = self::todayDate();
        foreach (self::getAvailableSlots($pdo, $serviceTypeId, $today, $isAdminBooking) as $slot) {
            if (!empty($slot['same_day'])) {
                return true;
            }
        }

        return false;
    }

    public static function getAvailabilityMeta(PDO $pdo, int $serviceTypeId, string $date, bool $isAdminBooking = false): array
    {
        $personnel = self::getPersonnelForService($pdo, $serviceTypeId);
        $slots = self::getAvailableSlots($pdo, $serviceTypeId, $date, $isAdminBooking);
        $normalizedDate = self::normalizeDate($date);

        return [
            'personnel_count' => count($personnel),
            'slot_count' => count($slots),
            'same_day_available' => $normalizedDate === self::todayDate() && $slots !== [],
            'is_admin_booking' => $isAdminBooking,
        ];
    }

    public static function getWeekSlots(PDO $pdo, int $serviceTypeId, string $weekStartDate, bool $isAdminBooking = false): array
    {
        self::ensureSchema($pdo);
        self::ensureTimezone();

        $weekStart = self::normalizeWeekStart($weekStartDate);
        if ($weekStart === null) {
            throw new RuntimeException('Invalid week start date.');
        }

        $personnel = self::getPersonnelForService($pdo, $serviceTypeId, $isAdminBooking);
        $gridStartHour = 7;
        $gridEndHour = 18;

        foreach ($personnel as $person) {
            $startHour = (int)date('G', strtotime((string)($person['work_start_time'] ?? self::DEFAULT_WORK_START)));
            $endHour = (int)date('G', strtotime((string)($person['work_end_time'] ?? self::DEFAULT_WORK_END)));
            $gridStartHour = min($gridStartHour, max(0, $startHour));
            $gridEndHour = max($gridEndHour, min(23, $endHour));
        }

        $hourRows = [];
        for ($hour = $gridStartHour; $hour < $gridEndHour; $hour++) {
            $hourRows[] = sprintf('%02d:00', $hour);
        }

        $days = [];
        for ($offset = 0; $offset < 7; $offset++) {
            $date = date('Y-m-d', strtotime($weekStart . ' +' . $offset . ' days'));
            $timestamp = strtotime($date);
            $isClosed = self::isSundayClosed($date);
            $slots = $isClosed ? [] : self::getAvailableSlots($pdo, $serviceTypeId, $date, $isAdminBooking);
            $slotMap = [];

            foreach ($slots as $slot) {
                $hourKey = substr((string)($slot['start_time'] ?? ''), 0, 5);
                if ($hourKey !== '') {
                    $slotMap[$hourKey] = $slot;
                }
            }

            $days[] = [
                'date' => $date,
                'weekday' => strtoupper(date('D', $timestamp)),
                'day' => (int)date('j', $timestamp),
                'month' => date('M', $timestamp),
                'is_today' => $date === self::todayDate(),
                'is_closed' => $isClosed,
                'closed_label' => $isClosed ? 'Closed' : '',
                'slots' => $slots,
                'slot_map' => $slotMap,
            ];
        }

        $weekEnd = $days[6]['date'] ?? $weekStart;

        return [
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'week_label' => date('M j', strtotime($weekStart)) . ' – ' . date('M j', strtotime($weekEnd)),
            'today' => self::todayDate(),
            'hour_rows' => $hourRows,
            'personnel_count' => count($personnel),
            'days' => $days,
        ];
    }

    public static function getBookingById(PDO $pdo, int $bookingId): ?array
    {
        self::ensureSchema($pdo);

        $stmt = $pdo->prepare("
            SELECT
                b.*,
                st.name AS service_name,
                st.duration_minutes,
                p.full_name AS personnel_name
            FROM service_bookings b
            INNER JOIN field_service_types st ON st.id = b.service_type_id
            INNER JOIN field_personnel p ON p.id = b.personnel_id
            WHERE b.id = ?
            LIMIT 1
        ");
        $stmt->execute([$bookingId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function updateBooking(PDO $pdo, int $bookingId, array $input): array
    {
        self::ensureSchema($pdo);
        self::ensureTimezone();

        $existing = self::getBookingById($pdo, $bookingId);
        if (!$existing) {
            throw new RuntimeException('Booking not found.');
        }

        if (strtoupper((string)($existing['status'] ?? '')) !== 'BOOKED') {
            throw new RuntimeException('Only active bookings can be rescheduled.');
        }

        $input['is_admin'] = !empty($input['is_admin']);
        $serviceTypeId = (int)($input['service_type_id'] ?? $existing['service_type_id'] ?? 0);
        $personnelId = (int)($input['personnel_id'] ?? $existing['personnel_id'] ?? 0);
        $bookingDate = self::normalizeDate((string)($input['booking_date'] ?? $existing['booking_date'] ?? ''));
        $startTime = self::normalizeTime((string)($input['start_time'] ?? $existing['start_time'] ?? ''));

        if ($serviceTypeId <= 0 || $personnelId <= 0 || $bookingDate === null || $startTime === null) {
            throw new RuntimeException('Please choose a valid date and time on the calendar.');
        }

        self::assertOperationsDay($bookingDate);

        $serviceType = self::getServiceType($pdo, $serviceTypeId);
        if (!$serviceType || (int)($serviceType['is_active'] ?? 0) !== 1) {
            throw new RuntimeException('Selected service is unavailable.');
        }

        $personnel = self::getPersonnelById($pdo, $personnelId);
        if (!$personnel || (int)($personnel['is_active'] ?? 0) !== 1) {
            throw new RuntimeException('Selected personnel is unavailable.');
        }

        if (!self::personnelHandlesService($pdo, $personnelId, $serviceTypeId)) {
            throw new RuntimeException('This personnel is not assigned to the selected service.');
        }

        $durationMinutes = max(15, (int)($serviceType['duration_minutes'] ?? 60));
        $endTime = self::addMinutesToTime($startTime, $durationMinutes);
        $isAdminBooking = !empty($input['is_admin']);

        $workStart = (string)($personnel['work_start_time'] ?? self::DEFAULT_WORK_START);
        $workEnd = (string)($personnel['work_end_time'] ?? self::DEFAULT_WORK_END);
        if ($startTime < $workStart || $endTime > $workEnd) {
            throw new RuntimeException('Selected time is outside personnel working hours.');
        }

        if ($bookingDate === self::todayDate()) {
            $earliest = self::earliestSameDayStart($isAdminBooking);
            if ($startTime < $earliest) {
                throw new RuntimeException('Selected time has already passed or is too soon. Tap a later open slot today.');
            }
        }

        if (self::hasConflict($pdo, $personnelId, $bookingDate, $startTime, $endTime, $bookingId)) {
            throw new RuntimeException('That time slot is already booked. Choose another open cell.');
        }

        $customerName = trim((string)($input['customer_name'] ?? $existing['customer_name'] ?? ''));
        $customerPhone = self::normalizePhone((string)($input['customer_phone'] ?? $existing['customer_phone'] ?? ''));
        $customerEmail = trim((string)($input['customer_email'] ?? $existing['customer_email'] ?? ''));
        $address = trim((string)($input['address'] ?? $existing['address'] ?? ''));
        $notes = trim((string)($input['notes'] ?? $existing['notes'] ?? ''));

        $update = $pdo->prepare("
            UPDATE service_bookings
            SET service_type_id = ?,
                personnel_id = ?,
                customer_name = ?,
                customer_phone = ?,
                customer_email = ?,
                address = ?,
                booking_date = ?,
                start_time = ?,
                end_time = ?,
                notes = ?
            WHERE id = ?
              AND status = 'BOOKED'
        ");
        $update->execute([
            $serviceTypeId,
            $personnelId,
            $customerName,
            $customerPhone,
            $customerEmail !== '' ? $customerEmail : null,
            $address !== '' ? $address : null,
            $bookingDate,
            $startTime,
            $endTime,
            $notes !== '' ? $notes : null,
            $bookingId,
        ]);

        return [
            'id' => $bookingId,
            'service_name' => (string)($serviceType['name'] ?? 'Service'),
            'personnel_name' => (string)($personnel['full_name'] ?? 'Personnel'),
            'booking_date' => $bookingDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'customer_name' => $customerName,
        ];
    }

    private static function normalizeWeekStart(string $date): ?string
    {
        $normalized = self::normalizeDate($date);
        if ($normalized === null) {
            return null;
        }

        $timestamp = strtotime($normalized);
        $dayOfWeek = (int)date('N', $timestamp);
        $mondayOffset = $dayOfWeek - 1;

        return date('Y-m-d', strtotime($normalized . ' -' . $mondayOffset . ' days'));
    }

    public static function createBooking(PDO $pdo, array $input): array
    {
        self::ensureSchema($pdo);
        self::ensureTimezone();

        $serviceTypeId = (int)($input['service_type_id'] ?? 0);
        $personnelId = (int)($input['personnel_id'] ?? 0);
        $bookingDate = self::normalizeDate((string)($input['booking_date'] ?? ''));
        $startTime = self::normalizeTime((string)($input['start_time'] ?? ''));
        $customerName = trim((string)($input['customer_name'] ?? ''));
        $customerPhone = self::normalizePhone((string)($input['customer_phone'] ?? ''));
        $customerEmail = trim((string)($input['customer_email'] ?? ''));
        $address = trim((string)($input['address'] ?? ''));
        $notes = trim((string)($input['notes'] ?? ''));
        $customerId = (int)($input['customer_id'] ?? 0);
        $serviceRequestId = (int)($input['service_request_id'] ?? 0);
        $isAdminBooking = !empty($input['is_admin']);

        if ($serviceTypeId <= 0 || $personnelId <= 0 || $bookingDate === null || $startTime === null) {
            throw new RuntimeException('Please choose a valid service, personnel, date, and time slot.');
        }

        self::assertOperationsDay($bookingDate);

        if ($customerName === '') {
            throw new RuntimeException('Customer name is required.');
        }

        if (!self::isValidPhone($customerPhone)) {
            throw new RuntimeException('Customer phone must be 11 digits and start with 09.');
        }

        if ($customerEmail !== '' && !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Please enter a valid customer email.');
        }

        $serviceType = self::getServiceType($pdo, $serviceTypeId);
        if (!$serviceType || (int)($serviceType['is_active'] ?? 0) !== 1) {
            throw new RuntimeException('Selected service is unavailable.');
        }

        $personnel = self::getPersonnelById($pdo, $personnelId);
        if (!$personnel || (int)($personnel['is_active'] ?? 0) !== 1) {
            throw new RuntimeException('Selected personnel is unavailable.');
        }

        if (!self::personnelHandlesService($pdo, $personnelId, $serviceTypeId)) {
            if ($isAdminBooking) {
                self::ensurePersonnelServiceAssignment($pdo, $personnelId, $serviceTypeId);
            } else {
                throw new RuntimeException('This personnel is not assigned to the selected service.');
            }
        }

        $durationMinutes = max(15, (int)($serviceType['duration_minutes'] ?? 60));
        $endTime = self::addMinutesToTime($startTime, $durationMinutes);

        $workStart = (string)($personnel['work_start_time'] ?? self::DEFAULT_WORK_START);
        $workEnd = (string)($personnel['work_end_time'] ?? self::DEFAULT_WORK_END);
        if ($isAdminBooking) {
            if ($startTime < $workStart || $startTime > $workEnd) {
                throw new RuntimeException('Selected time is outside technician working hours.');
            }
            if ($endTime > $workEnd) {
                $endTime = $workEnd;
            }
        } elseif ($startTime < $workStart || $endTime > $workEnd) {
            throw new RuntimeException('Selected time is outside personnel working hours.');
        }

        if ($bookingDate === self::todayDate()) {
            $earliest = self::earliestSameDayStart($isAdminBooking);
            if ($startTime < $earliest) {
                if ($isAdminBooking) {
                    throw new RuntimeException('Selected time has already passed or is too soon. Choose a later slot today.');
                }

                throw new RuntimeException('Same-day bookings must be at least 2 hours from now.');
            }
        }

        $pdo->beginTransaction();

        try {
            if (self::hasConflict($pdo, $personnelId, $bookingDate, $startTime, $endTime)) {
                throw new RuntimeException('That time slot was just booked. Please choose another slot.');
            }

            $insert = $pdo->prepare("
                INSERT INTO service_bookings (
                    service_type_id, personnel_id, customer_id, service_request_id,
                    customer_name, customer_phone, customer_email, address,
                    booking_date, start_time, end_time, status, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'BOOKED', ?)
            ");
            $insert->execute([
                $serviceTypeId,
                $personnelId,
                $customerId > 0 ? $customerId : null,
                $serviceRequestId > 0 ? $serviceRequestId : null,
                $customerName,
                $customerPhone,
                $customerEmail !== '' ? $customerEmail : null,
                $address !== '' ? $address : null,
                $bookingDate,
                $startTime,
                $endTime,
                $notes !== '' ? $notes : null,
            ]);

            $bookingId = (int)$pdo->lastInsertId();
            $pdo->commit();

            return [
                'id' => $bookingId,
                'service_name' => (string)($serviceType['name'] ?? 'Service'),
                'personnel_name' => (string)($personnel['full_name'] ?? 'Personnel'),
                'booking_date' => $bookingDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'same_day' => $bookingDate === self::todayDate(),
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function ensurePersonnelServiceAssignment(PDO $pdo, int $personnelId, int $serviceTypeId): void
    {
        self::ensureSchema($pdo);

        if ($personnelId <= 0 || $serviceTypeId <= 0) {
            return;
        }

        $stmt = $pdo->prepare('
            INSERT IGNORE INTO field_personnel_services (personnel_id, service_type_id)
            VALUES (?, ?)
        ');
        $stmt->execute([$personnelId, $serviceTypeId]);
    }

    public static function cancelBooking(PDO $pdo, int $bookingId): void
    {
        self::ensureSchema($pdo);

        $stmt = $pdo->prepare("
            UPDATE service_bookings
            SET status = 'CANCELLED'
            WHERE id = ?
              AND status = 'BOOKED'
        ");
        $stmt->execute([$bookingId]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Booking not found or already closed.');
        }
    }

    public static function completeBooking(PDO $pdo, int $bookingId): array
    {
        self::ensureSchema($pdo);

        $existing = self::getBookingById($pdo, $bookingId);
        if (!$existing) {
            throw new RuntimeException('Booking not found.');
        }

        if (strtoupper((string)($existing['status'] ?? '')) !== 'BOOKED') {
            throw new RuntimeException('Only active bookings can be marked as done.');
        }

        $stmt = $pdo->prepare("
            UPDATE service_bookings
            SET status = 'COMPLETED'
            WHERE id = ?
              AND status = 'BOOKED'
        ");
        $stmt->execute([$bookingId]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Booking could not be completed.');
        }

        $completed = self::getBookingById($pdo, $bookingId);
        if (!$completed) {
            throw new RuntimeException('Completed booking could not be loaded.');
        }

        return $completed;
    }

    public static function syncPersonnelServices(PDO $pdo, int $personnelId, array $serviceTypeIds): void
    {
        self::ensureSchema($pdo);

        $pdo->prepare('DELETE FROM field_personnel_services WHERE personnel_id = ?')->execute([$personnelId]);

        $insert = $pdo->prepare('
            INSERT INTO field_personnel_services (personnel_id, service_type_id)
            VALUES (?, ?)
        ');

        foreach ($serviceTypeIds as $serviceTypeId) {
            $serviceTypeId = (int)$serviceTypeId;
            if ($serviceTypeId > 0) {
                $insert->execute([$personnelId, $serviceTypeId]);
            }
        }
    }

    public static function getPersonnelServiceTypeIds(PDO $pdo, int $personnelId): array
    {
        self::ensureSchema($pdo);

        $stmt = $pdo->prepare('SELECT service_type_id FROM field_personnel_services WHERE personnel_id = ?');
        $stmt->execute([$personnelId]);

        return array_map('intval', array_column($stmt->fetchAll(), 'service_type_id'));
    }

    private static function buildSlotsForPersonnel(
        PDO $pdo,
        int $personnelId,
        string $personnelName,
        string $bookingDate,
        string $workStart,
        string $workEnd,
        int $durationMinutes,
        int $sameDayLeadMinutes = self::DEFAULT_SAME_DAY_LEAD_MINUTES
    ): array {
        $interval = max(15, $durationMinutes);
        $slots = [];
        $cursor = self::normalizeTime($workStart);
        $endBoundary = self::normalizeTime($workEnd);

        if ($cursor === null || $endBoundary === null) {
            return [];
        }

        $isToday = $bookingDate === self::todayDate();
        $earliestToday = self::earliestSameDayStartFromLead($sameDayLeadMinutes);

        while (self::addMinutesToTime($cursor, $durationMinutes) <= $endBoundary) {
            $slotEnd = self::addMinutesToTime($cursor, $durationMinutes);

            if ($isToday && $cursor < $earliestToday) {
                $cursor = self::addMinutesToTime($cursor, $interval);
                continue;
            }

            if (!self::hasConflict($pdo, $personnelId, $bookingDate, $cursor, $slotEnd)) {
                $slots[] = [
                    'personnel_id' => $personnelId,
                    'personnel_name' => $personnelName,
                    'booking_date' => $bookingDate,
                    'start_time' => $cursor,
                    'end_time' => $slotEnd,
                    'label' => self::formatSlotLabel($cursor, $slotEnd, $personnelName),
                    'same_day' => $isToday,
                ];
            }

            $cursor = self::addMinutesToTime($cursor, $interval);
        }

        return $slots;
    }

    private static function hasConflict(
        PDO $pdo,
        int $personnelId,
        string $bookingDate,
        string $startTime,
        string $endTime,
        int $excludeBookingId = 0
    ): bool {
        $sql = "
            SELECT id
            FROM service_bookings
            WHERE personnel_id = ?
              AND booking_date = ?
              AND status = 'BOOKED'
              AND start_time < ?
              AND end_time > ?
        ";
        $params = [$personnelId, $bookingDate, $endTime, $startTime];

        if ($excludeBookingId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeBookingId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (bool)$stmt->fetch();
    }

    private static function getServiceType(PDO $pdo, int $serviceTypeId): ?array
    {
        $stmt = $pdo->prepare('
            SELECT id, name, duration_minutes, is_active
            FROM field_service_types
            WHERE id = ?
            LIMIT 1
        ');
        $stmt->execute([$serviceTypeId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private static function getPersonnelById(PDO $pdo, int $personnelId): ?array
    {
        $stmt = $pdo->prepare('
            SELECT id, full_name, phone, work_start_time, work_end_time, is_active
            FROM field_personnel
            WHERE id = ?
            LIMIT 1
        ');
        $stmt->execute([$personnelId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private static function personnelHandlesService(PDO $pdo, int $personnelId, int $serviceTypeId): bool
    {
        $stmt = $pdo->prepare('
            SELECT 1
            FROM field_personnel_services
            WHERE personnel_id = ?
              AND service_type_id = ?
            LIMIT 1
        ');
        $stmt->execute([$personnelId, $serviceTypeId]);

        return (bool)$stmt->fetchColumn();
    }

    private static function seedDefaults(PDO $pdo): void
    {
        $count = (int)$pdo->query('SELECT COUNT(*) FROM field_service_types')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $pdo->exec("
            INSERT INTO field_service_types (name, duration_minutes, is_active) VALUES
            ('Installation', 60, 1),
            ('Repair / Troubleshooting', 90, 1),
            ('Site Survey', 60, 1)
        ");
    }

    private static function normalizeServiceTypeNames(PDO $pdo): void
    {
        $rows = $pdo->query('SELECT id, name FROM field_service_types')->fetchAll();
        if ($rows === []) {
            return;
        }

        $update = $pdo->prepare('UPDATE field_service_types SET name = ? WHERE id = ?');
        foreach ($rows as $row) {
            $currentName = (string)($row['name'] ?? '');
            $cleanName = self::displayServiceTypeName($currentName);
            if ($cleanName !== $currentName) {
                $update->execute([$cleanName, (int)($row['id'] ?? 0)]);
            }
        }
    }

    private static function earliestSameDayStart(bool $isAdminBooking): string
    {
        return self::earliestSameDayStartFromLead(self::sameDayLeadMinutes($isAdminBooking));
    }

    private static function earliestSameDayStartFromLead(int $leadMinutes): string
    {
        self::ensureTimezone();
        $now = function_exists('app_now') ? app_now() : new DateTimeImmutable('now');

        return $now->modify('+' . max(0, $leadMinutes) . ' minutes')->format('H:i:s');
    }

    private static function formatSlotLabel(string $startTime, string $endTime, string $personnelName): string
    {
        return date('g:i A', strtotime($startTime))
            . ' - '
            . date('g:i A', strtotime($endTime))
            . ' • '
            . $personnelName;
    }

    private static function normalizeDate(string $date): ?string
    {
        $date = trim($date);
        if ($date === '') {
            return null;
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return null;
        }

        $now = function_exists('app_now') ? app_now() : new DateTimeImmutable('now');

        return $now->setTimestamp($timestamp)->format('Y-m-d');
    }

    private static function normalizeTime(string $time): ?string
    {
        self::ensureTimezone();
        $time = trim($time);
        if ($time === '') {
            return null;
        }

        $timestamp = strtotime($time);
        if ($timestamp === false) {
            return null;
        }

        return date('H:i:s', $timestamp);
    }

    private static function addMinutesToTime(string $time, int $minutes): string
    {
        self::ensureTimezone();

        return date('H:i:s', strtotime($time) + ($minutes * 60));
    }

    private static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', trim($phone));
        if (str_starts_with($phone, '63') && strlen($phone) === 12) {
            $phone = '0' . substr($phone, 2);
        }

        return $phone;
    }

    private static function isValidPhone(string $phone): bool
    {
        return (bool)preg_match('/^09\d{9}$/', $phone);
    }

    private static function tableExists(PDO $pdo, string $tableName): bool
    {
        $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$tableName]);

        return (bool)$stmt->fetchColumn();
    }
}
