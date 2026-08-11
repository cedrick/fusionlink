<h1>Create Subscription</h1>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/subscriptions') ?>">Back to Subscriptions</a>
</div>

<div class="form-card">
    <h2 class="form-section-title">Subscription Details</h2>
    <div class="form-help">Assign an active customer to a plan and set the subscription start date.</div>

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
                <label for="start_date">Start Date</label>
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
    padding: 13px 14px;
    border: 1px solid rgba(255,255,255,.10);
    border-radius: 6px;
    font-size: 14px;
    background: rgba(255,255,255,.03);
    color: #fff;
}
.search-pick-input::placeholder {
    color: #8a8a8f;
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
})();
</script>
