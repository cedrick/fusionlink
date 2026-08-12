<?php
$invoice = $invoice ?? null;
$summary = $summary ?? [
    'balance_due' => 0,
    'unpaid_count' => 0,
    'latest_due_date' => '-',
    'total_paid' => 0
];
$history = $history ?? [];
$paymentError = $paymentError ?? null;
$paymentMethods = $paymentMethods ?? [];

$customerDisplayName = trim((string)($invoice['full_name'] ?? ''));
$customerDisplayEmail = trim((string)($_SESSION['user']['email'] ?? ($invoice['email'] ?? 'customer@email.com')));

if ($customerDisplayName === '') {
    $customerDisplayName = $customerDisplayEmail !== '' ? $customerDisplayEmail : 'Customer';
}
?>

<style>
.customer-portal {
    display: flex;
    flex-direction: column;
    gap: 22px;
}

.customer-portal .hero {
    background:
        radial-gradient(circle at top right, rgba(59,130,246,.16), transparent 24%),
        linear-gradient(135deg, #081226 0%, #0e1730 48%, #1d3156 100%);
    color: #fff;
    border-radius: 28px;
    padding: 28px;
    border: 1px solid rgba(255,255,255,.08);
    box-shadow: 0 18px 40px rgba(0,0,0,.35);
}

.customer-portal .hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
}

.customer-portal .hero-copy {
    flex: 1;
    min-width: 0;
}

.customer-portal .hero h1 {
    margin: 0 0 8px;
    font-size: 40px;
    line-height: 1.05;
    color: #fff;
    font-weight: 900;
    letter-spacing: -.03em;
}

.customer-portal .hero p {
    margin: 0;
    color: rgba(255,255,255,.80);
    line-height: 1.7;
    font-size: 15px;
}

.customer-portal .hero-welcome {
    color: rgba(255,255,255,.88);
}

.customer-portal .hero-logout {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
}

.customer-grid {
    display: grid;
    grid-template-columns: 1.45fr .85fr;
    gap: 18px;
    align-items: start;
}

.portal-card {
    background: linear-gradient(180deg, rgba(255,255,255,.04) 0%, rgba(255,255,255,.025) 100%);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 24px;
    padding: 22px;
    box-shadow: 0 10px 28px rgba(0,0,0,.24);
    color: #fff;
}

.portal-title {
    margin: 0 0 16px;
    font-size: 22px;
    font-weight: 900;
    color: #fff;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.detail-box {
    border: 1px solid rgba(255,255,255,.08);
    background: rgba(255,255,255,.03);
    border-radius: 18px;
    padding: 16px;
}

.detail-label {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #94a3b8;
    font-weight: 800;
    margin-bottom: 8px;
}

.detail-value {
    font-size: 16px;
    font-weight: 800;
    color: #f8fafc;
    line-height: 1.5;
    word-break: break-word;
}

.detail-meta {
    font-size: 13px;
    color: #94a3b8;
    font-weight: 700;
    margin-top: 6px;
}

.kpi-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
}

.kpi-card {
    border-radius: 18px;
    padding: 18px;
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(255,255,255,.08);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.02);
}

.kpi-label {
    font-size: 12px;
    color: #94a3b8;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 8px;
}

.kpi-value {
    font-size: 30px;
    font-weight: 900;
    color: #fff;
    line-height: 1.1;
}

.kpi-meta {
    margin-top: 8px;
    color: #60a5fa;
    font-size: 13px;
    font-weight: 700;
}

.customer-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.customer-form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.customer-form-group.full {
    grid-column: 1 / -1;
}

.customer-form-group label {
    font-size: 14px;
    font-weight: 800;
    color: #f3f4f6;
}

.customer-form-group input,
.customer-form-group select {
    width: 100%;
    padding: 13px 14px;
    border: 1px solid rgba(255,255,255,.10);
    border-radius: 16px;
    font-size: 14px;
    background: rgba(255,255,255,.03);
    color: #fff;
}

.customer-form-group input::placeholder {
    color: #8a8a8f;
}

.customer-form-group input:focus,
.customer-form-group select:focus {
    outline: none;
    border-color: rgba(255,255,255,.20);
    box-shadow: 0 0 0 4px rgba(255,255,255,.05);
    background: rgba(255,255,255,.05);
}

