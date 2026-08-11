<?php
$search = $search ?? '';
$statusFilter = $statusFilter ?? '';
$sortBy = $sortBy ?? 'created_at';
$sortDir = $sortDir ?? 'DESC';
$page = (int)($page ?? 1);
$totalPages = (int)($totalPages ?? 1);
$totalRows = (int)($totalRows ?? 0);
$rows = $rows ?? [];
$workflowStates = $workflowStates ?? [];
$flash = $flash ?? null;
?>

<style>
.inquiry-toolbar {
    display: grid;
    grid-template-columns: 1.6fr 1fr auto;
    gap: 12px;
    align-items: end;
}

.inquiry-actions-wrap {
    display: flex;
    flex-direction: column;
    gap: 6px;
    align-items: stretch;
    min-width: 180px;
}

.inquiry-actions-wrap .btn,
.inquiry-actions-wrap .btn-small {
    width: 100%;
    justify-content: center;
}

.inquiry-visit-status {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.inquiry-visit-hint,
.inquiry-note,
.inquiry-sub {
    font-size: 12px;
    color: var(--muted);
    line-height: 1.45;
}

.inquiry-name {
    font-weight: 650;
    color: #fff;
    margin-bottom: 2px;
}

.inquiry-stack div + div {
    margin-top: 2px;
}

.btn.is-disabled,
.btn-small.is-disabled {
    opacity: 0.45;
    pointer-events: none;
    cursor: not-allowed;
}

@media (max-width: 960px) {
    .inquiry-toolbar {
        grid-template-columns: 1fr;
    }

    .inquiry-table-desktop {
        display: none;
    }

    .inquiry-cards {
        display: grid;
        gap: 10px;
    }

    .inquiry-card {
        border: 1px solid rgba(255,255,255,.10);
        background: #0c0c0d;
        border-radius: 6px;
        padding: 14px;
    }

    .inquiry-card-row {
        display: grid;
        gap: 4px;
        margin-bottom: 10px;
    }

    .inquiry-card-row .label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #737373;
    }
}

@media (min-width: 961px) {
    .inquiry-cards {
        display: none;
    }
}
</style>

<h1>Inquiries</h1>
<p class="form-help">
    Plan applications: schedule <strong>Ocular</strong> or <strong>Installation</strong>, then pick date, time, and technician.
    <strong>Sunday is closed</strong> (Monday–Saturday only). After installation is marked done, the applicant converts to a customer.
</p>

<?php if (!empty($flash) && ($flash['type'] ?? '') === 'error'): ?>
    <div class="alert-error"><?= htmlspecialchars($flash['message'] ?? '') ?></div>
<?php endif; ?>

<?php if (!empty($flash) && ($flash['type'] ?? '') === 'success'): ?>
    <div class="alert-success"><?= htmlspecialchars($flash['message'] ?? '') ?></div>
<?php endif; ?>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/dashboard') ?>">Back to Home</a>
    <?php if ($totalRows > 0): ?>
        <form method="POST" action="<?= url('/inquiries/clear-processed') ?>" class="inline-form">
            <?= csrf_field() ?>
            <button
                type="submit"
                class="btn btn-danger"
                onclick="return confirm('Remove all converted and rejected inquiries from this list? Customer records already created will not be affected.')"
            >
                Clear Processed
            </button>
        </form>
    <?php endif; ?>
</div>

