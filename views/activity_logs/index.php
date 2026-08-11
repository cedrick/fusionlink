<?php
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
$totalRows = $totalRows ?? 0;
$search = $search ?? '';
$moduleFilter = $moduleFilter ?? '';
$actionFilter = $actionFilter ?? '';
$sortBy = $sortBy ?? 'created_at';
$sortDir = $sortDir ?? 'DESC';
$modules = $modules ?? [];
$actions = $actions ?? [];

function activity_logs_sort_url(string $column, string $currentSortBy, string $currentSortDir, string $search, string $moduleFilter, string $actionFilter): string
{
    $nextDir = 'ASC';
    if ($currentSortBy === $column && strtoupper($currentSortDir) === 'ASC') {
        $nextDir = 'DESC';
    }

    return url('/activity-logs') . '?search=' . urlencode($search)
        . '&module=' . urlencode($moduleFilter)
        . '&action=' . urlencode($actionFilter)
        . '&sort_by=' . urlencode($column)
        . '&sort_dir=' . urlencode($nextDir);
}

function activity_logs_page_url(int $page, string $search, string $moduleFilter, string $actionFilter, string $sortBy, string $sortDir): string
{
    return url('/activity-logs') . '?page=' . $page
        . '&search=' . urlencode($search)
        . '&module=' . urlencode($moduleFilter)
        . '&action=' . urlencode($actionFilter)
        . '&sort_by=' . urlencode($sortBy)
        . '&sort_dir=' . urlencode($sortDir);
}
?>

<style>
.logs-toolbar {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1fr auto;
    gap: 12px;
    align-items: end;
}

.logs-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.logs-field label {
    font-size: 13px;
    font-weight: 800;
    color: #e5e7eb;
}

.logs-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.logs-meta {
    margin: 16px 0 14px;
    color: var(--muted);
    font-size: 14px;
    font-weight: 700;
}

.logs-table-head-link {
    color: inherit;
    text-decoration: none;
}

.sort-arrow {
    font-size: 11px;
    opacity: .8;
    margin-left: 6px;
}

.desc-cell {
    max-width: 420px;
    white-space: normal;
    line-height: 1.6;
}

@media (max-width: 1100px) {
    .logs-toolbar {
        grid-template-columns: 1fr 1fr;
    }

    .logs-actions {
        grid-column: 1 / -1;
    }
}

@media (max-width: 640px) {
    .logs-toolbar {
        grid-template-columns: 1fr;
    }
}
</style>

<h1>Activity Logs</h1>
<p class="form-help">
    Tracks module visits (<strong>VIEW</strong>) and system actions (create, update, delete, login, and more).
    Repeat visits to the same page within 45 seconds are collapsed to reduce noise.
</p>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/dashboard') ?>">Back to Dashboard</a>
    <a class="btn btn-secondary" href="<?= url('/activity-logs') ?>?action=VIEW">Page Visits Only</a>
    <a class="btn btn-secondary" href="<?= url('/activity-logs') ?>">All Activity</a>
</div>

<form method="GET" action="<?= url('/activity-logs') ?>" class="toolbar-card">
    <div class="logs-toolbar">
        <div class="logs-field">
            <label for="search">Global Search</label>
            <input
                id="search"
                type="text"
                name="search"
                placeholder="user, role, module, action, description, IP"
                value="<?= htmlspecialchars($search) ?>"
            >
        </div>

        <div class="logs-field">
            <label for="module">Module Filter</label>
            <select id="module" name="module">
                <option value="">All Modules</option>
                <?php foreach ($modules as $module): ?>
                    <option value="<?= htmlspecialchars((string)$module) ?>" <?= $moduleFilter === (string)$module ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$module) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="logs-field">
            <label for="action">Action Filter</label>
            <select id="action" name="action">
                <option value="">All Actions</option>
                <?php foreach ($actions as $action): ?>
                    <option value="<?= htmlspecialchars((string)$action) ?>" <?= $actionFilter === (string)$action ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$action) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="logs-field">
            <label for="sort_by">Sort By</label>
            <select id="sort_by" name="sort_by">
                <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>Created</option>
                <option value="id" <?= $sortBy === 'id' ? 'selected' : '' ?>>ID</option>
                <option value="user_email" <?= $sortBy === 'user_email' ? 'selected' : '' ?>>User Email</option>
                <option value="user_role" <?= $sortBy === 'user_role' ? 'selected' : '' ?>>Role</option>
                <option value="module" <?= $sortBy === 'module' ? 'selected' : '' ?>>Module</option>
                <option value="action" <?= $sortBy === 'action' ? 'selected' : '' ?>>Action</option>
            </select>
        </div>

        <div class="logs-actions">
            <button type="submit" class="btn">Apply</button>
            <a href="<?= url('/activity-logs') ?>" class="btn btn-secondary">Reset</a>
        </div>
    </div>
