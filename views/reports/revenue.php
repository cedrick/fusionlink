<?php
$kpi = $kpi ?? [
    'overall_payment' => 0,
    'paid_customers_total_payment' => 0,
    'unpaid_customers_total_payment' => 0,
];
$rows = $rows ?? [];
$startDate = $startDate ?? date('Y-m-01');
$endDate = $endDate ?? date('Y-m-d');
$sortStatus = $sortStatus ?? '';
$search = $search ?? '';
$page = $page ?? 1;
$totalRows = $totalRows ?? 0;
$totalPages = $totalPages ?? 1;

$queryBase = '/reports/revenue?start_date=' . urlencode($startDate)
    . '&end_date=' . urlencode($endDate)
    . '&sort_status=' . urlencode($sortStatus)
    . '&search=' . urlencode($search);
?>

<style>
.report-shell {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.report-hero {
    background:
        radial-gradient(circle at top right, rgba(59,130,246,.18), transparent 24%),
        linear-gradient(135deg, #050505 0%, #090909 48%, #111113 100%);
    color: #fff;
    border-radius: 6px;
    padding: 28px;
    border: 1px solid rgba(255,255,255,.08);
    box-shadow: none;
}

.report-hero h1 {
    margin: 0 0 10px;
    font-size: 38px;
    line-height: 1.1;
    color: #fff;
}

.report-hero p {
    margin: 0;
    color: rgba(255,255,255,.78);
    line-height: 1.7;
    max-width: 760px;
}

.report-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 18px;
}

.report-kpis {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
}

.report-card {
    padding: 22px;
    border-radius: 6px;
}

.report-kpi-label {
    font-size: 12px;
    color: #94a3b8;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 10px;
}

.report-kpi-value {
    font-size: 40px;
    font-weight: 650;
    color: #fff;
    line-height: 1;
}

.report-kpi-meta {
    margin-top: 12px;
    font-size: 13px;
    color: #60a5fa;
    font-weight: 700;
}

.filter-card {
    padding: 22px;
    border-radius: 6px;
}

.filter-title {
    margin: 0 0 16px;
    font-size: 20px;
    font-weight: 800;
    color: #fff;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.filter-group label {
    font-size: 14px;
    font-weight: 800;
    color: #f3f4f6;
}

.filter-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.table-card {
    padding: 22px;
    border-radius: 6px;
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.table-title {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    color: #fff;
}

.table-subtitle {
    margin-top: 4px;
    color: var(--muted);
    font-size: 14px;
}

.table-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 38px;
    padding: 8px 14px;
    border-radius: 999px;
    background: rgba(255,255,255,.05);
    color: #e5e7eb;
    border: 1px solid rgba(255,255,255,.08);
    font-size: 13px;
    font-weight: 800;
}

.report-table-wrap {
    width: 100%;
    overflow-x: auto;
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 6px;
    background: rgba(255,255,255,.02);
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}

.report-table th,
.report-table td {
    padding: 12px 14px;
    border-bottom: 1px solid rgba(255,255,255,.08);
    text-align: left;
    vertical-align: middle;
    color: #f5f5f5;
}

.report-table th {
    background: rgba(255,255,255,.03);
    font-size: 14px;
}

.report-table tr:last-child td {
    border-bottom: 0;
}

.report-badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    border: 1px solid rgba(255,255,255,.08);
}

.report-badge-success {
    background: rgba(34,197,94,.12);
    color: #86efac;
}

.report-badge-warning {
    background: rgba(245,158,11,.12);
    color: #fcd34d;
}

.report-badge-danger {
    background: rgba(239,68,68,.12);
    color: #fca5a5;
}

.report-empty {
    padding: 18px;
    color: var(--muted);
}

.pagination {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    justify-content: space-between;
    margin-top: 16px;
}

.pagination-info {
    color: var(--muted);
    font-size: 14px;
    font-weight: 700;
}

.pagination-links {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.pagination-links a,
.pagination-links span {
    min-width: 42px;
    min-height: 42px;
    padding: 10px 14px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,.10);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #e5e7eb;
    font-weight: 700;
    background: rgba(255,255,255,.03);
}

