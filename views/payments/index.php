<?php
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
$totalRows = $totalRows ?? 0;
$search = $search ?? '';
$statusFilter = $statusFilter ?? '';
$sortBy = $sortBy ?? 'id';
$sortDir = $sortDir ?? 'DESC';

function payments_sort_url(string $column, string $currentSortBy, string $currentSortDir, string $search, string $statusFilter): string
{
    $nextDir = 'ASC';
    if ($currentSortBy === $column && strtoupper($currentSortDir) === 'ASC') {
        $nextDir = 'DESC';
    }

    return url('/payments') . '?search=' . urlencode($search)
        . '&status=' . urlencode($statusFilter)
        . '&sort_by=' . urlencode($column)
        . '&sort_dir=' . urlencode($nextDir);
}

function payments_page_url(int $page, string $search, string $statusFilter, string $sortBy, string $sortDir): string
{
    return url('/payments') . '?page=' . $page
        . '&search=' . urlencode($search)
        . '&status=' . urlencode($statusFilter)
        . '&sort_by=' . urlencode($sortBy)
        . '&sort_dir=' . urlencode($sortDir);
}
?>

<h1>Payments</h1>

<style>
.payments-toolbar-form {
    grid-template-columns: 1.4fr .9fr .9fr .8fr auto;
    align-items: end;
}

