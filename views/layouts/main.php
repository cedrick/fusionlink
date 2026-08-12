<?php
$systemCompanyName = 'ISP-BILLING-LITE';

try {
    $config = require __DIR__ . '/../../config/database.php';

    $dbName = $config['db'] ?? ($config['name'] ?? null);
    if ($dbName) {
        $host = $config['host'] ?? '127.0.0.1';
        $user = $config['user'] ?? '';
        $pass = $config['pass'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";

        $layoutPdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $stmt = $layoutPdo->query("
            SELECT company_name
            FROM settings
            ORDER BY id ASC
            LIMIT 1
        ");
        $settingsRow = $stmt->fetch();

        if (!empty($settingsRow['company_name'])) {
            $systemCompanyName = trim((string)$settingsRow['company_name']);
        }
    }
} catch (Throwable $e) {
    $systemCompanyName = 'ISP-BILLING-LITE';
}

$role = $_SESSION['user']['role'] ?? '';
$isLoggedIn = !empty($_SESSION['user']);
$isAdmin = in_array($role, ['ROLE_ADMIN', 'ADMIN', 'admin'], true);
$isStaff = in_array($role, ['ROLE_ADMIN', 'ROLE_STAFF', 'ADMIN', 'STAFF', 'admin', 'staff'], true);
$currentPath = request_path();
$isAuthPage = in_array($currentPath, ['/login', '/verify-otp'], true);
$pwaScope = base_path() === '' ? '/' : base_path() . '/';

$userEmail = $_SESSION['user']['email'] ?? 'User';
$userInitial = strtoupper(substr($userEmail, 0, 1));

$isActive = function (array $paths) use ($currentPath): string {
    foreach ($paths as $path) {
        if ($currentPath === $path) {
            return 'active';
        }
    }
    return '';
};
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= htmlspecialchars($title ?? $systemCompanyName) ?></title>
<link rel="icon" href="<?= url('/icon.svg') ?>" type="image/svg+xml">
<link rel="manifest" href="<?= url('/manifest.webmanifest') ?>">
<link rel="apple-touch-icon" href="<?= url('/icon-192.png') ?>">
<meta name="theme-color" content="#050505">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($systemCompanyName) ?>">
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
    --bg: #050505;
    --bg-2: #090909;
    --bg-3: #0d0d0f;
    --panel: rgba(255,255,255,0.03);
    --panel-2: rgba(255,255,255,0.05);
    --card: #0c0c0d;
    --card-2: #111113;
    --card-3: #151518;
    --text: #fafafa;
    --muted: #a3a3a3;
    --muted-2: #737373;
    --line: rgba(255,255,255,0.08);
    --line-2: rgba(255,255,255,0.12);
    --primary: #ffffff;
    --primary-text: #050505;
    --sidebar: #030303;
    --sidebar-2: #070707;
    --sidebar-3: #0c0c0d;
    --success-bg: rgba(34,197,94,.12);
    --success-text: #86efac;
    --warning-bg: rgba(245,158,11,.12);
    --warning-text: #fcd34d;
    --danger-bg: rgba(239,68,68,.12);
    --danger-text: #fca5a5;
    --info-bg: rgba(255,255,255,.06);
    --info-text: #e5e5e5;
    --shadow-1: 0 10px 30px rgba(0,0,0,.32);
    --shadow-2: 0 18px 50px rgba(0,0,0,.40);
    --radius-xl: 8px;
    --radius-lg: 6px;
    --radius-md: 6px;
    --radius-sm: 4px;
}

* {
    box-sizing: border-box;
}

html, body {
    margin: 0;
    padding: 0;
    min-height: 100%;
    font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    color: var(--text);
    background:
        radial-gradient(circle at top left, rgba(255,255,255,.04), transparent 18%),
        radial-gradient(circle at bottom right, rgba(255,255,255,.03), transparent 18%),
        linear-gradient(180deg, #020202 0%, #050505 55%, #090909 100%);
    overflow-x: hidden;
    -webkit-text-size-adjust: 100%;
    text-size-adjust: 100%;
    -webkit-tap-highlight-color: transparent;
}

input, select, textarea, button {
    font: inherit;
    max-width: 100%;
}

button, input[type="submit"], input[type="button"] {
    -webkit-appearance: none;
    appearance: none;
}

img, svg, video {
    max-width: 100%;
    height: auto;
}

body {
    overflow-x: hidden;
    -webkit-text-size-adjust: 100%;
}

button,
.btn,
.btn-primary,
.btn-secondary,
input,
select,
textarea {
    max-width: 100%;
}

@media (max-width: 480px) {
    .main {
        padding: 10px;
    }

    .page-card {
        padding: 12px;
        border-radius: 6px;
    }

    .table-wrap {
        margin-left: -12px;
        margin-right: -12px;
        padding-left: 12px;
        padding-right: 12px;
    }

    table {
        min-width: 560px;
    }
}

a {
    color: inherit;
}

.app-shell {
    min-height: 100vh;
}

.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 268px;
    height: 100vh;
    background: #050505;
    color: #fff;
    padding: 0;
    overflow-y: auto;
    border-right: 1px solid var(--line);
    box-shadow: none;
    z-index: 1000;
    display: flex;
    flex-direction: column;
}

.brand {
    margin: 0;
    padding: 16px 16px 14px;
    border-bottom: 1px solid var(--line);
    background: #030303;
}

.brand-title {
    font-size: 14px;
    font-weight: 700;
    letter-spacing: .04em;
    line-height: 1.3;
    color: #fff;
    text-transform: uppercase;
}

.brand-subtitle {
    margin-top: 4px;
    font-size: 11px;
    color: #737373;
    letter-spacing: .08em;
    text-transform: uppercase;
    font-weight: 600;
}

.user-panel {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 0;
    background: transparent;
    border: 0;
    border-bottom: 1px solid var(--line);
    margin: 0;
    box-shadow: none;
}

.user-avatar {
    width: 34px;
    height: 34px;
    border-radius: 4px;
    background: #17171a;
    border: 1px solid rgba(255,255,255,.12);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    color: #f5f5f5;
    flex: 0 0 auto;
}

.user-meta {
    min-width: 0;
}

.user-role {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .10em;
    color: #737373;
    margin-bottom: 3px;
    font-weight: 700;
}

.user-name {
    font-size: 12px;
    font-weight: 600;
    color: #e5e5e5;
    line-height: 1.35;
    word-break: break-word;
}

.sidebar-nav {
    padding: 12px 10px 18px;
    flex: 1;
}

.side-section {
    margin-bottom: 16px;
}

.side-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: #525252;
    margin: 0 0 6px 8px;
    font-weight: 700;
}