<div class="toolbar-card" style="margin-bottom:14px;">
    <form method="GET" action="<?= url('/inquiries') ?>" class="inquiry-toolbar">
        <div class="toolbar-group">
            <label for="search">Search</label>
            <input
                id="search"
                class="toolbar-input"
                type="text"
                name="search"
                value="<?= htmlspecialchars($search) ?>"
                placeholder="Name, email, phone, address, or plan"
            >
        </div>

        <div class="toolbar-group">
            <label for="status">Status</label>
            <select id="status" name="status" class="toolbar-select">
                <option value="">All Status</option>
                <option value="PENDING" <?= $statusFilter === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                <option value="VISIT_SCHEDULED" <?= $statusFilter === 'VISIT_SCHEDULED' ? 'selected' : '' ?>>Visit Scheduled</option>
                <option value="CONVERTED" <?= $statusFilter === 'CONVERTED' ? 'selected' : '' ?>>Converted</option>
                <option value="REJECTED" <?= $statusFilter === 'REJECTED' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </div>

        <div class="toolbar-actions">
            <button type="submit" class="btn">Filter</button>
            <a class="btn btn-secondary" href="<?= url('/inquiries') ?>">Reset</a>
        </div>
    </form>
</div>

<div class="table-top">
    <div class="table-meta">Review applications, schedule visits, convert to customers, or reject.</div>
    <div class="metric-pill">Total: <?= $totalRows ?></div>
</div>

<?php
$renderActions = static function (array $row, array $workflowStates): void {
    $status = strtoupper((string)($row['status'] ?? 'PENDING'));
    $inquiryId = (int)($row['id'] ?? 0);
    $planLabel = trim((string)($row['plan'] ?? ''));
    $isPortalSetup = stripos($planLabel, 'existing customer') !== false
        && stripos($planLabel, 'portal') !== false;
    $workflow = $workflowStates[$inquiryId] ?? null;
    $canConvert = $isPortalSetup || (!empty($workflow['can_convert']));
    $canScheduleOcular = !empty($workflow['can_schedule_ocular']);
    $canScheduleInstallation = !empty($workflow['can_schedule_installation']);
    $ocularBooking = is_array($workflow) ? ($workflow['ocular'] ?? null) : null;
    $installationBooking = is_array($workflow) ? ($workflow['installation'] ?? null) : null;
    $ocularStatus = strtoupper((string)($ocularBooking['status'] ?? ''));
    $installationStatus = strtoupper((string)($installationBooking['status'] ?? ''));

    if (!in_array($status, ['PENDING', 'VISIT_SCHEDULED'], true)) {
        ?>
        <div class="inquiry-actions-wrap">
            <div class="inquiry-note">This inquiry is already processed.</div>
            <form method="POST" action="<?= url('/inquiries/delete') ?>" class="inline-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $inquiryId ?>">
                <button
                    type="submit"
                    class="btn btn-danger btn-small"
                    onclick="return confirm('Delete this inquiry record? Existing customer data will not be removed.')"
                >
                    Delete
                </button>
            </form>
        </div>
        <?php
        return;
    }
    ?>
    <div class="inquiry-actions-wrap">
        <?php if (!$isPortalSetup): ?>
            <?php if ($canScheduleOcular): ?>
                <a class="btn btn-small" href="<?= url('/bookings/create') ?>?inquiry_id=<?= $inquiryId ?>&visit_type=ocular">Schedule Ocular</a>
            <?php else: ?>
                <div class="inquiry-visit-status">
                    <?php if ($ocularStatus === 'COMPLETED'): ?>
                        <span class="badge badge-success">Ocular completed</span>
                    <?php elseif ($ocularStatus === 'BOOKED'): ?>
                        <span class="badge badge-info">Ocular scheduled</span>
                        <?php if (!empty($ocularBooking['booking_date'])): ?>
                            <span class="inquiry-visit-hint">
                                <?= htmlspecialchars(date('M j, Y', strtotime((string)$ocularBooking['booking_date']))) ?>
                                <?= !empty($ocularBooking['start_time']) ? 'at ' . htmlspecialchars(date('g:i A', strtotime((string)$ocularBooking['start_time']))) : '' ?>
                            </span>
                        <?php endif; ?>
                        <a class="btn btn-small btn-secondary" href="<?= url('/bookings/edit') ?>?id=<?= (int)($ocularBooking['id'] ?? 0) ?>">View / Reschedule</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($canScheduleInstallation): ?>
                <a class="btn btn-small" href="<?= url('/bookings/create') ?>?inquiry_id=<?= $inquiryId ?>&visit_type=installation">Schedule Installation</a>
            <?php else: ?>
                <div class="inquiry-visit-status">
                    <?php if ($installationStatus === 'COMPLETED'): ?>
                        <span class="badge badge-success">Installation completed</span>
                    <?php elseif ($installationStatus === 'BOOKED'): ?>
                        <span class="badge badge-info">Installation scheduled</span>
                        <?php if (!empty($installationBooking['booking_date'])): ?>
                            <span class="inquiry-visit-hint">
                                <?= htmlspecialchars(date('M j, Y', strtotime((string)$installationBooking['booking_date']))) ?>
                                <?= !empty($installationBooking['start_time']) ? 'at ' . htmlspecialchars(date('g:i A', strtotime((string)$installationBooking['start_time']))) : '' ?>
                            </span>
                        <?php endif; ?>
                        <a class="btn btn-small btn-secondary" href="<?= url('/bookings/edit') ?>?id=<?= (int)($installationBooking['id'] ?? 0) ?>">View / Reschedule</a>
                    <?php elseif ($ocularStatus === 'BOOKED'): ?>
                        <span class="badge badge-warning">Installation locked</span>
                        <span class="inquiry-visit-hint">Mark the ocular visit done first.</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <a class="btn btn-small" href="<?= url('/bookings/create') ?>?inquiry_id=<?= $inquiryId ?>">Schedule Visit</a>
        <?php endif; ?>

        <form method="POST" action="<?= url('/inquiries/register-customer') ?>" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $inquiryId ?>">
            <button
                type="submit"
                class="btn btn-success btn-small<?= !$canConvert ? ' is-disabled' : '' ?>"
                <?= !$canConvert ? 'disabled title="Complete installation and mark the job as done first."' : '' ?>
                onclick="return <?= $canConvert ? 'confirm' : 'false' ?>('<?= $isPortalSetup
                    ? 'Register this existing customer, create or refresh portal login, and email credentials?'
                    : 'Convert this applicant to customer, create subscription and billing portal, and email login details?' ?>')"
            >
                <?= $isPortalSetup ? 'Register & Portal' : 'Convert to Customer' ?>
            </button>
        </form>

        <form method="POST" action="<?= url('/inquiries/reject') ?>" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $inquiryId ?>">
            <button type="submit" class="btn btn-danger btn-small" onclick="return confirm('Reject this inquiry?');">Reject</button>
        </form>

        <div class="inquiry-note">Creates customer, subscription, prorated invoice, and portal login.</div>
    </div>
    <?php
};
?>

