<?php $paymentError = $paymentError ?? null; ?>

<h1>Record Payment</h1>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/payments') ?>">Back to Payments</a>
</div>

<?php if (!empty($paymentError)): ?>
    <div class="alert-error"><?= htmlspecialchars($paymentError) ?></div>
<?php endif; ?>

<div class="form-card">
    <h2 class="form-section-title">Payment Details</h2>
    <div class="form-help">Search the invoice, enter payment details, and upload the receipt if available.</div>

    <form method="POST" action="<?= url('/payments/store') ?>" enctype="multipart/form-data" id="paymentForm">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="form-group full">
                <label for="invoice_search">Invoice</label>
                <div class="search-pick-wrap">
                    <input
                        id="invoice_search"
                        class="search-pick-input"
                        list="invoiceOptions"
                        placeholder="Search invoice number, customer, amount, or status..."
                        autocomplete="off"
                        value="<?= htmlspecialchars($selectedInvoiceLabel ?? '') ?>"
                        required
                    >
                    <datalist id="invoiceOptions">
                        <?php foreach (($invoices ?? []) as $inv): ?>
                            <?php
                                $invoiceLabel = 'Invoice #' . (int)($inv['id'] ?? 0)
                                    . ' - ' . (string)($inv['customer_name'] ?? '')
                                    . ' - Amount: ' . number_format((float)($inv['amount'] ?? 0), 2)
                                    . ' - Status: ' . (string)($inv['status'] ?? '');
                            ?>
                            <option value="<?= htmlspecialchars($invoiceLabel) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <input type="hidden" id="invoice_id" name="invoice_id" value="<?= (int)($selectedInvoiceId ?? 0) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="amount">Amount</label>
                <input id="amount" type="number" step="0.01" min="0.01" name="amount" value="<?= htmlspecialchars($prefillAmount ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="payment_date">Payment Date</label>
                <input id="payment_date" type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group">
                <label for="method">Method</label>
                <select id="method" name="method" required>
                    <option value="GCASH" selected>GCASH</option>
                    <option value="CASH">CASH</option>
                    <option value="BANK TRANSFER">BANK TRANSFER</option>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="PENDING" selected>PENDING</option>
                    <option value="VERIFIED">VERIFIED</option>
                    <option value="REJECTED">REJECTED</option>
                </select>
            </div>

            <div class="form-group full">
                <label for="receipt">Receipt Upload (jpg, jpeg, png, webp)</label>
                <input id="receipt" type="file" name="receipt" accept=".jpg,.jpeg,.png,.webp">
            </div>
        </div>

        <div class="page-actions">
            <a class="btn btn-secondary" href="<?= url('/payments') ?>">Cancel</a>
            <button type="submit" class="btn">Save Payment</button>
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
    const invoiceMap = {
        <?php foreach (($invoices ?? []) as $inv): ?>
            <?php
                $invoiceLabel = 'Invoice #' . (int)($inv['id'] ?? 0)
                    . ' - ' . (string)($inv['customer_name'] ?? '')
                    . ' - Amount: ' . number_format((float)($inv['amount'] ?? 0), 2)
                    . ' - Status: ' . (string)($inv['status'] ?? '');
            ?>
            <?= json_encode($invoiceLabel) ?>: {
                id: <?= (int)($inv['id'] ?? 0) ?>,
                amount: <?= json_encode((string)((float)($inv['amount'] ?? 0))) ?>
            },
        <?php endforeach; ?>
    };

    const invoiceSearch = document.getElementById('invoice_search');
    const invoiceId = document.getElementById('invoice_id');
    const amountInput = document.getElementById('amount');
    const form = document.getElementById('paymentForm');

    function syncHidden() {
        const value = invoiceSearch.value.trim();

        if (Object.prototype.hasOwnProperty.call(invoiceMap, value)) {
            invoiceId.value = invoiceMap[value].id;
            amountInput.value = invoiceMap[value].amount;
        } else {
            invoiceId.value = '';
        }
    }

    invoiceSearch.addEventListener('input', syncHidden);

    form.addEventListener('submit', function (e) {
        syncHidden();

        if (!invoiceId.value) {
            e.preventDefault();
            alert('Please select a valid invoice from the searchable list.');
            invoiceSearch.focus();
            return;
        }

        const selectedLabel = invoiceSearch.value.trim();
        const selectedInvoice = invoiceMap[selectedLabel];

        if (selectedInvoice) {
            const requiredAmount = parseFloat(selectedInvoice.amount || '0');
            const enteredAmount = parseFloat(amountInput.value || '0');

            if (enteredAmount < requiredAmount) {
                e.preventDefault();
                alert('Payment is insufficient. Required amount is ₱' + requiredAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '.');
                amountInput.focus();
            }
        }
    });
})();
</script>
