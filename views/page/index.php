<?php
$cms = $cms ?? [];
$plans = $plans ?? [];
$applied = $applied ?? false;
$error = $error ?? '';
$selectedPlanId = (int)($selectedPlanId ?? 0);
$heroImage = asset_url((string)($cms['hero_image'] ?? ''));
$aboutImage = asset_url((string)($cms['about_image'] ?? ''));
$successMessage = (string)($cms['apply_success_message'] ?? 'Your request has been recorded and will be reviewed by the team.');
?>

<section class="hero" id="home">
    <div class="page-section-inner hero-grid">
        <div>
            <h1><?= htmlspecialchars((string)($cms['hero_title'] ?? '')) ?></h1>
            <p><?= nl2br(htmlspecialchars((string)($cms['hero_subtitle'] ?? ''))) ?></p>
            <div class="hero-actions">
                <a class="btn-primary" href="<?= htmlspecialchars(page_link((string)($cms['cta_button_link'] ?? '#apply'))) ?>">
                    <?= htmlspecialchars((string)($cms['cta_button_text'] ?? 'Apply Now')) ?>
                </a>
                <a class="btn-secondary" href="<?= url('/page') ?>#plans">View Plans</a>
            </div>
        </div>
        <div class="hero-media">
            <?php if ($heroImage !== ''): ?>
                <img src="<?= htmlspecialchars($heroImage) ?>" alt="Hero image">
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="page-section" id="about">
    <div class="page-section-inner split">
        <div>
            <h2 class="section-title"><?= htmlspecialchars((string)($cms['about_title'] ?? '')) ?></h2>
            <p class="section-lead"><?= nl2br(htmlspecialchars((string)($cms['about_text'] ?? ''))) ?></p>
        </div>
        <?php if ($aboutImage !== ''): ?>
            <div class="about-media">
                <img src="<?= htmlspecialchars($aboutImage) ?>" alt="About image">
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="page-section alt" id="services">
    <div class="page-section-inner">
        <h2 class="section-title"><?= htmlspecialchars((string)($cms['services_title'] ?? '')) ?></h2>
        <p class="section-lead"><?= nl2br(htmlspecialchars((string)($cms['services_text'] ?? ''))) ?></p>
    </div>
</section>

<section class="page-section" id="plans">
    <div class="page-section-inner">
        <h2 class="section-title"><?= htmlspecialchars((string)($cms['plans_title'] ?? '')) ?></h2>
        <p class="section-lead"><?= nl2br(htmlspecialchars((string)($cms['plans_text'] ?? ''))) ?></p>

        <?php if (empty($plans)): ?>
            <div class="info-card">No plans are available yet. Add plans in the billing dashboard.</div>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($plans as $plan): ?>
                    <article class="plan-card">
                        <h3><?= htmlspecialchars((string)($plan['name'] ?? '')) ?></h3>
                        <div class="plan-speed"><?= htmlspecialchars((string)($plan['speed'] ?? '')) ?></div>
                        <div class="plan-price">₱<?= number_format((float)($plan['price'] ?? 0), 2) ?></div>
                        <a class="btn-primary" href="<?= url('/page') ?>?plan=<?= (int)($plan['id'] ?? 0) ?>#apply">Apply for this plan</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="page-section alt">
    <div class="page-section-inner">
        <div class="cta-box">
            <h2 class="section-title"><?= htmlspecialchars((string)($cms['cta_title'] ?? '')) ?></h2>
            <p class="section-lead" style="margin-left:auto;margin-right:auto;">
                <?= nl2br(htmlspecialchars((string)($cms['cta_text'] ?? ''))) ?>
            </p>
            <a class="btn-primary" href="<?= htmlspecialchars(page_link((string)($cms['cta_button_link'] ?? '#apply'))) ?>">
                <?= htmlspecialchars((string)($cms['cta_button_text'] ?? 'Apply Now')) ?>
            </a>
        </div>
    </div>
</section>

<section class="page-section" id="contact">
    <div class="page-section-inner">
        <h2 class="section-title"><?= htmlspecialchars((string)($cms['nav_contact_label'] ?? 'Contact')) ?></h2>
        <div class="card-grid">
            <div class="info-card">
                <h3>Company</h3>
                <p><?= htmlspecialchars((string)($cms['company_name'] ?? '')) ?></p>
            </div>
            <div class="info-card">
                <h3>Email</h3>
                <p><?= htmlspecialchars((string)($cms['company_email'] ?? '')) ?></p>
            </div>
            <div class="info-card">
                <h3>Phone</h3>
                <p><?= htmlspecialchars((string)($cms['company_phone'] ?? '')) ?></p>
            </div>
            <div class="info-card">
                <h3>Address</h3>
                <p><?= nl2br(htmlspecialchars((string)($cms['company_address'] ?? ''))) ?></p>
            </div>
        </div>
    </div>
</section>

<section class="page-section alt" id="apply">
    <div class="page-section-inner">
        <h2 class="section-title"><?= htmlspecialchars((string)($cms['apply_title'] ?? '')) ?></h2>
        <p class="section-lead"><?= nl2br(htmlspecialchars((string)($cms['apply_subtitle'] ?? ''))) ?></p>

        <div class="apply-card">
            <?php if ($applied): ?>
                <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <h3 style="margin-top:0;"><?= htmlspecialchars((string)($cms['apply_form_title'] ?? '')) ?></h3>
            <p><?= nl2br(htmlspecialchars((string)($cms['apply_form_text'] ?? ''))) ?></p>

            <form method="POST" action="<?= url('/page/apply') ?>" class="apply-grid" style="margin-top:20px;">
                <?= csrf_field() ?>
                <div class="full">
                    <label for="name">Full name<span class="required-mark" aria-hidden="true">*</span></label>
                    <input id="name" type="text" name="name" required>
                </div>
                <div>
                    <label for="email">Email<span class="required-mark" aria-hidden="true">*</span></label>
                    <input id="email" type="email" name="email" required>
                </div>
                <div>
                    <label for="phone">Phone (09XXXXXXXXX)<span class="required-mark" aria-hidden="true">*</span></label>
                    <input id="phone" type="text" name="phone" required maxlength="11" pattern="09[0-9]{9}">
                </div>
                <div class="full">
                    <label for="address">Address<span class="required-mark" aria-hidden="true">*</span></label>
                    <textarea id="address" name="address" required></textarea>
                </div>
                <div class="full">
                    <label for="plan_id">Preferred plan<span class="required-mark" aria-hidden="true">*</span></label>
                    <select id="plan_id" name="plan_id" required>
                        <option value="">Select a plan</option>
                        <?php foreach ($plans as $plan): ?>
                            <?php $planId = (int)($plan['id'] ?? 0); ?>
                            <option value="<?= $planId ?>" <?= $selectedPlanId === $planId ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)($plan['name'] ?? '') . ' - ' . (string)($plan['speed'] ?? '') . ' - ₱' . number_format((float)($plan['price'] ?? 0), 2)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="full">
                    <label for="referred_by_phone">Referrer phone (optional)</label>
                    <input id="referred_by_phone" type="text" name="referred_by_phone" maxlength="11" pattern="09[0-9]{9}" placeholder="09XXXXXXXXX">
                    <div class="helper-text" style="margin-top:8px;color:#cbd5e1;font-size:13px;">
                        If an existing customer referred you, enter their registered phone number. They receive a bill discount when your application is approved.
                    </div>
                </div>
                <div class="full">
                    <button type="submit" class="btn-primary">Submit application</button>
                </div>
            </form>
        </div>
    </div>
</section>