<div class="table-wrap inquiry-table-desktop">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Applicant</th>
                <th>Contact</th>
                <th>Address</th>
                <th>Plan</th>
                <th>Status</th>
                <th>Email</th>
                <th class="actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="8" class="empty-state">No inquiries found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <?php
                        $status = strtoupper((string)($row['status'] ?? 'PENDING'));
                        $badgeClass = match ($status) {
                            'PENDING' => 'badge-warning',
                            'VISIT_SCHEDULED' => 'badge-info',
                            'CONVERTED' => 'badge-success',
                            'REJECTED' => 'badge-danger',
                            default => 'badge-info',
                        };
                        $workflow = $workflowStates[(int)($row['id'] ?? 0)] ?? null;
                    ?>
                    <tr>
                        <td>#<?= (int)($row['id'] ?? 0) ?></td>
                        <td>
                            <div class="inquiry-name"><?= htmlspecialchars((string)($row['name'] ?? '-')) ?></div>
                            <div class="inquiry-sub">Submitted: <?= htmlspecialchars((string)($row['created_at'] ?? '-')) ?></div>
                        </td>
                        <td>
                            <div class="inquiry-stack">
                                <div><?= htmlspecialchars((string)($row['email'] ?? '-')) ?></div>
                                <div><?= htmlspecialchars((string)($row['phone'] ?? '-')) ?></div>
                                <?php if (!empty($row['referred_by_phone'])): ?>
                                    <div class="inquiry-sub">Referred by: <?= htmlspecialchars((string)$row['referred_by_phone']) ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= nl2br(htmlspecialchars((string)($row['address'] ?? '-'))) ?></td>
                        <td><?= nl2br(htmlspecialchars((string)($row['plan'] ?? '-'))) ?></td>
                        <td>
                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
                            <?php if (is_array($workflow) && !empty($workflow['summary'])): ?>
                                <div class="inquiry-sub" style="margin-top:6px;"><?= htmlspecialchars((string)$workflow['summary']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($row['converted_at'])): ?>
                                <div class="inquiry-sub">Converted: <?= htmlspecialchars((string)$row['converted_at']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int)($row['email_sent'] ?? 0) === 1): ?>
                                <span class="badge badge-success">Sent</span>
                            <?php else: ?>
                                <span class="badge badge-info">Not sent</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <?php $renderActions($row, $workflowStates); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="inquiry-cards">
    <?php if (empty($rows)): ?>
        <div class="empty-state">No inquiries found.</div>
    <?php else: ?>
        <?php foreach ($rows as $row): ?>
            <?php
                $status = strtoupper((string)($row['status'] ?? 'PENDING'));
                $badgeClass = match ($status) {
                    'PENDING' => 'badge-warning',
                    'VISIT_SCHEDULED' => 'badge-info',
                    'CONVERTED' => 'badge-success',
                    'REJECTED' => 'badge-danger',
                    default => 'badge-info',
                };
                $workflow = $workflowStates[(int)($row['id'] ?? 0)] ?? null;
            ?>
            <div class="inquiry-card">
                <div class="inquiry-card-row">
                    <div class="label">Applicant</div>
                    <div class="inquiry-name">#<?= (int)($row['id'] ?? 0) ?> · <?= htmlspecialchars((string)($row['name'] ?? '-')) ?></div>
                    <div class="inquiry-sub"><?= htmlspecialchars((string)($row['created_at'] ?? '-')) ?></div>
                </div>
                <div class="inquiry-card-row">
                    <div class="label">Contact</div>
                    <div><?= htmlspecialchars((string)($row['email'] ?? '-')) ?></div>
                    <div><?= htmlspecialchars((string)($row['phone'] ?? '-')) ?></div>
                </div>
                <div class="inquiry-card-row">
                    <div class="label">Plan / Address</div>
                    <div><?= nl2br(htmlspecialchars((string)($row['plan'] ?? '-'))) ?></div>
                    <div class="inquiry-sub"><?= nl2br(htmlspecialchars((string)($row['address'] ?? '-'))) ?></div>
                </div>
                <div class="inquiry-card-row">
                    <div class="label">Status</div>
                    <div>
                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
                        <?php if ((int)($row['email_sent'] ?? 0) === 1): ?>
                            <span class="badge badge-success">Email sent</span>
                        <?php endif; ?>
                    </div>
                    <?php if (is_array($workflow) && !empty($workflow['summary'])): ?>
                        <div class="inquiry-sub"><?= htmlspecialchars((string)$workflow['summary']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="inquiry-card-row" style="margin-bottom:0;">
                    <div class="label">Actions</div>
                    <?php $renderActions($row, $workflowStates); ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php
            $queryBase = [
                'search' => $search,
                'status' => $statusFilter,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ];
        ?>
        <?php if ($page > 1): ?>
            <a href="<?= url('/inquiries') ?>?<?= htmlspecialchars(http_build_query(array_merge($queryBase, ['page' => $page - 1]))) ?>">Prev</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($page === $i): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= url('/inquiries') ?>?<?= htmlspecialchars(http_build_query(array_merge($queryBase, ['page' => $i]))) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="<?= url('/inquiries') ?>?<?= htmlspecialchars(http_build_query(array_merge($queryBase, ['page' => $page + 1]))) ?>">Next</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