</form>

<div class="logs-meta">
    Showing <?= (int)$totalRows ?> log(s)
</div>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>
                    <a class="logs-table-head-link" href="<?= htmlspecialchars(activity_logs_sort_url('id', $sortBy, $sortDir, $search, $moduleFilter, $actionFilter)) ?>">
                        ID<?= $sortBy === 'id' ? '<span class="sort-arrow">' . (strtoupper($sortDir) === 'ASC' ? '▲' : '▼') . '</span>' : '' ?>
                    </a>
                </th>
                <th>
                    <a class="logs-table-head-link" href="<?= htmlspecialchars(activity_logs_sort_url('user_email', $sortBy, $sortDir, $search, $moduleFilter, $actionFilter)) ?>">
                        User<?= $sortBy === 'user_email' ? '<span class="sort-arrow">' . (strtoupper($sortDir) === 'ASC' ? '▲' : '▼') . '</span>' : '' ?>
                    </a>
                </th>
                <th>
                    <a class="logs-table-head-link" href="<?= htmlspecialchars(activity_logs_sort_url('user_role', $sortBy, $sortDir, $search, $moduleFilter, $actionFilter)) ?>">
                        Role<?= $sortBy === 'user_role' ? '<span class="sort-arrow">' . (strtoupper($sortDir) === 'ASC' ? '▲' : '▼') . '</span>' : '' ?>
                    </a>
                </th>
                <th>
                    <a class="logs-table-head-link" href="<?= htmlspecialchars(activity_logs_sort_url('module', $sortBy, $sortDir, $search, $moduleFilter, $actionFilter)) ?>">
                        Module<?= $sortBy === 'module' ? '<span class="sort-arrow">' . (strtoupper($sortDir) === 'ASC' ? '▲' : '▼') . '</span>' : '' ?>
                    </a>
                </th>
                <th>
                    <a class="logs-table-head-link" href="<?= htmlspecialchars(activity_logs_sort_url('action', $sortBy, $sortDir, $search, $moduleFilter, $actionFilter)) ?>">
                        Action<?= $sortBy === 'action' ? '<span class="sort-arrow">' . (strtoupper($sortDir) === 'ASC' ? '▲' : '▼') . '</span>' : '' ?>
                    </a>
                </th>
                <th>Description</th>
                <th>IP</th>
                <th>
                    <a class="logs-table-head-link" href="<?= htmlspecialchars(activity_logs_sort_url('created_at', $sortBy, $sortDir, $search, $moduleFilter, $actionFilter)) ?>">
                        Created<?= $sortBy === 'created_at' ? '<span class="sort-arrow">' . (strtoupper($sortDir) === 'ASC' ? '▲' : '▼') . '</span>' : '' ?>
                    </a>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($logs)): ?>
                <?php foreach ($logs as $log): ?>
                    <?php
                        $actionName = strtoupper((string)($log['action'] ?? ''));
                        $badgeClass = match ($actionName) {
                            'VIEW' => 'badge-info',
                            'CREATE', 'STORE', 'COMPLETE', 'LOGIN' => 'badge-success',
                            'UPDATE', 'EDIT', 'VERIFY' => 'badge-warning',
                            'DELETE', 'CANCEL', 'LOGOUT', 'REJECT' => 'badge-danger',
                            default => 'badge-info',
                        };
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($log['id'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($log['user_email'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($log['user_role'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($log['module'] ?? '-')) ?></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars((string)($log['action'] ?? '-')) ?></span></td>
                        <td class="desc-cell"><?= htmlspecialchars((string)($log['description'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($log['ip_address'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($log['created_at'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="empty-state">No activity logs found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (($totalPages ?? 1) > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="<?= htmlspecialchars(activity_logs_page_url($page - 1, $search, $moduleFilter, $actionFilter, $sortBy, $sortDir)) ?>">Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= htmlspecialchars(activity_logs_page_url($i, $search, $moduleFilter, $actionFilter, $sortBy, $sortDir)) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="<?= htmlspecialchars(activity_logs_page_url($page + 1, $search, $moduleFilter, $actionFilter, $sortBy, $sortDir)) ?>">Next</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
