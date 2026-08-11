<?php
$cms = $cms ?? [];
$success = $success ?? false;
$error = $error ?? false;
$message = $message ?? '';
$presets = CmsImageService::PRESETS;

$mediaFields = [
    'website_logo' => [
        'preview_class' => 'preview-logo',
        'accept' => 'image/png,image/jpeg,image/webp,image/gif',
    ],
    'website_favicon' => [
        'preview_class' => 'preview-favicon',
        'accept' => 'image/png,image/jpeg,image/webp,image/gif',
    ],
    'hero_image' => [
        'preview_class' => 'preview-hero',
        'accept' => 'image/jpeg,image/png,image/webp',
    ],
    'about_image' => [
        'preview_class' => 'preview-about',
        'accept' => 'image/jpeg,image/png,image/webp',
    ],
];
?>

<style>
.cms-tabs{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px}
.cms-tabs a{text-decoration:none;padding:10px 14px;border-radius:14px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);font-weight:800}
.cms-tabs a.active{background:linear-gradient(180deg,#fff,#ececec);color:#000}
.cms-card{background:linear-gradient(180deg,rgba(255,255,255,.04),rgba(255,255,255,.025));border-radius:6px;padding:24px;border:1px solid rgba(255,255,255,.08)}
.media-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
.media-item{padding:18px;border-radius:4px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08)}
.media-item h3{margin:0 0 8px;font-size:18px}
.media-hint{margin:0 0 14px;color:rgba(255,255,255,.68);font-size:13px;line-height:1.6}
.media-size{display:inline-block;margin-bottom:14px;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.06);font-size:12px;font-weight:800;color:#d4d4d8}
.media-preview-wrap{margin-bottom:14px;padding:14px;border-radius: 6px;background:rgba(0,0,0,.25);border:1px dashed rgba(255,255,255,.12);min-height:120px;display:flex;align-items:center;justify-content:center}
.media-preview-wrap img{display:block;max-width:100%;height:auto;border-radius:12px}
.preview-logo img{max-height:80px;width:auto}
.preview-favicon img{width:64px;height:64px;object-fit:cover;border-radius:12px}
.preview-hero img,.preview-about img{max-height:180px;width:100%;object-fit:cover;background:#0d0d10;border-radius:12px}
.media-empty{color:rgba(255,255,255,.45);font-size:13px;text-align:center}
.media-actions{display:flex;flex-direction:column;gap:10px}
.media-file input[type=file]{width:100%;padding:10px;border-radius:12px;border:1px dashed rgba(255,255,255,.18);background:rgba(255,255,255,.03);color:#fff}
.media-remove{display:flex;align-items:center;gap:8px;font-size:13px;color:rgba(255,255,255,.72)}
.alert-success,.alert-error{border-radius:14px;padding:12px 14px;margin-bottom:16px;font-weight:800}
.alert-success{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.22);color:#bbf7d0}
.alert-error{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.22);color:#fecaca}
@media (max-width:900px){.media-grid{grid-template-columns:1fr}}
</style>

<div class="cms-tabs">
    <a href="<?= url('/cms/dashboard') ?>">Dashboard</a>
    <a href="<?= url('/cms/content') ?>">Content</a>
    <a href="<?= url('/cms/design') ?>">Design</a>
    <a class="active" href="<?= url('/cms/media') ?>">Media</a>
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
    <h1>CMS Media</h1>
    <p class="section-note" style="margin:0 0 10px;">
        Upload photos for the public page at
        <a href="<?= url('/page') ?>" target="_blank" rel="noopener"><?= htmlspecialchars(url('/page')) ?></a>.
        Images are automatically resized to fit each section.
    </p>
    <p class="section-note" style="margin:0 0 20px;">Accepted formats: JPG, PNG, WebP. Maximum upload size: 8 MB.</p>

    <form method="POST" action="<?= url('/cms/media/update') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="media-grid">
            <?php foreach ($mediaFields as $field => $meta): ?>
                <?php
                $preset = $presets[$field];
                $current = trim((string)($cms[$field] ?? ''));
                $previewUrl = $current !== '' ? asset_url($current) : '';
                ?>
                <div class="media-item">
                    <h3><?= htmlspecialchars($preset['label']) ?></h3>
                    <p class="media-hint"><?= htmlspecialchars($preset['hint']) ?></p>
                    <span class="media-size">
                        Output: <?= (int)$preset['width'] ?>×<?= (int)$preset['height'] ?>px · fills frame
                    </span>

                    <div class="media-preview-wrap <?= htmlspecialchars($meta['preview_class']) ?>">
                        <?php if ($previewUrl !== ''): ?>
                            <img src="<?= htmlspecialchars($previewUrl) ?>" alt="<?= htmlspecialchars($preset['label']) ?> preview">
                        <?php else: ?>
                            <div class="media-empty">No image uploaded yet</div>
                        <?php endif; ?>
                    </div>

                    <div class="media-actions">
                        <label class="media-file">
                            <span style="display:block;margin-bottom:8px;font-weight:700;">Upload new image</span>
                            <input
                                type="file"
                                name="<?= htmlspecialchars($field) ?>"
                                accept="<?= htmlspecialchars($meta['accept']) ?>"
                            >
                        </label>

                        <?php if ($current !== ''): ?>
                            <label class="media-remove">
                                <input type="checkbox" name="remove_<?= htmlspecialchars($field) ?>" value="1">
                                Remove current image
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;">
            <button type="submit" class="btn">Save Media</button>
            <a class="btn btn-secondary" href="<?= url('/page') ?>" target="_blank" rel="noopener">Preview public page</a>
        </div>
    </form>
</div>
