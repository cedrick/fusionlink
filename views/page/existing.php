<?php
$cms = $cms ?? [];
$done = $done ?? false;
$outcome = $outcome ?? '';
$message = $message ?? '';
$error = $error ?? '';
$companyName = (string)($cms['company_name'] ?? 'Our team');
?>

<section class="hero" style="padding:72px 0 48px;">
    <div class="page-section-inner" style="max-width:720px;">
        <h1 style="margin:0 0 12px;font-size:clamp(30px,5vw,44px);line-height:1.1;">Already our customer?</h1>
        <p style="margin:0;font-size:18px;max-width:640px;">
            Set up your online billing portal in about 1 minute. No new application — just confirm your details.
        </p>
    </div>
</section>

<section class="page-section alt" style="padding-top:0;">
    <div class="page-section-inner" style="max-width:720px;">
        <div class="apply-card">
            <?php if ($done && $message !== ''): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php if ($outcome === 'portal_ready' || $outcome === 'portal_exists'): ?>
                    <div style="margin-top:18px;display:flex;flex-wrap:wrap;gap:12px;">
                        <a class="btn-primary" href="<?= url('/login') ?>">Go to Billing Login</a>
                        <a class="btn-secondary" href="<?= url('/page') ?>">Back to Website</a>
                    </div>
                <?php else: ?>
                    <p style="margin:18px 0 0;color:#e2e8f0;">
                        You can close this page now. <?= htmlspecialchars($companyName) ?> will follow up by email.
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <?php if ($error !== ''): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="info-card" style="margin-bottom:20px;">
                    <p style="margin:0;">
                        Use the <strong>same phone number</strong> registered with <?= htmlspecialchars($companyName) ?>.
                        If we find your account, your portal login is created automatically.
                    </p>
                </div>

                <form method="POST" action="<?= url('/page/existing') ?>" class="apply-grid" style="margin-top:0;">
                    <?= csrf_field() ?>

                    <div class="full">
                        <label for="phone">Your registered phone (09XXXXXXXXX)<span class="required-mark" aria-hidden="true">*</span></label>
                        <input
                            id="phone"
                            type="tel"
                            name="phone"
                            required
                            maxlength="11"
                            pattern="09[0-9]{9}"
                            inputmode="numeric"
                            autocomplete="tel"
                            placeholder="09XXXXXXXXX"
                        >
                    </div>

                    <div class="full">
                        <label for="name">Full name<span class="required-mark" aria-hidden="true">*</span></label>
                        <input id="name" type="text" name="name" required autocomplete="name" placeholder="As registered with us">
                    </div>

                    <div class="full">
                        <label for="email">Email for portal login<span class="required-mark" aria-hidden="true">*</span></label>
                        <input id="email" type="email" name="email" required autocomplete="email" placeholder="you@example.com">
                    </div>

                    <div class="full">
                        <label for="address">Service address <span style="font-weight:600;opacity:.75;">(optional)</span></label>
                        <textarea id="address" name="address" placeholder="Only if you want to update your address"></textarea>
                    </div>

                    <div class="full">
                        <button type="submit" class="btn-primary" style="width:100%;min-height:52px;font-size:16px;">
                            Confirm &amp; Set Up Portal
                        </button>
                    </div>
                </form>

                <p style="margin:20px 0 0;font-size:14px;color:#cbd5e1;">
                    New customer? <a href="<?= url('/page') ?>#apply" style="color:#c4b5fd;">Apply for service here</a>.
                    Already have portal access? <a href="<?= url('/login') ?>" style="color:#c4b5fd;">Log in</a>.
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>
