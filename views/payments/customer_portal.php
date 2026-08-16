<?php
$invoice = $invoice ?? null;
$summary = $summary ?? [
    'balance_due' => 0,
    'unpaid_count' => 0,
    'latest_due_date' => '-',
    'total_paid' => 0
];
$history = $history ?? [];
$openInvoices = $openInvoices ?? [];
$invoiceHistory = $invoiceHistory ?? [];
$officialReceipts = $officialReceipts ?? [];
$paymentError = $paymentError ?? null;
$paymentMethods = $paymentMethods ?? [];

$customerDisplayName = trim((string)($invoice['full_name'] ?? ''));
$customerDisplayEmail = trim((string)($_SESSION['user']['email'] ?? ($invoice['email'] ?? 'customer@email.com')));

if ($customerDisplayName === '') {
    $customerDisplayName = $customerDisplayEmail !== '' ? $customerDisplayEmail : 'Customer';
}

if (!function_exists('portal_period_label')) {
    function portal_period_label(array $row): string
    {
        $pStart = trim((string)($row['billing_period_start'] ?? ''));
        $pEnd = trim((string)($row['billing_period_end'] ?? ''));
        if ($pStart === '' || $pEnd === '') {
            return '—';
        }
        $label = date('M j', strtotime($pStart)) . ' – ' . date('M j, Y', strtotime($pEnd));
        if (!empty($row['is_prorated'])) {
            $days = (int)($row['coverage_days'] ?? 0);
            $label .= ' (prorated' . ($days > 0 ? ', ' . $days . ' day(s)' : '') . ')';
        }
        return $label;
    }
}

if (!function_exists('portal_billing_type_label')) {
    function portal_billing_type_label(?string $type): string
    {
        $type = strtoupper(trim((string)$type));
        if ($type === 'EXISTING_MIGRATE') {
            return 'Existing customer (full month)';
        }
        if ($type === 'NEW_ACTIVATION') {
            return 'New activation';
        }
        return '—';
    }
}

if (!function_exists('portal_status_badge')) {
    function portal_status_badge(string $status): string
    {
        $status = strtoupper($status);
        $class = 'badge-info';
        if ($status === 'PAID' || $status === 'VERIFIED') {
            $class = 'badge-success';
        } elseif ($status === 'ISSUED' || $status === 'DRAFT' || $status === 'PENDING') {
            $class = 'badge-warning';
        } elseif ($status === 'OVERDUE' || $status === 'REJECTED') {
            $class = 'badge-danger';
        }

        return $class;
    }
}
?>

<style>
.portal-pay-methods {
    display: grid;
    gap: 10px;
}
.portal-pay-method {
    border: 1px solid rgba(255,255,255,.10);
    background: #111113;
    border-radius: 6px;
    padding: 14px;
}
.portal-pay-method img {
    max-width: 160px;
    margin-top: 10px;
    border-radius: 4px;
    border: 1px solid rgba(255,255,255,.10);
}
.form-card + .form-card {
    margin-top: 16px;
}
.helper-text {
    color: #737373;
    font-size: 12px;
    margin-top: 6px;
    line-height: 1.45;
}
</style>

<h1>Billing Portal</h1>
<p class="form-help">
    Welcome, <?= htmlspecialchars($customerDisplayName) ?>.
    Coverage is the 1st through the last day of the month. Pay by the due date; overdue starts the next day.
</p>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/account/password') ?>">Change Password</a>
</div>

<?php if (!empty($paymentError)): ?>
    <div class="alert-error"><?= htmlspecialchars($paymentError) ?></div>
<?php endif; ?>

<div class="quick-grid">
    <div class="quick-card">
        <div class="label">Balance Due</div>
        <div class="value">₱<?= number_format((float)($summary['balance_due'] ?? 0), 2) ?></div>
    </div>
    <div class="quick-card">
        <div class="label">Unpaid Invoices</div>
        <div class="value"><?= (int)($summary['unpaid_count'] ?? 0) ?></div>
    </div>
    <div class="quick-card">
        <div class="label">Latest Due Date</div>
        <div class="value" style="font-size:20px;"><?= htmlspecialchars((string)($summary['latest_due_date'] ?? '-')) ?></div>
    </div>
    <div class="quick-card">
        <div class="label">Total Paid</div>
        <div class="value">₱<?= number_format((float)($summary['total_paid'] ?? 0), 2) ?></div>
    </div>
</div>

<div class="form-card">
    <h2 class="form-section-title">Account</h2>
    <div class="form-grid">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" value="<?= htmlspecialchars($invoice['full_name'] ?? '-') ?>" readonly>
        </div>
        <div class="form-group">
            <label>Account Number</label>
            <input type="text" value="<?= htmlspecialchars((string)($invoice['customer_id'] ?? '-')) ?>" readonly>
        </div>
        <div class="form-group">
            <label>Plan</label>
            <input type="text" value="<?= htmlspecialchars(trim((string)($invoice['plan_name'] ?? '-') . (!empty($invoice['speed']) ? ' · ' . $invoice['speed'] : ''))) ?>" readonly>
        </div>
        <div class="form-group">
            <label>Billing Type</label>
            <input type="text" value="<?= htmlspecialchars(portal_billing_type_label($invoice['billing_type'] ?? '')) ?>" readonly>
            <div class="helper-text">Existing = full month, never prorated. New activation may prorate the first month.</div>
        </div>
    </div>
