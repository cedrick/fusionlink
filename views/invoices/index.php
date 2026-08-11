<?php
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
$totalRows = $totalRows ?? 0;
$search = $search ?? '';
$statusFilter = $statusFilter ?? '';
$sortBy = $sortBy ?? 'id';
$sortDir = $sortDir ?? 'DESC';

function invoices_sort_url(string $column, string $currentSortBy, string $currentSortDir, string $search, string $statusFilter): string
{
    $nextDir = 'ASC';
    if ($currentSortBy === $column && strtoupper($currentSortDir) === 'ASC') {
        $nextDir = 'DESC';
    }

    return url('/invoices') . '?search=' . urlencode($search)
        . '&status=' . urlencode($statusFilter)
        . '&sort_by=' . urlencode($column)
        . '&sort_dir=' . urlencode($nextDir);
}

function invoices_page_url(int $page, string $search, string $statusFilter, string $sortBy, string $sortDir): string
{
    return url('/invoices') . '?page=' . $page
        . '&search=' . urlencode($search)
        . '&status=' . urlencode($statusFilter)
        . '&sort_by=' . urlencode($sortBy)
        . '&sort_dir=' . urlencode($sortDir);
}
?>

<h1>Invoices</h1>
<p class="form-help">
    Billing coverage is the 1st through month-end. Due date is the configured day of the <strong>following</strong> month
    (default 8th). Still on time on the due date; overdue starts the next day. Mid-month activations are prorated.
</p>

<style>
.invoices-toolbar-form {
    grid-template-columns: 1.4fr .9fr .9fr .8fr auto;
    align-items: end;
}

.invoice-actions-bar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}

