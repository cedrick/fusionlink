<h1>Plans</h1>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert-error"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/dashboard') ?>">Back to Dashboard</a>
    <a class="btn" href="<?= url('/plans/create') ?>">Add Plan</a>
</div>

<div class="table-top">
    <div class="table-meta">
        Manage internet plans and pricing. Legacy-only plans stay available for existing customers in Subscriptions but are hidden from the public signup page.
    </div>
</div>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Speed</th>
                <th>Price</th>
                <th>Visibility</th>
                <th class="actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($plans)): ?>
                <tr>
                    <td colspan="6" class="empty-state">No plans found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($plans as $p): ?>
                    <tr>
                        <td><?= (int)($p['id'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($p['name'] ?? '') ?></td>
                        <td>
                            <span class="badge badge-info">
                                <?= htmlspecialchars($p['speed'] ?? '') ?>
                            </span>
                        </td>
                        <td>₱<?= number_format((float)($p['price'] ?? 0), 2) ?></td>
                        <td>
                            <?php if (!empty($p['is_legacy'])): ?>
                                <span class="badge badge-warning">Legacy only</span>
                            <?php else: ?>
                                <span class="badge badge-success">Public signup</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <a class="btn btn-small" href="<?= url('/plans/edit') ?>?id=<?= (int)($p['id'] ?? 0) ?>">Edit</a>
                            <a class="btn btn-small btn-danger"
                               href="<?= url('/plans/delete') ?>?id=<?= (int)($p['id'] ?? 0) ?>"
                               onclick="return confirm('Delete this plan?');">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
