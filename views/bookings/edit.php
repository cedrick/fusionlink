<?php
$todayDate = function_exists('app_now') ? app_now()->format('Y-m-d') : date('Y-m-d');
$booking = $booking ?? [];
$bookingId = (int)($booking['id'] ?? 0);
?>

<h1>Edit Booking #<?= $bookingId ?> — <?= htmlspecialchars((string)($booking['customer_name'] ?? '')) ?></h1>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/bookings') ?>?date=<?= urlencode((string)($booking['booking_date'] ?? $todayDate)) ?>">← Cancel</a>
</div>

<?php if (!empty($flash) && ($flash['type'] ?? '') === 'error'): ?>
    <div class="alert-error"><?= htmlspecialchars($flash['message'] ?? '') ?></div>
<?php endif; ?>

<div class="form-card">
    <script src="<?= url('/assets/js/fusionlink-booking-dates.js') ?>"></script>
    <form method="POST" action="<?= url('/bookings/update') ?>" id="booking-form">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $bookingId ?>">
        <input type="hidden" name="booking_date" id="booking_date" value="<?= htmlspecialchars((string)($booking['booking_date'] ?? '')) ?>">
        <input type="hidden" name="personnel_id" id="personnel_id" value="<?= (int)($booking['personnel_id'] ?? 0) ?>">
        <input type="hidden" name="start_time" id="start_time" value="<?= htmlspecialchars((string)($booking['start_time'] ?? '')) ?>">

        <div class="form-grid">
            <div class="form-group full">
                <label for="service_type_id">Service</label>
                <select id="service_type_id" name="service_type_id" required>
                    <?php foreach (($serviceTypes ?? []) as $serviceType): ?>
                        <?php $serviceId = (int)($serviceType['id'] ?? 0); ?>
                        <option value="<?= $serviceId ?>" <?= (int)($booking['service_type_id'] ?? 0) === $serviceId ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($serviceType['name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php
            $calendarMode = 'edit';
            require __DIR__ . '/partials/week_calendar.php';
        ?>

        <div class="form-grid" style="margin-top:18px;">
            <div class="form-group full">
                <label for="customer_name">Customer name</label>
                <input id="customer_name" name="customer_name" type="text" value="<?= htmlspecialchars((string)($booking['customer_name'] ?? '')) ?>" required>
            </div>
            <div class="form-group">
                <label for="customer_phone">Phone</label>
                <input id="customer_phone" name="customer_phone" type="text" maxlength="11" pattern="09[0-9]{9}" value="<?= htmlspecialchars((string)($booking['customer_phone'] ?? '')) ?>" required>
            </div>
            <div class="form-group">
                <label for="customer_email">Email</label>
                <input id="customer_email" name="customer_email" type="email" value="<?= htmlspecialchars((string)($booking['customer_email'] ?? '')) ?>">
            </div>
            <div class="form-group full">
                <label for="address">Address</label>
                <textarea id="address" name="address"><?= htmlspecialchars((string)($booking['address'] ?? '')) ?></textarea>
            </div>
            <div class="form-group full">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes"><?= htmlspecialchars((string)($booking['notes'] ?? '')) ?></textarea>
            </div>
        </div>

        <div class="page-actions">
            <a class="btn btn-secondary" href="<?= url('/bookings') ?>">Cancel</a>
            <button type="submit" class="btn">Update</button>
        </div>
    </form>
</div>