.side-links {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.side-links a {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #d4d4d4;
    text-decoration: none;
    padding: 9px 10px;
    border-radius: 4px;
    background: transparent;
    transition: background .12s ease, color .12s ease, border-color .12s ease;
    font-size: 13px;
    font-weight: 500;
    line-height: 1.2;
    border: 1px solid transparent;
}

.side-links a:hover {
    background: rgba(255,255,255,.04);
    border-color: rgba(255,255,255,.06);
    color: #fff;
    transform: none;
}

.side-links a.active {
    background: #111113;
    color: #fff;
    box-shadow: none;
    border-color: rgba(255,255,255,.12);
    border-left: 2px solid #fff;
    padding-left: 9px;
    font-weight: 600;
}

.nav-icon {
    width: 26px;
    height: 26px;
    text-align: center;
    font-size: 13px;
    line-height: 1;
    flex: 0 0 26px;
    display: inline-grid;
    place-items: center;
    border: 1px solid rgba(255,255,255,.10);
    background: #0c0c0d;
    border-radius: 4px;
    color: #f5f5f5;
}

.nav-icon svg {
    width: 14px;
    height: 14px;
    fill: currentColor;
}

.side-links a.active .nav-icon {
    background: #17171a;
    border-color: rgba(255,255,255,.18);
}

.main {
    margin-left: 268px;
    min-height: 100vh;
    padding: 22px;
}

.page-card {
    background: #0c0c0d;
    border: 1px solid rgba(255,255,255,.10);
    border-radius: 8px;
    padding: 22px;
    box-shadow: 0 12px 32px rgba(0,0,0,.28);
    backdrop-filter: none;
}

/* ===== Enterprise shared UI ===== */
.auth-page {
    min-height: 100vh;
    background: linear-gradient(180deg, #020202 0%, #050505 55%, #090909 100%);
    padding: 32px 20px;
}

.auth-wrap {
    max-width: 1120px;
    margin: 0 auto;
    min-height: calc(100vh - 64px);
    display: grid;
    grid-template-columns: 1.05fr .95fr;
    gap: 24px;
    align-items: center;
}

.auth-brand {
    color: #fff;
    padding: 20px 12px;
}

.auth-kicker {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 4px;
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.10);
    color: #a3a3a3;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .10em;
    text-transform: uppercase;
    margin-bottom: 14px;
}

.auth-title {
    margin: 0 0 12px;
    font-size: 42px;
    line-height: 1.1;
    font-weight: 650;
    letter-spacing: -.02em;
}

.auth-text {
    margin: 0 0 18px;
    font-size: 15px;
    line-height: 1.65;
    color: #a3a3a3;
    max-width: 520px;
}

.auth-list {
    display: grid;
    gap: 10px;
    margin-top: 14px;
}

.auth-list-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #d4d4d4;
    font-size: 14px;
    font-weight: 500;
}