.customer-form-group select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image:
        linear-gradient(45deg, transparent 50%, rgba(255,255,255,.75) 50%),
        linear-gradient(135deg, rgba(255,255,255,.75) 50%, transparent 50%);
    background-position:
        calc(100% - 18px) calc(50% - 3px),
        calc(100% - 12px) calc(50% - 3px);
    background-size: 6px 6px, 6px 6px;
    background-repeat: no-repeat;
    padding-right: 34px;
}

.customer-form-group select option {
    background: #111113;
    color: #fff;
}

.customer-form-group input[readonly] {
    background: rgba(255,255,255,.04);
    color: #d1d5db;
    cursor: not-allowed;
}

.portal-table-wrap {
    width: 100%;
    overflow-x: auto;
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 18px;
    background: rgba(255,255,255,.02);
}

.portal-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 760px;
}

.portal-table th,
.portal-table td {
    padding: 14px 14px;
    border-bottom: 1px solid rgba(255,255,255,.07);
    text-align: left;
    vertical-align: middle;
    color: #f5f5f5;
}

.portal-table th {
    background: rgba(255,255,255,.03);
    font-size: 14px;
    font-weight: 800;
}

.portal-table tr:last-child td {
    border-bottom: 0;
}

.portal-badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    border: 1px solid rgba(255,255,255,.08);
}

.portal-badge-warning {
    background: rgba(245,158,11,.12);
    color: #fcd34d;
}

.portal-badge-success {
    background: rgba(34,197,94,.12);
    color: #86efac;
}

.portal-badge-danger {
    background: rgba(239,68,68,.12);
    color: #fca5a5;
}

.portal-empty {
    padding: 18px;
    color: #9ca3af;
}

.portal-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 18px;
}

.portal-alert-error {
    margin-bottom: 16px;
    padding: 14px 16px;
    border-radius: 16px;
    border: 1px solid rgba(239,68,68,.20);
    background: rgba(239,68,68,.10);
    color: #fecaca;
    font-size: 14px;
    font-weight: 700;
}

@media (max-width: 1100px) {
    .customer-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 760px) {
    .detail-grid,
    .customer-form-grid {
        grid-template-columns: 1fr;
    }

    .customer-portal .hero-top {
        flex-direction: column;
        align-items: flex-start;
    }

    .customer-portal .hero h1 {
        font-size: 30px;
    }

    .customer-portal .hero-logout {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var sidebar = document.querySelector('.sidebar');
    var mobileTopbar = document.querySelector('.mobile-topbar');
    var main = document.querySelector('.main');
    var pageCard = document.querySelector('.page-card');

    if (sidebar) {
        sidebar.style.display = 'none';
    }

    if (mobileTopbar) {
        mobileTopbar.style.display = 'none';
    }

    if (main) {
        main.style.marginLeft = '0';
        main.style.padding = '24px';
    }

    if (pageCard) {
        pageCard.style.maxWidth = '1280px';
        pageCard.style.margin = '0 auto';
        pageCard.style.width = '100%';
    }
});
</script>

