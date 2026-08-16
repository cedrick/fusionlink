<?php
$invoice = (isset($invoice) && is_array($invoice)) ? $invoice : [];
$invoiceId = (int)($invoice['id'] ?? 0);

$status = strtoupper((string)($invoice['status'] ?? ''));
$badgeClass = 'badge-info';

if ($status === 'PAID') {
    $badgeClass = 'badge-success';
} elseif ($status === 'ISSUED' || $status === 'DRAFT') {
    $badgeClass = 'badge-warning';
} elseif ($status === 'OVERDUE' || $status === 'UNPAID') {
    $badgeClass = 'badge-danger';
}
?>

<h1>Invoice #<?= $invoiceId ?></h1>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/invoices') ?>">Back to Invoices</a>

    <?php if (!empty($invoice) && $invoiceId > 0): ?>
        <a class="btn" href="<?= url('/invoices/pdf') ?>?id=<?= $invoiceId ?>">
            Download PDF
        </a>

        <a class="btn btn-secondary"
           href="<?= url('/invoices/email') ?>?id=<?= $invoiceId ?>"
           onclick="return confirm('Send this invoice PDF to the customer email?');">
            Send Invoice Email
        </a>
    <?php endif; ?>
</div>

<?php if (empty($invoice) || $invoiceId <= 0): ?>
    <div class="empty-state">Invoice not found.</div>
<?php else: ?>

<div class="quick-grid">

<div class="quick-card">
<div class="label">Invoice Amount</div>
<div class="value">
₱<?= number_format((float)($invoice['amount'] ?? 0), 2) ?>
</div>
<?php if ((float)($invoice['installment_amount'] ?? 0) > 0): ?>
<div class="helper-text" style="margin-top:8px;">
Plan ₱<?= number_format((float)($invoice['plan_amount'] ?? 0), 2) ?>
+ Installation installment ₱<?= number_format((float)($invoice['installment_amount'] ?? 0), 2) ?>
</div>
<?php endif; ?>
<?php if ((float)($invoice['vat_amount'] ?? 0) > 0): ?>
<div class="helper-text" style="margin-top:8px;">
Subtotal ₱<?= number_format((float)($invoice['subtotal'] ?? 0), 2) ?>
+ <?= number_format((float)($invoice['vat_rate'] ?? 12), 0) ?>% VAT ₱<?= number_format((float)($invoice['vat_amount'] ?? 0), 2) ?>
(VAT inclusive)
</div>
<?php endif; ?>
</div>

<div class="quick-card">
<div class="label">Due Date</div>
<div class="value" style="font-size:22px;">
<?= htmlspecialchars((string)($invoice['due_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
</div>
</div>

<div class="quick-card">
<div class="label">Status</div>
<div style="margin-top: 8px;">
<span class="badge <?= $badgeClass ?>">
<?= htmlspecialchars($status !== '' ? $status : 'N/A', ENT_QUOTES, 'UTF-8') ?>
</span>
</div>
</div>

</div>

<div class="form-grid">

<div class="form-group">
<label>Customer Name</label>
<input type="text"
value="<?= htmlspecialchars((string)($invoice['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
readonly>
</div>

<div class="form-group">
<label>Email</label>
<input type="text"
value="<?= htmlspecialchars((string)($invoice['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
readonly>
</div>

<div class="form-group">
<label>Phone</label>
<input type="text"
value="<?= htmlspecialchars((string)($invoice['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
readonly>
</div>

<div class="form-group">
<label>Created</label>
<input type="text"
value="<?= htmlspecialchars((string)($invoice['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
readonly>
</div>

<div class="form-group full">
<label>Address</label>
<textarea readonly><?= htmlspecialchars((string)($invoice['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
</div>

</div>

<div class="page-actions">
<a class="btn btn-danger"
href="<?= url('/invoices/delete') ?>?id=<?= $invoiceId ?>"
onclick="return confirm('Delete this invoice?');">
Delete Invoice
</a>
</div>

<?php endif; ?>
