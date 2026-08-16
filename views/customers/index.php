<?php
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
$totalRows = $totalRows ?? 0;
$search = $search ?? '';
$statusFilter = $statusFilter ?? '';
$sortBy = $sortBy ?? 'id';
$sortDir = $sortDir ?? 'DESC';

function customers_sort_url(string $column, string $currentSortBy, string $currentSortDir, string $search, string $statusFilter): string
{
    $nextDir = 'ASC';
    if ($currentSortBy === $column && strtoupper($currentSortDir) === 'ASC') {
        $nextDir = 'DESC';
    }

    return url('/customers') . '?search=' . urlencode($search)
        . '&status=' . urlencode($statusFilter)
        . '&sort_by=' . urlencode($column)
        . '&sort_dir=' . urlencode($nextDir);
}

function customers_page_url(int $page, string $search, string $statusFilter, string $sortBy, string $sortDir): string
{
    return url('/customers') . '?page=' . $page
        . '&search=' . urlencode($search)
        . '&status=' . urlencode($statusFilter)
        . '&sort_by=' . urlencode($sortBy)
        . '&sort_dir=' . urlencode($sortDir);
}
?>

<h1>Customers</h1>
<p class="form-help">
    <strong>NEW</strong> means the FusionLink account is less than 30 days old. It is not the same as
    <strong>New activation</strong> billing (that is set on the subscription: existing = full month, never prorated).
    VAT inclusive, static IP, and installments are on Edit. Plan and open balance are shown here.
</p>

<?php if (!empty($error)): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!empty($existingCustomerLink)): ?>
    <div class="toolbar-card" style="margin-bottom:18px;">
        <div class="form-section-title" style="margin-bottom:8px;">Share with existing customers</div>
        <div class="form-help" style="margin-bottom:12px;">
            Send this short link so active subscribers can confirm their details and get billing portal access in about 1 minute.
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
            <input
                type="text"
                class="toolbar-input"
                id="existing-customer-link"
                readonly
                value="<?= htmlspecialchars($existingCustomerLink) ?>"
                style="flex:1;min-width:240px;"
            >
            <button type="button" class="btn btn-small" id="copy-existing-customer-link">Copy Link</button>
        </div>
        <div class="form-help" style="margin-top:12px;">
            SMS example: Hi [Name], set up your online billing here (1 min): <?= htmlspecialchars($existingCustomerLink) ?>
        </div>
    </div>
    <script>
    (function () {
        var button = document.getElementById('copy-existing-customer-link');
        var input = document.getElementById('existing-customer-link');
        if (!button || !input) {
            return;
        }

        button.addEventListener('click', function () {
            input.select();
            input.setSelectionRange(0, input.value.length);
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(input.value).then(function () {
                    button.textContent = 'Copied';
                    setTimeout(function () { button.textContent = 'Copy Link'; }, 2000);
                });
            }
        });
    })();
    </script>
<?php endif; ?>

<style>
.customers-toolbar-form {
    grid-template-columns: 1.4fr .9fr .9fr .9fr auto;
    align-items: end;
}

