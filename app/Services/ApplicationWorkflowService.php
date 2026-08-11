<?php

if (file_exists(__DIR__ . '/FieldService.php')) {
    require_once __DIR__ . '/FieldService.php';
}

class ApplicationWorkflowService
{
    public const VISIT_OCULAR = 'ocular';
    public const VISIT_INSTALLATION = 'installation';

    public static function ensureVisitServiceTypes(PDO $pdo): void
    {
        FieldService::ensureSchema($pdo);

        if (!self::findServiceTypeIdByNames($pdo, ['Ocular'])) {
            $pdo->exec("
                INSERT INTO field_service_types (name, duration_minutes, is_active)
                VALUES ('Ocular', 60, 1)
            ");
        }

        if (!self::findServiceTypeIdByNames($pdo, ['Installation'])) {
            $pdo->exec("
                INSERT INTO field_service_types (name, duration_minutes, is_active)
                VALUES ('Installation', 60, 1)
            ");
        }

        $pdo->exec("
            UPDATE field_service_types
            SET duration_minutes = 60
            WHERE LOWER(name) IN ('ocular', 'installation', 'site survey')
        ");

        self::syncPersonnelToVisitServiceTypes($pdo);
    }

    private static function syncPersonnelToVisitServiceTypes(PDO $pdo): void
    {
        $serviceTypeIds = [];
        foreach (['Ocular', 'Site Survey', 'Installation'] as $name) {
            $serviceTypeId = self::findServiceTypeIdByNames($pdo, [$name]);
            if ($serviceTypeId > 0) {
                $serviceTypeIds[] = $serviceTypeId;
            }
        }

        if ($serviceTypeIds === []) {
            return;
        }

        $personnelIds = $pdo->query('SELECT id FROM field_personnel WHERE is_active = 1')->fetchAll(PDO::FETCH_COLUMN);
        if ($personnelIds === []) {
            return;
        }

        $insert = $pdo->prepare('
            INSERT IGNORE INTO field_personnel_services (personnel_id, service_type_id)
            VALUES (?, ?)
        ');

        foreach ($personnelIds as $personnelId) {
            foreach ($serviceTypeIds as $serviceTypeId) {
                $insert->execute([(int)$personnelId, (int)$serviceTypeId]);
            }
        }
    }

    public static function resolveVisitServiceTypeId(PDO $pdo, string $visitType): int
    {
        self::ensureVisitServiceTypes($pdo);

        if ($visitType === self::VISIT_INSTALLATION) {
            return self::findServiceTypeIdByNames($pdo, ['Installation']);
        }

        return self::findServiceTypeIdByNames($pdo, ['Ocular', 'Site Survey']);
    }

    public static function isOcularServiceName(string $name): bool
    {
        $name = strtolower(trim($name));

        return $name === 'ocular' || $name === 'site survey';
    }

    public static function isInstallationServiceName(string $name): bool
    {
        return strtolower(trim($name)) === 'installation';
    }

    public static function getInquiryBookings(PDO $pdo, int $inquiryId): array
    {
        if ($inquiryId <= 0) {
            return [];
        }

        FieldService::ensureSchema($pdo);

        $stmt = $pdo->prepare("
            SELECT
                b.*,
                st.name AS service_name,
                p.full_name AS personnel_name
            FROM service_bookings b
            INNER JOIN field_service_types st ON st.id = b.service_type_id
            INNER JOIN field_personnel p ON p.id = b.personnel_id
            WHERE b.service_request_id = ?
            ORDER BY b.booking_date ASC, b.start_time ASC, b.id ASC
        ");
        $stmt->execute([$inquiryId]);

        return $stmt->fetchAll();
    }

    public static function getWorkflowStatesForInquiries(PDO $pdo, array $inquiryIds): array
    {
        $inquiryIds = array_values(array_filter(array_map('intval', $inquiryIds), static fn(int $id): bool => $id > 0));
        if ($inquiryIds === []) {
            return [];
        }

        FieldService::ensureSchema($pdo);

        $placeholders = implode(',', array_fill(0, count($inquiryIds), '?'));
        $stmt = $pdo->prepare("
            SELECT
                b.service_request_id,
                b.id,
                b.status,
                b.booking_date,
                b.start_time,
                st.name AS service_name,
                p.full_name AS personnel_name
            FROM service_bookings b
            INNER JOIN field_service_types st ON st.id = b.service_type_id
            INNER JOIN field_personnel p ON p.id = b.personnel_id
            WHERE b.service_request_id IN ({$placeholders})
            ORDER BY b.service_request_id ASC, b.booking_date ASC, b.start_time ASC, b.id ASC
        ");
        $stmt->execute($inquiryIds);
        $rows = $stmt->fetchAll();

        $states = [];
        foreach ($inquiryIds as $inquiryId) {
            $states[$inquiryId] = self::emptyWorkflowState();
        }

        foreach ($rows as $row) {
            $inquiryId = (int)($row['service_request_id'] ?? 0);
            if ($inquiryId <= 0 || !isset($states[$inquiryId])) {
                continue;
            }

            $serviceName = (string)($row['service_name'] ?? '');
            $status = strtoupper((string)($row['status'] ?? ''));
            $bookingSummary = [
                'id' => (int)($row['id'] ?? 0),
                'status' => $status,
                'service_name' => $serviceName,
                'personnel_name' => (string)($row['personnel_name'] ?? ''),
                'booking_date' => (string)($row['booking_date'] ?? ''),
                'start_time' => (string)($row['start_time'] ?? ''),
            ];

            if (self::isOcularServiceName($serviceName)) {
                $states[$inquiryId]['ocular'] = $bookingSummary;
            } elseif (self::isInstallationServiceName($serviceName)) {
                $states[$inquiryId]['installation'] = $bookingSummary;
            } else {
                $states[$inquiryId]['other'][] = $bookingSummary;
            }
        }

        foreach ($states as $inquiryId => $state) {
            $states[$inquiryId]['can_convert'] = self::workflowAllowsConversion($state);
            $states[$inquiryId]['summary'] = self::buildWorkflowSummary($state);
            $states[$inquiryId]['can_schedule_ocular'] = self::canScheduleOcular($state);
            $states[$inquiryId]['can_schedule_installation'] = self::canScheduleInstallation($state);
        }

        return $states;
    }

    public static function canScheduleOcular(array $state): bool
    {
        return !in_array(self::visitLifecycle($state['ocular'] ?? null), ['booked', 'completed'], true);
    }

    public static function canScheduleInstallation(array $state): bool
    {
        $installationLifecycle = self::visitLifecycle($state['installation'] ?? null);
        if (in_array($installationLifecycle, ['booked', 'completed'], true)) {
            return false;
        }

        $ocularLifecycle = self::visitLifecycle($state['ocular'] ?? null);
        if ($ocularLifecycle === 'booked') {
            return false;
        }

        return true;
    }

    public static function assertCanScheduleVisit(PDO $pdo, int $inquiryId, string $visitType): void
    {
        if ($inquiryId <= 0) {
            return;
        }

        $states = self::getWorkflowStatesForInquiries($pdo, [$inquiryId]);
        $state = $states[$inquiryId] ?? self::emptyWorkflowState();

        if ($visitType === self::VISIT_INSTALLATION) {
            if (!self::canScheduleInstallation($state)) {
                $ocularLifecycle = self::visitLifecycle($state['ocular'] ?? null);
                if ($ocularLifecycle === 'booked') {
                    throw new RuntimeException('Complete the ocular visit first, or wait until it is marked done, before scheduling installation.');
                }

                throw new RuntimeException('Installation is already scheduled or completed for this application.');
            }

            return;
        }

        if (!self::canScheduleOcular($state)) {
            throw new RuntimeException('Ocular visit is already scheduled or completed for this application.');
        }
    }

    private static function visitLifecycle(?array $booking): string
    {
        if (!is_array($booking)) {
            return 'none';
        }

        $status = strtoupper((string)($booking['status'] ?? ''));

        return match ($status) {
            'BOOKED' => 'booked',
            'COMPLETED' => 'completed',
            'CANCELLED' => 'cancelled',
            default => 'none',
        };
    }

    public static function canConvertPlanApplication(PDO $pdo, int $inquiryId): bool
    {
        $states = self::getWorkflowStatesForInquiries($pdo, [$inquiryId]);

        return !empty($states[$inquiryId]['can_convert']);
    }

    public static function markInquiryVisitScheduled(PDO $pdo, int $inquiryId): void
    {
        if ($inquiryId <= 0) {
            return;
        }

        $stmt = $pdo->prepare("
            UPDATE service_requests
            SET status = 'VISIT_SCHEDULED'
            WHERE id = ?
              AND status = 'PENDING'
        ");
        $stmt->execute([$inquiryId]);
    }

    public static function afterBookingCreated(PDO $pdo, int $serviceRequestId): void
    {
        if ($serviceRequestId <= 0) {
            return;
        }

        self::markInquiryVisitScheduled($pdo, $serviceRequestId);
    }

    public static function afterBookingCompleted(PDO $pdo, int $bookingId): ?array
    {
        FieldService::ensureSchema($pdo);

        $booking = FieldService::getBookingById($pdo, $bookingId);
        if (!$booking) {
            return null;
        }

        $inquiryId = (int)($booking['service_request_id'] ?? 0);
        if ($inquiryId <= 0) {
            return null;
        }

        $serviceName = (string)($booking['service_name'] ?? '');

        if (self::isInstallationServiceName($serviceName)) {
            return [
                'action' => 'installation_completed',
                'inquiry_id' => $inquiryId,
                'booking' => $booking,
            ];
        }

        if (self::isOcularServiceName($serviceName)) {
            return [
                'action' => 'ocular_completed',
                'inquiry_id' => $inquiryId,
                'booking' => $booking,
            ];
        }

        return null;
    }

    private static function emptyWorkflowState(): array
    {
        return [
            'ocular' => null,
            'installation' => null,
            'other' => [],
            'can_convert' => false,
            'summary' => 'No visit scheduled yet',
        ];
    }

    private static function workflowAllowsConversion(array $state): bool
    {
        $installation = $state['installation'] ?? null;
        if (!is_array($installation)) {
            return false;
        }

        return strtoupper((string)($installation['status'] ?? '')) === 'COMPLETED';
    }

    private static function buildWorkflowSummary(array $state): string
    {
        $parts = [];

        $ocular = $state['ocular'] ?? null;
        if (is_array($ocular)) {
            $parts[] = 'Ocular: ' . self::formatVisitStatus($ocular);
        }

        $installation = $state['installation'] ?? null;
        if (is_array($installation)) {
            $parts[] = 'Installation: ' . self::formatVisitStatus($installation);
        }

        if ($parts === []) {
            return 'No visit scheduled yet';
        }

        return implode(' | ', $parts);
    }

    private static function formatVisitStatus(array $booking): string
    {
        $status = strtoupper((string)($booking['status'] ?? ''));
        $date = (string)($booking['booking_date'] ?? '');
        $dateLabel = $date !== '' ? date('M j', strtotime($date)) : 'TBD';

        if ($status === 'COMPLETED') {
            return 'done';
        }

        if ($status === 'CANCELLED') {
            return 'cancelled';
        }

        return 'scheduled ' . $dateLabel;
    }

    private static function findServiceTypeIdByNames(PDO $pdo, array $names): int
    {
        foreach ($names as $name) {
            $stmt = $pdo->prepare('
                SELECT id
                FROM field_service_types
                WHERE LOWER(name) = LOWER(?)
                  AND is_active = 1
                ORDER BY id ASC
                LIMIT 1
            ');
            $stmt->execute([$name]);
            $id = (int)$stmt->fetchColumn();
            if ($id > 0) {
                return $id;
            }
        }

        return 0;
    }
}
