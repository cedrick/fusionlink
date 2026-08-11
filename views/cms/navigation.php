<?php
$cms = $cms ?? [];
$success = $success ?? false;
$error = $error ?? false;
$message = $message ?? '';
?>

<style>
.cms-tabs{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px}
.cms-tabs a{text-decoration:none;padding:10px 14px;border-radius:14px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);font-weight:800}
.cms-tabs a.active{background:linear-gradient(180deg,#fff,#ececec);color:#000}
.cms-card{background:linear-gradient(180deg,rgba(255,255,255,.04),rgba(255,255,255,.025));border-radius:6px;padding:24px;border:1px solid rgba(255,255,255,.08)}
.cms-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.cms-group{display:flex;flex-direction:column;gap:8px}
.alert-success,.alert-error{border-radius:14px;padding:12px 14px;margin-bottom:16px;font-weight:800}
.alert-success{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.22);color:#bbf7d0}
.alert-error{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.22);color:#fecaca}
@media (max-width:900px){.cms-grid{grid-template-columns:1fr}}
</style>

<div class="cms-tabs">
    <a href="<?= url('/cms/dashboard') ?>">Dashboard</a>
    <a href="<?= url('/cms/content') ?>">Content</a>
    <a href="<?= url('/cms/design') ?>">Design</a>
    <a href="<?= url('/cms/media') ?>">Media</a>
    <a class="active" href="<?= url('/cms/navigation') ?>">Navigation</a>
    <a href="<?= url('/cms/settings') ?>">Website Settings</a>
    <a href="<?= url('/inquiries') ?>">Forms / Submissions</a>
</div>

<?php if ($success): ?>
    <div class="alert-success"><?= htmlspecialchars($message !== '' ? $message : 'CMS updated successfully.') ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert-error"><?= htmlspecialchars($message !== '' ? $message : 'Something went wrong.') ?></div>
<?php endif; ?>

<div class="cms-card">
    <h1>CMS Navigation</h1>
    <p class="section-note" style="margin:0 0 20px;">Manage your standard external website menu labels.</p>

    <form method="POST" action="<?= url('/cms/navigation/update') ?>">
        <?= csrf_field() ?>
        <div class="cms-grid">
            <div class="cms-group">
                <label>Home Label</label>
                <input type="text" name="nav_home_label" value="<?= htmlspecialchars((string)($cms['nav_home_label'] ?? '')) ?>">
            </div>
            <div class="cms-group">
                <label>About Label</label>
                <input type="text" name="nav_about_label" value="<?= htmlspecialchars((string)($cms['nav_about_label'] ?? '')) ?>">
            </div>
            <div class="cms-group">
                <label>Plans Label</label>
                <input type="text" name="nav_plans_label" value="<?= htmlspecialchars((string)($cms['nav_plans_label'] ?? '')) ?>">
            </div>
            <div class="cms-group">
                <label>Contact Label</label>
                <input type="text" name="nav_contact_label" value="<?= htmlspecialchars((string)($cms['nav_contact_label'] ?? '')) ?>">
            </div>
            <div class="cms-group">
                <label>Apply Button Label</label>
                <input type="text" name="nav_apply_label" value="<?= htmlspecialchars((string)($cms['nav_apply_label'] ?? '')) ?>">
            </div>
        </div>

        <div style="margin-top:20px;">
            <button type="submit" class="btn">Save Navigation</button>
        </div>
    </form>
</div>
