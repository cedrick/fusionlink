<?php
$calendarMode = $calendarMode ?? 'create';
$booking = $booking ?? null;
$serviceTypes = $serviceTypes ?? [];
$prefill = $prefill ?? [];
$todayDate = function_exists('app_now') ? app_now()->format('Y-m-d') : date('Y-m-d');
$selectedServiceTypeId = (int)($prefill['service_type_id'] ?? $booking['service_type_id'] ?? 0);
$rawSelectedDate = (string)($prefill['booking_date'] ?? $booking['booking_date'] ?? $todayDate);
$selectedDate = ($calendarMode ?? 'create') === 'create' && class_exists('FieldService')
    ? FieldService::nextOpenDate($rawSelectedDate)
    : $rawSelectedDate;
$selectedPersonnelId = (int)($booking['personnel_id'] ?? 0);
$selectedStartTime = (string)($booking['start_time'] ?? '');
$excludeBookingId = (int)($booking['id'] ?? 0);
$customerTitle = trim((string)($prefill['customer_name'] ?? $booking['customer_name'] ?? ''));
?>

<style>
.booking-week-card {
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 6px;
    background: rgba(255,255,255,.02);
    padding: 16px;
    margin-bottom: 18px;
}

.booking-week-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}

.booking-week-label {
    font-weight: 800;
    font-size: 15px;
}

.booking-week-nav {
    display: flex;
    gap: 8px;
}

