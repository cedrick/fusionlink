<?php
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
$totalRows = $totalRows ?? 0;
$search = $search ?? '';
$roleFilter = $roleFilter ?? '';
$sortBy = $sortBy ?? 'id';
$sortDir = $sortDir ?? 'DESC';

function users_sort_url(string $column, string $currentSortBy, string $currentSortDir, string $search, string $roleFilter): string
{
    $nextDir = 'ASC';
    if ($currentSortBy === $column && strtoupper($currentSortDir) === 'ASC') {
        $nextDir = 'DESC';
    }

    return url('/users') . '?search=' . urlencode($search)
        . '&role=' . urlencode($roleFilter)
        . '&sort_by=' . urlencode($column)
        . '&sort_dir=' . urlencode($nextDir);
}

function users_page_url(int $page, string $search, string $roleFilter, string $sortBy, string $sortDir): string
{
    return url('/users') . '?page=' . $page
        . '&search=' . urlencode($search)
        . '&role=' . urlencode($roleFilter)
        . '&sort_by=' . urlencode($sortBy)
        . '&sort_dir=' . urlencode($sortDir);
}
?>

<style>
.users-toolbar {
    display: grid;
    grid-template-columns: 1.3fr 1fr 1fr 1fr auto;
    gap: 12px;
    align-items: end;
}

.users-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.users-field label {
    font-size: 13px;
    font-weight: 800;
    color: #e5e7eb;
}

.users-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.users-meta {
    margin: 16px 0 14px;
    color: var(--muted);
    font-size: 14px;
    font-weight: 700;
}

.role-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 11px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    border: 1px solid rgba(255,255,255,.08);
}

.role-admin {
    background: rgba(255,255,255,.12);
    color: #ffffff;
}

.role-staff {
    background: rgba(59,130,246,.14);
    color: #bfdbfe;
}

.role-customer {
    background: rgba(34,197,94,.12);
    color: #86efac;
}

.sort-arrow {
    font-size: 11px;
    opacity: .8;
    margin-left: 6px;
}

.users-table-head-link {
    color: inherit;
    text-decoration: none;
}

@media (max-width: 1100px) {
    .users-toolbar {
        grid-template-columns: 1fr 1fr;
    }

    .users-actions {
        grid-column: 1 / -1;
    }
}

@media (max-width: 640px) {
    .users-toolbar {
        grid-template-columns: 1fr;
    }
}
</style>

<h1>Users</h1>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/dashboard') ?>">Back to Dashboard</a>
    <a class="btn" href="<?= url('/users/create') ?>">Add User</a>
</div>

<form method="GET" action="<?= url('/users') ?>" class="toolbar-card">
    <div class="users-toolbar">
        <div class="users-field">
            <label for="search">Global Search</label>
            <input
                id="search"
                type="text"
                name="search"
                placeholder="ID, email, role, created date"
                value="<?= htmlspecialchars($search) ?>"
            >
        </div>

        <div class="users-field">
            <label for="role">Role Filter</label>
            <select id="role" name="role">
                <option value="" <?= $roleFilter === '' ? 'selected' : '' ?>>All Roles</option>
                <option value="ROLE_ADMIN" <?= $roleFilter === 'ROLE_ADMIN' ? 'selected' : '' ?>>ROLE_ADMIN</option>
                <option value="ROLE_STAFF" <?= $roleFilter === 'ROLE_STAFF' ? 'selected' : '' ?>>ROLE_STAFF</option>
                <option value="ROLE_CUSTOMER" <?= $roleFilter === 'ROLE_CUSTOMER' ? 'selected' : '' ?>>ROLE_CUSTOMER</option>
            </select>
        </div>

        <div class="users-field">
            <label for="sort_by">Sort By</label>
            <select id="sort_by" name="sort_by">
                <option value="id" <?= $sortBy === 'id' ? 'selected' : '' ?>>ID</option>
                <option value="email" <?= $sortBy === 'email' ? 'selected' : '' ?>>Email</option>
                <option value="role" <?= $sortBy === 'role' ? 'selected' : '' ?>>Role</option>
                <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>Created</option>
            </select>
        </div>

        <div class="users-field">
            <label for="sort_dir">Direction</label>
            <select id="sort_dir" name="sort_dir">
                <option value="ASC" <?= strtoupper($sortDir) === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                <option value="DESC" <?= strtoupper($sortDir) === 'DESC' ? 'selected' : '' ?>>Descending</option>
            </select>
        </div>

        <div class="users-actions">
            <button type="submit" class="btn">Apply</button>
            <a href="<?= url('/users') ?>" class="btn btn-secondary">Reset</a>
        </div>
    </div>
