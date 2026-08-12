<h1>Edit Subscription</h1>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/subscriptions') ?>">Back to Subscriptions</a>
</div>

<div class="form-card">
    <h2 class="form-section-title">Subscription Details</h2>
    <div class="form-help">Update the assigned customer, plan, enrollment date, billing type, and status.</div>

    <form method="POST" action="<?= url('/subscriptions/update') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$subscription['id'] ?>">

        <div class="form-grid">
            <div class="form-group full">
                <label for="customer_id">Customer</label>
                <select id="customer_id" name="customer_id" required>
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= ((int)$subscription['customer_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group full">
                <label for="plan_id">Plan</label>
                <select id="plan_id" name="plan_id" required>
                    <option value="">-- Select Plan --</option>
                    <?php foreach ($plans as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= ((int)$subscription['plan_id'] === (int)$p['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['speed']) ?>) - ₱<?= htmlspecialchars($p['price']) ?><?= !empty($p['is_legacy']) ? ' [legacy]' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="start_date">Enrollment / Start Date</label>
                <input id="start_date" type="date" name="start_date" value="<?= htmlspecialchars($subscription['start_date']) ?>" required>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="ACTIVE" <?= ($subscription['status'] === 'ACTIVE') ? 'selected' : '' ?>>ACTIVE</option>
                    <option value="SUSPENDED" <?= ($subscription['status'] === 'SUSPENDED') ? 'selected' : '' ?>>SUSPENDED</option>
                    <option value="CANCELLED" <?= ($subscription['status'] === 'CANCELLED') ? 'selected' : '' ?>>CANCELLED</option>
                </select>
            </div>

            <?php
                $currentBillingType = strtoupper((string)($subscription['billing_type'] ?? 'EXISTING_MIGRATE'));
                if ($currentBillingType !== 'NEW_ACTIVATION') {
                    $currentBillingType = 'EXISTING_MIGRATE';
                }
            ?>
            <div class="form-group full">
                <label>Billing type</label>
                <div class="billing-type-grid">
                    <label class="billing-type-card">
                        <input type="radio" name="billing_type" value="EXISTING_MIGRATE" <?= $currentBillingType === 'EXISTING_MIGRATE' ? 'checked' : '' ?>>
                        <span class="billing-type-body">
                            <span class="billing-type-title">Existing customer</span>
                            <span class="billing-type-copy">Already on service. Gets a regular full-month bill for the enrollment month (never prorated).</span>
                        </span>
                    </label>
                    <label class="billing-type-card">
                        <input type="radio" name="billing_type" value="NEW_ACTIVATION" <?= $currentBillingType === 'NEW_ACTIVATION' ? 'checked' : '' ?>>
                        <span class="billing-type-body">
                            <span class="billing-type-title">New activation</span>
                            <span class="billing-type-copy">New install. Start date is the real activation date and may be prorated.</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <div class="page-actions">
            <a class="btn btn-secondary" href="<?= url('/subscriptions') ?>">Cancel</a>
            <button type="submit" class="btn">Update Subscription</button>
        </div>
    </form>
</div>

<style>
.billing-type-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.billing-type-card {
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
.billing-type-card:has(input:checked) {
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
}
.billing-type-copy {
    display: block;
    font-size: 12px;
    line-height: 1.45;
    color: #a3a3a3;
    font-weight: 500;
}
@media (max-width: 820px) {
    .billing-type-grid {
        grid-template-columns: 1fr;
    }
}
</style>