@media (max-width: 1100px) {
    .payments-toolbar-form {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 700px) {
    .payments-toolbar-form {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/dashboard') ?>">Back to Dashboard</a>
    <a class="btn" href="<?= url('/payments/create') ?>">Record Payment</a>
</div>

<div class="toolbar-card">
    <form method="GET" action="<?= url('/payments') ?>" class="toolbar-form payments-toolbar-form">
        <div class="toolbar-group">
            <label for="search">Search</label>
            <input
                type="text"
                id="search"
                name="search"
                class="toolbar-input"
                placeholder="Payment ID, invoice #, customer, amount, method, status"
                value="<?= htmlspecialchars($search) ?>"
            >
        </div>

        <div class="toolbar-group">
            <label for="status">Status Filter</label>
            <select id="status" name="status" class="toolbar-select">
                <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>All Status</option>
                <option value="PENDING" <?= $statusFilter === 'PENDING' ? 'selected' : '' ?>>PENDING</option>
                <option value="VERIFIED" <?= $statusFilter === 'VERIFIED' ? 'selected' : '' ?>>VERIFIED</option>
                <option value="REJECTED" <?= $statusFilter === 'REJECTED' ? 'selected' : '' ?>>REJECTED</option>
            </select>
        </div>

        <div class="toolbar-group">
            <label for="sort_by">Sort By</label>
            <select id="sort_by" name="sort_by" class="toolbar-select">
                <option value="id" <?= $sortBy === 'id' ? 'selected' : '' ?>>ID</option>
                <option value="invoice_number" <?= $sortBy === 'invoice_number' ? 'selected' : '' ?>>Invoice #</option>
                <option value="customer_name" <?= $sortBy === 'customer_name' ? 'selected' : '' ?>>Customer</option>
                <option value="amount" <?= $sortBy === 'amount' ? 'selected' : '' ?>>Amount</option>
                <option value="payment_date" <?= $sortBy === 'payment_date' ? 'selected' : '' ?>>Payment Date</option>
                <option value="method" <?= $sortBy === 'method' ? 'selected' : '' ?>>Method</option>
                <option value="status" <?= $sortBy === 'status' ? 'selected' : '' ?>>Status</option>
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
            <button type="submit" class="btn">Apply Filters</button>
            <a class="btn btn-secondary" href="<?= url('/payments') ?>">Reset</a>
        </div>
    </form>
</div>

<div class="table-top">
    <div class="table-meta">
        Showing <?= count($payments) ?> of <?= (int)$totalRows ?> payment(s)
    </div>
</div>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>
                    <a class="sort-link" href="<?= htmlspecialchars(payments_sort_url('id', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                        ID
                        <?php if ($sortBy === 'id'): ?>
                            <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a class="sort-link" href="<?= htmlspecialchars(payments_sort_url('invoice_number', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                        Invoice #
                        <?php if ($sortBy === 'invoice_number'): ?>
                            <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a class="sort-link" href="<?= htmlspecialchars(payments_sort_url('customer_name', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                        Customer
                        <?php if ($sortBy === 'customer_name'): ?>
                            <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a class="sort-link" href="<?= htmlspecialchars(payments_sort_url('amount', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                        Amount
                        <?php if ($sortBy === 'amount'): ?>
                            <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a class="sort-link" href="<?= htmlspecialchars(payments_sort_url('payment_date', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                        Payment Date
                        <?php if ($sortBy === 'payment_date'): ?>
                            <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a class="sort-link" href="<?= htmlspecialchars(payments_sort_url('method', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                        Method
                        <?php if ($sortBy === 'method'): ?>
                            <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>Receipt</th>
                <th>
                    <a class="sort-link" href="<?= htmlspecialchars(payments_sort_url('status', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                        Status
                        <?php if ($sortBy === 'status'): ?>
                            <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th class="actions">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($payments)): ?>
            <?php foreach ($payments as $payment): ?>
                <?php
                    $status = strtoupper((string)($payment['status'] ?? ''));
                    $badgeClass = 'badge-warning';

                    if ($status === 'VERIFIED') {
                        $badgeClass = 'badge-success';
                    } elseif ($status === 'REJECTED') {
                        $badgeClass = 'badge-danger';
                    }
                ?>
                <tr>
                    <td><?= htmlspecialchars($payment['id'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($payment['invoice_number'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($payment['customer_name'] ?? '-') ?></td>
                    <td>₱<?= number_format((float)($payment['amount'] ?? 0), 2) ?></td>
                    <td><?= htmlspecialchars($payment['payment_date'] ?? '-') ?></td>
                    <td>
                        <span class="badge badge-info">
                            <?= htmlspecialchars($payment['method'] ?? '-') ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($payment['receipt_path'])): ?>
                            <a class="btn btn-small" href="<?= htmlspecialchars(url($payment['receipt_path'])) ?>" target="_blank">View Receipt</a>
                        <?php else: ?>
                            <span class="empty-state" style="padding:0;">No receipt</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $badgeClass ?>">
                            <?= htmlspecialchars($status !== '' ? $status : 'N/A') ?>
                        </span>
                    </td>
                    <td class="actions">
                        <?php if ($status === 'PENDING'): ?>
                            <form method="POST" action="<?= url('/payments/verify') ?>" class="inline-form">
        <?= csrf_field() ?>
                                <input type="hidden" name="payment_id" value="<?= htmlspecialchars($payment['id']) ?>">
                                <button type="submit" class="btn btn-small btn-success" onclick="return confirm('Verify this payment?')">Verify</button>
                            </form>

                            <form method="POST" action="<?= url('/payments/reject') ?>" class="inline-form">
        <?= csrf_field() ?>
                                <input type="hidden" name="payment_id" value="<?= htmlspecialchars($payment['id']) ?>">
                                <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Reject this payment?')">Reject</button>
                            </form>
                        <?php else: ?>
                            <span class="empty-state" style="padding:0;">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="9" class="empty-state">No payments found.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a class="btn btn-small btn-secondary" href="<?= htmlspecialchars(payments_page_url($page - 1, $search, $statusFilter, $sortBy, $sortDir)) ?>">Prev</a>
        <?php endif; ?>

        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        for ($i = $startPage; $i <= $endPage; $i++):
        ?>
            <a class="btn btn-small <?= $i === (int)$page ? '' : 'btn-secondary' ?>" href="<?= htmlspecialchars(payments_page_url($i, $search, $statusFilter, $sortBy, $sortDir)) ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a class="btn btn-small btn-secondary" href="<?= htmlspecialchars(payments_page_url($page + 1, $search, $statusFilter, $sortBy, $sortDir)) ?>">Next</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