.pagination-links .active {
    background: linear-gradient(180deg, #ffffff 0%, #ececec 100%);
    color: #000;
    border-color: rgba(255,255,255,.20);
}

@media (max-width: 1200px) {
    .filter-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 980px) {
    .report-kpis {
        grid-template-columns: 1fr;
    }

    .filter-grid {
        grid-template-columns: 1fr;
    }

    .report-hero h1 {
        font-size: 30px;
    }
}
</style>

<div class="report-shell">
    <div class="report-hero">
        <h1>Revenue Summary Report</h1>
        <p>
            Track total collections, paid customer totals, unpaid customer totals, and invoice records in one report page.
            The filter below affects the cards and the invoice report table.
        </p>

        <div class="report-actions">
            <a class="btn btn-secondary" href="<?= url('/dashboard') ?>">Back to Dashboard</a>
            <a class="btn" href="<?= $queryBase ?>&export=csv">Export CSV</a>
        </div>
    </div>

    <div class="report-kpis">
        <div class="report-card">
            <div class="report-kpi-label">Overall Payment</div>
            <div class="report-kpi-value">₱<?= number_format((float)($kpi['overall_payment'] ?? 0), 2) ?></div>
            <div class="report-kpi-meta">Verified payments within selected dates</div>
        </div>

        <div class="report-card">
            <div class="report-kpi-label">Total Payment of Paid Customer</div>
            <div class="report-kpi-value">₱<?= number_format((float)($kpi['paid_customers_total_payment'] ?? 0), 2) ?></div>
            <div class="report-kpi-meta">Total invoice amount of paid customers</div>
        </div>

        <div class="report-card">
            <div class="report-kpi-label">Total Payment of Unpaid Customer</div>
            <div class="report-kpi-value">₱<?= number_format((float)($kpi['unpaid_customers_total_payment'] ?? 0), 2) ?></div>
            <div class="report-kpi-meta">Total invoice amount still unpaid or overdue</div>
        </div>
    </div>

    <div class="filter-card">
        <h2 class="filter-title">Filter Report</h2>

        <form method="GET" action="<?= url('/reports/revenue') ?>">
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="start_date">Start Date</label>
                    <input id="start_date" type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
                </div>

                <div class="filter-group">
                    <label for="end_date">End Date</label>
                    <input id="end_date" type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
                </div>

                <div class="filter-group">
                    <label for="sort_status">Sort Status</label>
                    <select id="sort_status" name="sort_status">
                        <option value="" <?= $sortStatus === '' ? 'selected' : '' ?>>All</option>
                        <option value="PAID" <?= $sortStatus === 'PAID' ? 'selected' : '' ?>>Paid</option>
                        <option value="UNPAID" <?= $sortStatus === 'UNPAID' ? 'selected' : '' ?>>Unpaid</option>
                        <option value="OVERDUE" <?= $sortStatus === 'OVERDUE' ? 'selected' : '' ?>>Overdue</option>
                        <option value="ISSUED" <?= $sortStatus === 'ISSUED' ? 'selected' : '' ?>>Issued</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="search">Global Search</label>
                    <input
                        id="search"
                        type="text"
                        name="search"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Invoice #, customer, amount, status"
                    >
                </div>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn">Apply Filter</button>
                <a class="btn btn-secondary" href="<?= url('/reports/revenue') ?>">Reset</a>
            </div>
        </form>
    </div>

    <div class="table-card">
        <div class="table-header">
            <div>
                <h2 class="table-title">Invoice Report</h2>
                <div class="table-subtitle">Showing invoice records based on selected date, sort status, and global search.</div>
            </div>

            <div class="table-pill"><?= (int)$totalRows ?> total record(s)</div>
        </div>

        <div class="report-table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="6" class="report-empty">No invoice records found for the selected filters.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                                $status = strtoupper((string)($row['status'] ?? ''));
                                $badgeClass = 'report-badge-warning';

                                if ($status === 'PAID') {
                                    $badgeClass = 'report-badge-success';
                                } elseif ($status === 'OVERDUE') {
                                    $badgeClass = 'report-badge-danger';
                                }
                            ?>
                            <tr>
                                <td><?= (int)($row['id'] ?? 0) ?></td>
                                <td><?= htmlspecialchars($row['customer_name'] ?? '-') ?></td>
                                <td>₱<?= number_format((float)($row['amount'] ?? 0), 2) ?></td>
                                <td><?= htmlspecialchars($row['due_date'] ?? '-') ?></td>
                                <td>
                                    <span class="report-badge <?= $badgeClass ?>">
                                        <?= htmlspecialchars($status !== '' ? $status : 'DRAFT') ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['created_at'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <div class="pagination-info">
                Page <?= (int)$page ?> of <?= (int)$totalPages ?>
            </div>

            <div class="pagination-links">
                <?php if ($page > 1): ?>
                    <a href="<?= $queryBase ?>&page=<?= $page - 1 ?>">Prev</a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <?php if ($i === (int)$page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= $queryBase ?>&page=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= $queryBase ?>&page=<?= $page + 1 ?>">Next</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
