<h1>Service Bookings</h1>

<?php
$bookingsTodayDate = class_exists('FieldService')
    ? FieldService::nextOpenDate()
    : (function_exists('app_now') ? app_now()->format('Y-m-d') : date('Y-m-d'));
?>

<style>
.bookings-toolbar-form {
    grid-template-columns: 1fr 1fr auto auto;
    align-items: end;
}

@media (max-width: 900px) {
    .bookings-toolbar-form {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php if (!empty($flash) && ($flash['type'] ?? '') === 'error'): ?>
    <div class="alert-error"><?= htmlspecialchars($flash['message'] ?? '') ?></div>
<?php endif; ?>

<?php if (!empty($flash) && ($flash['type'] ?? '') === 'success'): ?>
    <div class="alert-success"><?= htmlspecialchars($flash['message'] ?? '') ?></div>
<?php endif; ?>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/dashboard') ?>">Back to Dashboard</a>
    <a class="btn btn-secondary" href="<?= url('/personnel') ?>">Field Personnel</a>
    <a class="btn" href="<?= url('/bookings/create') ?>">Book Service</a>
</div>

<div class="toolbar-card">
    <form method="GET" action="<?= url('/bookings') ?>" class="toolbar-form bookings-toolbar-form">
        <div class="toolbar-group">
            <label for="date">Date</label>
            <input id="date" class="toolbar-input" type="date" name="date" value="<?= htmlspecialchars($dateFilter ?? $bookingsTodayDate) ?>">
            <div class="form-help">Sunday is closed.</div>
        </div>
        <div class="toolbar-group">
            <label for="status">Status</label>
            <select id="status" class="toolbar-select" name="status">
                <option value="" <?= ($statusFilter ?? '') === '' ? 'selected' : '' ?>>All</option>
                <option value="BOOKED" <?= ($statusFilter ?? '') === 'BOOKED' ? 'selected' : '' ?>>Booked</option>
                <option value="COMPLETED" <?= ($statusFilter ?? '') === 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                <option value="CANCELLED" <?= ($statusFilter ?? '') === 'CANCELLED' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <button type="submit" class="btn">Apply</button>
        <a class="btn btn-secondary" href="<?= url('/bookings') ?>?date=<?= urlencode($bookingsTodayDate) ?>">Today</a>
    </form>
</div>

<script src="<?= url('/assets/js/fusionlink-booking-dates.js') ?>"></script>
<script>
(function () {
    var dateInput = document.getElementById('date');
    if (dateInput && window.FusionLinkBookingDates) {
        window.FusionLinkBookingDates.bindNoSunday(dateInput);
    }
})();
</script>

<div class="table-top">
    <div class="table-meta">
        Schedule for <?= htmlspecialchars(date('F j, Y', strtotime($dateFilter ?? $bookingsTodayDate))) ?>.
        <strong>Sunday is closed.</strong> Same-day booking is allowed Monday to Saturday when a slot is free.
    </div>
</div>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Time</th>
                <th>Service</th>
                <th>Personnel</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Status</th>
                <th class="actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($bookings)): ?>
                <tr>
                    <td colspan="8" class="empty-state">No bookings for this date.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($bookings as $booking): ?>
                    <?php
                        $status = strtoupper((string)($booking['status'] ?? ''));
                        $badgeClass = $status === 'BOOKED' ? 'badge-success' : ($status === 'CANCELLED' ? 'badge-danger' : 'badge-info');
                    ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars(date('g:i A', strtotime((string)($booking['start_time'] ?? '')))) ?>
                            -
                            <?= htmlspecialchars(date('g:i A', strtotime((string)($booking['end_time'] ?? '')))) ?>
                        </td>
                        <td><?= htmlspecialchars(class_exists('FieldService') ? FieldService::displayServiceTypeName((string)($booking['service_name'] ?? '')) : (string)($booking['service_name'] ?? '')) ?></td>
                        <td><?= htmlspecialchars($booking['personnel_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($booking['customer_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($booking['customer_phone'] ?? '') ?></td>
                        <td><?= htmlspecialchars($booking['address'] ?? '') ?></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span></td>
                        <td class="actions">
                            <?php if ($status === 'BOOKED'): ?>
                                <a class="btn btn-small" href="<?= url('/bookings/edit') ?>?id=<?= (int)($booking['id'] ?? 0) ?>">Edit</a>
                                <form method="POST" action="<?= url('/bookings/complete') ?>" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)($booking['id'] ?? 0) ?>">
                                    <input type="hidden" name="date" value="<?= htmlspecialchars($dateFilter ?? date('Y-m-d')) ?>">
                                    <button type="submit" class="btn btn-small btn-success" onclick="return confirm('Mark this job as done?<?= !empty($booking['service_request_id']) ? ' Installation visits will convert the applicant to a customer automatically.' : '' ?>')">Mark Done</button>
                                </form>
                                <form method="POST" action="<?= url('/bookings/cancel') ?>" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)($booking['id'] ?? 0) ?>">
                                    <input type="hidden" name="date" value="<?= htmlspecialchars($dateFilter ?? date('Y-m-d')) ?>">
                                    <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Cancel this booking?')">Cancel</button>
                                </form>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