</form>

<div class="users-meta">
    Showing <?= (int)$totalRows ?> user(s)
</div>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>
                    <a class="users-table-head-link" href="<?= htmlspecialchars(users_sort_url('id', $sortBy, $sortDir, $search, $roleFilter)) ?>">
                        ID<?= $sortBy === 'id' ? '<span class="sort-arrow">' . (strtoupper($sortDir) === 'ASC' ? '▲' : '▼') . '</span>' : '' ?>
                    </a>
                </th>
                <th>
                    <a class="users-table-head-link" href="<?= htmlspecialchars(users_sort_url('email', $sortBy, $sortDir, $search, $roleFilter)) ?>">
                        Email<?= $sortBy === 'email' ? '<span class="sort-arrow">' . (strtoupper($sortDir) === 'ASC' ? '▲' : '▼') . '</span>' : '' ?>
                    </a>
                </th>
                <th>
                    <a class="users-table-head-link" href="<?= htmlspecialchars(users_sort_url('role', $sortBy, $sortDir, $search, $roleFilter)) ?>">
                        Role<?= $sortBy === 'role' ? '<span class="sort-arrow">' . (strtoupper($sortDir) === 'ASC' ? '▲' : '▼') . '</span>' : '' ?>
                    </a>
                </th>
                <th>
                    <a class="users-table-head-link" href="<?= htmlspecialchars(users_sort_url('created_at', $sortBy, $sortDir, $search, $roleFilter)) ?>">
                        Created<?= $sortBy === 'created_at' ? '<span class="sort-arrow">' . (strtoupper($sortDir) === 'ASC' ? '▲' : '▼') . '</span>' : '' ?>
                    </a>
                </th>
                <th class="actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <?php
                    $role = strtoupper((string)($user['role'] ?? ''));
                    $roleClass = 'role-staff';

                    if ($role === 'ROLE_ADMIN') {
                        $roleClass = 'role-admin';
                    } elseif ($role === 'ROLE_CUSTOMER') {
                        $roleClass = 'role-customer';
                    }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($user['email'] ?? '-') ?></td>
                        <td>
                            <span class="role-pill <?= $roleClass ?>">
                                <?= htmlspecialchars($role ?: '-') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($user['created_at'] ?? '-') ?></td>
                        <td class="actions">
                            <a class="btn btn-small" href="<?= url('/users/edit') ?>?id=<?= urlencode($user['id'] ?? '') ?>">Edit</a>

                            <form method="POST" action="<?= url('/users/delete') ?>" class="inline-form" onsubmit="return confirm('Delete this user?');">
        <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= htmlspecialchars($user['id'] ?? '') ?>">
                                <button type="submit" class="btn btn-danger btn-small">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="empty-state">No users found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (($totalPages ?? 1) > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="<?= htmlspecialchars(users_page_url($page - 1, $search, $roleFilter, $sortBy, $sortDir)) ?>">Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= htmlspecialchars(users_page_url($i, $search, $roleFilter, $sortBy, $sortDir)) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="<?= htmlspecialchars(users_page_url($page + 1, $search, $roleFilter, $sortBy, $sortDir)) ?>">Next</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
