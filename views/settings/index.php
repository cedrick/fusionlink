<?php
$settings = $settings ?? [
    'company_name' => 'ISP-BILLING-LITE',
    'business_address' => '',
    'bank_account' => '',
    'gcash_account' => '',
    'contact_number' => '',
    'email' => '',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls',
    'billing_due_day' => 8,
    'referral_reward_amount' => 500,
];
$success = $success ?? false;
$error = $error ?? false;
$message = $message ?? '';
$latestBackupFile = $latestBackupFile ?? null;
$latestBackupMeta = $latestBackupMeta ?? null;
$backupFiles = $backupFiles ?? [];
$backupUploadLimit = $backupUploadLimit ?? '2 MB';
$backupRetentionDays = (int)($backupRetentionDays ?? 14);
$paymentMethods = $paymentMethods ?? [];
?>

<style>
.settings-shell {
    display: flex;
    flex-direction: column;
    gap: 22px;
}

.settings-hero {
    position: relative;
    overflow: hidden;
    border-radius: 6px;
    padding: 30px;
    background:
        radial-gradient(circle at top right, rgba(255,255,255,.05), transparent 22%),
        linear-gradient(135deg, #050505 0%, #090909 48%, #111113 100%);
    border: 1px solid rgba(255,255,255,.08);
    box-shadow: none;
}

.settings-hero h1 {
    margin: 0 0 12px;
    font-size: 36px;
    line-height: 1.08;
    color: #ffffff;
    font-weight: 650;
    letter-spacing: -.03em;
}

.settings-hero p {
    margin: 0;
    color: rgba(255,255,255,.76);
    line-height: 1.75;
    max-width: 780px;
    font-size: 15px;
}

.settings-card {
    background: linear-gradient(180deg, rgba(255,255,255,.04) 0%, rgba(255,255,255,.025) 100%);
    border-radius: 6px;
    padding: 24px;
    border: 1px solid rgba(255,255,255,.08);
    box-shadow: 0 14px 36px rgba(0,0,0,.28);
}

.settings-card + .settings-card {
    margin-top: 0;
}

.settings-card h2 {
    margin: 0 0 8px;
    color: #ffffff;
    font-size: 22px;
    font-weight: 800;
}

.settings-card p.section-note {
    margin: 0 0 20px;
    color: rgba(255,255,255,.68);
    font-size: 14px;
    line-height: 1.7;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.settings-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.settings-group.full {
    grid-column: 1 / -1;
}

.settings-group label {
    font-size: 14px;
    font-weight: 800;
    color: #f3f4f6;
}

.settings-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 20px;
}

.settings-card input,
.settings-card textarea,
.settings-card select {
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(255,255,255,.10);
    color: #ffffff;
    border-radius: 6px;
    padding: 13px 14px;
}

.settings-card select option {
    color: #111111;
}

.settings-card textarea {
    min-height: 120px;
}

.settings-card input:focus,
.settings-card textarea:focus,
.settings-card select:focus {
    border-color: rgba(255,255,255,.20);
    box-shadow: 0 0 0 4px rgba(255,255,255,.05);
    background: rgba(255,255,255,.05);
}

.settings-divider {
    height: 1px;
    background: rgba(255,255,255,.08);
    margin: 20px 0;
}

.alert-success,
.alert-error {
    border-radius: 6px;
    padding: 14px 16px;
    margin-bottom: 18px;
    font-weight: 700;
}

.alert-success {
    background: rgba(34, 197, 94, 0.12);
    border: 1px solid rgba(34, 197, 94, 0.25);
    color: #bbf7d0;
}

.alert-error {
    background: rgba(239, 68, 68, 0.12);
    border: 1px solid rgba(239, 68, 68, 0.25);
    color: #fecaca;
}

.danger-box {
    background: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.20);
    border-radius: 4px;
    padding: 18px;
}

.danger-box h3 {
    margin: 0 0 8px;
    color: #fecaca;
    font-size: 18px;
    font-weight: 800;
}

