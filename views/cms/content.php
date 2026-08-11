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
.cms-group.full{grid-column:1 / -1}
.cms-divider{height:1px;background:rgba(255,255,255,.08);margin:22px 0}
.alert-success,.alert-error{border-radius:14px;padding:12px 14px;margin-bottom:16px;font-weight:800}
.alert-success{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.22);color:#bbf7d0}
.alert-error{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.22);color:#fecaca}
@media (max-width:900px){.cms-grid{grid-template-columns:1fr}}
</style>

<div class="cms-tabs">
    <a href="<?= url('/cms/dashboard') ?>">Dashboard</a>
    <a class="active" href="<?= url('/cms/content') ?>">Content</a>
    <a href="<?= url('/cms/design') ?>">Design</a>
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
    <h1>CMS Content</h1>
    <p class="section-note" style="margin:0 0 20px;">Manage the public page content shown at <a href="<?= url('/page') ?>" target="_blank" rel="noopener"><?= htmlspecialchars(url('/page')) ?></a>.</p>

    <form method="POST" action="<?= url('/cms/content/update') ?>">
        <?= csrf_field() ?>
        <h2>Homepage Hero</h2>
        <div class="cms-grid">
            <div class="cms-group full">
                <label>Hero Title</label>
                <input type="text" name="hero_title" value="<?= htmlspecialchars((string)($cms['hero_title'] ?? '')) ?>">
            </div>
            <div class="cms-group full">
                <label>Hero Subtitle</label>
                <textarea name="hero_subtitle"><?= htmlspecialchars((string)($cms['hero_subtitle'] ?? '')) ?></textarea>
            </div>
        </div>

        <div class="cms-divider"></div>

        <h2>About Section</h2>
        <div class="cms-grid">
            <div class="cms-group full">
                <label>About Title</label>
                <input type="text" name="about_title" value="<?= htmlspecialchars((string)($cms['about_title'] ?? '')) ?>">
            </div>
            <div class="cms-group full">
                <label>About Description</label>
                <textarea name="about_text"><?= htmlspecialchars((string)($cms['about_text'] ?? '')) ?></textarea>
            </div>
        </div>

        <div class="cms-divider"></div>

        <h2>Services Section</h2>
        <div class="cms-grid">
            <div class="cms-group full">
                <label>Services Title</label>
                <input type="text" name="services_title" value="<?= htmlspecialchars((string)($cms['services_title'] ?? '')) ?>">
            </div>
            <div class="cms-group full">
                <label>Services Description</label>
                <textarea name="services_text"><?= htmlspecialchars((string)($cms['services_text'] ?? '')) ?></textarea>
            </div>
        </div>

        <div class="cms-divider"></div>

        <h2>Plans Section Intro</h2>
        <div class="cms-grid">
            <div class="cms-group full">
                <label>Plans Title</label>
                <input type="text" name="plans_title" value="<?= htmlspecialchars((string)($cms['plans_title'] ?? '')) ?>">
            </div>
            <div class="cms-group full">
                <label>Plans Description</label>
                <textarea name="plans_text"><?= htmlspecialchars((string)($cms['plans_text'] ?? '')) ?></textarea>
            </div>
        </div>

        <div class="cms-divider"></div>

        <h2>Call To Action</h2>
        <div class="cms-grid">
            <div class="cms-group full">
                <label>CTA Title</label>
                <input type="text" name="cta_title" value="<?= htmlspecialchars((string)($cms['cta_title'] ?? '')) ?>">
            </div>
            <div class="cms-group full">
                <label>CTA Text</label>
                <textarea name="cta_text"><?= htmlspecialchars((string)($cms['cta_text'] ?? '')) ?></textarea>
            </div>
            <div class="cms-group">
                <label>CTA Button Text</label>
                <input type="text" name="cta_button_text" value="<?= htmlspecialchars((string)($cms['cta_button_text'] ?? '')) ?>">
            </div>
            <div class="cms-group">
                <label>CTA Button Link</label>
                <input type="text" name="cta_button_link" value="<?= htmlspecialchars((string)($cms['cta_button_link'] ?? '')) ?>">
            </div>
        </div>

        <div class="cms-divider"></div>

        <h2>Apply Page</h2>
        <div class="cms-grid">
            <div class="cms-group full">
                <label>Apply Page Title</label>
                <input type="text" name="apply_title" value="<?= htmlspecialchars((string)($cms['apply_title'] ?? '')) ?>">
            </div>
            <div class="cms-group full">
                <label>Apply Page Subtitle</label>
                <textarea name="apply_subtitle"><?= htmlspecialchars((string)($cms['apply_subtitle'] ?? '')) ?></textarea>
            </div>
            <div class="cms-group full">
                <label>Apply Form Title</label>
                <input type="text" name="apply_form_title" value="<?= htmlspecialchars((string)($cms['apply_form_title'] ?? '')) ?>">
            </div>
            <div class="cms-group full">
                <label>Apply Form Description</label>
                <textarea name="apply_form_text"><?= htmlspecialchars((string)($cms['apply_form_text'] ?? '')) ?></textarea>
            </div>
            <div class="cms-group full">
                <label>Apply Success Message</label>
                <textarea name="apply_success_message"><?= htmlspecialchars((string)($cms['apply_success_message'] ?? '')) ?></textarea>
            </div>
        </div>

        <div class="cms-divider"></div>

        <h2>Footer</h2>
        <div class="cms-grid">
            <div class="cms-group full">
                <label>Footer Text</label>
                <input type="text" name="footer_text" value="<?= htmlspecialchars((string)($cms['footer_text'] ?? '')) ?>">
            </div>
        </div>

        <div style="margin-top:20px;">
            <button type="submit" class="btn">Save Content</button>
        </div>
    </form>
</div>
