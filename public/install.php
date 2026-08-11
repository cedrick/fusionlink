<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';

if (!class_exists('CmsService', false)) {
    require_once __DIR__ . '/../app/Services/CmsService.php';
}

$cms = CmsService::get();
$company = (string)($cms['company_name'] ?? 'FusionLink');
$loginUrl = url('/login');
$iconUrl = url('/icon-192.png');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Install <?= htmlspecialchars($company) ?></title>
    <link rel="manifest" href="<?= url('/manifest.webmanifest') ?>">
    <link rel="icon" href="<?= url('/icon-192.png') ?>" type="image/png">
    <meta name="theme-color" content="#6d28d9">
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
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: Inter, Arial, sans-serif;
            background: #0f0f10;
            color: #fff;
        }
        .card {
            width: min(100%, 420px);
            text-align: center;
            padding: 36px 28px;
            border-radius: 24px;
            border: 1px solid rgba(255,255,255,.1);
            background: rgba(255,255,255,.04);
        }
        .card img {
            width: 96px;
            height: 96px;
            border-radius: 20px;
            margin-bottom: 20px;
        }
        .card h1 {
            margin: 0 0 24px;
            font-size: 28px;
        }
        .install-btn {
            width: 100%;
            min-height: 52px;
            border: 0;
            border-radius: 14px;
            background: linear-gradient(135deg, #6d28d9, #8b5cf6);
            color: #fff;
            font-size: 17px;
            font-weight: 800;
            cursor: pointer;
        }
        .back {
            display: inline-block;
            margin-top: 18px;
            color: #fff;
            text-decoration: none;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="card">
        <img src="<?= htmlspecialchars($iconUrl) ?>" alt="<?= htmlspecialchars($company) ?>">
        <h1><?= htmlspecialchars($company) ?></h1>
        <button type="button" class="install-btn" id="fusionlinkInstallBtnPage">Install app</button>
        <a class="back" href="<?= url('/page') ?>">← Back to website</a>
    </div>
    <script>window.FUSIONLINK_BASE = <?= json_encode(base_path()) ?>;</script>
    <script src="<?= url('/assets/js/fusionlink-pwa.js') ?>" defer></script>
    <script>
    (function () {
        var btn = document.getElementById('fusionlinkInstallBtnPage');
        var loginUrl = <?= json_encode($loginUrl) ?>;

        function tryInstall() {
            if (window.FUSIONLINK_PWA && window.FUSIONLINK_PWA.isStandalone) {
                window.location.href = loginUrl;
                return;
            }
            if (window.fusionlinkInstallApp) {
                window.fusionlinkInstallApp({ loginUrl: loginUrl });
                return;
            }
            try { sessionStorage.setItem('fusionlink_want_install', '1'); } catch (e) {}
            window.location.href = loginUrl;
        }

        if (btn) {
            btn.addEventListener('click', tryInstall);
        }
    })();
    </script>
</body>
</html>
