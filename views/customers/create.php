<h1>Add Customer</h1>

<div class="page-actions">
    <a href="<?= url('/customers') ?>" class="btn btn-secondary">Back to Customers</a>
</div>

<div class="form-card">
    <h2 class="form-section-title">Customer Information</h2>
    <div class="form-help">Fill out the details below to create a new customer account.</div>

    <form method="POST" action="<?= url('/customers/store') ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="form-group full">
                <label for="full_name">Full Name</label>
                <input id="full_name" type="text" name="full_name" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email">
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input
                    id="phone"
                    type="text"
                    name="phone"
                    inputmode="numeric"
                    pattern="^09[0-9]{9}$"
                    maxlength="11"
                    minlength="11"
                    placeholder="09XXXXXXXXX"
                    title="Enter a valid 11-digit mobile number starting with 09"
                    required
                >
            </div>

            <div class="form-group full">
                <label for="address">Address</label>
                <textarea id="address" name="address"></textarea>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="ACTIVE">ACTIVE</option>
                    <option value="DISCONNECTED">DISCONNECTED</option>
                </select>
            </div>
        </div>

        <div class="page-actions">
            <a href="<?= url('/customers') ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn">Save Customer</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const phoneInput = document.getElementById('phone');

    if (!phoneInput) {
        return;
    }

    phoneInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 11);
    });
});
</script>