.auth-list-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #fff;
    box-shadow: none;
    flex: 0 0 auto;
}

.auth-card {
    background: #0c0c0d;
    border-radius: 8px;
    padding: 28px 24px;
    box-shadow: 0 12px 32px rgba(0,0,0,.30);
    border: 1px solid rgba(255,255,255,.10);
    color: var(--text);
}

h1 {
    margin: 0 0 10px;
    font-size: 28px;
    font-weight: 650;
    color: #fff;
    letter-spacing: -.02em;
    line-height: 1.2;
}

h2, h3 {
    color: #fff;
    font-weight: 650;
}

p {
    margin: 0;
}

.page-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 12px 0 16px;
}

.btn,
.btn:visited,
button.btn,
input.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    border: 1px solid rgba(255,255,255,.14);
    border-radius: 4px;
    padding: 9px 14px;
    min-height: 38px;
    font-size: 13px;
    cursor: pointer;
    background: #ffffff;
    color: #050505 !important;
    font-weight: 650;
    transition: background .12s ease, border-color .12s ease, opacity .12s ease;
    box-shadow: none;
    appearance: none;
    -webkit-appearance: none;
}

.btn:hover,
button.btn:hover,
input.btn:hover {
    transform: none;
    opacity: .92;
}

.btn-secondary,
.btn-secondary:visited,
button.btn-secondary,
input.btn-secondary {
    background: #111113;
    color: #ffffff !important;
    border-color: rgba(255,255,255,.14);
    box-shadow: none;
}

.btn-secondary:hover,
button.btn-secondary:hover,
input.btn-secondary:hover {
    background: #17171a;
    color: #ffffff !important;
}

.btn-danger,
.btn-danger:visited,
button.btn-danger,
input.btn-danger {
    background: #b91c1c;
    color: #fff !important;
    border-color: rgba(239,68,68,.35);
}

.btn-success,
.btn-success:visited,
button.btn-success,
input.btn-success {
    background: #15803d;
    color: #fff !important;
    border-color: rgba(34,197,94,.30);
}

.btn-small {
    padding: 6px 10px;
    min-height: 32px;
    font-size: 12px;
    border-radius: 4px;
    font-weight: 600;
}

.table-wrap {
    width: 100%;
    overflow-x: auto;
    border: 1px solid rgba(255,255,255,.10);
    border-radius: 6px;
    background: #0a0a0b;
    box-shadow: none;
}

.table {
    width: 100%;
    border-collapse: collapse;
    min-width: 720px;
}

.table th,
.table td {
    padding: 11px 12px;
    border-bottom: 1px solid rgba(255,255,255,.07);
    text-align: left;
    vertical-align: middle;
    color: #e5e5e5;
    font-size: 13px;
}

.table th {
    background: #111113;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #a3a3a3;
}

.table td {
    background: transparent;
}

