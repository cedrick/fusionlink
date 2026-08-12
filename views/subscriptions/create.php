<h1>Create Subscription</h1>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/subscriptions') ?>">Back to Subscriptions</a>
</div>

<div class="form-card">
    <h2 class="form-section-title">Subscription Details</h2>
    <div class="form-help">
        Assign an active customer to a plan. For existing customers already on service, choose
        <strong>Existing customer</strong> so billing starts on the next full calendar month (no proration from enrollment date).
    </div>

    <form method="POST" action="<?= url('/subscriptions') ?>" id="subscriptionForm">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="form-group full">
                <label for="customer_search">Customer</label>
                <div class="search-pick-wrap">
                    <input
                        id="customer_search"
                        class="search-pick-input"
                        list="customerOptions"
                        placeholder="Search active customer name..."
                        autocomplete="off"
                        required
                    >
                    <datalist id="customerOptions">
                        <?php foreach (($customers ?? []) as $c): ?>
                            <option value="<?= htmlspecialchars($c['full_name'] ?? '') ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <input type="hidden" id="customer_id" name="customer_id" required>
                </div>
            </div>

            <div class="form-group full">
                <label for="plan_search">Plan</label>
                <div class="search-pick-wrap">
                    <input
                        id="plan_search"
                        class="search-pick-input"
                        list="planOptions"
                        placeholder="Search plan name or speed..."
                        autocomplete="off"
                        required
                    >
                    <datalist id="planOptions">
                        <?php foreach (($plans ?? []) as $p): ?>
                            <?php
                            $planLabel = ($p['name'] ?? '') . ' (' . ($p['speed'] ?? '') . ') - ₱' . ($p['price'] ?? '');
                            if (!empty($p['is_legacy'])) {
                                $planLabel .= ' [legacy]';
                            }
                            ?>
                            <option value="<?= htmlspecialchars($planLabel) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <input type="hidden" id="plan_id" name="plan_id" required>
                </div>
            </div>

            <div class="form-group">
                <label for="start_date">Enrollment / Start Date</label>
                <input id="start_date" type="date" name="start_date" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="ACTIVE" selected>ACTIVE</option>
                    <option value="SUSPENDED">SUSPENDED</option>
                    <option value="CANCELLED">CANCELLED</option>
                </select>
            </div>

            <div class="form-group full">
                <label>Billing type</label>
                <div class="billing-type-grid">
                    <label class="billing-type-card">
                        <input type="radio" name="billing_type" value="EXISTING_MIGRATE" id="billing_type_existing" checked>
                        <span class="billing-type-body">
                            <span class="billing-type-title">Existing customer</span>
                            <span class="billing-type-copy">Already on service. Gets a regular full-month bill for the enrollment month (never prorated).</span>
                        </span>
                    </label>
                    <label class="billing-type-card">
                        <input type="radio" name="billing_type" value="NEW_ACTIVATION" id="billing_type_new">
                        <span class="billing-type-body">
                            <span class="billing-type-title">New activation</span>
                            <span class="billing-type-copy">New install. Start date is the real activation date and may be prorated.</span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="form-group full" id="first_bill_wrap" hidden>
                <label class="compact-check" for="create_first_bill">
                    <input type="checkbox" id="create_first_bill" name="create_first_bill" value="1">
                    <span>
                        <span class="billing-type-title">Create first bill now</span>
                        <span class="billing-type-copy">Email a prorated invoice immediately. Leave unchecked to wait until month-end.</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="page-actions">
            <a class="btn btn-secondary" href="<?= url('/subscriptions') ?>">Cancel</a>
            <button type="submit" class="btn">Create Subscription</button>
        </div>
    </form>
</div>

<style>
.search-pick-wrap {
    position: relative;
}
.search-pick-input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 4px;
    font-size: 13px;
    min-height: 38px;
    background: #111113;
    color: #fff;
}
.search-pick-input::placeholder {
    color: #737373;
}
.billing-type-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.billing-type-card,
.compact-check {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin: 0;
    padding: 10px 12px;
    border: 1px solid rgba(255,255,255,.10);
    border-radius: 4px;
    background: #111113;
    cursor: pointer;
    font-weight: 500;
    color: #d4d4d4;
}
.billing-type-card:has(input:checked),
.compact-check:has(input:checked) {
    border-color: rgba(255,255,255,.28);
    background: #17171a;
}
.billing-type-body {
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
}
.billing-type-title {
    display: block;
    font-size: 13px;
    font-weight: 650;
    color: #fff;
    letter-spacing: .01em;
}
.billing-type-copy,
.compact-check .billing-type-copy {
    display: block;
    font-size: 12px;
    line-height: 1.45;
    color: #a3a3a3;
    font-weight: 500;
    margin: 0;
}
@media (max-width: 820px) {
    .billing-type-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
(function () {
    const customerMap = {
        <?php foreach (($customers ?? []) as $c): ?>
            <?= json_encode((string)($c['full_name'] ?? '')) ?>: <?= (int)($c['id'] ?? 0) ?>,
        <?php endforeach; ?>
    };

    const planMap = {
        <?php foreach (($plans ?? []) as $p): ?>
            <?php
            $planLabel = (string)(($p['name'] ?? '') . ' (' . ($p['speed'] ?? '') . ') - ₱' . ($p['price'] ?? ''));
            if (!empty($p['is_legacy'])) {
                $planLabel .= ' [legacy]';
            }
            ?>
            <?= json_encode($planLabel) ?>: <?= (int)($p['id'] ?? 0) ?>,
        <?php endforeach; ?>
    };

    const customerSearch = document.getElementById('customer_search');
    const customerId = document.getElementById('customer_id');
    const planSearch = document.getElementById('plan_search');
    const planId = document.getElementById('plan_id');
    const form = document.getElementById('subscriptionForm');

    function syncHidden(input, hidden, map) {
        const value = input.value.trim();
        hidden.value = Object.prototype.hasOwnProperty.call(map, value) ? map[value] : '';
    }

    customerSearch.addEventListener('input', function () {
        syncHidden(customerSearch, customerId, customerMap);
    });

    planSearch.addEventListener('input', function () {
        syncHidden(planSearch, planId, planMap);
    });

    form.addEventListener('submit', function (e) {
        syncHidden(customerSearch, customerId, customerMap);
        syncHidden(planSearch, planId, planMap);

        if (!customerId.value) {
            e.preventDefault();
            alert('Please select a valid active customer from the searchable list.');
            customerSearch.focus();
            return;
        }

        if (!planId.value) {
            e.preventDefault();
            alert('Please select a valid plan from the searchable list.');
            planSearch.focus();
        }
    });

    const firstBillWrap = document.getElementById('first_bill_wrap');
    const firstBillCheckbox = document.getElementById('create_first_bill');
    const billingRadios = document.querySelectorAll('input[name="billing_type"]');

    function syncBillingTypeUi() {
        const selected = document.querySelector('input[name="billing_type"]:checked');
        const isNew = selected && selected.value === 'NEW_ACTIVATION';
        if (firstBillWrap) {
            firstBillWrap.hidden = !isNew;
        }
        if (!isNew && firstBillCheckbox) {
            firstBillCheckbox.checked = false;
        }
    }

    billingRadios.forEach(function (radio) {
        radio.addEventListener('change', syncBillingTypeUi);
    });
    syncBillingTypeUi();
})();
</script>
