<h1>Edit Customer</h1>

<div class="page-actions">
    <a href="<?= url('/customers') ?>" class="btn btn-secondary">Back to Customers</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php
    $portalStatus = $portalStatus ?? ['has_portal' => false, 'email' => ''];
    $customerEmail = trim((string)($customer['email'] ?? ''));
    $customerStatus = strtoupper((string)($customer['status'] ?? ''));
    $canActivatePortal = empty($portalStatus['has_portal'])
        && $customerEmail !== ''
        && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)
        && $customerStatus !== 'DISCONNECTED';
?>

<div class="form-card" style="margin-bottom:18px;">
    <h2 class="form-section-title">Billing Portal</h2>
    <div class="form-help">
        <?php if (!empty($portalStatus['has_portal'])): ?>
            This customer already has portal access using <strong><?= htmlspecialchars($portalStatus['email'] ?? $customerEmail) ?></strong>.
            If they forgot the password, reset it here. They should change it after login under Billing Portal → Password.
            Customers who do not use the portal can still pay GCash; record it under Payments.
        <?php elseif ($customerEmail === ''): ?>
            Add a valid email below, save the customer, then you can create portal login credentials.
        <?php elseif ($customerStatus === 'DISCONNECTED'): ?>
            Portal access cannot be created for disconnected customers.
        <?php else: ?>
            One click creates a portal login and emails credentials to the customer.
        <?php endif; ?>
    </div>

    <?php if ($canActivatePortal): ?>
        <form method="POST" action="<?= url('/customers/activate-portal') ?>" class="page-actions" style="margin-top:12px;">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)($customer['id'] ?? 0) ?>">
            <input type="hidden" name="return_to" value="/customers/edit">
            <button
                type="submit"
                class="btn"
                onclick="return confirm('Create portal login and email credentials to <?= htmlspecialchars($customerEmail, ENT_QUOTES, 'UTF-8') ?>?')"
            >
                Create Portal Login &amp; Email Credentials
            </button>
            <a class="btn btn-secondary" href="<?= url('/payments/create') ?>">Record Payment</a>
        </form>
    <?php elseif (!empty($portalStatus['has_portal'])): ?>
        <div class="page-actions" style="margin-top:12px;">
            <form method="POST" action="<?= url('/customers/reset-portal-password') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)($customer['id'] ?? 0) ?>">
                <input type="hidden" name="return_to" value="/customers/edit">
                <button
                    type="submit"
                    class="btn"
                    onclick="return confirm('Reset the portal password and email a temporary password to <?= htmlspecialchars((string)($portalStatus['email'] ?? $customerEmail), ENT_QUOTES, 'UTF-8') ?>?')"
                >
                    Reset Portal Password
                </button>
            </form>
            <a class="btn btn-secondary" href="<?= url('/payments/create') ?>">Record Payment</a>
        </div>
    <?php else: ?>
        <div class="page-actions" style="margin-top:12px;">
            <a class="btn btn-secondary" href="<?= url('/payments/create') ?>">Record Payment</a>
        </div>
    <?php endif; ?>
</div>

