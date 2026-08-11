<?php
if (!class_exists('CmsService', false)) {
    require_once __DIR__ . '/../../app/Services/CmsService.php';
}
$cms = $cms ?? CmsService::get();
$title = $title ?? ($cms['company_name'] ?? 'FusionLink');
$primary = (string)($cms['primary_color'] ?? '#6d28d9');
$secondary = (string)($cms['secondary_color'] ?? '#8b5cf6');
$accent = (string)($cms['accent_color'] ?? '#a78bfa');
$text = '#ffffff';
$headerBg = (string)($cms['header_background'] ?? '#0f0f10');
$sectionBg = (string)($cms['section_background'] ?? '#111113');
$footerBg = (string)($cms['footer_background'] ?? '#0a0a0a');
$radius = (int)($cms['button_radius'] ?? 16);
$logo = asset_url((string)($cms['website_logo'] ?? ''));
$favicon = asset_url((string)($cms['website_favicon'] ?? ''));
$pwaScope = base_path() === '' ? '/' : base_path() . '/';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="manifest" href="<?= url('/manifest.webmanifest') ?>">
    <meta name="theme-color" content="<?= htmlspecialchars($headerBg) ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars((string)($cms['company_name'] ?? 'FusionLink')) ?>">
    <?php if ($favicon !== ''): ?>
        <link rel="icon" href="<?= htmlspecialchars($favicon) ?>">
        <link rel="apple-touch-icon" href="<?= htmlspecialchars($favicon) ?>">
    <?php else: ?>
        <link rel="icon" href="<?= url('/icon.svg') ?>" type="image/svg+xml">
        <link rel="apple-touch-icon" href="<?= htmlspecialchars(url('/icon-192.png')) ?>">
    <?php endif; ?>
    <script>
    window.FUSIONLINK_PWA = window.FUSIONLINK_PWA || {};
    window.FUSIONLINK_INSTALL_PROMPT = window.FUSIONLINK_INSTALL_PROMPT || null;
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        window.FUSIONLINK_INSTALL_PROMPT = e;
        window.FUSIONLINK_PWA.canInstall = true;
        document.dispatchEvent(new CustomEvent('fusionlink-install-ready'));
    });
    </script>
    <script>window.FUSIONLINK_BASE = <?= json_encode(base_path()) ?>;</script>
    <script src="<?= url('/assets/js/fusionlink-pwa.js') ?>" defer></script>
    <style>
        :root {
            --primary: <?= htmlspecialchars($primary) ?>;
            --secondary: <?= htmlspecialchars($secondary) ?>;
            --accent: <?= htmlspecialchars($accent) ?>;
            --text: <?= htmlspecialchars($text) ?>;
            --header-bg: <?= htmlspecialchars($headerBg) ?>;
            --section-bg: <?= htmlspecialchars($sectionBg) ?>;
            --footer-bg: <?= htmlspecialchars($footerBg) ?>;
            --radius: <?= $radius ?>px;
        }

        * { box-sizing: border-box; }

        img, video, svg {
            max-width: 100%;
            height: auto;
        }

        .page-header-inner {
            position: relative;
            min-height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .nav-toggle {
            display: none;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border: 1px solid rgba(255,255,255,.14);
            border-radius: 12px;
            background: rgba(255,255,255,.04);
            color: #ffffff;
            font-size: 22px;
            cursor: pointer;
        }

        .page-footer-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            margin-top: 16px;
        }

        .footer-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 10px 18px;
            border-radius: var(--radius);
            font-weight: 800;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
            border: 0;
        }

        .footer-btn-app {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #ffffff;
        }

        .footer-btn-login {
            border: 1px solid rgba(255,255,255,.18);
            color: #ffffff;
            background: rgba(255,255,255,.04);
        }

        .install-hint {
            display: none;
        }

        body {
            margin: 0;
            font-family: Inter, Arial, Helvetica, sans-serif;
            background: var(--section-bg);
            color: #ffffff;
            line-height: 1.6;
        }

        body,
        body p,
        body h1,
        body h2,
        body h3,
        body label,
        body .brand,
        body .section-lead,
        body .hero p,
        body .plan-card,
        body .info-card,
        body .cta-box,
        body .apply-card {
            color: #ffffff;
        }

        a { color: inherit; }

        .page-header {
            position: sticky;
            top: 0;
            z-index: 20;
            background: color-mix(in srgb, var(--header-bg) 92%, transparent);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .page-header-inner,
        .page-section-inner,
        .page-footer-inner {
            max-width: 1120px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            font-weight: 800;
            font-size: 18px;
        }

        .brand img {
            height: 40px;
            width: auto;
            display: block;
        }

        .page-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .page-nav a {
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 14px;
            color: #ffffff;
        }

        .page-nav a:hover {
            background: rgba(255,255,255,.06);
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            padding: 12px 18px;
            border: 0;
            border-radius: var(--radius);
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            font-weight: 800;
            cursor: pointer;
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            padding: 12px 18px;
            border-radius: var(--radius);
            border: 1px solid rgba(255,255,255,.14);
            color: #fff;
            font-weight: 700;
        }

        .page-section {
            padding: 72px 0;
        }

        .page-section.alt {
            background: rgba(255,255,255,.02);
        }

        .hero {
            padding: 96px 0 72px;
            background:
                radial-gradient(circle at top right, color-mix(in srgb, var(--primary) 24%, transparent), transparent 30%),
                linear-gradient(180deg, var(--header-bg), var(--section-bg));
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 32px;
            align-items: center;
        }

        .hero h1 {
            margin: 0 0 18px;
            font-size: clamp(34px, 5vw, 58px);
            line-height: 1.05;
            letter-spacing: -.03em;
        }

        .hero p {
            margin: 0 0 24px;
            color: #ffffff;
            font-size: 18px;
            max-width: 640px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .hero-media,
        .about-media {
            --frame-radius: 24px;
            position: relative;
            width: 100%;
            overflow: hidden;
            border-radius: var(--frame-radius);
            background: #0d0d10;
            isolation: isolate;
            box-shadow:
                0 24px 48px rgba(0, 0, 0, 0.35),
                inset 0 0 0 1px rgba(255, 255, 255, 0.06);
        }

        .hero-media {
            aspect-ratio: 16 / 9;
        }

        .about-media {
            aspect-ratio: 3 / 2;
        }

        .hero-media img,
        .about-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center center;
            display: block;
            border-radius: var(--frame-radius);
            transform: scale(1.02);
        }

        .section-title {
            margin: 0 0 12px;
            font-size: clamp(28px, 4vw, 40px);
            letter-spacing: -.02em;
        }

        .section-lead {
            margin: 0 0 28px;
            color: #ffffff;
            max-width: 760px;
        }

        .split {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 28px;
            align-items: center;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 18px;
        }

        .plan-card,
        .info-card,
        .apply-card {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 22px;
            padding: 22px;
        }

        .plan-card h3,
        .info-card h3 {
            margin: 0 0 8px;
            color: #ffffff;
        }

        .plan-card .plan-speed {
            color: #ffffff;
            font-size: 15px;
        }

        .plan-price {
            font-size: 28px;
            font-weight: 900;
            margin: 12px 0 16px;
            color: #ffffff;
            letter-spacing: -0.02em;
        }

        .info-card p {
            margin: 0;
            color: #ffffff;
        }

        .cta-box {
            padding: 36px;
            border-radius: 28px;
            background: linear-gradient(135deg, color-mix(in srgb, var(--primary) 28%, #111), color-mix(in srgb, var(--secondary) 18%, #111));
            border: 1px solid rgba(255,255,255,.08);
            text-align: center;
        }

        .apply-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .apply-grid .full { grid-column: 1 / -1; }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            font-size: 14px;
        }

        .required-mark {
            color: #f87171;
            margin-left: 2px;
        }

        input, select, textarea {
            width: 100%;
            padding: 14px 16px;
            border-radius: calc(var(--radius) - 2px);
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.04);
            color: #fff;
            font: inherit;
        }

        select {
            color-scheme: dark;
            background-color: #1a1a1c;
            cursor: pointer;
        }

        select option {
            color: #ffffff;
            background-color: #1a1a1c;
        }

        textarea { min-height: 110px; resize: vertical; }

        .alert {
            border-radius: 16px;
            padding: 14px 16px;
            margin-bottom: 18px;
            font-weight: 700;
        }

        .alert-success {
            background: rgba(34,197,94,.12);
            border: 1px solid rgba(34,197,94,.22);
            color: #ffffff;
        }

        .alert-error {
            background: rgba(239,68,68,.12);
            border: 1px solid rgba(239,68,68,.22);
            color: #ffffff;
        }

        .page-footer {
            background: var(--footer-bg);
            border-top: 1px solid rgba(255,255,255,.08);
            padding: 36px 0;
        }

        .page-footer p {
            margin: 0;
            color: #ffffff;
        }

        .login-link {
            font-size: 13px;
            color: #ffffff;
            text-decoration: none;
        }

        @media (max-width: 900px) {
            .hero-grid,
            .split,
            .apply-grid { grid-template-columns: 1fr; }

            .page-section {
                padding: 56px 0;
            }

            .hero {
                padding: 72px 0 56px;
            }

            .nav-toggle {
                display: inline-flex;
            }

            .page-nav {
                display: none;
                position: absolute;
                top: calc(100% + 8px);
                right: 0;
                left: 0;
                flex-direction: column;
                align-items: stretch;
                gap: 6px;
                padding: 14px;
                border-radius: 18px;
                border: 1px solid rgba(255,255,255,.1);
                background: color-mix(in srgb, var(--header-bg) 96%, #000);
                box-shadow: 0 18px 40px rgba(0,0,0,.35);
            }

            .page-nav.open {
                display: flex;
            }

            .page-nav a {
                width: 100%;
                text-align: center;
            }

            .card-grid {
                grid-template-columns: 1fr;
            }

            .cta-box {
                padding: 28px 20px;
            }
        }

        @media (max-width: 640px) {
            .page-header-inner,
            .page-section-inner,
            .page-footer-inner {
                padding: 0 16px;
            }

            .page-header-inner {
                min-height: 64px;
            }

            .brand {
                font-size: 16px;
                max-width: calc(100% - 56px);
            }

            .brand span {
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .hero h1 {
                font-size: clamp(28px, 8vw, 42px);
            }

            .hero p,
            .section-lead {
                font-size: 16px;
            }

            .hero-actions {
                flex-direction: column;
            }

            .hero-actions .btn-primary,
            .hero-actions .btn-secondary,
            .plan-card .btn-primary,
            .footer-btn {
                width: 100%;
            }

            .plan-card,
            .info-card,
            .apply-card {
                padding: 18px;
            }

            .plan-price {
                font-size: 24px;
            }

            .page-footer-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }

        @media (max-width: 380px) {
            .page-nav a {
                font-size: 13px;
                padding: 10px 12px;
            }
        }

        .fusionlink-install-banner {
            display: none;
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 2000;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            padding: 0.75rem 1rem calc(0.75rem + env(safe-area-inset-bottom, 0px));
            box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.25);
        }

        .fusionlink-install-banner.is-visible {
            display: block;
        }

        .fusionlink-install-banner__inner {
            max-width: 960px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            font-size: 0.95rem;
        }

        .fusionlink-install-banner__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .fusionlink-install-banner__btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            padding: 0 14px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            border: 0;
        }

        .fusionlink-install-banner__btn-light {
            background: #fff;
            color: #1e1b4b;
        }

        .fusionlink-install-banner__btn-outline {
            background: transparent;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.45);
        }

        @media (display-mode: standalone) {
            .fusionlink-install-banner { display: none !important; }
        }
    </style>