@media (max-width: 1100px) {
    .invoices-toolbar-form {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 700px) {
    .invoices-toolbar-form {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/dashboard') ?>">Back to Dashboard</a>
</div>

<div class="toolbar-card">
    <form method="GET" action="<?= url('/invoices') ?>" class="toolbar-form invoices-toolbar-form">
        <div class="toolbar-group">
            <label for="search">Search</label>
            <input
                type="text"
                id="search"
                name="search"
                class="toolbar-input"
                placeholder="Invoice #, customer, amount, due date, status"
                value="<?= htmlspecialchars($search) ?>"
            >
        </div>

        <div class="toolbar-group">
            <label for="status">Status Filter</label>
            <select id="status" name="status" class="toolbar-select">
                <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>All Status</option>
                <option value="DRAFT" <?= $statusFilter === 'DRAFT' ? 'selected' : '' ?>>DRAFT</option>
                <option value="ISSUED" <?= $statusFilter === 'ISSUED' ? 'selected' : '' ?>>ISSUED</option>
                <option value="PAID" <?= $statusFilter === 'PAID' ? 'selected' : '' ?>>PAID</option>
                <option value="OVERDUE" <?= $statusFilter === 'OVERDUE' ? 'selected' : '' ?>>OVERDUE</option>
            </select>
        </div>

        <div class="toolbar-group">
            <label for="sort_by">Sort By</label>
            <select id="sort_by" name="sort_by" class="toolbar-select">
                <option value="id" <?= $sortBy === 'id' ? 'selected' : '' ?>>ID</option>
                <option value="customer_name" <?= $sortBy === 'customer_name' ? 'selected' : '' ?>>Customer</option>
                <option value="amount" <?= $sortBy === 'amount' ? 'selected' : '' ?>>Amount</option>
                <option value="due_date" <?= $sortBy === 'due_date' ? 'selected' : '' ?>>Due Date</option>
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
            <button type="submit" class="btn">Apply Filters</button>
            <a class="btn btn-secondary" href="<?= url('/invoices') ?>">Reset</a>
        </div>
    </form>
</div>

<form method="POST" action="<?= url('/invoices/generate-send') ?>">
        <?= csrf_field() ?>
    <div class="page-actions">
        <button type="submit" class="btn">Generate Monthly Invoices</button>
    </div>

    <div class="table-top">
        <div class="table-meta">
            Showing <?= count($invoices) ?> of <?= (int)$totalRows ?> invoice(s)
        </div>
    </div>

    <div class="invoice-actions-bar">
        <button type="button" id="selectAllBtn" class="select-all-btn">Select All</button>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th class="checkbox-cell">
                        <input type="checkbox" id="masterCheckbox">
                    </th>
                    <th>
                        <a class="sort-link" href="<?= htmlspecialchars(invoices_sort_url('id', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                            ID
                            <?php if ($sortBy === 'id'): ?>
                                <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th>
                        <a class="sort-link" href="<?= htmlspecialchars(invoices_sort_url('customer_name', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                            Customer
                            <?php if ($sortBy === 'customer_name'): ?>
                                <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th>
                        <a class="sort-link" href="<?= htmlspecialchars(invoices_sort_url('amount', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                            Amount
                            <?php if ($sortBy === 'amount'): ?>
                                <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th>Billing Period</th>
                    <th>
                        <a class="sort-link" href="<?= htmlspecialchars(invoices_sort_url('due_date', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                            Due Date
                            <?php if ($sortBy === 'due_date'): ?>
                                <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th>
                        <a class="sort-link" href="<?= htmlspecialchars(invoices_sort_url('status', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                            Status
                            <?php if ($sortBy === 'status'): ?>
                                <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th>
                        <a class="sort-link" href="<?= htmlspecialchars(invoices_sort_url('created_at', $sortBy, $sortDir, $search, $statusFilter)) ?>">
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
            <?php if (empty($invoices)): ?>
                <tr>
                    <td colspan="9" class="empty-state">No invoices found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($invoices as $inv): ?>
                    <?php
                        $status = strtoupper((string)($inv['status'] ?? ''));
                        $badgeClass = 'badge-info';

                        if ($status === 'PAID') {
                            $badgeClass = 'badge-success';
                        } elseif ($status === 'ISSUED' || $status === 'DRAFT') {
                            $badgeClass = 'badge-warning';
                        } elseif ($status === 'OVERDUE') {
                            $badgeClass = 'badge-danger';
                        }

                        $periodStart = trim((string)($inv['billing_period_start'] ?? ''));
                        $periodEnd = trim((string)($inv['billing_period_end'] ?? ''));
                        $periodLabel = '—';
                        if ($periodStart !== '' && $periodEnd !== '') {
                            $periodLabel = date('M j', strtotime($periodStart)) . ' – ' . date('M j, Y', strtotime($periodEnd));
                            if (!empty($inv['is_prorated'])) {
                                $days = (int)($inv['coverage_days'] ?? 0);
                                $periodLabel .= ' (prorated' . ($days > 0 ? ', ' . $days . 'd' : '') . ')';
                            }
                        }
                    ?>
                    <tr>
                        <td class="checkbox-cell">
                            <input type="checkbox" class="invoice-checkbox" name="invoice_ids[]" value="<?= (int)($inv['id'] ?? 0) ?>">
                        </td>
                        <td><?= (int)($inv['id'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($inv['customer_name'] ?? '') ?></td>
                        <td>₱<?= number_format((float)($inv['amount'] ?? 0), 2) ?></td>
                        <td><?= htmlspecialchars($periodLabel) ?></td>
                        <td><?= htmlspecialchars($inv['due_date'] ?? '') ?></td>
                        <td>
                            <span class="badge <?= $badgeClass ?>">
                                <?= htmlspecialchars($status !== '' ? $status : 'N/A') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($inv['created_at'] ?? '') ?></td>
                        <td class="actions">
                            <a class="btn btn-small" href="<?= url('/payments/create') ?>?invoice_id=<?= (int)($inv['id'] ?? 0) ?>">View</a>
                            <a class="btn btn-small btn-secondary" href="<?= url('/invoices/pdf') ?>?id=<?= (int)($inv['id'] ?? 0) ?>">Download PDF</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</form>

<?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a class="btn btn-small btn-secondary" href="<?= htmlspecialchars(invoices_page_url($page - 1, $search, $statusFilter, $sortBy, $sortDir)) ?>">Prev</a>
        <?php endif; ?>

        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        for ($i = $startPage; $i <= $endPage; $i++):
        ?>
            <a class="btn btn-small <?= $i === (int)$page ? '' : 'btn-secondary' ?>" href="<?= htmlspecialchars(invoices_page_url($i, $search, $statusFilter, $sortBy, $sortDir)) ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a class="btn btn-small btn-secondary" href="<?= htmlspecialchars(invoices_page_url($page + 1, $search, $statusFilter, $sortBy, $sortDir)) ?>">Next</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
(function () {
    const masterCheckbox = document.getElementById('masterCheckbox');
    const selectAllBtn = document.getElementById('selectAllBtn');
    const rowCheckboxes = Array.from(document.querySelectorAll('.invoice-checkbox'));

    function setAll(checked) {
        rowCheckboxes.forEach(cb => {
            cb.checked = checked;
        });
        if (masterCheckbox) {
            masterCheckbox.checked = checked;
        }
        if (selectAllBtn) {
            selectAllBtn.textContent = checked ? 'Unselect All' : 'Select All';
        }
    }

    function syncMaster() {
        const allChecked = rowCheckboxes.length > 0 && rowCheckboxes.every(cb => cb.checked);
        const anyChecked = rowCheckboxes.some(cb => cb.checked);

        if (masterCheckbox) {
            masterCheckbox.checked = allChecked;
        }

        if (selectAllBtn) {
            selectAllBtn.textContent = (allChecked || anyChecked) ? 'Unselect All' : 'Select All';
        }
    }

    if (masterCheckbox) {
        masterCheckbox.addEventListener('change', function () {
            setAll(masterCheckbox.checked);
        });
    }

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function () {
            const shouldCheckAll = !rowCheckboxes.every(cb => cb.checked);
            setAll(shouldCheckAll);
        });
    }

    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', syncMaster);
    });
})();
</script>