<div class="form-card">
    <h2 class="form-section-title">Customer Information</h2>
    <div class="form-help">Update the customer details and current account status.</div>

    <form method="POST" action="<?= url('/customers/update') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)($customer['id'] ?? 0) ?>">

        <div class="form-grid">
            <div class="form-group full">
                <label for="full_name">Full Name</label>
                <input id="full_name" type="text" name="full_name" value="<?= htmlspecialchars($customer['full_name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="<?= htmlspecialchars($customer['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input id="phone" type="text" name="phone" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="static_ip">Static LAN IP (for Omada/ER605)</label>
                <input
                    id="static_ip"
                    type="text"
                    name="static_ip"
                    value="<?= htmlspecialchars((string)($customer['static_ip'] ?? '')) ?>"
                    placeholder="e.g. 192.168.10.55"
                >
            </div>

            <div class="form-group full">
                <label for="address">Address</label>
                <textarea id="address" name="address"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="ACTIVE" <?= (($customer['status'] ?? '') === 'ACTIVE') ? 'selected' : '' ?>>ACTIVE</option>
                    <option value="DISCONNECTED" <?= (($customer['status'] ?? '') === 'DISCONNECTED') ? 'selected' : '' ?>>DISCONNECTED</option>
                </select>
            </div>

            <div class="form-group full">
                <label class="compact-check" for="vat_inclusive">
                    <input
                        type="checkbox"
                        id="vat_inclusive"
                        name="vat_inclusive"
                        value="1"
                        <?= !empty($customer['vat_inclusive']) ? 'checked' : '' ?>
                    >
                    <span>
                        <span class="billing-type-title">VAT inclusive billing</span>
                        <span class="billing-type-copy">Adds Settings VAT % on top of the plan price for this customer. Saving also recalculates their open (unpaid) invoices. Leave unchecked for normal VAT-excluded billing.</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="page-actions">
            <a href="<?= url('/customers') ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn">Update Customer</button>
        </div>
    </form>
</div>

<div class="form-card" style="margin-top:18px;">
    <h2 class="form-section-title">Network access (Omada / ER605)</h2>
    <div class="form-help">
        Suspend blocks this customer’s static IP from internet on the ER605.
        Restore unblocks after payment. Save the static IP above first.
        <?php if (!empty($customer['network_blocked'])): ?>
            <br><strong>Current:</strong> blocked on network.
        <?php else: ?>
            <br><strong>Current:</strong> not blocked.
        <?php endif; ?>
    </div>
    <div class="page-actions" style="margin-top:12px;">
        <form method="POST" action="<?= url('/customers/network/suspend') ?>" style="display:inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="customer_id" value="<?= (int)($customer['id'] ?? 0) ?>">
            <button type="submit" class="btn btn-secondary"
                    onclick="return confirm('Suspend this customer and block their static IP?');">
                Suspend now
            </button>
        </form>
        <form method="POST" action="<?= url('/customers/network/restore') ?>" style="display:inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="customer_id" value="<?= (int)($customer['id'] ?? 0) ?>">
            <button type="submit" class="btn"
                    onclick="return confirm('Restore this customer and unblock their static IP?');">
                Restore now
            </button>
        </form>
    </div>
</div>

<?php
    $activeInstallment = $activeInstallment ?? null;
    $installmentPlans = $installmentPlans ?? [];
    $editInstallment = $activeInstallment ?: null;
?>
<div class="form-card" style="margin-top:18px;">
    <h2 class="form-section-title">Installation fee installment</h2>
    <div class="form-help">
        Example: total ₱7,500 at ₱1,000/month. If already on the 3rd month, set
        <strong>Months already completed</strong> to 2 so the next bill adds installment #3
        (plan + installment on one invoice).
    </div>

    <?php if (!empty($activeInstallment)): ?>
        <div class="helper-text" style="margin-bottom:12px;">
            Active: remaining ₱<?= number_format((float)$activeInstallment['remaining_balance'], 2) ?>
            of ₱<?= number_format((float)$activeInstallment['total_amount'], 2) ?>
            (₱<?= number_format((float)$activeInstallment['monthly_amount'], 2) ?>/mo,
            <?= (int)$activeInstallment['months_completed'] ?> month(s) completed).
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/customers/installment/save') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="customer_id" value="<?= (int)($customer['id'] ?? 0) ?>">
        <?php if (!empty($editInstallment['id'])): ?>
            <input type="hidden" name="installment_id" value="<?= (int)$editInstallment['id'] ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-group">
                <label for="total_amount">Installation fee total (₱)</label>
                <input
                    id="total_amount"
                    type="number"
                    step="0.01"
                    min="0.01"
                    name="total_amount"
                    value="<?= htmlspecialchars(number_format((float)($editInstallment['total_amount'] ?? 7500), 2, '.', '')) ?>"
                    required
                >
            </div>
            <div class="form-group">
                <label for="monthly_amount">Monthly installment (₱)</label>
                <input
                    id="monthly_amount"
                    type="number"
                    step="0.01"
                    min="0.01"
                    name="monthly_amount"
                    value="<?= htmlspecialchars(number_format((float)($editInstallment['monthly_amount'] ?? 1000), 2, '.', '')) ?>"
                    required
                >
            </div>
            <div class="form-group">
                <label for="months_already_completed">Months already completed</label>
                <input
                    id="months_already_completed"
                    type="number"
                    min="0"
                    step="1"
                    name="months_already_completed"
                    value="<?= (int)($editInstallment['months_completed'] ?? 0) ?>"
                >
            </div>
            <div class="form-group full">
                <label for="installment_notes">Notes</label>
                <input
                    id="installment_notes"
                    type="text"
                    name="notes"
                    value="<?= htmlspecialchars((string)($editInstallment['notes'] ?? '')) ?>"
                    placeholder="Optional"
                >
            </div>
        </div>

        <div class="page-actions">
            <button type="submit" class="btn"><?= !empty($editInstallment) ? 'Update Installment Plan' : 'Save Installment Plan' ?></button>
        </div>
    </form>

    <?php if (!empty($activeInstallment)): ?>
        <form method="POST" action="<?= url('/customers/installment/cancel') ?>" style="margin-top:10px;">
            <?= csrf_field() ?>
            <input type="hidden" name="customer_id" value="<?= (int)($customer['id'] ?? 0) ?>">
            <input type="hidden" name="installment_id" value="<?= (int)$activeInstallment['id'] ?>">
            <button
                type="submit"
                class="btn btn-secondary"
                onclick="return confirm('Cancel the active installation installment plan?');"
            >
                Cancel Active Plan
            </button>
        </form>
    <?php endif; ?>

    <?php if (!empty($installmentPlans)): ?>
        <div style="margin-top:16px; overflow-x:auto;">
            <table class="data-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Total</th>
                        <th>Monthly</th>
                        <th>Remaining</th>
                        <th>Months done</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($installmentPlans as $plan): ?>
                        <tr>
                            <td><?= (int)$plan['id'] ?></td>
                            <td>₱<?= number_format((float)$plan['total_amount'], 2) ?></td>
                            <td>₱<?= number_format((float)$plan['monthly_amount'], 2) ?></td>
                            <td>₱<?= number_format((float)$plan['remaining_balance'], 2) ?></td>
                            <td><?= (int)$plan['months_completed'] ?></td>
                            <td><?= htmlspecialchars((string)$plan['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
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
.compact-check:has(input:checked) {
    border-color: rgba(255,255,255,.28);
    background: #17171a;
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
    margin: 0;
}
</style>
