<?php
$email = $email ?? '';
$rememberMe = !empty($rememberMe);
$success = $success ?? '';
$error = $error ?? '';
?>

<h1>Verify your login</h1>
<p class="form-help">
    We sent a 6-digit verification code to
    <strong><?= htmlspecialchars($email) ?></strong>.
    <?php if ($rememberMe): ?>
        Keep me signed in is enabled — you will stay logged in for 30 days after verification.
    <?php endif; ?>
</p>

<?php if ($success !== ''): ?>
    <div class="alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="<?= url('/verify-otp') ?>">
    <?= csrf_field() ?>
    <div class="form-grid" style="grid-template-columns:1fr;">
        <div class="form-group">
            <label for="otp_code">OTP Code</label>
            <input
                type="text"
                id="otp_code"
                name="otp_code"
                required
                maxlength="6"
                inputmode="numeric"
                pattern="[0-9]{6}"
                placeholder="Enter 6-digit code"
                style="letter-spacing:0.35em;text-align:center;font-size:18px;"
            >
        </div>
        <div class="form-group">
            <button type="submit" class="btn" style="width:100%;">Verify OTP</button>
        </div>
    </div>
</form>

<form method="POST" action="<?= url('/verify-otp/resend') ?>" style="margin-top:8px;">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-secondary" style="width:100%;">Resend OTP</button>
</form>

<p class="form-help" style="margin-top:16px;text-align:center;">Secure OTP verification for FusionLink</p>
