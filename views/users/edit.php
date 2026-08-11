<?php
$user = $user ?? [];
$customers = $customers ?? [];
$currentCustomerId = (int)($user['customer_id'] ?? 0);
?>

<h1>Edit User</h1>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/users') ?>">Back to Users</a>
</div>

<form method="POST" action="<?= url('/users/update') ?>">
        <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= htmlspecialchars($user['id'] ?? '') ?>">

    <div class="form-grid">
        <div class="form-group full">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
        </div>

        <div class="form-group full">
            <label for="password">New Password</label>
            <input id="password" type="password" name="password" placeholder="Leave blank to keep current password">
        </div>

        <div class="form-group full">
            <label for="role">Role</label>
            <select id="role" name="role">
                <option value="ROLE_ADMIN" <?= (($user['role'] ?? '') === 'ROLE_ADMIN') ? 'selected' : '' ?>>ROLE_ADMIN</option>
                <option value="ROLE_STAFF" <?= (($user['role'] ?? '') === 'ROLE_STAFF') ? 'selected' : '' ?>>ROLE_STAFF</option>
                <option value="ROLE_CUSTOMER" <?= (($user['role'] ?? '') === 'ROLE_CUSTOMER') ? 'selected' : '' ?>>ROLE_CUSTOMER</option>
            </select>
        </div>

        <div class="form-group full" id="customerSelectWrap" style="display:none;">
            <label for="customer_id">Linked Customer</label>
            <select id="customer_id" name="customer_id">
                <option value="">Select customer</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?= (int)($customer['id'] ?? 0) ?>" <?= $currentCustomerId === (int)($customer['id'] ?? 0) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(($customer['full_name'] ?? 'Unknown') . ' - ' . ($customer['email'] ?? 'No email')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="page-actions">
        <a class="btn btn-secondary" href="<?= url('/users') ?>">Cancel</a>
        <button type="submit" class="btn">Update User</button>
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