@media (max-width: 1100px) {
    .customers-toolbar-form {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 700px) {
    .customers-toolbar-form {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/dashboard') ?>">Back to Dashboard</a>
    <a class="btn btn-secondary" href="<?= url('/customers/import') ?>">Import CSV</a>
    <a class="btn" href="<?= url('/customers/create') ?>">Add Customer</a>
</div>

<div class="toolbar-card">
    <form method="GET" action="<?= url('/customers') ?>" class="toolbar-form customers-toolbar-form">
        <div class="toolbar-group">
            <label for="search">Search</label>
            <input
                type="text"
                id="search"
                name="search"
                class="toolbar-input"
                placeholder="Name, email, phone, plan, billing type"
                value="<?= htmlspecialchars($search) ?>"
            >
        </div>

        <div class="toolbar-group">
            <label for="status">Status Filter</label>
            <select id="status" name="status" class="toolbar-select">
                <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>All Status</option>
                <option value="NEW" <?= $statusFilter === 'NEW' ? 'selected' : '' ?>>NEW</option>
                <option value="ACTIVE" <?= $statusFilter === 'ACTIVE' ? 'selected' : '' ?>>ACTIVE</option>
                <option value="DISCONNECTED" <?= $statusFilter === 'DISCONNECTED' ? 'selected' : '' ?>>DISCONNECTED</option>
            </select>
        </div>

        <div class="toolbar-group">
            <label for="sort_by">Sort By</label>
            <select id="sort_by" name="sort_by" class="toolbar-select">
                <option value="id" <?= $sortBy === 'id' ? 'selected' : '' ?>>ID</option>
                <option value="full_name" <?= $sortBy === 'full_name' ? 'selected' : '' ?>>Full Name</option>
                <option value="email" <?= $sortBy === 'email' ? 'selected' : '' ?>>Email</option>
                <option value="phone" <?= $sortBy === 'phone' ? 'selected' : '' ?>>Phone</option>
                <option value="plan_name" <?= $sortBy === 'plan_name' ? 'selected' : '' ?>>Plan</option>
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
            <a class="btn btn-secondary" href="<?= url('/customers') ?>">Reset</a>
        </div>
    </form>
</div>

<div class="table-top">
    <div class="table-meta">
        Showing <?= count($customers) ?> of <?= (int)$totalRows ?> customer(s)
    </div>
</div>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>
                    <a class="sort-link" href="<?= htmlspecialchars(customers_sort_url('id', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                        ID
                        <?php if ($sortBy === 'id'): ?>
                            <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a class="sort-link" href="<?= htmlspecialchars(customers_sort_url('full_name', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                        Full Name
                        <?php if ($sortBy === 'full_name'): ?>
                            <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a class="sort-link" href="<?= htmlspecialchars(customers_sort_url('email', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                        Email
                        <?php if ($sortBy === 'email'): ?>
                            <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a class="sort-link" href="<?= htmlspecialchars(customers_sort_url('phone', $sortBy, $sortDir, $search, $statusFilter)) ?>">
                        Phone
                        <?php if ($sortBy === 'phone'): ?>
                            <span class="sort-arrow"><?= strtoupper($sortDir) === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>Plan</th>
                <th>Billing</th>
                <th>VAT</th>
                <th>Open Balance</th>
                <th>Status</th>
                <th>Portal</th>
                <th>
                    <a class="sort-link" href="<?= htmlspecialchars(customers_sort_url('created_at', $sortBy, $sortDir, $search, $statusFilter)) ?>">
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
            <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="12" class="empty-state">No customers found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($customers as $c): ?>
                    <?php
                        $status = strtoupper((string)($c['display_status'] ?? $c['status'] ?? ''));
                        $badgeClass = 'badge-info';
                        $customerId = (int)($c['id'] ?? 0);
                        $portal = $portalStatuses[$customerId] ?? ['has_portal' => false, 'email' => ''];
                        $customerEmail = trim((string)($c['email'] ?? ''));
                        $canActivatePortal = !$portal['has_portal']
                            && $customerEmail !== ''
                            && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)
                            && $status !== 'DISCONNECTED';

                        if ($status === 'ACTIVE') {
                            $badgeClass = 'badge-success';
                        } elseif ($status === 'NEW') {
                            $badgeClass = 'badge-warning';
                        } elseif ($status === 'DISCONNECTED') {
                            $badgeClass = 'badge-danger';
                        }
                    ?>
                    <tr>
                        <td><?= (int)($c['id'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($c['full_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($c['email'] ?? '') ?></td>
                        <td><?= htmlspecialchars($c['phone'] ?? '') ?></td>
                        <td>
                            <?= htmlspecialchars((string)($c['plan_name'] ?? '') !== '' ? $c['plan_name'] : '—') ?>
                            <?php if (!empty($c['plan_speed'])): ?>
                                <div class="helper-text" style="margin:2px 0 0;"><?= htmlspecialchars((string)$c['plan_speed']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                                $billingType = strtoupper((string)($c['billing_type'] ?? ''));
                                if ($billingType === 'EXISTING_MIGRATE') {
                                    echo 'Existing';
                                } elseif ($billingType === 'NEW_ACTIVATION') {
                                    echo 'New activation';
                                } else {
                                    echo '—';
                                }
                            ?>
                        </td>
                        <td>
                            <?php if (!empty($c['vat_inclusive'])): ?>
                                <span class="badge badge-info">Inclusive</span>
                            <?php else: ?>
                                <span class="helper-text">Excluded</span>
                            <?php endif; ?>
                        </td>
                        <td>₱<?= number_format((float)($c['balance_due'] ?? 0), 2) ?></td>
                        <td>
                            <span class="badge <?= $badgeClass ?>">
                                <?= htmlspecialchars($status !== '' ? $status : 'N/A') ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($portal['has_portal']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php elseif ($customerEmail === ''): ?>
                                <span class="badge badge-warning">No email</span>
                            <?php else: ?>
                                <span class="badge badge-info">None</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($c['created_at'] ?? '') ?></td>
                        <td class="actions">
                            <a class="btn btn-small" href="<?= url('/customers/edit') ?>?id=<?= $customerId ?>">Edit</a>

                            <?php if ($canActivatePortal): ?>
                                <form method="POST" action="<?= url('/customers/activate-portal') ?>" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $customerId ?>">
                                    <input type="hidden" name="return_to" value="/customers">
                                    <button
                                        class="btn btn-small"
                                        type="submit"
                                        onclick="return confirm('Create portal login and email credentials to <?= htmlspecialchars($customerEmail, ENT_QUOTES, 'UTF-8') ?>?')"
                                    >
                                        Portal Login
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if (!empty($portal['has_portal'])): ?>
                                <form method="POST" action="<?= url('/customers/reset-portal-password') ?>" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $customerId ?>">
                                    <input type="hidden" name="return_to" value="/customers">
                                    <button
                                        class="btn btn-small btn-secondary"
                                        type="submit"
                                        onclick="return confirm('Reset the portal password and email a temporary password to <?= htmlspecialchars((string)($portal['email'] ?? $customerEmail), ENT_QUOTES, 'UTF-8') ?>?')"
                                    >
                                        Reset Password
                                    </button>
                                </form>
                            <?php endif; ?>

                            <form method="POST" action="<?= url('/customers/delete') ?>" class="inline-form">
        <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $customerId ?>">
                                <button class="btn btn-small btn-danger" type="submit" onclick="return confirm('Delete this customer and all related subscriptions, invoices, and payments? This cannot be undone.')">Delete</button>
                            </form>
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
            <a class="btn btn-small btn-secondary" href="<?= htmlspecialchars(customers_page_url($page - 1, $search, $statusFilter, $sortBy, $sortDir)) ?>">Prev</a>
        <?php endif; ?>

        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        for ($i = $startPage; $i <= $endPage; $i++):
        ?>
            <a class="btn btn-small <?= $i === (int)$page ? '' : 'btn-secondary' ?>" href="<?= htmlspecialchars(customers_page_url($i, $search, $statusFilter, $sortBy, $sortDir)) ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a class="btn btn-small btn-secondary" href="<?= htmlspecialchars(customers_page_url($page + 1, $search, $statusFilter, $sortBy, $sortDir)) ?>">Next</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