.table tr:hover td {
    background: rgba(255,255,255,.02);
}

.table tr:last-child td {
    border-bottom: 0;
}

.table .actions {
    white-space: nowrap;
}

.table .actions .btn,
.table .actions .btn-secondary,
.table .actions .btn-danger,
.table .actions .btn-success {
    margin: 2px 2px 2px 0;
}

.badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    border: 1px solid rgba(255,255,255,.10);
}

.badge-success {
    background: var(--success-bg);
    color: var(--success-text);
}

.badge-warning {
    background: var(--warning-bg);
    color: var(--warning-text);
}

.badge-danger {
    background: var(--danger-bg);
    color: var(--danger-text);
}

.badge-info {
    background: var(--info-bg);
    color: var(--info-text);
}

.empty-state {
    padding: 18px;
    color: var(--muted);
    font-size: 13px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group.full {
    grid-column: 1 / -1;
}

label {
    font-size: 12px;
    font-weight: 650;
    color: #d4d4d4;
    letter-spacing: .02em;
}

input,
select,
textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 4px;
    font-size: 13px;
    background: #111113;
    color: #fff;
    transition: border-color .12s ease, box-shadow .12s ease, background .12s ease;
    min-height: 38px;
}

input[type="radio"],
input[type="checkbox"] {
    width: 14px;
    height: 14px;
    min-width: 14px;
    min-height: 0;
    padding: 0;
    margin: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
    accent-color: #ffffff;
    cursor: pointer;
    flex: none;
    appearance: auto;
}

input::placeholder,
textarea::placeholder {
    color: #737373;
}

input:focus,
select:focus,
textarea:focus {
    outline: none;
    border-color: rgba(255,255,255,.28);
    box-shadow: 0 0 0 2px rgba(255,255,255,.06);
    background: #141416;
}

select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image:
        linear-gradient(45deg, transparent 50%, rgba(255,255,255,.75) 50%),
        linear-gradient(135deg, rgba(255,255,255,.75) 50%, transparent 50%);
    background-position:
        calc(100% - 16px) calc(50% - 3px),
        calc(100% - 10px) calc(50% - 3px);
    background-size: 6px 6px, 6px 6px;
    background-repeat: no-repeat;
    padding-right: 32px;
}

select option {
    color: #f5f5f5;
    background: #111113;
}

textarea {
    min-height: 110px;
    resize: vertical;
}

.alert-error {
    background: rgba(239,68,68,.10);
    color: #fecaca;
    border: 1px solid rgba(239,68,68,.22);
    border-radius: 4px;
    padding: 10px 12px;
    margin-bottom: 12px;
    font-size: 13px;
}

.alert-success {
    background: rgba(34,197,94,.10);
    color: #bbf7d0;
    border: 1px solid rgba(34,197,94,.22);
    border-radius: 4px;
    padding: 10px 12px;
    margin-bottom: 12px;
    font-size: 13px;
}

.inline-form {
    display: inline-block;
    margin: 0;
}

.quick-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1px;
    margin-bottom: 16px;
    background: rgba(255,255,255,.10);
    border: 1px solid rgba(255,255,255,.10);
}

.quick-card {
    background: #0c0c0d;
    border: 0;
    border-radius: 0;
    padding: 14px 16px;
    box-shadow: none;
}

.quick-card .label {
    font-size: 11px;
    color: #737373;
    margin-bottom: 8px;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.quick-card .value {
    font-size: 24px;
    font-weight: 650;
    line-height: 1.15;
    color: #fff;
    font-variant-numeric: tabular-nums;
}

.mobile-topbar {
    display: none;
    align-items: center;
    gap: 10px;
    background: #050505;
    color: #fff;
    padding: 10px 12px;
    font-weight: 700;
    font-size: 13px;
    letter-spacing: .06em;
    text-transform: uppercase;
    border-bottom: 1px solid var(--line);
    position: sticky;
    top: 0;
    z-index: 1100;
}

.mobile-nav-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 4px;
    border: 1px solid rgba(255,255,255,.12);
    background: #111113;
    color: #fff;
    font-size: 16px;
    cursor: pointer;
    -webkit-appearance: none;
    appearance: none;
}

