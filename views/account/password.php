<?php
$error = $error ?? null;
$success = $success ?? null;
$isCustomer = !empty($isCustomer);
?>

<h1>Change Password</h1>
<p class="form-help">
    Update the password you use to sign in to FusionLink.
    Use at least 8 characters. Do not share this password.
</p>

<div class="page-actions">
    <?php if ($isCustomer): ?>
        <a class="btn btn-secondary" href="<?= url('/payments/create') ?>">Back to Billing</a>
    <?php else: ?>
        <a class="btn btn-secondary" href="<?= url('/dashboard') ?>">Back to Home</a>
    <?php endif; ?>
</div>

<?php if (!empty($error)): ?>
    <div class="alert-error"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert-success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="form-card">
    <h2 class="form-section-title">Your login</h2>
    <div class="form-help"><?= htmlspecialchars((string)($_SESSION['user']['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>

    <form method="POST" action="<?= url('/account/password') ?>" autocomplete="off">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="form-group full">
                <label for="current_password">Current Password</label>
                <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input id="new_password" type="password" name="new_password" minlength="8" required autocomplete="new-password">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input id="confirm_password" type="password" name="confirm_password" minlength="8" required autocomplete="new-password">
            </div>
        </div>
        <div class="page-actions">
            <button type="submit" class="btn">Save Password</button>
        </div>
    </form>
</div>