.danger-box p {
    margin: 0 0 14px;
    color: rgba(255,255,255,.78);
    line-height: 1.7;
    font-size: 14px;
}

.settings-list {
    margin: 0 0 14px 18px;
    padding: 0;
    color: rgba(255,255,255,.82);
    line-height: 1.7;
    font-size: 14px;
}

.settings-list li {
    margin-bottom: 4px;
}

.helper-text {
    color: rgba(255,255,255,.60);
    font-size: 13px;
    line-height: 1.6;
}

.payment-methods-shell {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.payment-method-card {
    border: 1px solid rgba(255,255,255,.10);
    border-radius: 4px;
    padding: 18px;
    background: rgba(255,255,255,.02);
}

.payment-method-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    margin-bottom: 16px;
}

.payment-method-head h3 {
    margin: 0;
    color: #fff;
    font-size: 16px;
    font-weight: 800;
}

.payment-method-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.payment-method-grid .settings-group.full {
    grid-column: 1 / -1;
}

.qr-preview {
    margin-top: 10px;
    max-width: 160px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,.12);
}

.btn-danger-soft {
    background: rgba(239, 68, 68, 0.14);
    color: #fecaca;
    border: 1px solid rgba(239, 68, 68, 0.28);
}

@media (max-width: 820px) {
    .payment-method-grid {
        grid-template-columns: 1fr;
    }
}

