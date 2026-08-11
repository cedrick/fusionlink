<?php
$todayDate = function_exists('app_now') ? app_now()->format('Y-m-d') : date('Y-m-d');
$defaultBookingDate = class_exists('FieldService')
    ? FieldService::nextOpenDate($prefill['booking_date'] ?? $todayDate)
    : ($prefill['booking_date'] ?? $todayDate);
$useSimpleScheduler = !empty($useSimpleScheduler);
$timeSlots = [];
for ($hour = 8; $hour <= 16; $hour++) {
    $timeSlots[] = sprintf('%02d:00:00', $hour);
}
?>
<h1>Book Service</h1>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/bookings') ?>">Back to Bookings</a>
    <?php if ((int)($prefill['service_request_id'] ?? 0) > 0): ?>
        <a class="btn btn-secondary" href="<?= url('/inquiries') ?>">Back to Inquiries</a>
    <?php endif; ?>
</div>

<?php if (!empty($flash) && ($flash['type'] ?? '') === 'error'): ?>
    <div class="alert-error"><?= htmlspecialchars($flash['message'] ?? '') ?></div>
<?php endif; ?>

<div class="form-card">
    <script src="<?= url('/assets/js/fusionlink-booking-dates.js') ?>"></script>
    <h2 class="form-section-title">Schedule Appointment</h2>
    <div class="form-help">
        <?php if ($useSimpleScheduler && ($visitServiceLabel ?? '') !== ''): ?>
            Scheduling <strong><?= htmlspecialchars($visitServiceLabel) ?></strong> for this applicant. Pick the date, time, and technician below.
        <?php elseif ($useSimpleScheduler): ?>
            Pick the visit date, time, and technician below. <strong>Sunday is closed</strong> — operations run Monday to Saturday only.
        <?php else: ?>
            Tap an open green cell on the calendar to plot same-day or future visits. <strong>Sunday is closed.</strong> Operations run Monday to Saturday.
        <?php endif; ?>
    </div>

    <form method="POST" action="<?= url('/bookings/store') ?>" id="booking-form">
        <?= csrf_field() ?>
        <input type="hidden" name="service_request_id" value="<?= (int)($prefill['service_request_id'] ?? 0) ?>">
        <?php if (!empty($visitType)): ?>
            <input type="hidden" name="visit_type" value="<?= htmlspecialchars($visitType) ?>">
        <?php endif; ?>
        <input type="hidden" name="booking_date" id="booking_date" value="<?= htmlspecialchars($defaultBookingDate) ?>">
        <input type="hidden" name="personnel_id" id="personnel_id" value="">
        <input type="hidden" name="start_time" id="start_time" value="">

        <?php if ($useSimpleScheduler && (int)($prefill['service_type_id'] ?? 0) > 0): ?>
            <input type="hidden" name="service_type_id" value="<?= (int)$prefill['service_type_id'] ?>">
            <div class="form-grid">
                <div class="form-group full">
                    <label>Visit type</label>
                    <input type="text" class="toolbar-input" value="<?= htmlspecialchars($visitServiceLabel !== '' ? $visitServiceLabel : 'Application visit') ?>" readonly>
                </div>
            </div>
        <?php elseif ($useSimpleScheduler): ?>
            <div class="alert-error">
                Could not load the visit type for this application. Go back to Inquiries and try Schedule Ocular or Schedule Installation again.
            </div>
        <?php else: ?>
            <div class="form-grid">
                <div class="form-group full">
                    <label for="service_type_id">Service</label>
                    <select id="service_type_id" name="service_type_id" required>
                        <option value="">Select service</option>
                        <?php foreach (($serviceTypes ?? []) as $serviceType): ?>
                            <?php $serviceId = (int)($serviceType['id'] ?? 0); ?>
                            <option
                                value="<?= $serviceId ?>"
                                <?= (int)($prefill['service_type_id'] ?? 0) === $serviceId ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars((string)($serviceType['name'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($useSimpleScheduler): ?>
            <div class="form-grid" style="margin-top:18px;">
                <div class="form-group">
                    <label for="simple_booking_date">Date</label>
                    <input
                        id="simple_booking_date"
                        type="date"
                        class="toolbar-input"
                        value="<?= htmlspecialchars($defaultBookingDate) ?>"
                        min="<?= htmlspecialchars($defaultBookingDate) ?>"
                        required
                    >
                    <div class="form-help">Sunday is closed. Choose Monday to Saturday.</div>
                </div>

                <div class="form-group">
                    <label for="simple_start_time">Time</label>
                    <select id="simple_start_time" class="toolbar-select" required>
                        <option value="">Select time</option>
                        <?php foreach ($timeSlots as $slot): ?>
                            <option value="<?= htmlspecialchars($slot) ?>">
                                <?= htmlspecialchars(date('g:i A', strtotime($slot))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group full">
                    <label for="simple_personnel_id">Technician</label>
                    <select id="simple_personnel_id" class="toolbar-select" required>
                        <option value="">Select technician</option>
                        <?php foreach (($personnel ?? []) as $person): ?>
                            <option value="<?= (int)($person['id'] ?? 0) ?>">
                                <?= htmlspecialchars((string)($person['full_name'] ?? 'Technician')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($personnel)): ?>
                        <div class="form-help" style="margin-top:8px;color:#fbbf24;">
                            No technicians found. Add active personnel under <a href="<?= url('/personnel') ?>">Field Personnel</a> first.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <?php
                $calendarMode = 'create';
                $booking = null;
                require __DIR__ . '/partials/week_calendar.php';
            ?>
        <?php endif; ?>

        <div class="form-grid" style="margin-top:18px;">
            <div class="form-group full">
                <label for="customer_id">Link to customer (optional)</label>
                <select id="customer_id" name="customer_id">
                    <option value="0">Manual entry</option>
                    <?php foreach (($customers ?? []) as $customer): ?>
                        <?php $customerId = (int)($customer['id'] ?? 0); ?>
                        <option
                            value="<?= $customerId ?>"
                            data-name="<?= htmlspecialchars($customer['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-phone="<?= htmlspecialchars($customer['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-email="<?= htmlspecialchars($customer['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-address="<?= htmlspecialchars($customer['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            <?= (int)($prefill['customer_id'] ?? 0) === $customerId ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($customer['full_name'] ?? '') ?> (<?= htmlspecialchars($customer['phone'] ?? '') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group full">
                <label for="customer_name">Customer name</label>
                <input id="customer_name" name="customer_name" type="text" value="<?= htmlspecialchars($prefill['customer_name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="customer_phone">Phone</label>
                <input id="customer_phone" name="customer_phone" type="text" maxlength="11" pattern="09[0-9]{9}" value="<?= htmlspecialchars($prefill['customer_phone'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="customer_email">Email</label>
                <input id="customer_email" name="customer_email" type="email" value="<?= htmlspecialchars($prefill['customer_email'] ?? '') ?>">
            </div>

            <div class="form-group full">
                <label for="address">Address</label>
                <textarea id="address" name="address"><?= htmlspecialchars($prefill['address'] ?? '') ?></textarea>
            </div>

            <div class="form-group full">
                <label for="notes">Notes (optional)</label>
                <textarea id="notes" name="notes" placeholder="Gate code, landmarks, issue details..."></textarea>
            </div>
        </div>

        <div class="page-actions">
            <a class="btn btn-secondary" href="<?= url('/bookings') ?>">Cancel</a>
            <?php if (!$useSimpleScheduler || (int)($prefill['service_type_id'] ?? 0) > 0): ?>
                <button type="submit" class="btn">Confirm Booking</button>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
(function () {
    var customerSelect = document.getElementById('customer_id');
    if (customerSelect) {
        function fillCustomerFields() {
            var option = customerSelect.options[customerSelect.selectedIndex];
            if (!option || option.value === '0') {
                return;
            }

            document.getElementById('customer_name').value = option.getAttribute('data-name') || '';
            document.getElementById('customer_phone').value = option.getAttribute('data-phone') || '';
            document.getElementById('customer_email').value = option.getAttribute('data-email') || '';
            document.getElementById('address').value = option.getAttribute('data-address') || '';
        }

        customerSelect.addEventListener('change', fillCustomerFields);
        fillCustomerFields();
    }

    var simpleDate = document.getElementById('simple_booking_date');
    if (!simpleDate) {
        return;
    }

    var simpleTime = document.getElementById('simple_start_time');
    var simplePersonnel = document.getElementById('simple_personnel_id');
    var bookingDateInput = document.getElementById('booking_date');
    var personnelInput = document.getElementById('personnel_id');
    var startTimeInput = document.getElementById('start_time');
    var bookingForm = document.getElementById('booking-form');

    function syncSimpleScheduler() {
        if (bookingDateInput) {
            bookingDateInput.value = simpleDate.value || '';
        }
        if (startTimeInput) {
            startTimeInput.value = simpleTime ? simpleTime.value : '';
        }
        if (personnelInput) {
            personnelInput.value = simplePersonnel ? simplePersonnel.value : '';
        }
    }

    simpleDate.addEventListener('change', syncSimpleScheduler);
    if (simpleTime) {
        simpleTime.addEventListener('change', syncSimpleScheduler);
    }
    if (simplePersonnel) {
        simplePersonnel.addEventListener('change', syncSimpleScheduler);
    }

    if (bookingForm) {
        bookingForm.addEventListener('submit', function (event) {
            syncSimpleScheduler();
            if (window.FusionLinkBookingDates && window.FusionLinkBookingDates.isSunday(bookingDateInput.value)) {
                event.preventDefault();
                window.alert('Sunday is closed. Please choose Monday to Saturday.');
                return;
            }
            if (!bookingDateInput.value || !startTimeInput.value || !personnelInput.value) {
                event.preventDefault();
                alert('Please choose the date, time, and technician.');
            }
        });
    }

    if (window.FusionLinkBookingDates) {
        window.FusionLinkBookingDates.bindNoSunday(simpleDate);
    }

    syncSimpleScheduler();
})();
</script>

<style>
@media (max-width: 900px) {
    .booking-week-card {
        padding: 12px;
    }

    .form-card {
        padding: 16px;
    }

    .page-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .page-actions .btn,
    .page-actions .btn-secondary {
        width: 100%;
        text-align: center;
    }
}
</style>
