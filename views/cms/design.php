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
.preview{margin-top:22px;padding:20px;border-radius:6px;border:1px solid rgba(255,255,255,.08)}
.preview-btn{display:inline-block;padding:12px 16px;text-decoration:none;font-weight:800}
@media (max-width:900px){.cms-grid{grid-template-columns:1fr}}
</style>

<div class="cms-tabs">
    <a href="<?= url('/cms/dashboard') ?>">Dashboard</a>
    <a href="<?= url('/cms/content') ?>">Content</a>
    <a class="active" href="<?= url('/cms/design') ?>">Design</a>
    <a href="<?= url('/cms/media') ?>">Media</a>
    <a href="<?= url('/cms/navigation') ?>">Navigation</a>
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
    <h1>CMS Design</h1>
    <p class="section-note" style="margin:0 0 20px;">Control the standard branding and design values of the external website.</p>

    <form method="POST" action="<?= url('/cms/design/update') ?>">
        <?= csrf_field() ?>
        <div class="cms-grid">
            <div class="cms-group">
                <label>Primary Color</label>
                <input type="text" name="primary_color" value="<?= htmlspecialchars((string)($cms['primary_color'] ?? '')) ?>" placeholder="#6d28d9">
            </div>
            <div class="cms-group">
                <label>Secondary Color</label>
                <input type="text" name="secondary_color" value="<?= htmlspecialchars((string)($cms['secondary_color'] ?? '')) ?>" placeholder="#8b5cf6">
            </div>
            <div class="cms-group">
                <label>Accent Color</label>
                <input type="text" name="accent_color" value="<?= htmlspecialchars((string)($cms['accent_color'] ?? '')) ?>" placeholder="#a78bfa">
                <small style="color:rgba(255,255,255,.55);">Used for highlights and buttons. Plan prices stay white for readability.</small>
            </div>
            <div class="cms-group">
                <label>Text Color</label>
                <input type="text" name="text_color" value="<?= htmlspecialchars((string)($cms['text_color'] ?? '')) ?>" placeholder="#ffffff">
            </div>
            <div class="cms-group">
                <label>Header Background</label>
                <input type="text" name="header_background" value="<?= htmlspecialchars((string)($cms['header_background'] ?? '')) ?>" placeholder="#0f0f10">
            </div>
            <div class="cms-group">
                <label>Section Background</label>
                <input type="text" name="section_background" value="<?= htmlspecialchars((string)($cms['section_background'] ?? '')) ?>" placeholder="#111113">
            </div>
            <div class="cms-group">
                <label>Footer Background</label>
                <input type="text" name="footer_background" value="<?= htmlspecialchars((string)($cms['footer_background'] ?? '')) ?>" placeholder="#0a0a0a">
            </div>
            <div class="cms-group">
                <label>Button Radius (px)</label>
                <input type="text" name="button_radius" value="<?= htmlspecialchars((string)($cms['button_radius'] ?? '16')) ?>" placeholder="16">
            </div>
        </div>

        <div style="margin-top:20px;">
            <button type="submit" class="btn">Save Design</button>
        </div>
    </form>

    <div class="preview" style="background:<?= htmlspecialchars((string)($cms['section_background'] ?? '#111113')) ?>; color:<?= htmlspecialchars((string)($cms['text_color'] ?? '#ffffff')) ?>;">
        <h2 style="margin-top:0;">Preview Block</h2>
        <p style="color:inherit;opacity:.85;">
            This preview helps you visualize your CMS design values.
        </p>
        <a
            href="#"
            class="preview-btn"
            style="
                margin-top:12px;
                background:<?= htmlspecialchars((string)($cms['primary_color'] ?? '#6d28d9')) ?>;
                color:#fff;
                border-radius:<?= htmlspecialchars((string)($cms['button_radius'] ?? '16')) ?>px;
            "
        >Sample Button</a>
    </div>
</div>
