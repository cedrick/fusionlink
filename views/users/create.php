<?php
$customers = $customers ?? [];
?>

<h1>Create User</h1>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/users') ?>">Back to Users</a>
</div>

<form method="POST" action="<?= url('/users/store') ?>">
        <?= csrf_field() ?>
    <div class="form-grid">
        <div class="form-group full">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" required placeholder="Enter user email">
        </div>

        <div class="form-group full">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required placeholder="Enter password">
        </div>

        <div class="form-group full">
            <label for="role">Role</label>
            <select id="role" name="role">
                <option value="ROLE_ADMIN">ROLE_ADMIN</option>
                <option value="ROLE_STAFF" selected>ROLE_STAFF</option>
                <option value="ROLE_CUSTOMER">ROLE_CUSTOMER</option>
            </select>
        </div>

        <div class="form-group full" id="customerSelectWrap" style="display:none;">
            <label for="customer_id">Linked Customer</label>
            <select id="customer_id" name="customer_id">
                <option value="">Select customer</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?= (int)($customer['id'] ?? 0) ?>">
                        <?= htmlspecialchars(($customer['full_name'] ?? 'Unknown') . ' - ' . ($customer['email'] ?? 'No email')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="page-actions">
        <a class="btn btn-secondary" href="<?= url('/users') ?>">Cancel</a>
        <button type="submit" class="btn">Create User</button>
    </div>
</form>

<script>
(function () {
    const roleSelect = document.getElementById('role');
    const customerWrap = document.getElementById('customerSelectWrap');
    const customerSelect = document.getElementById('customer_id');

    function syncCustomerField() {
        const isCustomer = roleSelect.value === 'ROLE_CUSTOMER';
        customerWrap.style.display = isCustomer ? 'block' : 'none';
        customerSelect.required = isCustomer;

        if (!isCustomer) {
            customerSelect.value = '';
        }
    }

    roleSelect.addEventListener('change', syncCustomerField);
    syncCustomerField();
})();
</script>
