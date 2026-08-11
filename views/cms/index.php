<?php
$cms = $cms ?? [];
$success = $success ?? false;
$error = $error ?? false;
$message = $message ?? '';
?>

<style>
.cms-shell {
    display: flex;
    flex-direction: column;
    gap: 22px;
}

.cms-hero {
    position: relative;
    overflow: hidden;
    border-radius: 6px;
    padding: 30px;
    background:
        radial-gradient(circle at top right, rgba(255,255,255,.05), transparent 22%),
        linear-gradient(135deg, #050505 0%, #090909 48%, #111113 100%);
    border: 1px solid rgba(255,255,255,.08);
    box-shadow: none;
}

.cms-hero h1 {
    margin: 0 0 12px;
    font-size: 36px;
    line-height: 1.08;
    color: #ffffff;
    font-weight: 650;
    letter-spacing: -.03em;
}

.cms-hero p {
    margin: 0;
    color: rgba(255,255,255,.76);
    line-height: 1.75;
    max-width: 860px;
    font-size: 15px;
}

.cms-card {
    background: linear-gradient(180deg, rgba(255,255,255,.04) 0%, rgba(255,255,255,.025) 100%);
    border-radius: 6px;
    padding: 24px;
    border: 1px solid rgba(255,255,255,.08);
    box-shadow: 0 14px 36px rgba(0,0,0,.28);
}

.cms-card h2 {
    margin: 0 0 8px;
    color: #ffffff;
    font-size: 22px;
    font-weight: 800;
}

.cms-card p.section-note {
    margin: 0 0 20px;
    color: rgba(255,255,255,.68);
    font-size: 14px;
    line-height: 1.7;
}

.cms-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.cms-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.cms-group.full {
    grid-column: 1 / -1;
}

.cms-group label {
    font-size: 14px;
    font-weight: 800;
    color: #f3f4f6;
}

.cms-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 20px;
}

.cms-card input,
.cms-card textarea {
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(255,255,255,.10);
    color: #ffffff;
    border-radius: 6px;
    padding: 13px 14px;
}

.cms-card textarea {
    min-height: 120px;
}

.cms-card input:focus,
.cms-card textarea:focus {
    border-color: rgba(255,255,255,.20);
    box-shadow: 0 0 0 4px rgba(255,255,255,.05);
    background: rgba(255,255,255,.05);
}

.cms-divider {
    height: 1px;
    background: rgba(255,255,255,.08);
    margin: 20px 0;
}

.alert-success,
.alert-error {
    border-radius: 6px;
    padding: 14px 16px;
    margin-bottom: 18px;
    font-weight: 700;
}

.alert-success {
    background: rgba(34, 197, 94, 0.12);
    border: 1px solid rgba(34, 197, 94, 0.25);
    color: #bbf7d0;
}

.alert-error {
    background: rgba(239, 68, 68, 0.12);
    border: 1px solid rgba(239, 68, 68, 0.25);
    color: #fecaca;
}

.helper-text {
    color: rgba(255,255,255,.60);
    font-size: 13px;
    line-height: 1.6;
}

@media (max-width: 900px) {
    .cms-grid {
        grid-template-columns: 1fr;
    }

    .cms-hero h1 {
        font-size: 28px;
    }
}
</style>