</head>
<body data-login-url="<?= htmlspecialchars(url('/login')) ?>">
    <header class="page-header">
        <div class="page-header-inner">
            <a class="brand" href="<?= url('/page') ?>#home">
                <?php if ($logo !== ''): ?>
                    <img src="<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars((string)($cms['company_name'] ?? 'Logo')) ?>">
                <?php endif; ?>
                <span><?= htmlspecialchars((string)($cms['company_name'] ?? 'FusionLink')) ?></span>
            </a>
            <nav class="page-nav" id="page-nav">
                <a href="<?= url('/page') ?>#home"><?= htmlspecialchars((string)($cms['nav_home_label'] ?? 'Home')) ?></a>
                <a href="<?= url('/page') ?>#about"><?= htmlspecialchars((string)($cms['nav_about_label'] ?? 'About')) ?></a>
                <a href="<?= url('/page') ?>#plans"><?= htmlspecialchars((string)($cms['nav_plans_label'] ?? 'Plans')) ?></a>
                <a href="<?= url('/page') ?>#contact"><?= htmlspecialchars((string)($cms['nav_contact_label'] ?? 'Contact')) ?></a>
                <a class="btn-primary" href="<?= url('/page') ?>#apply"><?= htmlspecialchars((string)($cms['nav_apply_label'] ?? 'Apply Now')) ?></a>
            </nav>
            <button type="button" class="nav-toggle" id="nav-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="page-nav">☰</button>
        </div>
    </header>

    <main>
        <?= $content ?? '' ?>
    </main>

    <footer class="page-footer">
        <div class="page-footer-inner">
            <p><?= htmlspecialchars((string)($cms['footer_text'] ?? '')) ?></p>
            <div class="page-footer-actions">
                <a class="footer-btn footer-btn-app" href="<?= url('/install.php') ?>" data-fusionlink-install>Install App</a>
                <a class="footer-btn footer-btn-login" href="<?= url('/login') ?>">ISP Billing Login</a>
                <a class="footer-btn footer-btn-login" href="<?= url('/page/existing') ?>">Existing Customer Setup</a>
                <a class="footer-btn footer-btn-login" href="<?= url('/page/book') ?>">Book Service Visit</a>
            </div>
        </div>
    </footer>

<?php require __DIR__ . '/../partials/pwa-install-banner.php'; ?>

<script>
(function () {
    var navToggle = document.getElementById('nav-toggle');
    var pageNav = document.getElementById('page-nav');

    if (navToggle && pageNav) {
        navToggle.addEventListener('click', function () {
            var isOpen = pageNav.classList.toggle('open');
            navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            navToggle.textContent = isOpen ? '✕' : '☰';
        });

        pageNav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                pageNav.classList.remove('open');
                navToggle.setAttribute('aria-expanded', 'false');
                navToggle.textContent = '☰';
            });
        });
    }
})();
</script>
</body>
</html>
