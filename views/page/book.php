<?php
$cms = $cms ?? [];
$serviceTypes = $serviceTypes ?? [];
$done = $done ?? false;
$message = $message ?? '';
$error = $error ?? '';
?>

<section class="hero" style="padding:72px 0 48px;">
    <div class="page-section-inner" style="max-width:720px;">
        <h1 style="margin:0 0 12px;font-size:clamp(30px,5vw,44px);line-height:1.1;">Book a service visit</h1>
        <p style="margin:0;font-size:18px;max-width:640px;">
            Choose a service and open time slot. <strong>Sunday is closed.</strong> We operate Monday to Saturday.
        </p>
    </div>
</section>

<section class="page-section alt" style="padding-top:0;">
    <div class="page-section-inner" style="max-width:720px;">
        <div class="apply-card">
            <?php if ($done && $message !== ''): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <p style="margin:18px 0 0;">We sent a confirmation to your email if provided. Our team will see you at the scheduled time.</p>
            <?php else: ?>
                <?php if ($error !== ''): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (empty($serviceTypes)): ?>
                    <div class="info-card">Online booking is not available yet. Please contact <?= htmlspecialchars((string)($cms['company_phone'] ?? 'our office')) ?>.</div>
                <?php else: ?>
                    <form method="POST" action="<?= url('/page/book') ?>" class="apply-grid" id="public-booking-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="personnel_id" id="personnel_id" value="">
                        <input type="hidden" name="start_time" id="start_time" value="">

                        <div class="full">
                            <label for="service_type_id">Service needed<span class="required-mark" aria-hidden="true">*</span></label>
                            <select id="service_type_id" name="service_type_id" required>
                                <option value="">Select service</option>
                                <?php foreach ($serviceTypes as $serviceType): ?>
                                    <option value="<?= (int)($serviceType['id'] ?? 0) ?>">
                                        <?= htmlspecialchars((string)($serviceType['name'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="booking_date">Preferred date<span class="required-mark" aria-hidden="true">*</span></label>
                            <input id="booking_date" type="date" name="booking_date" value="<?= htmlspecialchars(class_exists('FieldService') ? FieldService::nextOpenDate() : date('Y-m-d')) ?>" min="<?= htmlspecialchars(class_exists('FieldService') ? FieldService::nextOpenDate() : date('Y-m-d')) ?>" required>
                            <div class="helper-text" style="color:#cbd5e1;font-size:13px;margin-top:6px;">Sunday is closed. Choose Monday to Saturday.</div>
                        </div>

                        <div>
                            <label for="slot_picker">Available time<span class="required-mark" aria-hidden="true">*</span></label>
                            <select id="slot_picker" required>
                                <option value="">Choose service and date first</option>
                            </select>
                        </div>

                        <div class="full">
                            <div id="slot-hint" class="helper-text" style="color:#cbd5e1;font-size:13px;"></div>
                        </div>

                        <div class="full">
                            <label for="customer_name">Full name<span class="required-mark" aria-hidden="true">*</span></label>
                            <input id="customer_name" name="customer_name" type="text" required>
                        </div>

                        <div>
                            <label for="customer_phone">Phone (09XXXXXXXXX)<span class="required-mark" aria-hidden="true">*</span></label>
                            <input id="customer_phone" name="customer_phone" type="tel" maxlength="11" pattern="09[0-9]{9}" required>
                        </div>

                        <div>
                            <label for="customer_email">Email</label>
                            <input id="customer_email" name="customer_email" type="email">
                        </div>

                        <div class="full">
                            <label for="address">Service address<span class="required-mark" aria-hidden="true">*</span></label>
                            <textarea id="address" name="address" required></textarea>
                        </div>

                        <div class="full">
                            <label for="notes">Notes (optional)</label>
                            <textarea id="notes" name="notes" placeholder="Landmark, issue details, gate code..."></textarea>
                        </div>

                        <div class="full">
                            <button type="submit" class="btn-primary" style="width:100%;min-height:52px;">Confirm Booking</button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<script src="<?= url('/assets/js/fusionlink-booking-dates.js') ?>"></script>
<script>
(function () {
    var serviceSelect = document.getElementById('service_type_id');
    var dateInput = document.getElementById('booking_date');
    var slotPicker = document.getElementById('slot_picker');
    var slotHint = document.getElementById('slot-hint');
    var personnelInput = document.getElementById('personnel_id');
    var startTimeInput = document.getElementById('start_time');

    if (!serviceSelect || !dateInput || !slotPicker) {
        return;
    }

    var slotsUrl = <?= json_encode(url('/page/booking-slots')) ?>;

    function loadSlots() {
        var serviceTypeId = serviceSelect.value;
        var date = dateInput.value;

        slotPicker.innerHTML = '<option value="">Loading slots...</option>';
        if (slotHint) {
            slotHint.textContent = '';
        }
        personnelInput.value = '';
        startTimeInput.value = '';

        if (!serviceTypeId || !date) {
            slotPicker.innerHTML = '<option value="">Choose service and date first</option>';
            return;
        }

        if (window.FusionLinkBookingDates && window.FusionLinkBookingDates.isSunday(date)) {
            slotPicker.innerHTML = '<option value="">Sunday is closed</option>';
            if (slotHint) {
                slotHint.textContent = 'Sunday is closed. Please choose Monday to Saturday.';
            }
            return;
        }

        fetch(slotsUrl + '?service_type_id=' + encodeURIComponent(serviceTypeId) + '&date=' + encodeURIComponent(date))
            .then(function (response) { return response.json(); })
            .then(function (data) {
                slotPicker.innerHTML = '';

                if (!data.ok || !data.slots || data.slots.length === 0) {
                    slotPicker.innerHTML = '<option value="">No open slots for this date</option>';
                    if (slotHint) {
                        slotHint.textContent = 'Try another date. Same-day slots appear only when personnel are available.';
                    }
                    return;
                }

                var placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = 'Select a time slot';
                slotPicker.appendChild(placeholder);

                data.slots.forEach(function (slot) {
                    var option = document.createElement('option');
                    option.value = slot.personnel_id + '|' + slot.start_time;
                    option.textContent = slot.label + (slot.same_day ? ' • Today' : '');
                    option.setAttribute('data-personnel-id', slot.personnel_id);
                    option.setAttribute('data-start-time', slot.start_time);
                    slotPicker.appendChild(option);
                });

                if (slotHint) {
                    slotHint.textContent = data.same_day_available
                        ? 'Same-day booking is available right now.'
                        : 'No same-day slots left. You can still book a future open slot.';
                }
            })
            .catch(function () {
                slotPicker.innerHTML = '<option value="">Unable to load slots</option>';
            });
    }

    slotPicker.addEventListener('change', function () {
        var option = slotPicker.options[slotPicker.selectedIndex];
        if (!option || !option.value) {
            personnelInput.value = '';
            startTimeInput.value = '';
            return;
        }

        personnelInput.value = option.getAttribute('data-personnel-id') || '';
        startTimeInput.value = option.getAttribute('data-start-time') || '';
    });

    serviceSelect.addEventListener('change', loadSlots);
    dateInput.addEventListener('change', loadSlots);
    if (window.FusionLinkBookingDates) {
        window.FusionLinkBookingDates.bindNoSunday(dateInput);
    }
    loadSlots();
})();
</script>