</div>

<div class="form-card">
    <h2 class="form-section-title">Unpaid Bills</h2>
    <?php if (empty($openInvoices)): ?>
        <div class="empty-state">No outstanding invoice found for your account.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Period</th>
                        <th>Due</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($openInvoices as $openRow): ?>
                        <?php
                            $openId = (int)($openRow['invoice_id'] ?? 0);
                            $isSelected = $openId === (int)($invoice['invoice_id'] ?? 0);
                            $openStatus = strtoupper((string)($openRow['status'] ?? 'ISSUED'));
                        ?>
                        <tr>
                            <td>#<?= $openId ?></td>
                            <td><?= htmlspecialchars(portal_period_label($openRow)) ?></td>
                            <td><?= htmlspecialchars((string)($openRow['due_date'] ?? '-')) ?></td>
                            <td>
                                ₱<?= number_format((float)($openRow['amount'] ?? 0), 2) ?>
                                <?php if ((float)($openRow['vat_amount'] ?? 0) > 0): ?>
                                    <div class="helper-text">VAT inclusive</div>
                                <?php endif; ?>
                                <?php if ((float)($openRow['installment_amount'] ?? 0) > 0): ?>
                                    <div class="helper-text">Includes installment</div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= portal_status_badge($openStatus) ?>"><?= htmlspecialchars($openStatus) ?></span></td>
                            <td class="actions">
                                <a class="btn btn-small" href="<?= url('/invoices/pdf') ?>?id=<?= $openId ?>">Invoice PDF</a>
                                <?php if (!$isSelected): ?>
                                    <a class="btn btn-small btn-secondary" href="<?= url('/payments/create') ?>?invoice_id=<?= $openId ?>">Pay this bill</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($openInvoices) && !empty($invoice['invoice_id'])): ?>
