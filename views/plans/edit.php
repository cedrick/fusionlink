<h1>Edit Plan</h1>

<div class="page-actions">
    <a href="<?= url('/plans') ?>" class="btn btn-secondary">Back to Plans</a>
</div>

<div class="form-card">
    <h2 class="form-section-title">Plan Details</h2>
    <div class="form-help">Update the plan name, speed, and monthly price.</div>

    <form method="POST" action="<?= url('/plans/update') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)($plan['id'] ?? 0) ?>">

        <div class="form-grid">
            <div class="form-group full">
                <label for="name">Plan Name</label>
                <input id="name" type="text" name="name" value="<?= htmlspecialchars($plan['name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="speed">Speed</label>
                <input id="speed" type="text" name="speed" value="<?= htmlspecialchars($plan['speed'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="price">Price</label>
                <input id="price" type="number" step="0.01" name="price" value="<?= htmlspecialchars($plan['price'] ?? '') ?>" required>
            </div>

            <div class="form-group full">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_legacy" value="1" <?= !empty($plan['is_legacy']) ? 'checked' : '' ?>>
                    Legacy customers only (hidden from public signup page)
                </label>
                <div class="form-help">Use for old plans kept for existing subscribers. Staff can still assign these in Subscriptions.</div>
            </div>
        </div>

        <div class="page-actions">
            <a href="<?= url('/plans') ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn">Update Plan</button>
        </div>
    </form>
</div>