<div class="cms-shell">
    <div class="cms-hero">
        <h1>Website CMS</h1>
        <p>
            Manage the public content of your external FusionLink website from inside the billing system.
            Update the homepage hero text, company information, apply page content, and footer without editing code.
        </p>
    </div>

    <div class="cms-card">
        <?php if ($success): ?>
            <div class="alert-success">
                <?= htmlspecialchars($message !== '' ? $message : 'CMS updated successfully.') ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-error">
                <?= htmlspecialchars($message !== '' ? $message : 'Something went wrong.') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('/cms/update') ?>">
        <?= csrf_field() ?>
            <h2>Homepage Hero</h2>
            <p class="section-note">
                These values appear on the main landing section of your external website.
            </p>

            <div class="cms-grid">
                <div class="cms-group full">
                    <label for="hero_title">Hero Title</label>
                    <input
                        type="text"
                        id="hero_title"
                        name="hero_title"
                        value="<?= htmlspecialchars((string)($cms['hero_title'] ?? '')) ?>"
                    >
                </div>

                <div class="cms-group full">
                    <label for="hero_subtitle">Hero Subtitle</label>
                    <textarea
                        id="hero_subtitle"
                        name="hero_subtitle"
                    ><?= htmlspecialchars((string)($cms['hero_subtitle'] ?? '')) ?></textarea>
                </div>

                <div class="cms-group full">
                    <label for="hero_image">Hero Background Image URL</label>
                    <input
                        type="text"
                        id="hero_image"
                        name="hero_image"
                        value="<?= htmlspecialchars((string)($cms['hero_image'] ?? '')) ?>"
                        placeholder="https://example.com/your-image.jpg"
                    >
                    <div class="helper-text">
                        Leave blank if you want to keep the current default background image.
                    </div>
                </div>
            </div>

            <div class="cms-divider"></div>

            <h2>Company Information</h2>
            <p class="section-note">
                These values are shown in your external website contact and footer areas.
            </p>

            <div class="cms-grid">
                <div class="cms-group">
                    <label for="company_name">Company Name</label>
                    <input
                        type="text"
                        id="company_name"
                        name="company_name"
                        value="<?= htmlspecialchars((string)($cms['company_name'] ?? '')) ?>"
                    >
                </div>

                <div class="cms-group">
                    <label for="company_email">Company Email</label>
                    <input
                        type="email"
                        id="company_email"
                        name="company_email"
                        value="<?= htmlspecialchars((string)($cms['company_email'] ?? '')) ?>"
                    >
                </div>

                <div class="cms-group">
                    <label for="company_phone">Company Phone</label>
                    <input
                        type="text"
                        id="company_phone"
                        name="company_phone"
                        value="<?= htmlspecialchars((string)($cms['company_phone'] ?? '')) ?>"
                    >
                </div>

                <div class="cms-group full">
                    <label for="company_address">Company Address</label>
                    <textarea
                        id="company_address"
                        name="company_address"
                    ><?= htmlspecialchars((string)($cms['company_address'] ?? '')) ?></textarea>
                </div>
            </div>

            <div class="cms-divider"></div>

            <h2>Apply Page</h2>
            <p class="section-note">
                These values are shown on the external online application page.
            </p>

            <div class="cms-grid">
                <div class="cms-group full">
                    <label for="apply_title">Apply Page Title</label>
                    <input
                        type="text"
                        id="apply_title"
                        name="apply_title"
                        value="<?= htmlspecialchars((string)($cms['apply_title'] ?? '')) ?>"
                    >
                </div>

                <div class="cms-group full">
                    <label for="apply_subtitle">Apply Page Subtitle</label>
                    <textarea
                        id="apply_subtitle"
                        name="apply_subtitle"
                    ><?= htmlspecialchars((string)($cms['apply_subtitle'] ?? '')) ?></textarea>
                </div>

                <div class="cms-group full">
                    <label for="apply_form_title">Apply Form Title</label>
                    <input
                        type="text"
                        id="apply_form_title"
                        name="apply_form_title"
                        value="<?= htmlspecialchars((string)($cms['apply_form_title'] ?? '')) ?>"
                    >
                </div>

                <div class="cms-group full">
                    <label for="apply_form_text">Apply Form Description</label>
                    <textarea
                        id="apply_form_text"
                        name="apply_form_text"
                    ><?= htmlspecialchars((string)($cms['apply_form_text'] ?? '')) ?></textarea>
                </div>

                <div class="cms-group full">
                    <label for="apply_success_message">Apply Success Message</label>
                    <textarea
                        id="apply_success_message"
                        name="apply_success_message"
                    ><?= htmlspecialchars((string)($cms['apply_success_message'] ?? '')) ?></textarea>
                </div>
            </div>

            <div class="cms-divider"></div>

            <h2>Footer</h2>
            <p class="section-note">
                This value appears at the bottom of your external website.
            </p>

            <div class="cms-grid">
                <div class="cms-group full">
                    <label for="footer_text">Footer Text</label>
                    <input
                        type="text"
                        id="footer_text"
                        name="footer_text"
                        value="<?= htmlspecialchars((string)($cms['footer_text'] ?? '')) ?>"
                    >
                </div>
            </div>

            <div class="cms-actions">
                <button type="submit" class="btn">Save CMS</button>
                <a href="<?= url('/dashboard') ?>" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </form>
    </div>
</div>
