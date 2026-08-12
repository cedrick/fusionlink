<?php
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
$totalRows = $totalRows ?? 0;
$search = $search ?? '';
$statusFilter = $statusFilter ?? '';
$sortBy = $sortBy ?? 'id';
$sortDir = $sortDir ?? 'DESC';

function subscriptions_sort_url(string $column, string $currentSortBy, string $currentSortDir, string $search, string $statusFilter): string
{
    $nextDir = 'ASC';
    if ($currentSortBy === $column && strtoupper($currentSortDir) === 'ASC') {
        $nextDir = 'DESC';
    }

    return url('/subscriptions') . '?search=' . urlencode($search)
        . '&status=' . urlencode($statusFilter)
        . '&sort_by=' . urlencode($column)
        . '&sort_dir=' . urlencode($nextDir);
}

function subscriptions_page_url(int $page, string $search, string $statusFilter, string $sortBy, string $sortDir): string
{
    return url('/subscriptions') . '?page=' . $page
        . '&search=' . urlencode($search)
        . '&status=' . urlencode($statusFilter)
        . '&sort_by=' . urlencode($sortBy)
        . '&sort_dir=' . urlencode($sortDir);
}
?>

<h1>Subscriptions</h1>

<style>
.subscriptions-toolbar-form {
    grid-template-columns: 1.4fr .9fr .9fr .9fr auto;
    align-items: end;
}