<div class="form-card">
    <h2 class="form-section-title">Pay Invoice #<?= (int)$invoice['invoice_id'] ?></h2>
    <div class="form-help">
        <?= htmlspecialchars(portal_period_label($invoice)) ?>.
        Due <?= htmlspecialchars((string)($invoice['due_date'] ?? '-')) ?>.
        On time through this date. Overdue starts the next day.
    </div>

    <?php if (!empty($paymentMethods)): ?>
        <h3 class="form-section-title" style="margin-top:8px;">Payment Options</h3>
        <div class="portal-pay-methods">
            <?php foreach ($paymentMethods as $method): ?>
                <?php
                    $type = strtolower((string)($method['type'] ?? ''));
                    $qrUrl = class_exists('PaymentMethodService')
                        ? PaymentMethodService::publicUrl((string)($method['qr_path'] ?? ''))
                        : '';
                ?>
                <div class="portal-pay-method">
                    <div style="font-weight:650;margin-bottom:8px;"><?= $type === 'gcash' ? 'GCash' : 'Bank Transfer' ?></div>
                    <?php if (trim((string)($method['account_name'] ?? '')) !== ''): ?>
                        <div class="helper-text">Account Name: <?= htmlspecialchars((string)$method['account_name']) ?></div>
                    <?php endif; ?>
                    <?php if (trim((string)($method['account_number'] ?? '')) !== ''): ?>
                        <div class="helper-text">
                            <?= $type === 'gcash' ? 'GCash Number' : 'Account Number' ?>:
                            <?= htmlspecialchars((string)$method['account_number']) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($type === 'bank' && trim((string)($method['bank_branch'] ?? '')) !== ''): ?>
                        <div class="helper-text">Bank Branch: <?= htmlspecialchars((string)$method['bank_branch']) ?></div>
                    <?php endif; ?>
                    <?php if ($qrUrl !== ''): ?>
                        <img src="<?= htmlspecialchars($qrUrl) ?>" alt="Payment QR code">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/payments/store') ?>" enctype="multipart/form-data" style="margin-top:16px;">
        <?= csrf_field() ?>
        <input type="hidden" name="invoice_id" value="<?= (int)($invoice['invoice_id'] ?? 0) ?>">
        <input type="hidden" name="amount" value="<?= htmlspecialchars((string)($invoice['amount'] ?? '0')) ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="fixed_amount_display">Amount to Pay</label>
                <input
                    id="fixed_amount_display"
                    type="text"
                    value="₱<?= number_format((float)($invoice['amount'] ?? 0), 2) ?>"
                    readonly
                >
                <?php if ((float)($invoice['vat_amount'] ?? 0) > 0): ?>
                    <div class="helper-text">
                        Subtotal ₱<?= number_format((float)($invoice['subtotal'] ?? 0), 2) ?>
                        + <?= number_format((float)($invoice['vat_rate'] ?? 12), 0) ?>% VAT
                        ₱<?= number_format((float)($invoice['vat_amount'] ?? 0), 2) ?>
                        (VAT inclusive)
                    </div>
                <?php endif; ?>
                <?php if ((float)($invoice['installment_amount'] ?? 0) > 0): ?>
                    <div class="helper-text">
                        Plan ₱<?= number_format((float)($invoice['plan_amount'] ?? 0), 2) ?>
                        + Installation installment ₱<?= number_format((float)($invoice['installment_amount'] ?? 0), 2) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="payment_date">Payment Date</label>
                <input id="payment_date" type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group">
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

            <div class="form-group full">
                <label for="receipt">Upload Payment Proof (jpg, jpeg, png, webp)</label>
                <input id="receipt" type="file" name="receipt" accept=".jpg,.jpeg,.png,.webp" required>
            </div>
        </div>

        <div class="page-actions">
            <button type="submit" class="btn">Submit Payment</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="form-card">
    <h2 class="form-section-title">My Bills</h2>
    <div class="form-help">
        Download the invoice PDF anytime. VAT bills also get an Official Receipt after FusionLink attaches it.
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Billing Period</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th class="actions">Documents</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoiceHistory)): ?>
                    <tr>
                        <td colspan="6" class="empty-state">No bills found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($invoiceHistory as $bill): ?>
                        <?php
                            $billId = (int)($bill['id'] ?? 0);
                            $billStatus = strtoupper((string)($bill['status'] ?? ''));
                        ?>
                        <tr>
                            <td>#<?= $billId ?></td>
                            <td><?= htmlspecialchars(portal_period_label($bill)) ?></td>
                            <td><?= htmlspecialchars((string)($bill['due_date'] ?? '-')) ?></td>
                            <td>
                                ₱<?= number_format((float)($bill['amount'] ?? 0), 2) ?>
                                <?php if ((float)($bill['vat_amount'] ?? 0) > 0): ?>
                                    <div class="helper-text">VAT inclusive</div>
                                <?php endif; ?>
                                <?php if ((float)($bill['installment_amount'] ?? 0) > 0): ?>
                                    <div class="helper-text">Includes installment</div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= portal_status_badge($billStatus) ?>"><?= htmlspecialchars($billStatus) ?></span></td>
                            <td class="actions">
                                <a class="btn btn-small" href="<?= url('/invoices/pdf') ?>?id=<?= $billId ?>">Invoice PDF</a>
                                <?php if ((float)($bill['vat_amount'] ?? 0) > 0 && !empty($bill['official_receipt_path'])): ?>
                                    <a class="btn btn-small btn-secondary" href="<?= url('/invoices/official-receipt') ?>?id=<?= $billId ?>">Official Receipt</a>
                                <?php elseif ((float)($bill['vat_amount'] ?? 0) > 0): ?>
                                    <span class="empty-state" style="padding:0;">OR pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="form-card">
    <h2 class="form-section-title">Payment History</h2>
    <div class="form-help">Your GCash or bank payment proof, not the Official Receipt.</div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Invoice #</th>
                    <th>Amount</th>
                    <th>Payment Date</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Payment Proof</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">No payment history found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($history as $row): ?>
                        <?php $status = strtoupper((string)($row['status'] ?? 'PENDING')); ?>
                        <tr>
                            <td><?= (int)($row['id'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string)($row['invoice_id'] ?? '-')) ?></td>
                            <td>₱<?= number_format((float)($row['amount'] ?? 0), 2) ?></td>
                            <td><?= htmlspecialchars($row['payment_date'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['method'] ?? '-') ?></td>
                            <td><span class="badge <?= portal_status_badge($status) ?>"><?= htmlspecialchars($status) ?></span></td>
                            <td>
                                <?php if (!empty($row['receipt_path'])): ?>
                                    <a class="btn btn-small" href="<?= htmlspecialchars(url($row['receipt_path'])) ?>" target="_blank">View</a>
                                <?php else: ?>
                                    <span class="empty-state" style="padding:0;">No file</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($officialReceipts)): ?>
<div class="form-card">
    <h2 class="form-section-title">Official Receipts</h2>
    <div class="form-help">VAT-inclusive bills only. This is the Official Receipt FusionLink attaches, not your payment proof.</div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Billing Period</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Official Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($officialReceipts as $orRow): ?>
                    <?php $orStatus = strtoupper((string)($orRow['status'] ?? '')); ?>
                    <tr>
                        <td>#<?= (int)($orRow['id'] ?? 0) ?></td>
                        <td><?= htmlspecialchars(portal_period_label($orRow)) ?></td>
                        <td>₱<?= number_format((float)($orRow['amount'] ?? 0), 2) ?></td>
                        <td><span class="badge <?= portal_status_badge($orStatus) ?>"><?= htmlspecialchars($orStatus) ?></span></td>
                        <td>
                            <?php if (!empty($orRow['official_receipt_path'])): ?>
                                <a class="btn btn-small" href="<?= url('/invoices/official-receipt') ?>?id=<?= (int)($orRow['id'] ?? 0) ?>">Download Official Receipt</a>
                            <?php else: ?>
                                <span class="empty-state" style="padding:0;">Not attached yet</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
