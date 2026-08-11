<h1>Edit Subscription</h1>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/subscriptions') ?>">Back to Subscriptions</a>
</div>

<div class="form-card">
    <h2 class="form-section-title">Subscription Details</h2>
    <div class="form-help">Update the assigned customer, plan, start date, and subscription status.</div>

    <form method="POST" action="<?= url('/subscriptions/update') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$subscription['id'] ?>">

        <div class="form-grid">
            <div class="form-group full">
                <label for="customer_id">Customer</label>
                <select id="customer_id" name="customer_id" required>
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= ((int)$subscription['customer_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group full">
                <label for="plan_id">Plan</label>
                <select id="plan_id" name="plan_id" required>
                    <option value="">-- Select Plan --</option>
                    <?php foreach ($plans as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= ((int)$subscription['plan_id'] === (int)$p['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['speed']) ?>) - ₱<?= htmlspecialchars($p['price']) ?><?= !empty($p['is_legacy']) ? ' [legacy]' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input id="start_date" type="date" name="start_date" value="<?= htmlspecialchars($subscription['start_date']) ?>" required>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="ACTIVE" <?= ($subscription['status'] === 'ACTIVE') ? 'selected' : '' ?>>ACTIVE</option>
                    <option value="SUSPENDED" <?= ($subscription['status'] === 'SUSPENDED') ? 'selected' : '' ?>>SUSPENDED</option>
                    <option value="CANCELLED" <?= ($subscription['status'] === 'CANCELLED') ? 'selected' : '' ?>>CANCELLED</option>
                </select>
            </div>
        </div>

        <div class="page-actions">
            <a class="btn btn-secondary" href="<?= url('/subscriptions') ?>">Cancel</a>
            <button type="submit" class="btn">Update Subscription</button>
        </div>
    </form>
</div>