.mobile-topbar-title {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sidebar-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.55);
    z-index: 999;
}


.pagination {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
    margin-top: 14px;
}

.pagination a,
.pagination span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    min-height: 34px;
    padding: 0 10px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 650;
    font-size: 13px;
    border: 1px solid rgba(255,255,255,.10);
    background: #111113;
    color: #e5e5e5;
}

.pagination a:hover {
    background: #17171a;
}

.pagination .active {
    background: #ffffff;
    color: #000;
    border-color: rgba(255,255,255,.20);
}

.toolbar-card,
.filter-card,
.table-card,
.report-card,
.panel,
.form-card {
    background: #0c0c0d;
    border: 1px solid rgba(255,255,255,.10);
    border-radius: 6px;
    box-shadow: none;
    color: #fff;
    padding: 16px;
}

.toolbar-form {
    display: grid;
    gap: 12px;
}

.toolbar-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.toolbar-group label {
    font-size: 12px;
    font-weight: 650;
    color: #d4d4d4;
}

.toolbar-input,
.toolbar-select {
    min-height: 38px;
    padding: 10px 12px;
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 4px;
    font-size: 13px;
    background: #111113;
    color: #fff;
}

.toolbar-input::placeholder {
    color: #737373;
}

.toolbar-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: end;
}

.table-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}

.table-meta {
    font-size: 13px;
    color: var(--muted);
    font-weight: 500;
}

