<h1>Field Personnel & Services</h1>

<?php if (!empty($flash) && ($flash['type'] ?? '') === 'error'): ?>
    <div class="alert-error"><?= htmlspecialchars($flash['message'] ?? '') ?></div>
<?php endif; ?>

<?php if (!empty($flash) && ($flash['type'] ?? '') === 'success'): ?>
    <div class="alert-success"><?= htmlspecialchars($flash['message'] ?? '') ?></div>
<?php endif; ?>

<div class="page-actions">
    <a class="btn btn-secondary" href="<?= url('/bookings') ?>">Back to Bookings</a>
    <a class="btn btn-secondary" href="<?= url('/page/book') ?>" target="_blank" rel="noopener">Public Booking Page</a>
</div>

<div class="form-card" style="margin-bottom:18px;">
    <h2 class="form-section-title">Service Types</h2>
    <div class="form-help">Each service has a duration used for conflict checking and same-day slot availability.</div>

    <div class="table-wrap" style="margin-top:16px;">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Duration</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($serviceTypes)): ?>
                    <tr><td colspan="3" class="empty-state">No service types yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($serviceTypes as $serviceType): ?>
                        <tr>
                            <td><?= htmlspecialchars($serviceType['name'] ?? '') ?></td>
                            <td><?= (int)($serviceType['duration_minutes'] ?? 0) ?> min</td>
                            <td>
                                <span class="badge <?= (int)($serviceType['is_active'] ?? 0) === 1 ? 'badge-success' : 'badge-danger' ?>">
                                    <?= (int)($serviceType['is_active'] ?? 0) === 1 ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <form method="POST" action="<?= url('/personnel/service-types/store') ?>" class="form-grid" style="margin-top:18px;">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="service_name">Add service</label>
            <input id="service_name" name="name" type="text" placeholder="e.g. Fiber Installation" required>
        </div>
        <div class="form-group">
            <label for="duration_minutes">Duration (minutes)</label>
            <input id="duration_minutes" name="duration_minutes" type="number" min="15" step="15" value="60" required>
        </div>
        <div class="form-group" style="align-self:end;">
            <button type="submit" class="btn">Add Service Type</button>
        </div>
    </form>
</div>

<div class="form-card" style="margin-bottom:18px;">
    <h2 class="form-section-title">Add Personnel</h2>
    <form method="POST" action="<?= url('/personnel/store') ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="form-group">
                <label for="full_name">Full name</label>
                <input id="full_name" name="full_name" type="text" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input id="phone" name="phone" type="text" maxlength="11" placeholder="09XXXXXXXXX">
            </div>
            <div class="form-group">
                <label for="work_start_time">Work start</label>
                <input id="work_start_time" name="work_start_time" type="time" value="08:00" required>
            </div>
            <div class="form-group">
                <label for="work_end_time">Work end</label>
                <input id="work_end_time" name="work_end_time" type="time" value="17:00" required>
            </div>
            <div class="form-group full">
                <label>Services this personnel can handle</label>
                <div style="display:flex;flex-wrap:wrap;gap:12px;">
                    <?php foreach (($serviceTypes ?? []) as $serviceType): ?>
                        <?php if ((int)($serviceType['is_active'] ?? 0) !== 1) { continue; } ?>
                        <label style="display:flex;align-items:center;gap:8px;font-weight:600;">
                            <input type="checkbox" name="service_type_ids[]" value="<?= (int)($serviceType['id'] ?? 0) ?>">
                            <?= htmlspecialchars($serviceType['name'] ?? '') ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="page-actions">
            <button type="submit" class="btn">Add Personnel</button>
        </div>
    </form>
</div>

<div class="form-card">
    <h2 class="form-section-title">Personnel List</h2>
    <?php if (empty($personnel)): ?>
        <div class="empty-state">No field personnel yet. Add at least one person and assign services to enable booking.</div>
    <?php else: ?>
        <?php foreach ($personnel as $person): ?>
            <?php $personId = (int)($person['id'] ?? 0); ?>
            <form method="POST" action="<?= url('/personnel/update') ?>" style="border-top:1px solid rgba(255,255,255,.08);padding-top:18px;margin-top:18px;">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $personId ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full name</label>
                        <input name="full_name" type="text" value="<?= htmlspecialchars($person['full_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input name="phone" type="text" maxlength="11" value="<?= htmlspecialchars($person['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Work start</label>
                        <input name="work_start_time" type="time" value="<?= htmlspecialchars(substr((string)($person['work_start_time'] ?? '08:00:00'), 0, 5)) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Work end</label>
                        <input name="work_end_time" type="time" value="<?= htmlspecialchars(substr((string)($person['work_end_time'] ?? '17:00:00'), 0, 5)) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <label style="display:flex;align-items:center;gap:8px;font-weight:600;">
                            <input type="checkbox" name="is_active" value="1" <?= (int)($person['is_active'] ?? 0) === 1 ? 'checked' : '' ?>>
                            Active
                        </label>
                    </div>
                    <div class="form-group full">
                        <label>Assigned services</label>
                        <div style="display:flex;flex-wrap:wrap;gap:12px;">
                            <?php foreach (($serviceTypes ?? []) as $serviceType): ?>
                                <?php
                                    $serviceId = (int)($serviceType['id'] ?? 0);
                                    $checked = in_array($serviceId, $assignments[$personId] ?? [], true);
                                ?>
                                <label style="display:flex;align-items:center;gap:8px;font-weight:600;">
                                    <input type="checkbox" name="service_type_ids[]" value="<?= $serviceId ?>" <?= $checked ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($serviceType['name'] ?? '') ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="page-actions">
                    <button type="submit" class="btn btn-small">Save Personnel</button>
                </div>
            </form>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
