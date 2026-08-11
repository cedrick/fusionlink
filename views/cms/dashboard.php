<?php
$cms = $cms ?? [];
$success = $success ?? false;
$error = $error ?? false;
$message = $message ?? '';

$cards = [
    ['title' => 'Content', 'desc' => 'Manage homepage, about, services, CTA, apply page, and footer.', 'link' => '/cms/content'],
    ['title' => 'Design', 'desc' => 'Manage colors, backgrounds, text color, and button radius.', 'link' => '/cms/design'],
    ['title' => 'Media', 'desc' => 'Manage logo, favicon, hero image, and about image.', 'link' => '/cms/media'],
    ['title' => 'Navigation', 'desc' => 'Manage public website menu labels.', 'link' => '/cms/navigation'],
    ['title' => 'Website Settings', 'desc' => 'Manage company name, email, phone, and address.', 'link' => '/cms/settings'],
    ['title' => 'Forms / Submissions', 'desc' => 'Review public inquiries and application records.', 'link' => '/inquiries'],
];
?>

<style>
.cms-tabs{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px}
.cms-tabs a{text-decoration:none;padding:10px 14px;border-radius:14px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);font-weight:800}
.cms-tabs a.active{background:linear-gradient(180deg,#fff,#ececec);color:#000}
.cms-hero{padding:28px;border-radius:6px;background:#0c0c0d;border:1px solid rgba(255,255,255,.08);margin-bottom:20px}
.cms-hero h1{margin:0 0 10px;font-size:34px;color:#fff}
.cms-hero p{margin:0;color:rgba(255,255,255,.72);line-height:1.7}
.cms-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:18px}
.cms-box{padding:22px;border-radius:6px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08)}
.cms-box h3{margin:0 0 10px}
.cms-box p{margin:0 0 16px;color:rgba(255,255,255,.72);line-height:1.7}
.cms-stat{font-size:13px;color:#a1a1aa;margin-top:6px}
.alert-success,.alert-error{border-radius:14px;padding:12px 14px;margin-bottom:16px;font-weight:800}
.alert-success{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.22);color:#bbf7d0}
.alert-error{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.22);color:#fecaca}
</style>

<div class="cms-tabs">
    <a class="active" href="<?= url('/cms/dashboard') ?>">Dashboard</a>
    <a href="<?= url('/cms/content') ?>">Content</a>
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

<div class="cms-hero">
    <h1>Standard Website CMS</h1>
    <p>
        This CMS is now organized into standard website management sections. Use Content for text sections,
        Design for branding colors, Media for visual assets, Navigation for menu labels, and Website Settings
        for company information.
    </p>
    <p style="margin-top:16px;">
        <a class="btn" href="<?= url('/page') ?>" target="_blank" rel="noopener">View public page</a>
    </p>
</div>

<div class="quick-grid">
    <div class="quick-card">
        <div class="label">Company Name</div>
        <div class="value" style="font-size:24px;"><?= htmlspecialchars((string)($cms['company_name'] ?? 'FusionLink')) ?></div>
    </div>
    <div class="quick-card">
        <div class="label">Primary Color</div>
        <div class="value" style="font-size:24px;"><?= htmlspecialchars((string)($cms['primary_color'] ?? '#6d28d9')) ?></div>
    </div>
    <div class="quick-card">
        <div class="label">Navigation CTA</div>
        <div class="value" style="font-size:24px;"><?= htmlspecialchars((string)($cms['nav_apply_label'] ?? 'Apply Now')) ?></div>
    </div>
</div>

<div class="cms-grid">
    <?php foreach ($cards as $card): ?>
        <div class="cms-box">
            <h3><?= htmlspecialchars($card['title']) ?></h3>
            <p><?= htmlspecialchars($card['desc']) ?></p>
            <a class="btn" href="<?= url($card['link']) ?>">Open</a>
        </div>
    <?php endforeach; ?>
</div>