.sort-link {
    color: inherit;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.sort-arrow {
    font-size: 11px;
    color: var(--muted);
}

.toolbar-submit-group {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: end;
}

.section-note {
    font-size: 13px;
    color: var(--muted);
    line-height: 1.55;
}

.metric-pill {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border-radius: 3px;
    background: #111113;
    border: 1px solid rgba(255,255,255,.10);
    color: #e5e5e5;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.select-all-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    border: 1px solid rgba(255,255,255,.14);
    border-radius: 4px;
    padding: 9px 14px;
    min-height: 38px;
    font-size: 13px;
    cursor: pointer;
    background: #111113;
    color: #fff;
    font-weight: 650;
    transition: background .12s ease;
    box-shadow: none;
}

.select-all-btn:hover {
    transform: none;
    background: #17171a;
    opacity: 1;
}

.checkbox-cell {
    width: 42px;
    text-align: center;
}

.checkbox-cell input {
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: #ffffff;
    min-height: 0;
}

.form-card {
    padding: 18px;
}

.form-section-title {
    margin: 0 0 12px;
    font-size: 16px;
    font-weight: 650;
    color: #fff;
    letter-spacing: -.01em;
}

.form-help {
    color: var(--muted);
    font-size: 13px;
    margin-bottom: 14px;
    line-height: 1.55;
}

.section-block {
    margin-top: 16px;
}

.info-card,
.stat-card,
.card,
.content-card,
.settings-card,
.cms-card {
    background: #0c0c0d;
    border: 1px solid rgba(255,255,255,.10);
    border-radius: 6px;
    box-shadow: none;
}

@media (max-width: 980px) {
    .auth-wrap {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .auth-brand {
        padding: 0;
    }

    .auth-title {
        font-size: 38px;
    }

    .auth-text {
        font-size: 16px;
    }
}

@media (max-width: 900px) {
    .mobile-topbar {
        display: flex;
    }

    .sidebar-backdrop {
        display: none;
    }

    body.nav-open .sidebar-backdrop {
        display: block;
    }

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: min(268px, 88vw);
        height: 100vh;
        transform: translateX(-105%);
        transition: transform .24s ease;
        z-index: 1001;
        padding: 0;
        box-shadow: 0 12px 32px rgba(0,0,0,.45);
    }

    body.nav-open .sidebar {
        transform: translateX(0);
    }

    .brand {
        display: block;
    }

    .user-panel {
        margin-bottom: 0;
    }

    .side-links {
        flex-direction: column;
        flex-wrap: nowrap;
    }

    .side-links a {
        background: transparent;
    }

    .main {
        margin-left: 0;
        padding: 14px;
    }

    .page-card {
        padding: 14px;
        border-radius: 6px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    h1 {
        font-size: 24px;
    }

    .page-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .page-actions .btn,
    .page-actions .btn-secondary,
    .page-actions form {
        width: 100%;
    }

    .toolbar-form {
        grid-template-columns: 1fr !important;
    }

    .toolbar-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .table .actions {
        white-space: normal;
    }

    .table .actions .inline-form {
        display: block;
        margin-top: 6px;
    }
}

@media (max-width: 640px) {
    .auth-page {
        padding: 18px 12px;
    }

    .auth-card {
        padding: 22px 16px;
        border-radius: 8px;
    }

    .auth-title {
        font-size: 28px;
    }
}

@media (max-width: 480px) {
    .main {
        padding: 10px;
    }

    .page-card {
        padding: 12px;
        border-radius: 6px;
    }

    h1 {
        font-size: 22px;
    }

    .quick-card .value {
        font-size: 22px;
    }
}

.fusionlink-install-banner {
    display: none;
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 2000;
    background: #0c0c0d;
    color: #fff;
    border-top: 1px solid rgba(255,255,255,.12);
    padding: 0.75rem 1rem calc(0.75rem + env(safe-area-inset-bottom, 0px));
    box-shadow: 0 -8px 24px rgba(0, 0, 0, 0.35);
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
    min-height: 34px;
    padding: 0 12px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 650;
    cursor: pointer;
    text-decoration: none;
    border: 0;
}

.fusionlink-install-banner__btn-light {
    background: #fff;
    color: #050505;
}

.fusionlink-install-banner__btn-outline {
    background: transparent;
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.35);
}

@media (display-mode: standalone) {
    .fusionlink-install-banner { display: none !important; }
}
</style>
</head>

<body data-login-url="<?= htmlspecialchars(url('/login')) ?>">

<?php if ($isAuthPage || !$isLoggedIn): ?>
    <div class="auth-page">
        <div class="auth-wrap">
            <div class="auth-brand">
                <div class="auth-kicker">ISP Billing Platform</div>
                <h1 class="auth-title">Manage billing, customers, and payments in one place.</h1>
                <p class="auth-text">
                    <?= htmlspecialchars($systemCompanyName) ?> helps you manage subscribers, generate invoices, verify payments,
                    and track outstanding balances with a clean and premium workflow.
                </p>

                <div class="auth-list">
                    <div class="auth-list-item">
                        <span class="auth-list-dot"></span>
                        <span>Customer and subscription management</span>
                    </div>
                    <div class="auth-list-item">
                        <span class="auth-list-dot"></span>
                        <span>Invoice generation and PDF export</span>
                    </div>
                    <div class="auth-list-item">
                        <span class="auth-list-dot"></span>
                        <span>Payment verification and email notifications</span>
                    </div>
                    <div class="auth-list-item">
                        <span class="auth-list-dot"></span>
                        <span>Outstanding and revenue reporting</span>
                    </div>
                </div>
            </div>

            <div class="auth-card">
                <?= $content ?? '' ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="mobile-topbar">
        <button type="button" class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Open navigation menu">☰</button>
        <span class="mobile-topbar-title"><?= htmlspecialchars($systemCompanyName) ?></span>
    </div>
    <div class="sidebar-backdrop" id="sidebarBackdrop" hidden></div>

    <div class="app-shell">
        <aside class="sidebar">
            <?php
            $navIcon = static function (string $name): string {
                $icons = [
                    'home' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 3 10v11h6v-6h6v6h6V10L12 3Z"/></svg>',
                    'users' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm-8 0a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-2.67 0-8 1.34-8 4v2h10v-2c0-1.54.78-2.88 2-3.72A12.3 12.3 0 0 0 8 13Zm8 0c-.29 0-.62.02-.97.05A5.4 5.4 0 0 1 17 17v2h7v-2c0-2.66-5.33-4-8-4Z"/></svg>',
                    'signal' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 17h3v5H2zm5-4h3v9H7zm5-4h3v13h-3zm5-5h3v18h-3z"/></svg>',
                    'invoice' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16l4-2 4 2 4-2 4 2V8l-6-6Zm0 2.5L17.5 8H14V4.5ZM8 11h8v2H8v-2Zm0 4h8v2H8v-2Z"/></svg>',
                    'card' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 4H4V6h16v2Zm0 10H4v-6h16v6Z"/></svg>',
                    'mail' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 4-8 5L4 8V6l8 5 8-5v2Z"/></svg>',
                    'calendar' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 16H5V10h14v10Zm0-12H5V6h14v2Z"/></svg>',
                    'tool' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22.7 19.3 13.6 10.2a6 6 0 0 0-7.5-7.5l3.1 3.1-2.1 2.1-3.1-3.1a6 6 0 0 0 7.5 7.5l9.1 9.1a1 1 0 0 0 1.4 0l1.7-1.7a1 1 0 0 0 0-1.4Z"/></svg>',
                    'box' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 8.5 12 3 3 8.5V16l9 5.5L21 16V8.5ZM12 5.2l6.3 3.8L12 12.8 5.7 9 12 5.2ZM5 10.7l6 3.7v5.6l-6-3.7v-5.6Zm8 9.3v-5.6l6-3.7v5.6l-6 3.7Z"/></svg>',
                    'chart' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18v-2H5V3H3Zm4 14h2V9H7v8Zm4 0h2V5h-2v12Zm4 0h2v-6h-2v6Z"/></svg>',
                    'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3Zm0 2.2 6 2.2v4.6c0 3.9-2.5 7.6-6 8.8-3.5-1.2-6-4.9-6-8.8V6.4l6-2.2Z"/></svg>',
                    'list' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h2v2H4V6Zm4 0h12v2H8V6ZM4 11h2v2H4v-2Zm4 0h12v2H8v-2ZM4 16h2v2H4v-2Zm4 0h12v2H8v-2Z"/></svg>',
                    'gear' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19.4 13a7.7 7.7 0 0 0 0-2l2-1.5-2-3.5-2.4 1a7.4 7.4 0 0 0-1.7-1L15 3h-6l-.3 2.9a7.4 7.4 0 0 0-1.7 1L4.6 6l-2 3.5 2 1.5a7.7 7.7 0 0 0 0 2l-2 1.5 2 3.5 2.4-1a7.4 7.4 0 0 0 1.7 1L9 21h6l.3-2.9a7.4 7.4 0 0 0 1.7-1l2.4 1 2-3.5-2-1.5ZM12 15.5A3.5 3.5 0 1 1 15.5 12 3.5 3.5 0 0 1 12 15.5Z"/></svg>',
                    'grid' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h8v8H3V3Zm10 0h8v8h-8V3ZM3 13h8v8H3v-8Zm10 0h8v8h-8v-8Z"/></svg>',
                    'logout' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17v2H4V5h6v2H6v10h4Zm9-5-4-4v3H9v2h6v3l4-4Z"/></svg>',
                ];

                return $icons[$name] ?? $icons['grid'];
            };
            ?>
            <div class="brand">
                <div class="brand-title"><?= htmlspecialchars($systemCompanyName) ?></div>
                <div class="brand-subtitle">Enterprise Console</div>
            </div>

            <div class="user-panel">
                <div class="user-avatar"><?= htmlspecialchars($userInitial) ?></div>
                <div class="user-meta">
                    <div class="user-role"><?= htmlspecialchars(str_replace('ROLE_', '', (string)($role ?: 'USER'))) ?></div>
                    <div class="user-name"><?= htmlspecialchars($userEmail) ?></div>
                </div>
            </div>

            <div class="sidebar-nav">
                <div class="side-section">
                    <div class="side-label">Workspace</div>
                    <div class="side-links">
                        <a class="<?= $isActive(['/dashboard']) ?>" href="<?= url('/dashboard') ?>"><span class="nav-icon"><?= $navIcon('home') ?></span><span>Home</span></a>
                    </div>
                </div>

                <?php if ($isStaff): ?>
                    <div class="side-section">
                        <div class="side-label">Operations</div>
                        <div class="side-links">
                            <a class="<?= $isActive(['/customers', '/customers/create', '/customers/edit']) ?>" href="<?= url('/customers') ?>"><span class="nav-icon"><?= $navIcon('users') ?></span><span>Customers</span></a>
                            <a class="<?= $isActive(['/subscriptions', '/subscriptions/create', '/subscriptions/edit']) ?>" href="<?= url('/subscriptions') ?>"><span class="nav-icon"><?= $navIcon('signal') ?></span><span>Subscriptions</span></a>
                            <a class="<?= $isActive(['/invoices', '/invoices/create', '/invoices/view', '/invoices/pdf']) ?>" href="<?= url('/invoices') ?>"><span class="nav-icon"><?= $navIcon('invoice') ?></span><span>Invoices</span></a>
                            <a class="<?= $isActive(['/payments', '/payments/create']) ?>" href="<?= url('/payments') ?>"><span class="nav-icon"><?= $navIcon('card') ?></span><span>Payments</span></a>
                            <a class="<?= $isActive(['/inquiries']) ?>" href="<?= url('/inquiries') ?>"><span class="nav-icon"><?= $navIcon('mail') ?></span><span>Inquiries</span></a>
                            <a class="<?= $isActive(['/bookings', '/bookings/create', '/bookings/edit']) ?>" href="<?= url('/bookings') ?>"><span class="nav-icon"><?= $navIcon('calendar') ?></span><span>Bookings</span></a>
                            <a class="<?= $isActive(['/personnel']) ?>" href="<?= url('/personnel') ?>"><span class="nav-icon"><?= $navIcon('tool') ?></span><span>Personnel</span></a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($isAdmin): ?>
                    <div class="side-section">
                        <div class="side-label">Administration</div>
                        <div class="side-links">
                            <a class="<?= $isActive(['/plans', '/plans/create', '/plans/edit']) ?>" href="<?= url('/plans') ?>"><span class="nav-icon"><?= $navIcon('box') ?></span><span>Plans</span></a>
                            <a class="<?= $isActive(['/reports/revenue', '/reports/outstanding']) ?>" href="<?= url('/reports/revenue') ?>"><span class="nav-icon"><?= $navIcon('chart') ?></span><span>Reports</span></a>
                            <a class="<?= $isActive(['/users']) ?>" href="<?= url('/users') ?>"><span class="nav-icon"><?= $navIcon('shield') ?></span><span>Users</span></a>
                            <a class="<?= $isActive(['/activity-logs']) ?>" href="<?= url('/activity-logs') ?>"><span class="nav-icon"><?= $navIcon('list') ?></span><span>Activity Logs</span></a>
                            <a class="<?= $isActive(['/settings']) ?>" href="<?= url('/settings') ?>"><span class="nav-icon"><?= $navIcon('gear') ?></span><span>Settings</span></a>
                            <a class="<?= $isActive(['/cms', '/cms/dashboard']) ?>" href="<?= url('/cms/dashboard') ?>"><span class="nav-icon"><?= $navIcon('grid') ?></span><span>CMS</span></a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="side-section">
                    <div class="side-label">Account</div>
                    <div class="side-links">
                        <a href="<?= url('/logout') ?>"><span class="nav-icon"><?= $navIcon('logout') ?></span><span>Logout</span></a>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main">
            <div class="page-card">
                <?= $content ?? '' ?>
            </div>
        </main>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/pwa-install-banner.php'; ?>

<?php if ($isLoggedIn && !$isAuthPage): ?>
<script>
(function () {
    var toggle = document.getElementById('mobileNavToggle');
    var backdrop = document.getElementById('sidebarBackdrop');
    if (!toggle || !backdrop) {
        return;
    }

    function setNavOpen(open) {
        document.body.classList.toggle('nav-open', open);
        backdrop.hidden = !open;
        toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
        toggle.textContent = open ? '✕' : '☰';
    }

    toggle.addEventListener('click', function () {
        setNavOpen(!document.body.classList.contains('nav-open'));
    });

    backdrop.addEventListener('click', function () {
        setNavOpen(false);
    });

    document.querySelectorAll('.sidebar a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.matchMedia('(max-width: 900px)').matches) {
                setNavOpen(false);
            }
        });
    });
})();
</script>
<?php endif; ?>

</body>
</html>