<div class="customer-portal">
    <div class="hero">
        <div class="hero-top">
            <div class="hero-copy">
                <h1>My Subscription Payment</h1>
                <p class="hero-welcome">
                    Welcome, <?= htmlspecialchars($customerDisplayEmail !== '' ? $customerDisplayEmail : $customerDisplayName) ?><br>
                    Manage your billing and payment here.
                </p>
            </div>

            <a class="btn btn-secondary hero-logout" href="<?= url('/logout') ?>">Logout</a>
        </div>
    </div>

    <div class="customer-grid">
        <div class="portal-card">
            <h2 class="portal-title">Current Billing Details</h2>

            <?php if (!empty($paymentError)): ?>
                <div class="portal-alert-error"><?= htmlspecialchars($paymentError) ?></div>
            <?php endif; ?>

            <?php if (!$invoice || empty($invoice['invoice_id'])): ?>
                <div class="portal-empty">No outstanding invoice found for your account.</div>
            <?php else: ?>
                <div class="detail-grid">
                    <div class="detail-box">
                        <div class="detail-label">Full Name</div>
                        <div class="detail-value"><?= htmlspecialchars($invoice['full_name'] ?? '-') ?></div>
                    </div>

                    <div class="detail-box">
                        <div class="detail-label">Account Number</div>
                        <div class="detail-value"><?= htmlspecialchars((string)($invoice['customer_id'] ?? '-')) ?></div>
                    </div>

                    <div class="detail-box">
                        <div class="detail-label">Plan</div>
                        <div class="detail-value"><?= htmlspecialchars($invoice['plan_name'] ?? '-') ?></div>
                        <?php if (!empty($invoice['speed'])): ?>
                            <div class="detail-meta"><?= htmlspecialchars($invoice['speed']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="detail-box">
                        <div class="detail-label">Billing Period</div>
                        <div class="detail-value">
                            <?php
                                $pStart = trim((string)($invoice['billing_period_start'] ?? ''));
                                $pEnd = trim((string)($invoice['billing_period_end'] ?? ''));
                                if ($pStart !== '' && $pEnd !== '') {
                                    echo htmlspecialchars(date('M j', strtotime($pStart)) . ' – ' . date('M j, Y', strtotime($pEnd)));
                                    if (!empty($invoice['is_prorated'])) {
                                        $days = (int)($invoice['coverage_days'] ?? 0);
                                        echo ' <span class="detail-meta">(prorated' . ($days > 0 ? ', ' . $days . ' day(s)' : '') . ')</span>';
                                    }
                                } else {
                                    echo '—';
                                }
                            ?>
                        </div>
                    </div>

                    <div class="detail-box">
                        <div class="detail-label">Due Date</div>
                        <div class="detail-value"><?= htmlspecialchars($invoice['due_date'] ?? '-') ?></div>
                        <div class="detail-meta">On time through this date. Overdue starts the next day.</div>
                    </div>
                </div>

                <?php if (!empty($paymentMethods)): ?>
                    <div class="portal-card" style="margin-top:18px;padding:18px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:18px;">
                        <h3 style="margin:0 0 12px;color:#fff;font-size:18px;">Payment Options</h3>
                        <div style="display:grid;gap:14px;">
                            <?php foreach ($paymentMethods as $method): ?>
                                <?php
                                    $type = strtolower((string)($method['type'] ?? ''));
                                    $qrUrl = class_exists('PaymentMethodService')
                                        ? PaymentMethodService::publicUrl((string)($method['qr_path'] ?? ''))
                                        : '';
                                ?>
                                <div style="padding:14px;border-radius:14px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.02);">
                                    <div style="font-weight:800;color:#fff;margin-bottom:8px;">
                                        <?= $type === 'gcash' ? 'GCash' : 'Bank Transfer' ?>
                                    </div>
                                    <?php if (trim((string)($method['account_name'] ?? '')) !== ''): ?>
                                        <div style="color:#cbd5e1;font-size:14px;">Account Name: <?= htmlspecialchars((string)$method['account_name']) ?></div>
                                    <?php endif; ?>
                                    <?php if (trim((string)($method['account_number'] ?? '')) !== ''): ?>
                                        <div style="color:#cbd5e1;font-size:14px;">
                                            <?= $type === 'gcash' ? 'GCash Number' : 'Account Number' ?>:
                                            <?= htmlspecialchars((string)$method['account_number']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($type === 'bank' && trim((string)($method['bank_branch'] ?? '')) !== ''): ?>
                                        <div style="color:#cbd5e1;font-size:14px;">Bank Branch: <?= htmlspecialchars((string)$method['bank_branch']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($qrUrl !== ''): ?>
                                        <div style="margin-top:10px;">
                                            <img src="<?= htmlspecialchars($qrUrl) ?>" alt="GCash QR code" style="max-width:160px;border-radius:12px;border:1px solid rgba(255,255,255,.12);">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= url('/payments/store') ?>" enctype="multipart/form-data" style="margin-top:18px;">
        <?= csrf_field() ?>
                    <input type="hidden" name="invoice_id" value="<?= (int)($invoice['invoice_id'] ?? 0) ?>">
                    <input type="hidden" name="amount" value="<?= htmlspecialchars((string)($invoice['amount'] ?? '0')) ?>">

                    <div class="customer-form-grid">
                        <div class="customer-form-group">
                            <label for="fixed_amount_display">Amount to Pay</label>
                            <input
                                id="fixed_amount_display"
                                type="text"
                                value="₱<?= number_format((float)($invoice['amount'] ?? 0), 2) ?>"
                                readonly
                            >
                            <?php if ((float)($invoice['vat_amount'] ?? 0) > 0): ?>
                                <div class="helper-text" style="margin-top:6px;">
                                    Subtotal ₱<?= number_format((float)($invoice['subtotal'] ?? 0), 2) ?>
                                    + <?= number_format((float)($invoice['vat_rate'] ?? 12), 0) ?>% VAT
                                    ₱<?= number_format((float)($invoice['vat_amount'] ?? 0), 2) ?>
                                    (VAT inclusive)
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="customer-form-group">
                            <label for="payment_date">Payment Date</label>
                            <input id="payment_date" type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="customer-form-group">
                            <label for="method">Payment Method</label>
                            <select id="method" name="method" required>
                                <?php if (empty($paymentMethods)): ?>
                                    <option value="GCASH">GCASH</option>
                                    <option value="BANK TRANSFER">BANK TRANSFER</option>
                                <?php else: ?>
                                    <?php foreach ($paymentMethods as $method): ?>
                                        <?php
                                            $type = strtolower((string)($method['type'] ?? ''));
                                            $label = $type === 'gcash' ? 'GCASH' : 'BANK TRANSFER';
                                            $name = trim((string)($method['account_name'] ?? ''));
                                            $number = trim((string)($method['account_number'] ?? ''));
                                            $optionLabel = $label;
                                            if ($name !== '') {
                                                $optionLabel .= ' - ' . $name;
                                            } elseif ($number !== '') {
                                                $optionLabel .= ' - ' . $number;
                                            }
                                        ?>
                                        <option value="<?= htmlspecialchars($optionLabel) ?>"><?= htmlspecialchars($optionLabel) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="customer-form-group full">
                            <label for="receipt">Upload Receipt (jpg, jpeg, png, webp)</label>
                            <input id="receipt" type="file" name="receipt" accept=".jpg,.jpeg,.png,.webp" required>
                        </div>
                    </div>

                    <div class="portal-actions">
                        <button type="submit" class="btn">Submit Payment</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <div class="portal-card">
            <h2 class="portal-title">Payment Overview</h2>

            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Balance Due</div>
                    <div class="kpi-value">₱<?= number_format((float)($summary['balance_due'] ?? 0), 2) ?></div>
                    <div class="kpi-meta">Outstanding balance to settle</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-label">Unpaid Invoices</div>
                    <div class="kpi-value"><?= (int)($summary['unpaid_count'] ?? 0) ?></div>
                    <div class="kpi-meta">Invoices awaiting payment</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-label">Latest Due Date</div>
                    <div class="kpi-value" style="font-size:22px;">
                        <?= htmlspecialchars((string)($summary['latest_due_date'] ?? '-')) ?>
                    </div>
                    <div class="kpi-meta">Most recent payment deadline</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-label">Total Paid</div>
                    <div class="kpi-value">₱<?= number_format((float)($summary['total_paid'] ?? 0), 2) ?></div>
                    <div class="kpi-meta">Verified payments recorded</div>
                </div>
            </div>
        </div>
    </div>

    <div class="portal-card">
        <h2 class="portal-title">My Payment History</h2>

        <div class="portal-table-wrap">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Invoice #</th>
                        <th>Amount</th>
                        <th>Payment Date</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="7" class="portal-empty">No payment history found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($history as $row): ?>
                            <?php
                                $status = strtoupper((string)($row['status'] ?? ''));
                                $badgeClass = 'portal-badge-warning';

                                if ($status === 'VERIFIED') {
                                    $badgeClass = 'portal-badge-success';
                                } elseif ($status === 'REJECTED') {
                                    $badgeClass = 'portal-badge-danger';
                                }
                            ?>
                            <tr>
                                <td><?= (int)($row['id'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string)($row['invoice_id'] ?? '-')) ?></td>
                                <td>₱<?= number_format((float)($row['amount'] ?? 0), 2) ?></td>
                                <td><?= htmlspecialchars($row['payment_date'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['method'] ?? '-') ?></td>
                                <td>
                                    <span class="portal-badge <?= $badgeClass ?>">
                                        <?= htmlspecialchars($status !== '' ? $status : 'PENDING') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($row['receipt_path'])): ?>
                                        <a class="btn btn-small" href="<?= htmlspecialchars(url($row['receipt_path'])) ?>" target="_blank">Download</a>
                                    <?php else: ?>
                                        <span class="portal-empty" style="padding:0;">No file</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