@media (max-width: 1100px) {
    .subscriptions-toolbar-form {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 700px) {
    .subscriptions-toolbar-form {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/dashboard') ?>">Back to Dashboard</a>
    <a class="btn" href="<?= url('/subscriptions/create') ?>">Add Subscription</a>
</div>

<div class="toolbar-card">
    <form method="GET" action="<?= url('/subscriptions') ?>" class="toolbar-form subscriptions-toolbar-form">
        <div class="toolbar-group">
            <label for="search">Search</label>
            <input
                type="text"
                id="search"
                name="search"
                class="toolbar-input"
                placeholder="Customer, plan, speed, price, status"
                value="<?= htmlspecialchars($search) ?>"
            >
        </div>

        <div class="toolbar-group">
            <label for="status">Status Filter</label>
            <select id="status" name="status" class="toolbar-select">
                <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>All Status</option>
                <option value="ACTIVE" <?= $statusFilter === 'ACTIVE' ? 'selected' : '' ?>>ACTIVE</option>
                <option value="SUSPENDED" <?= $statusFilter === 'SUSPENDED' ? 'selected' : '' ?>>SUSPENDED</option>
                <option value="CANCELLED" <?= $statusFilter === 'CANCELLED' ? 'selected' : '' ?>>CANCELLED</option>
            </select>
        </div>

        <div class="toolbar-group">
            <label for="sort_by">Sort By</label>
            <select id="sort_by" name="sort_by" class="toolbar-select">
                <option value="id" <?= $sortBy === 'id' ? 'selected' : '' ?>>ID</option>
                <option value="customer_name" <?= $sortBy === 'customer_name' ? 'selected' : '' ?>>Customer</option>
                <option value="plan_name" <?= $sortBy === 'plan_name' ? 'selected' : '' ?>>Plan</option>
                <option value="speed" <?= $sortBy === 'speed' ? 'selected' : '' ?>>Speed</option>
                <option value="price" <?= $sortBy === 'price' ? 'selected' : '' ?>>Price</option>
                <option value="start_date" <?= $sortBy === 'start_date' ? 'selected' : '' ?>>Start Date</option>
                <option value="status" <?= $sortBy === 'status' ? 'selected' : '' ?>>Status</option>
                <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>Created</option>
            </select>
        </div>

        <div class="toolbar-group">
            <label for="sort_dir">Direction</label>
            <select id="sort_dir" name="sort_dir" class="toolbar-select">
                <option value="ASC" <?= strtoupper($sortDir) === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                <option value="DESC" <?= strtoupper($sortDir) === 'DESC' ? 'selected' : '' ?>>Descending</option>
            </select>
        </div>

        <div class="toolbar-submit-group">
            <button type="submit" class="btn">Apply</button>
            <a class="btn btn-secondary" href="<?= url('/subscriptions') ?>">Reset</a>
        </div>
    </form>
</div>

<div class="table-top">
    <div class="table-meta">
        Showing <?= count($subscriptions) ?> of <?= (int)$totalRows ?> subscription(s)
    </div>
</div>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>
                    <a class="sort-link" href="<?= htmlspecialchars(subscriptions_sort_url('id', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                        ID
                        <?php if ($sortBy === 'id'): ?>
                            <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a class="sort-link" href="<?= htmlspecialchars(subscriptions_sort_url('customer_name', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                        Customer
                        <?php if ($sortBy === 'customer_name'): ?>
                            <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a class="sort-link" href="<?= htmlspecialchars(subscriptions_sort_url('plan_name', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                        Plan
                        <?php if ($sortBy === 'plan_name'): ?>
                            <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a class="sort-link" href="<?= htmlspecialchars(subscriptions_sort_url('speed', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                        Speed
                        <?php if ($sortBy === 'speed'): ?>
                            <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a class="sort-link" href="<?= htmlspecialchars(subscriptions_sort_url('price', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                        Price
                        <?php if ($sortBy === 'price'): ?>
                            <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a class="sort-link" href="<?= htmlspecialchars(subscriptions_sort_url('start_date', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                        Start Date
                        <?php if ($sortBy === 'start_date'): ?>
                            <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>Billing</th>
                <th>
                    <a class="sort-link" href="<?= htmlspecialchars(subscriptions_sort_url('status', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                        Status
                        <?php if ($sortBy === 'status'): ?>
                            <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a class="sort-link" href="<?= htmlspecialchars(subscriptions_sort_url('created_at', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                        Created
                        <?php if ($sortBy === 'created_at'): ?>
                            <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th class="actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($subscriptions)): ?>
                <tr>
                    <td colspan="10" class="empty-state">No subscriptions found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($subscriptions as $sub): ?>
                    <?php
                        $status = strtoupper((string)($sub['status'] ?? ''));
                        $badgeClass = 'badge-info';
                        $billingType = strtoupper((string)($sub['billing_type'] ?? 'NEW_ACTIVATION'));
                        $billingLabel = $billingType === 'EXISTING_MIGRATE' ? 'Existing' : 'New';

                        if ($status === 'ACTIVE') {
                            $badgeClass = 'badge-success';
                        } elseif ($status === 'SUSPENDED') {
                            $badgeClass = 'badge-warning';
                        } elseif ($status === 'CANCELLED') {
                            $badgeClass = 'badge-danger';
                        }
                    ?>
                    <tr>
                        <td><?= (int)($sub['id'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($sub['customer_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($sub['plan_name'] ?? '') ?></td>
                        <td>
                            <span class="badge badge-info">
                                <?= htmlspecialchars($sub['speed'] ?? '') ?>
                            </span>
                        </td>
                        <td>₱<?= number_format((float)($sub['price'] ?? 0), 2) ?></td>
                        <td><?= htmlspecialchars($sub['start_date'] ?? '') ?></td>
                        <td>
                            <span class="badge <?= $billingType === 'EXISTING_MIGRATE' ? 'badge-warning' : 'badge-info' ?>">
                                <?= htmlspecialchars($billingLabel) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $badgeClass ?>">
                                <?= htmlspecialchars($status !== '' ? $status : 'N/A') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($sub['created_at'] ?? '') ?></td>
                        <td class="actions">
                            <a class="btn btn-small" href="<?= url('/subscriptions/edit') ?>?id=<?= (int)($sub['id'] ?? 0) ?>">Edit</a>
                            <a class="btn btn-small btn-danger" href="<?= url('/subscriptions/delete') ?>?id=<?= (int)($sub['id'] ?? 0) ?>" onclick="return confirm('Delete this subscription?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a class="btn btn-small btn-secondary" href="<?= htmlspecialchars(subscriptions_page_url($page - 1, $search, $statusFilter, $sortBy, $sortDir)) ?>">Prev</a>
        <?php endif; ?>

        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        for ($i = $startPage; $i <= $endPage; $i++):
        ?>
            <a class="btn btn-small <?= $i === (int)$page ? '' : 'btn-secondary' ?>" href="<?= htmlspecialchars(subscriptions_page_url($i, $search, $statusFilter, $sortBy, $sortDir)) ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a class="btn btn-small btn-secondary" href="<?= htmlspecialchars(subscriptions_page_url($page + 1, $search, $statusFilter, $sortBy, $sortDir)) ?>">Next</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
