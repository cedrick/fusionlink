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
        </form>
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
        </div>

        <div class="page-actions">
            <a href="<?= url('/customers') ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn">Update Customer</button>
        </div>
    </form>
</div>
