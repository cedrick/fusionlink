<h1>Import Customers</h1>

<div class="page-actions">
    <a href="<?= url('/customers') ?>" class="btn btn-secondary">Back to Customers</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="form-card">
    <h2 class="form-section-title">Upload CSV</h2>
    <div class="form-help">
        Bulk onboard existing clients. Required columns: <strong>full_name</strong>, <strong>phone</strong>.
        Optional: <strong>email</strong>, <strong>address</strong>, <strong>status</strong> (ACTIVE or DISCONNECTED),
        <strong>plan_name</strong>, <strong>start_date</strong> (YYYY-MM-DD).
        Rows with duplicate phone or email are skipped.
    </div>

    <form method="POST" action="<?= url('/customers/import') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="form-grid">
            <div class="form-group full">
                <label for="csv_file">CSV File</label>
                <input id="csv_file" type="file" name="csv_file" accept=".csv,text/csv" required>
            </div>

            <div class="form-group full">
                <label class="checkbox-label">
                    <input type="checkbox" name="create_subscriptions" value="1">
                    Create subscriptions when <code>plan_name</code> matches an existing plan
                </label>
            </div>

            <div class="form-group full">
                <label class="checkbox-label">
                    <input type="checkbox" name="activate_portal" value="1" checked>
                    Create portal login and email credentials when <code>email</code> is provided
                </label>
            </div>
        </div>

        <div class="page-actions">
            <a href="<?= url('/customers') ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn">Import Customers</button>
        </div>
    </form>
</div>

<div class="form-card" style="margin-top:18px;">
    <h2 class="form-section-title">Sample CSV</h2>
    <pre style="white-space:pre-wrap;overflow:auto;background:#0f172a;color:#e2e8f0;padding:16px;border-radius:12px;font-size:13px;">full_name,phone,email,address,status,plan_name,start_date
Juan Dela Cruz,09171234567,juan@example.com,Manila,ACTIVE,Residential Connectivity,2024-01-15
Maria Santos,09189876543,maria@example.com,Quezon City,ACTIVE,,</pre>
</div>
