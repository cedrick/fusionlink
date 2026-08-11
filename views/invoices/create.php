<h1>Generate Invoice</h1>

<style>
.search-pick-wrap {
    position: relative;
}

.search-pick-input {
    width: 100%;
    padding: 11px 13px;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    font-size: 14px;
    background: #fff;
}
</style>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/invoices') ?>">Back to Invoices</a>
</div>

<form method="POST" action="<?= url('/invoices/store') ?>" id="invoiceForm">
        <?= csrf_field() ?>
    <div class="form-grid">
        <div class="form-group full">
            <label for="customer_search">Customer</label>
            <div class="search-pick-wrap">
                <input
                    id="customer_search"
                    class="search-pick-input"
                    list="customerOptions"
                    placeholder="Search customer name..."
                    autocomplete="off"
                    required
                >
                <datalist id="customerOptions">
                    <?php foreach (($customers ?? []) as $c): ?>
                        <option value="<?= htmlspecialchars($c['full_name'] ?? '') ?>"></option>
                    <?php endforeach; ?>
                </datalist>
                <input type="hidden" id="customer_id" name="customer_id" required>
            </div>
        </div>

        <div class="form-group">
            <label for="amount">Amount</label>
            <input id="amount" type="number" step="0.01" name="amount" required>
        </div>

        <div class="form-group">
            <label for="due_date">Due Date</label>
            <input id="due_date" type="date" name="due_date" value="<?= htmlspecialchars($defaultDue ?? date('Y-m-d')) ?>">
        </div>

        <div class="form-group full">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="ISSUED" selected>ISSUED</option>
                <option value="PAID">PAID</option>
            </select>
        </div>
    </div>

    <div class="page-actions">
        <a class="btn btn-secondary" href="<?= url('/invoices') ?>">Cancel</a>
        <button type="submit" class="btn">Create Invoice</button>
    </div>
</form>

<script>
(function () {
    const customerMap = {
        <?php foreach (($customers ?? []) as $c): ?>
            <?= json_encode((string)($c['full_name'] ?? '')) ?>: <?= (int)($c['id'] ?? 0) ?>,
        <?php endforeach; ?>
    };

    const customerSearch = document.getElementById('customer_search');
    const customerId = document.getElementById('customer_id');
    const form = document.getElementById('invoiceForm');

    function syncHidden() {
        const value = customerSearch.value.trim();
        customerId.value = Object.prototype.hasOwnProperty.call(customerMap, value) ? customerMap[value] : '';
    }

    customerSearch.addEventListener('input', syncHidden);

    form.addEventListener('submit', function (e) {
        syncHidden();

        if (!customerId.value) {
            e.preventDefault();
            alert('Please select a valid customer from the searchable list.');
            customerSearch.focus();
        }
    });
})();
</script>