.btn-danger {
    background: linear-gradient(180deg, #ef4444 0%, #dc2626 100%);
    color: #ffffff;
    border: 0;
}

.btn-danger:hover {
    opacity: .95;
}

.backup-meta {
    margin-top: 10px;
    padding: 14px 16px;
    border-radius: 6px;
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(255,255,255,.08);
    color: rgba(255,255,255,.78);
    font-size: 14px;
    line-height: 1.7;
}

.backup-meta strong {
    color: #ffffff;
}

.backup-table-wrap {
    margin-top: 18px;
    overflow-x: auto;
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 6px;
}

.backup-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.backup-table th,
.backup-table td {
    padding: 10px 12px;
    text-align: left;
    border-bottom: 1px solid rgba(255,255,255,.06);
    vertical-align: middle;
}

.backup-table th {
    color: rgba(255,255,255,.55);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    font-size: 11px;
    background: rgba(255,255,255,.02);
}

.backup-table tr:last-child td {
    border-bottom: 0;
}

.backup-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.backup-source {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .02em;
    border: 1px solid rgba(255,255,255,.12);
    color: rgba(255,255,255,.8);
}

.backup-source.automatic { border-color: rgba(56,189,248,.35); color: #7dd3fc; }
.backup-source.manual { border-color: rgba(52,211,153,.35); color: #6ee7b7; }
.backup-source.uploaded { border-color: rgba(251,191,36,.35); color: #fcd34d; }

@media (max-width: 900px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }

    .settings-hero h1 {
        font-size: 28px;
    }
}
</style>

<div class="settings-shell">
    <div class="settings-hero">
        <h1>System Settings</h1>
        <p>
            Manage the company information used by your billing system.
            Update your company details, SMTP sender setup, set the monthly billing due day,
            and control backup, restore, and clear billing data actions from one place.
        </p>
    </div>

    <div class="settings-card">
        <?php if ($success): ?>
            <div class="alert-success">
                <?= htmlspecialchars($message !== '' ? $message : 'Settings updated successfully.') ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-error">
                <?= htmlspecialchars($message !== '' ? $message : 'Something went wrong.') ?>
            </div>
        <?php endif; ?>

        <h2>General Settings</h2>
        <p class="section-note">
            These values are used across invoices, billing details, PDF output, and customer communication.
        </p>

        <form method="POST" action="<?= url('/settings/update') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
            <div class="settings-grid">
                <div class="settings-group">
                    <label for="company_name">Company Name</label>
                    <input
                        type="text"
                        id="company_name"
                        name="company_name"
                        value="<?= htmlspecialchars((string)($settings['company_name'] ?? '')) ?>"
                        required
                    >
                </div>

                <div class="settings-group">
                    <label for="email">Sender Email / Company Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars((string)($settings['email'] ?? '')) ?>"
                        placeholder="admin@fusionitsolution.com"
                    >
                    <div class="helper-text">
                        This is the email address that will appear as your company sender.
                    </div>
                </div>

                <div class="settings-group full">
                    <label for="business_address">Business Address</label>
                    <textarea
                        id="business_address"
                        name="business_address"
                    ><?= htmlspecialchars((string)($settings['business_address'] ?? '')) ?></textarea>
                </div>

                <div class="settings-group full">
                    <label>Payment Methods</label>
                    <div class="helper-text" style="margin-bottom:12px;">
                        Add bank or GCash payment options. These appear on invoices and the customer payment portal.
                    </div>
                    <div id="payment-methods-list" class="payment-methods-shell">
                        <?php foreach ($paymentMethods as $index => $method): ?>
                            <?php
                                $methodType = strtolower((string)($method['type'] ?? 'bank'));
                                $qrUrl = class_exists('PaymentMethodService')
                                    ? PaymentMethodService::publicUrl((string)($method['qr_path'] ?? ''))
                                    : '';
                            ?>
                            <div class="payment-method-card" data-method-row>
                                <div class="payment-method-head">
                                    <h3><?= $methodType === 'gcash' ? 'GCash Method' : 'Bank Method' ?></h3>
                                    <button type="button" class="btn btn-small btn-danger-soft" data-remove-method>Remove</button>
                                </div>
                                <input type="hidden" name="payment_methods[<?= (int)$index ?>][id]" value="<?= (int)($method['id'] ?? 0) ?>">
                                <input type="hidden" name="payment_methods[<?= (int)$index ?>][existing_qr_path]" value="<?= htmlspecialchars((string)($method['qr_path'] ?? '')) ?>">
                                <div class="payment-method-grid">
                                    <div class="settings-group">
                                        <label>Type</label>
                                        <select name="payment_methods[<?= (int)$index ?>][type]" data-method-type>
                                            <option value="bank" <?= $methodType === 'bank' ? 'selected' : '' ?>>Bank</option>
                                            <option value="gcash" <?= $methodType === 'gcash' ? 'selected' : '' ?>>GCash</option>
                                        </select>
                                    </div>
                                    <div class="settings-group">
                                        <label>Account Name</label>
                                        <input type="text" name="payment_methods[<?= (int)$index ?>][account_name]" value="<?= htmlspecialchars((string)($method['account_name'] ?? '')) ?>" placeholder="Account holder name">
                                    </div>
                                    <div class="settings-group">
                                        <label data-label-number><?= $methodType === 'gcash' ? 'GCash Number' : 'Account Number' ?></label>
                                        <input type="text" name="payment_methods[<?= (int)$index ?>][account_number]" value="<?= htmlspecialchars((string)($method['account_number'] ?? '')) ?>" placeholder="<?= $methodType === 'gcash' ? '09XXXXXXXXX' : 'Account number' ?>">
                                    </div>
                                    <div class="settings-group" data-bank-branch-group style="<?= $methodType === 'gcash' ? 'display:none;' : '' ?>">
                                        <label>Bank Branch</label>
                                        <input type="text" name="payment_methods[<?= (int)$index ?>][bank_branch]" value="<?= htmlspecialchars((string)($method['bank_branch'] ?? '')) ?>" placeholder="Branch name / location">
                                    </div>
                                    <div class="settings-group full" data-qr-group style="<?= $methodType === 'bank' ? 'display:none;' : '' ?>">
                                        <label>GCash QR Code (optional)</label>
                                        <input type="file" name="payment_methods_qr[<?= (int)$index ?>]" accept=".jpg,.jpeg,.png,.webp,.gif">
                                        <?php if ($qrUrl !== ''): ?>
                                            <img src="<?= htmlspecialchars($qrUrl) ?>" alt="GCash QR preview" class="qr-preview">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="settings-actions" style="margin-top:0;">
                        <button type="button" class="btn btn-secondary" id="add-payment-method">+ Add Payment Method</button>
                    </div>
                </div>

                <div class="settings-group">
                    <label for="contact_number">Contact Number</label>
                    <input
                        type="text"
                        id="contact_number"
                        name="contact_number"
                        value="<?= htmlspecialchars((string)($settings['contact_number'] ?? '')) ?>"
                    >
                </div>

                <div class="settings-group">
                    <label for="billing_due_day">Billing Due Day of Following Month</label>
                    <input
                        type="number"
                        id="billing_due_day"
                        name="billing_due_day"
                        min="1"
                        max="31"
                        value="<?= (int)($settings['billing_due_day'] ?? 8) ?>"
                        required
                    >
                    <div class="helper-text">
                        Coverage is always the 1st through the last day of the service month.
                        Due date is this day of the <strong>next</strong> month (default 8).
                        Still on time on the due date; overdue starts the day after.
                        Mid-month activations are prorated for remaining days only.
                        <br><br>
                        Bill / due / overdue emails are sent automatically to the customer, with a BCC copy
                        to the company email above (and admin users).
                        A system cron runs daily at <strong>06:05 Asia/Manila</strong>
                        (<code>bin/billing-cron.php</code> → log: <code>storage/logs/billing-cron.log</code>).
                        Optional HTTP fallback:
                        <code>/cron/billing?token=YOUR_TOKEN&amp;task=all</code>
                        (token in <code>config/app.php</code>).
                    </div>
                </div>

                <div class="settings-group">
                    <label for="referral_reward_amount">Referral Reward Amount (₱)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="1"
                        id="referral_reward_amount"
                        name="referral_reward_amount"
                        value="<?= htmlspecialchars(number_format((float)($settings['referral_reward_amount'] ?? 500), 2, '.', '')) ?>"
                    >
                    <div class="helper-text">
                        Bill discount given to an existing customer when someone they referred is approved.
                    </div>
                </div>
            </div>

            <div class="settings-divider"></div>

            <h2>SMTP Email Sender Settings</h2>
            <p class="section-note">
                These are the real credentials used to send emails. To make your Settings email truly become the sender,
                your SMTP username and password must belong to that same email account.
            </p>

            <div class="settings-grid">
                <div class="settings-group">
                    <label for="smtp_host">SMTP Host</label>
                    <input
                        type="text"
                        id="smtp_host"
                        name="smtp_host"
                        value="<?= htmlspecialchars((string)($settings['smtp_host'] ?? 'smtp.gmail.com')) ?>"
                        placeholder="smtp.gmail.com"
                    >
                </div>

                <div class="settings-group">
                    <label for="smtp_port">SMTP Port</label>
                    <input
                        type="number"
                        id="smtp_port"
                        name="smtp_port"
                        value="<?= (int)($settings['smtp_port'] ?? 587) ?>"
                        placeholder="587"
                    >
                </div>

                <div class="settings-group">
                    <label for="smtp_username">SMTP Username</label>
                    <input
                        type="email"
                        id="smtp_username"
                        name="smtp_username"
                        value="<?= htmlspecialchars((string)($settings['smtp_username'] ?? '')) ?>"
                        placeholder="admin@fusionitsolution.com"
                    >
                </div>

                <div class="settings-group">
                    <label for="smtp_encryption">SMTP Encryption</label>
                    <select id="smtp_encryption" name="smtp_encryption">
                        <?php $smtpEncryption = strtolower((string)($settings['smtp_encryption'] ?? 'tls')); ?>
                        <option value="tls" <?= $smtpEncryption === 'tls' ? 'selected' : '' ?>>TLS</option>
                        <option value="ssl" <?= $smtpEncryption === 'ssl' ? 'selected' : '' ?>>SSL</option>
                        <option value="starttls" <?= $smtpEncryption === 'starttls' ? 'selected' : '' ?>>STARTTLS</option>
                        <option value="" <?= $smtpEncryption === '' ? 'selected' : '' ?>>None</option>
                    </select>
                </div>

                <div class="settings-group full">
                    <label for="smtp_password">SMTP Password / App Password</label>
                    <input
                        type="password"
                        id="smtp_password"
                        name="smtp_password"
                        value=""
                        placeholder="<?= !empty($hasSmtpPassword) ? 'Leave blank to keep current app password' : 'Enter SMTP password or Gmail app password' ?>"
                        autocomplete="new-password"
                    >
                    <div class="helper-text">
                        For Gmail, use an App Password, not your normal Gmail login password.
                        <?php if (!empty($hasSmtpPassword)): ?>
                            A password is already saved on the server.
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="settings-actions">
                <button type="submit" class="btn">Save Settings</button>
                <a href="<?= url('/dashboard') ?>" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </form>
    </div>

    <div class="settings-card">
        <h2>Email Alert Test</h2>
        <p class="section-note">
            Send a test message to confirm SMTP is working before relying on billing alerts.
            Customer alerts go to the customer email or linked login email. Staff alerts go to the company email and all Admin/Staff users.
        </p>

        <form method="POST" action="<?= url('/settings/test-email') ?>">
            <?= csrf_field() ?>
            <div class="settings-grid">
                <div class="settings-group">
                    <label for="test_email">Send Test Email To</label>
                    <input
                        type="email"
                        id="test_email"
                        name="test_email"
                        value="<?= htmlspecialchars((string)($settings['email'] ?? '')) ?>"
                        placeholder="admin@fusionitsolution.com"
                        required
                    >
                </div>
            </div>
            <div class="settings-actions" style="margin-top:0;">
                <button type="submit" class="btn btn-secondary">Send Test Email</button>
            </div>
        </form>
    </div>

    <div class="settings-card">
        <h2>Database Backup & Restore</h2>
        <p class="section-note">
            Create a full SQL snapshot, restore from an uploaded file, or manage copies already stored on the server in
            <strong>storage/backups</strong>. Automatic backups run daily at <strong>02:30 Asia/Manila</strong>
            and older <strong>automatic</strong> backups are pruned after
            <strong><?= (int)$backupRetentionDays ?> days</strong> (manual/uploaded copies are kept).
        </p>

        <div class="settings-actions" style="margin-top:0;">
            <form method="POST" action="<?= url('/settings/backup') ?>" style="margin:0;">
                <?= csrf_field() ?>
                <button type="submit" class="btn">Backup Now &amp; Download</button>
            </form>
        </div>

        <div class="backup-meta" style="margin-top:18px;">
            <?php if (!empty($latestBackupMeta)): ?>
                <strong>Latest server backup:</strong> <?= htmlspecialchars((string)($latestBackupMeta['name'] ?? '')) ?><br>
                <span class="helper-text">
                    Saved: <?= htmlspecialchars((string)($latestBackupMeta['modified_at'] ?? '')) ?>
                    <?php if ((int)($latestBackupMeta['size'] ?? 0) > 0): ?>
                        | Size: <?= htmlspecialchars(
                            class_exists('DatabaseBackupService')
                                ? DatabaseBackupService::formatBytes((int)$latestBackupMeta['size'])
                                : (number_format(((int)$latestBackupMeta['size']) / 1024, 1) . ' KB'),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    <?php endif; ?>
                    <?php if (!empty($latestBackupMeta['source'])): ?>
                        | Source: <?= htmlspecialchars((string)$latestBackupMeta['source'], ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </span>
                <form
                    method="POST"
                    action="<?= url('/settings/restore-latest') ?>"
                    style="margin-top:12px;"
                    onsubmit="return confirm('Restore the latest server backup and replace the entire database?');"
                >
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-secondary">Restore Latest Server Backup</button>
                </form>
            <?php else: ?>
                <strong>No server backup file found.</strong><br>
                <span class="helper-text">Run Backup Now, or wait for the daily automatic backup.</span>
            <?php endif; ?>
        </div>

        <?php if (!empty($backupFiles)): ?>
            <div class="backup-table-wrap">
                <table class="backup-table">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Source</th>
                            <th>Saved</th>
                            <th>Size</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backupFiles as $backupRow): ?>
                            <?php
                                $rowName = (string)($backupRow['name'] ?? '');
                                $rowSource = (string)($backupRow['source'] ?? 'manual');
                                $rowSize = (int)($backupRow['size'] ?? 0);
                            ?>
                            <tr>
                                <td><code><?= htmlspecialchars($rowName, ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td><span class="backup-source <?= htmlspecialchars($rowSource, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($rowSource, ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><?= htmlspecialchars((string)($backupRow['modified_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?= htmlspecialchars(
                                        class_exists('DatabaseBackupService')
                                            ? DatabaseBackupService::formatBytes($rowSize)
                                            : (number_format($rowSize / 1024, 1) . ' KB'),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>
                                <td>
                                    <div class="backup-actions">
                                        <a class="btn btn-secondary" href="<?= htmlspecialchars(url('/settings/backup/download?file=' . rawurlencode($rowName)), ENT_QUOTES, 'UTF-8') ?>">Download</a>
                                        <form
                                            method="POST"
                                            action="<?= url('/settings/restore-selected') ?>"
                                            style="margin:0;"
                                            onsubmit="return confirm('Restore <?= htmlspecialchars($rowName, ENT_QUOTES, 'UTF-8') ?> and replace the entire database?');"
                                        >
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="file" value="<?= htmlspecialchars($rowName, ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="btn">Restore</button>
                                        </form>
                                        <form
                                            method="POST"
                                            action="<?= url('/settings/backup/delete') ?>"
                                            style="margin:0;"
                                            onsubmit="return confirm('Delete backup <?= htmlspecialchars($rowName, ENT_QUOTES, 'UTF-8') ?>?');"
                                        >
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="file" value="<?= htmlspecialchars($rowName, ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="btn btn-danger-soft">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <form
            method="POST"
            action="<?= url('/settings/restore') ?>"
            enctype="multipart/form-data"
            onsubmit="return confirm('This will replace the entire database with the uploaded SQL file. Continue?');"
            style="margin-top:18px;"
        >
            <?= csrf_field() ?>
            <div class="settings-grid">
                <div class="settings-group full">
                    <label for="backup_file">Upload SQL Backup File</label>
                    <input
                        type="file"
                        id="backup_file"
                        name="backup_file"
                        accept=".sql,application/sql,text/plain"
                        required
                    >
                    <div class="helper-text">
                        Choose a FusionLink <strong>.sql</strong> backup file from your computer.
                        Current server upload limit: <strong><?= htmlspecialchars((string)$backupUploadLimit, ENT_QUOTES, 'UTF-8') ?></strong>.
                    </div>
                </div>
            </div>
            <div class="settings-actions" style="margin-top:0;">
                <button type="submit" class="btn">Restore Uploaded Backup</button>
            </div>
        </form>
    </div>

    <div class="settings-card">
        <div class="danger-box">
            <h3>Clear Billing & Application Data</h3>
            <p>
                This removes operational billing data so you can start fresh. It is <strong>not</strong> a full database restore or structure reset.
                Use <strong>Restore Database</strong> above only when you want to replace everything from a backup file.
            </p>

            <p><strong>Will be removed:</strong></p>
            <ul class="settings-list">
                <li>Customers, subscriptions, invoices, and payments</li>
                <li>Email notification history</li>
                <li>Website inquiries / applications</li>
                <li>Referral reward records</li>
                <li>Customer portal logins (<code>ROLE_CUSTOMER</code> users only)</li>
                <li>Pending login verification codes</li>
            </ul>

            <p><strong>Will be kept:</strong></p>
            <ul class="settings-list">
                <li>Settings, SMTP configuration, and payment methods</li>
                <li>Plans and CMS content</li>
                <li>Admin and staff user accounts</li>
                <li>Activity logs and database tables</li>
            </ul>

            <form method="POST" action="<?= url('/settings/reset') ?>" onsubmit="return confirm('Clear all billing and application data listed above? This cannot be undone.');">
        <?= csrf_field() ?>
                <div class="settings-grid">
                    <div class="settings-group full">
                        <label for="reset_confirmation">Type RESET to confirm</label>
                        <input
                            type="text"
                            id="reset_confirmation"
                            name="reset_confirmation"
                            placeholder="RESET"
                            required
                        >
                    </div>
                </div>

                <div class="settings-actions">
                    <button type="submit" class="btn btn-danger">Clear Billing & Application Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var list = document.getElementById('payment-methods-list');
    var addBtn = document.getElementById('add-payment-method');
    if (!list || !addBtn) {
        return;
    }

    function nextIndex() {
        return list.querySelectorAll('[data-method-row]').length;
    }

    function toggleMethodFields(card) {
        var typeSelect = card.querySelector('[data-method-type]');
        var type = typeSelect ? typeSelect.value : 'bank';
        var branchGroup = card.querySelector('[data-bank-branch-group]');
        var qrGroup = card.querySelector('[data-qr-group]');
        var numberLabel = card.querySelector('[data-label-number]');
        var title = card.querySelector('.payment-method-head h3');

        if (branchGroup) {
            branchGroup.style.display = type === 'bank' ? '' : 'none';
        }
        if (qrGroup) {
            qrGroup.style.display = type === 'gcash' ? '' : 'none';
        }
        if (numberLabel) {
            numberLabel.textContent = type === 'gcash' ? 'GCash Number' : 'Account Number';
        }
        if (title) {
            title.textContent = type === 'gcash' ? 'GCash Method' : 'Bank Method';
        }
    }

    function bindCard(card) {
        var typeSelect = card.querySelector('[data-method-type]');
        if (typeSelect) {
            typeSelect.addEventListener('change', function () {
                toggleMethodFields(card);
            });
        }

        var removeBtn = card.querySelector('[data-remove-method]');
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                card.remove();
            });
        }

        toggleMethodFields(card);
    }

    function createCard(index) {
        var wrapper = document.createElement('div');
        wrapper.className = 'payment-method-card';
        wrapper.setAttribute('data-method-row', '1');
        wrapper.innerHTML = ''
            + '<div class="payment-method-head">'
            + '<h3>Bank Method</h3>'
            + '<button type="button" class="btn btn-small btn-danger-soft" data-remove-method>Remove</button>'
            + '</div>'
            + '<input type="hidden" name="payment_methods[' + index + '][id]" value="">'
            + '<input type="hidden" name="payment_methods[' + index + '][existing_qr_path]" value="">'
            + '<div class="payment-method-grid">'
            + '<div class="settings-group"><label>Type</label>'
            + '<select name="payment_methods[' + index + '][type]" data-method-type>'
            + '<option value="bank" selected>Bank</option><option value="gcash">GCash</option>'
            + '</select></div>'
            + '<div class="settings-group"><label>Account Name</label>'
            + '<input type="text" name="payment_methods[' + index + '][account_name]" placeholder="Account holder name"></div>'
            + '<div class="settings-group"><label data-label-number>Account Number</label>'
            + '<input type="text" name="payment_methods[' + index + '][account_number]" placeholder="Account number"></div>'
            + '<div class="settings-group" data-bank-branch-group><label>Bank Branch</label>'
            + '<input type="text" name="payment_methods[' + index + '][bank_branch]" placeholder="Branch name / location"></div>'
            + '<div class="settings-group full" data-qr-group style="display:none;"><label>GCash QR Code (optional)</label>'
            + '<input type="file" name="payment_methods_qr[' + index + ']" accept=".jpg,.jpeg,.png,.webp,.gif"></div>'
            + '</div>';
        return wrapper;
    }

    list.querySelectorAll('[data-method-row]').forEach(bindCard);

    addBtn.addEventListener('click', function () {
        var card = createCard(nextIndex());
        list.appendChild(card);
        bindCard(card);
    });
})();
</script>