.booking-week-scroll {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.booking-week-grid {
    min-width: 760px;
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 4px;
    overflow: hidden;
}

.booking-week-grid table {
    width: 100%;
    border-collapse: collapse;
}

.booking-week-grid th,
.booking-week-grid td {
    border: 1px solid rgba(255,255,255,.08);
    text-align: center;
    vertical-align: middle;
}

.booking-week-grid th {
    background: rgba(255,255,255,.04);
    padding: 10px 8px;
    font-size: 12px;
    font-weight: 800;
}

.booking-week-grid th.is-today {
    color: #93c5fd;
}

.booking-week-grid .time-col {
    width: 92px;
    font-size: 12px;
    font-weight: 700;
    background: rgba(255,255,255,.03);
    white-space: nowrap;
}

.booking-slot-cell {
    min-width: 88px;
    height: 52px;
    padding: 0;
    background: rgba(255,255,255,.02);
}

.booking-slot-cell button {
    width: 100%;
    height: 100%;
    border: 0;
    background: transparent;
    color: #d4d4d8;
    font-size: 11px;
    cursor: pointer;
}

.booking-slot-cell.is-open button {
    background: rgba(34,197,94,.12);
    color: #bbf7d0;
}

.booking-slot-cell.is-open button:hover {
    background: rgba(34,197,94,.22);
}

.booking-slot-cell.is-selected button {
    background: rgba(59,130,246,.28);
    color: #ffffff;
    box-shadow: inset 0 0 0 2px #60a5fa;
}

.booking-slot-cell.is-blocked {
    background: rgba(255,255,255,.01);
    color: #52525b;
}

.booking-selected-box {
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 4px;
    padding: 14px 16px;
    background: rgba(255,255,255,.03);
    margin-top: 14px;
}

.booking-selected-box strong {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    letter-spacing: .04em;
}

.booking-selected-value {
    font-size: 15px;
    font-weight: 700;
}

.booking-slot-cell.is-closed-day {
    background: rgba(239, 68, 68, 0.08);
    color: #9ca3af;
    font-size: 11px;
    font-weight: 700;
}

.booking-week-grid th.is-closed {
    color: #fca5a5;
}
</style>

<div class="booking-week-card" id="booking-week-calendar"
    data-week-url="<?= htmlspecialchars(url('/bookings/week-slots')) ?>"
    data-today="<?= htmlspecialchars($todayDate) ?>"
    data-mode="<?= htmlspecialchars($calendarMode) ?>"
    data-initial-service="<?= (int)$selectedServiceTypeId ?>"
    data-initial-date="<?= htmlspecialchars($selectedDate) ?>"
    data-initial-personnel="<?= (int)$selectedPersonnelId ?>"
    data-initial-start="<?= htmlspecialchars(substr($selectedStartTime, 0, 8)) ?>"
    data-exclude-booking="<?= (int)$excludeBookingId ?>"
>
    <div class="booking-week-toolbar">
        <div class="booking-week-label" id="booking-week-range">Loading calendar...</div>
        <div class="booking-week-nav">
            <button type="button" class="btn btn-small btn-secondary" id="booking-week-prev">Prev</button>
            <button type="button" class="btn btn-small btn-secondary" id="booking-week-today">Today</button>
            <button type="button" class="btn btn-small btn-secondary" id="booking-week-next">Next</button>
        </div>
    </div>

    <div class="form-help" style="margin-bottom:12px;">
        Tap a green cell to plot the schedule. <strong>Sunday is closed.</strong> Operations run Monday to Saturday.
    </div>

    <div class="booking-week-scroll">
        <div class="booking-week-grid">
            <table>
                <thead id="booking-week-head"></thead>
                <tbody id="booking-week-body"></tbody>
            </table>
        </div>
    </div>

    <div class="booking-selected-box">
        <strong>SELECTED DATE &amp; TIME</strong>
        <div class="booking-selected-value booking-selected-empty" id="booking-selected-label">
            <?= $customerTitle !== '' ? htmlspecialchars($customerTitle) . ' — ' : '' ?>No schedule selected
        </div>
        <div class="page-actions" style="margin-top:12px;">
            <button type="button" class="btn btn-small btn-danger" id="booking-clear-selection">Clear</button>
        </div>
    </div>
</div>

<script>
(function () {
    var root = document.getElementById('booking-week-calendar');
    if (!root) {
        return;
    }

    var weekUrl = root.getAttribute('data-week-url');
    var todayDate = root.getAttribute('data-today');
    var excludeBookingId = parseInt(root.getAttribute('data-exclude-booking') || '0', 10);
    var weekStart = todayDate;
    var selected = {
        date: root.getAttribute('data-initial-date') || '',
        start_time: root.getAttribute('data-initial-start') || '',
        personnel_id: parseInt(root.getAttribute('data-initial-personnel') || '0', 10),
        label: ''
    };

    var serviceSelect = document.getElementById('service_type_id');
    var bookingDateInput = document.getElementById('booking_date');
    var personnelInput = document.getElementById('personnel_id');
    var startTimeInput = document.getElementById('start_time');
    var rangeLabel = document.getElementById('booking-week-range');
    var headEl = document.getElementById('booking-week-head');
    var bodyEl = document.getElementById('booking-week-body');
    var selectedLabel = document.getElementById('booking-selected-label');

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    function shiftWeek(days) {
        var parts = weekStart.split('-').map(Number);
        var dt = new Date(parts[0], parts[1] - 1, parts[2]);
        dt.setDate(dt.getDate() + days);
        weekStart = dt.getFullYear() + '-' + pad(dt.getMonth() + 1) + '-' + pad(dt.getDate());
        loadWeek();
    }

    function formatSelectedLabel(slot) {
        if (!slot) {
            return 'No schedule selected';
        }

        return slot.label || (slot.date + ' ' + slot.start_time);
    }

    function applySelection(slot) {
        if (!slot) {
            selected = { date: '', start_time: '', personnel_id: 0, label: '' };
            if (bookingDateInput) bookingDateInput.value = '';
            if (personnelInput) personnelInput.value = '';
            if (startTimeInput) startTimeInput.value = '';
            selectedLabel.textContent = 'No schedule selected';
            selectedLabel.classList.add('booking-selected-empty');
            return;
        }

        selected = {
            date: slot.booking_date || slot.date,
            start_time: slot.start_time,
            personnel_id: parseInt(slot.personnel_id, 10) || 0,
            label: slot.label || ''
        };

        if (bookingDateInput) bookingDateInput.value = selected.date;
        if (personnelInput) personnelInput.value = String(selected.personnel_id);
        if (startTimeInput) startTimeInput.value = selected.start_time;
        selectedLabel.textContent = formatSelectedLabel(selected);
        selectedLabel.classList.remove('booking-selected-empty');
    }

    function renderWeek(week) {
        rangeLabel.textContent = week.week_label || 'Schedule';
        headEl.innerHTML = '';
        bodyEl.innerHTML = '';

        var headRow = document.createElement('tr');
        var timeHead = document.createElement('th');
        timeHead.className = 'time-col';
        timeHead.textContent = 'Time';
        headRow.appendChild(timeHead);

        (week.days || []).forEach(function (day) {
            var th = document.createElement('th');
            if (day.is_today) {
                th.className = 'is-today';
            }
            if (day.is_closed) {
                th.className = (th.className ? th.className + ' ' : '') + 'is-closed';
            }
            th.innerHTML = day.weekday + '<br>' + day.day + ' ' + day.month;
            headRow.appendChild(th);
        });
        headEl.appendChild(headRow);

        (week.hour_rows || []).forEach(function (hour) {
            var row = document.createElement('tr');
            var timeCell = document.createElement('td');
            timeCell.className = 'time-col';

            var hourNum = parseInt(hour.split(':')[0], 10);
            var nextHour = hourNum + 1;
            var suffix = function (h) { return h >= 12 ? 'PM' : 'AM'; };
            var displayHour = function (h) {
                var base = h % 12;
                return (base === 0 ? 12 : base);
            };
            timeCell.textContent = displayHour(hourNum) + '–' + displayHour(nextHour) + ' ' + suffix(nextHour);
            row.appendChild(timeCell);

            (week.days || []).forEach(function (day) {
                var td = document.createElement('td');

                if (day.is_closed) {
                    td.className = 'booking-slot-cell is-closed-day';
                    if (hour === (week.hour_rows[0] || '')) {
                        td.textContent = 'Closed';
                    }
                    row.appendChild(td);
                    return;
                }

                td.className = 'booking-slot-cell is-blocked';
                var slot = (day.slot_map || {})[hour] || null;

                if (slot) {
                    td.className = 'booking-slot-cell is-open';
                    var isSelected = selected.date === day.date
                        && selected.start_time === slot.start_time
                        && selected.personnel_id === parseInt(slot.personnel_id, 10);
                    if (isSelected) {
                        td.className = 'booking-slot-cell is-selected';
                    }

                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.textContent = 'Open';
                    btn.addEventListener('click', function () {
                        slot.booking_date = day.date;
                        applySelection(slot);
                        renderWeek(week);
                    });
                    td.appendChild(btn);
                }

                row.appendChild(td);
            });

            bodyEl.appendChild(row);
        });

        if (selected.date && selected.start_time && selected.personnel_id) {
            selectedLabel.textContent = selected.label || (selected.date + ' ' + selected.start_time);
            selectedLabel.classList.remove('booking-selected-empty');
        }
    }

    function loadWeek() {
        var serviceTypeId = serviceSelect ? serviceSelect.value : '';
        if (!serviceTypeId) {
            rangeLabel.textContent = 'Select a service first';
            headEl.innerHTML = '';
            bodyEl.innerHTML = '';
            return;
        }

        var url = weekUrl
            + '?service_type_id=' + encodeURIComponent(serviceTypeId)
            + '&week_start=' + encodeURIComponent(weekStart);
        if (excludeBookingId > 0) {
            url += '&exclude_booking_id=' + encodeURIComponent(excludeBookingId);
        }

        fetch(url, { credentials: 'same-origin' })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data.ok || !data.week) {
                    rangeLabel.textContent = data.message || 'Unable to load calendar';
                    return;
                }

                weekStart = data.week.week_start || weekStart;
                renderWeek(data.week);

                if (selected.date && selected.start_time && !selected.label) {
                    (data.week.days || []).forEach(function (day) {
                        if (day.date !== selected.date) {
                            return;
                        }
                        Object.keys(day.slot_map || {}).forEach(function (key) {
                            var slot = day.slot_map[key];
                            if (slot.start_time === selected.start_time
                                && parseInt(slot.personnel_id, 10) === selected.personnel_id) {
                                selected.label = slot.label;
                                selectedLabel.textContent = formatSelectedLabel(selected);
                            }
                        });
                    });
                }
            })
            .catch(function () {
                rangeLabel.textContent = 'Unable to load calendar';
            });
    }

    document.getElementById('booking-week-prev').addEventListener('click', function () {
        shiftWeek(-7);
    });
    document.getElementById('booking-week-next').addEventListener('click', function () {
        shiftWeek(7);
    });
    document.getElementById('booking-week-today').addEventListener('click', function () {
        weekStart = todayDate;
        loadWeek();
    });
    document.getElementById('booking-clear-selection').addEventListener('click', function () {
        applySelection(null);
        loadWeek();
    });

    if (serviceSelect) {
        serviceSelect.addEventListener('change', function () {
            applySelection(null);
            loadWeek();
        });
    }

    var bookingForm = document.getElementById('booking-form');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function (event) {
            if (!personnelInput || !startTimeInput || !bookingDateInput) {
                return;
            }
            if (window.FusionLinkBookingDates && window.FusionLinkBookingDates.isSunday(bookingDateInput.value)) {
                event.preventDefault();
                alert('Sunday is closed. Please choose Monday to Saturday.');
                return;
            }
            if (!personnelInput.value || !startTimeInput.value || !bookingDateInput.value) {
                event.preventDefault();
                alert('Please tap an open green cell on the calendar first.');
            }
        });
    }

    if (selected.date && selected.start_time && selected.personnel_id) {
        weekStart = selected.date;
    }

    loadWeek();
})();
</script>
