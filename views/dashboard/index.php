<?php
$totalCustomers = (int)($totalCustomers ?? 0);
$activeSubscriptions = (int)($activeSubscriptions ?? 0);
$outstandingInvoices = (int)($outstandingInvoices ?? 0);
$verifiedRevenue = (float)($verifiedRevenue ?? 0);
$paymentsThisMonth = (float)($paymentsThisMonth ?? 0);
$user = $user ?? [];
$isAdmin = !empty($isAdmin);
$isStaff = !empty($isStaff);
$displayName = trim((string)($displayName ?? ''));
if ($displayName === '') {
    $emailFallback = trim((string)($user['email'] ?? 'User'));
    $displayName = strstr($emailFallback, '@', true) ?: $emailFallback;
}
$companyName = trim((string)($companyName ?? 'FusionLink'));
$activityAlerts = $activityAlerts ?? ['items' => [], 'pendingApplications' => 0, 'pendingPayments' => 0, 'overdueInvoices' => 0];
$alertItems = $activityAlerts['items'] ?? [];
$pendingApplications = (int)($activityAlerts['pendingApplications'] ?? 0);
$pendingPaymentsCount = (int)($activityAlerts['pendingPayments'] ?? 0);
$overdueInvoicesCount = (int)($activityAlerts['overdueInvoices'] ?? 0);
$totalActionAlerts = $pendingApplications + $pendingPaymentsCount + $overdueInvoicesCount;

$hour = (int)(function_exists('app_now') ? app_now()->format('G') : date('G'));
if ($hour < 12) {
    $greeting = 'Good morning';
} elseif ($hour < 18) {
    $greeting = 'Good afternoon';
} else {
    $greeting = 'Good evening';
}

$icon = static function (string $name): string {
    $icons = [
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
        'plus' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 5h2v6h6v2h-6v6h-2v-6H5v-2h6V5Z"/></svg>',
        'check' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9.2 16.6 4.8 12.2l1.4-1.4 3 3 8.6-8.6 1.4 1.4-10 10Z"/></svg>',
        'person' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-3.3 0-10 1.7-10 5v2h20v-2c0-3.3-6.7-5-10-5Z"/></svg>',
        'eye' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5C7 5 2.7 8.1 1 12c1.7 3.9 6 7 11 7s9.3-3.1 11-7c-1.7-3.9-6-7-11-7Zm0 12a5 5 0 1 1 5-5 5 5 0 0 1-5 5Zm0-8a3 3 0 1 0 3 3 3 3 0 0 0-3-3Z"/></svg>',
    ];

    return $icons[$name] ?? $icons['grid'];
};

$opsApps = [];
$adminApps = [];
$quickActions = [];

if ($isStaff) {
    $opsApps = [
        ['label' => 'Customers', 'href' => url('/customers'), 'icon' => 'users'],
        ['label' => 'Subscriptions', 'href' => url('/subscriptions'), 'icon' => 'signal'],
        ['label' => 'Invoices', 'href' => url('/invoices'), 'icon' => 'invoice'],
        ['label' => 'Payments', 'href' => url('/payments'), 'icon' => 'card'],
        ['label' => 'Inquiries', 'href' => url('/inquiries'), 'icon' => 'mail', 'badge' => $pendingApplications],
        ['label' => 'Bookings', 'href' => url('/bookings'), 'icon' => 'calendar'],
        ['label' => 'Personnel', 'href' => url('/personnel'), 'icon' => 'tool'],
    ];

    $quickActions = [
        ['label' => 'Schedule Ocular / Installation', 'href' => url('/inquiries'), 'icon' => 'eye', 'badge' => $pendingApplications],
        ['label' => 'Book Service', 'href' => url('/bookings/create'), 'icon' => 'calendar'],
        ['label' => 'Verify Payments', 'href' => url('/payments'), 'icon' => 'check', 'badge' => $pendingPaymentsCount],
        ['label' => 'Record Payment', 'href' => url('/payments/create'), 'icon' => 'card'],
        ['label' => 'Add Customer', 'href' => url('/customers/create'), 'icon' => 'person'],
        ['label' => 'New Invoice', 'href' => url('/invoices/create'), 'icon' => 'invoice'],
        ['label' => 'Outstanding Invoices', 'href' => url('/invoices'), 'icon' => 'list', 'badge' => $overdueInvoicesCount],
    ];
}

if ($isAdmin) {
    $adminApps = [
        ['label' => 'Plans', 'href' => url('/plans'), 'icon' => 'box'],
        ['label' => 'Reports', 'href' => url('/reports/revenue'), 'icon' => 'chart'],
        ['label' => 'Users', 'href' => url('/users'), 'icon' => 'shield'],
        ['label' => 'Activity Logs', 'href' => url('/activity-logs'), 'icon' => 'list'],
        ['label' => 'Settings', 'href' => url('/settings'), 'icon' => 'gear'],
        ['label' => 'CMS', 'href' => url('/cms/dashboard'), 'icon' => 'grid'],
    ];
}
?>

<style>
.launchpad {
    --lp-ink: #f5f5f5;
    --lp-muted: #a3a3a3;
    --lp-line: rgba(255, 255, 255, .10);
    --lp-tile: #111113;
    --lp-tile-hover: #17171a;
    --lp-surface: #0c0c0d;
    margin: 0;
    border-radius: 6px;
    overflow: hidden;
    color: var(--lp-ink);
    background: #09090b;
    border: 1px solid rgba(255,255,255,.10);
    box-shadow: 0 1px 0 rgba(255,255,255,.04) inset, 0 12px 32px rgba(0,0,0,.35);
    position: relative;
}

.launchpad::before {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background:
        linear-gradient(180deg, rgba(255,255,255,.03), transparent 120px),
        repeating-linear-gradient(
            -12deg,
            transparent,
            transparent 18px,
            rgba(255,255,255,.012) 18px,
            rgba(255,255,255,.012) 19px
        );
}

.launchpad-chrome {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    padding: 10px 18px;
    background: #050505;
    border-bottom: 1px solid var(--lp-line);
    font-size: 12px;
    color: var(--lp-muted);
}

.launchpad-chrome-left,
.launchpad-chrome-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.launchpad-chrome strong {
    color: #fff;
    font-weight: 650;
    letter-spacing: .02em;
}

.launchpad-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 8px;
    border: 1px solid var(--lp-line);
    background: rgba(255,255,255,.03);
    border-radius: 4px;
    color: #d4d4d4;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.launchpad-pill::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #86efac;
    box-shadow: 0 0 0 2px rgba(134, 239, 172, .15);
}

.launchpad-inner {
    position: relative;
    z-index: 1;
    padding: 22px 22px 18px;
}

.launchpad-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}

.launchpad-greeting {
    margin: 0 0 6px;
    font-size: clamp(24px, 3.2vw, 34px);
    font-weight: 650;
    letter-spacing: -.02em;
    line-height: 1.15;
    color: #fff;
}

.launchpad-brand {
    margin: 0;
    color: var(--lp-muted);
    font-size: 13px;
    letter-spacing: .01em;
}

.launchpad-brand strong {
    color: #e5e5e5;
    font-weight: 650;
}

.launchpad-header-meta {
    text-align: right;
    color: var(--lp-muted);
    font-size: 12px;
    line-height: 1.5;
}

.launchpad-header-meta strong {
    display: block;
    color: #fff;
    font-size: 13px;
    font-weight: 650;
}

.launchpad-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0;
    border-bottom: 1px solid var(--lp-line);
    margin-bottom: 20px;
}

.launchpad-tab {
    appearance: none;
    background: transparent;
    border: 0;
    border-bottom: 2px solid transparent;
    color: var(--lp-muted);
    cursor: pointer;
    font: inherit;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: .01em;
    padding: 11px 14px 12px;
    margin-bottom: -1px;
}

.launchpad-tab:hover {
    color: #e5e5e5;
    background: rgba(255,255,255,.02);
}

.launchpad-tab.is-active {
    color: #fff;
    border-bottom-color: #fff;
}

.launchpad-panel[hidden] {
    display: none !important;
}

.launchpad-split {
    display: grid;
    grid-template-columns: minmax(240px, 300px) minmax(0, 1fr);
    gap: 0;
    align-items: stretch;
    border: 1px solid var(--lp-line);
    background: rgba(255,255,255,.015);
}

.launchpad-rail,
.launchpad-main {
    padding: 16px;
}

.launchpad-rail {
    border-right: 1px solid var(--lp-line);
    background: rgba(0,0,0,.18);
}

.launchpad-section-label {
    margin: 0 0 12px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .10em;
    text-transform: uppercase;
    color: #737373;
}

.launchpad-actions {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.launchpad-action {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #e5e5e5;
    text-decoration: none;
    padding: 10px 10px;
    border-radius: 4px;
    border: 1px solid transparent;
    transition: background .12s ease, border-color .12s ease;
}

.launchpad-action:hover {
    background: rgba(255,255,255,.05);
    border-color: var(--lp-line);
    color: #fff;
}

.launchpad-action-icon {
    width: 28px;
    height: 28px;
    flex: 0 0 28px;
    display: grid;
    place-items: center;
    border: 1px solid var(--lp-line);
    background: #111113;
    border-radius: 4px;
    color: #fafafa;
}

.launchpad-action-icon svg {
    width: 15px;
    height: 15px;
    fill: currentColor;
}

.launchpad-action-label {
    flex: 1;
    font-size: 13px;
    line-height: 1.3;
    font-weight: 500;
}

.launchpad-badge {
    min-width: 20px;
    height: 18px;
    padding: 0 6px;
    border-radius: 3px;
    background: #1a1a1c;
    border: 1px solid rgba(255,255,255,.16);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .02em;
    display: inline-grid;
    place-items: center;
}

.launchpad-apps {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(112px, 1fr));
    gap: 10px;
}

.launchpad-tile {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    text-align: center;
    text-decoration: none;
    color: #f5f5f5;
    background: var(--lp-tile);
    border: 1px solid var(--lp-line);
    border-radius: 6px;
    padding: 14px 10px;
    position: relative;
    transition: background .12s ease, border-color .12s ease;
    min-height: 112px;
}

.launchpad-tile:hover {
    background: var(--lp-tile-hover);
    border-color: rgba(255,255,255,.22);
    color: #fff;
}

.launchpad-tile-icon {
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    border: 1px solid rgba(255,255,255,.12);
    background: #0a0a0b;
    border-radius: 6px;
}

.launchpad-tile-icon svg {
    width: 22px;
    height: 22px;
    fill: currentColor;
}

.launchpad-tile-label {
    font-size: 12px;
    font-weight: 600;
    line-height: 1.25;
    letter-spacing: .01em;
}

.launchpad-tile-badge {
    position: absolute;
    top: 8px;
    right: 8px;
}

.launchpad-empty {
    color: var(--lp-muted);
    font-size: 13px;
    padding: 12px 0;
}

.launchpad-overview {
    display: grid;
    gap: 12px;
}

.launchpad-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1px;
    background: var(--lp-line);
    border: 1px solid var(--lp-line);
}

.launchpad-kpi {
    background: #0c0c0d;
    padding: 14px 16px;
}

.launchpad-kpi .label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #737373;
    margin-bottom: 8px;
}

.launchpad-kpi .value {
    font-size: 20px;
    font-weight: 650;
    letter-spacing: -.02em;
    color: #fff;
    font-variant-numeric: tabular-nums;
}

.launchpad-alerts {
    background: #0c0c0d;
    border: 1px solid var(--lp-line);
    padding: 0;
}

.launchpad-alerts h3 {
    margin: 0;
    padding: 12px 14px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #a3a3a3;
    border-bottom: 1px solid var(--lp-line);
}

.launchpad-alert-list {
    display: flex;
    flex-direction: column;
    gap: 0;
    max-height: 280px;
    overflow: auto;
}

.launchpad-alert {
    display: block;
    text-decoration: none;
    color: #e5e5e5;
    background: transparent;
    border: 0;
    border-bottom: 1px solid rgba(255,255,255,.06);
    border-radius: 0;
    padding: 12px 14px;
}

.launchpad-alert:last-child {
    border-bottom: 0;
}

.launchpad-alert:hover {
    background: rgba(255,255,255,.04);
    color: #fff;
}

.launchpad-alert strong {
    display: block;
    font-size: 13px;
    font-weight: 650;
    margin-bottom: 4px;
}

.launchpad-alert span {
    display: block;
    font-size: 12px;
    color: var(--lp-muted);
    line-height: 1.45;
}

.launchpad-strip {
    margin-top: 14px;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1px;
    background: var(--lp-line);
    border: 1px solid var(--lp-line);
}

.launchpad-strip a,
.launchpad-strip .strip-item {
    display: block;
    text-decoration: none;
    color: #fff;
    background: #0c0c0d;
    border: 0;
    border-radius: 0;
    padding: 12px 14px;
}

.launchpad-strip a:hover {
    background: #121214;
    color: #fff;
}

.launchpad-strip .label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #737373;
    margin-bottom: 6px;
}

.launchpad-strip .value {
    font-size: 16px;
    font-weight: 650;
    font-variant-numeric: tabular-nums;
}

@media (max-width: 960px) {
    .launchpad-split {
        grid-template-columns: 1fr;
    }

    .launchpad-rail {
        border-right: 0;
        border-bottom: 1px solid var(--lp-line);
    }

    .launchpad-strip {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .launchpad-header-meta {
        text-align: left;
    }
}

@media (max-width: 640px) {
    .launchpad {
        margin: -4px 0 0;
        border-radius: 6px;
    }

    .launchpad-chrome,
    .launchpad-inner {
        padding-left: 14px;
        padding-right: 14px;
    }

    .launchpad-inner {
        padding-top: 16px;
        padding-bottom: 14px;
    }

    .launchpad-greeting {
        font-size: 24px;
    }

    .launchpad-tabs {
        overflow-x: auto;
        flex-wrap: nowrap;
        -webkit-overflow-scrolling: touch;
    }

    .launchpad-tab {
        white-space: nowrap;
        padding: 10px 12px;
    }

    .launchpad-apps {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .launchpad-tile {
        min-height: 104px;
    }

    .launchpad-action {
        min-height: 44px;
    }
}
</style>

<section class="launchpad" id="fusionlink-launchpad">
    <div class="launchpad-chrome">
        <div class="launchpad-chrome-left">
            <strong><?= htmlspecialchars($companyName) ?></strong>
            <span>Workspace</span>
            <span class="launchpad-pill">Operational</span>
        </div>
        <div class="launchpad-chrome-right">
            <span><?= htmlspecialchars(function_exists('app_now') ? app_now()->format('D, M j, Y') : date('D, M j, Y')) ?></span>
            <span>·</span>
            <span><?= htmlspecialchars(str_replace('ROLE_', '', (string)($role ?? 'USER'))) ?></span>
        </div>
    </div>

    <div class="launchpad-inner">
        <div class="launchpad-header">
            <div>
                <h1 class="launchpad-greeting"><?= htmlspecialchars($greeting) ?>, <?= htmlspecialchars($displayName) ?></h1>
                <p class="launchpad-brand"><strong>Billing Operations</strong> · Enterprise console</p>
            </div>
            <div class="launchpad-header-meta">
                <strong><?= number_format($totalActionAlerts) ?> open items</strong>
                <?= (int)$pendingApplications ?> applications · <?= (int)$pendingPaymentsCount ?> payments · <?= (int)$overdueInvoicesCount ?> overdue
            </div>
        </div>

        <div class="launchpad-tabs" role="tablist" aria-label="Home sections">
            <button type="button" class="launchpad-tab is-active" data-tab="operations" role="tab" aria-selected="true">Operations</button>
            <?php if ($isAdmin): ?>
                <button type="button" class="launchpad-tab" data-tab="admin" role="tab" aria-selected="false">Administration</button>
            <?php endif; ?>
            <button type="button" class="launchpad-tab" data-tab="overview" role="tab" aria-selected="false">
                Overview<?= $totalActionAlerts > 0 ? ' (' . $totalActionAlerts . ')' : '' ?>
            </button>
        </div>

        <div class="launchpad-panel" data-panel="operations">
            <?php if ($isStaff): ?>
                <div class="launchpad-split">
                    <div class="launchpad-rail">
                        <h2 class="launchpad-section-label">Quick Actions</h2>
                        <div class="launchpad-actions">
                            <?php foreach ($quickActions as $action): ?>
                                <a class="launchpad-action" href="<?= htmlspecialchars($action['href']) ?>">
                                    <span class="launchpad-action-icon"><?= $icon($action['icon']) ?></span>
                                    <span class="launchpad-action-label"><?= htmlspecialchars($action['label']) ?></span>
                                    <?php if (!empty($action['badge'])): ?>
                                        <span class="launchpad-badge"><?= (int)$action['badge'] ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="launchpad-main">
                        <h2 class="launchpad-section-label">Applications</h2>
                        <div class="launchpad-apps">
                            <?php foreach ($opsApps as $app): ?>
                                <a class="launchpad-tile" href="<?= htmlspecialchars($app['href']) ?>">
                                    <?php if (!empty($app['badge'])): ?>
                                        <span class="launchpad-badge launchpad-tile-badge"><?= (int)$app['badge'] ?></span>
                                    <?php endif; ?>
                                    <span class="launchpad-tile-icon"><?= $icon($app['icon']) ?></span>
                                    <span class="launchpad-tile-label"><?= htmlspecialchars($app['label']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <p class="launchpad-empty">No operations modules are available for this account.</p>
            <?php endif; ?>
        </div>

        <?php if ($isAdmin): ?>
            <div class="launchpad-panel" data-panel="admin" hidden>
                <div class="launchpad-split">
                    <div class="launchpad-rail">
                        <h2 class="launchpad-section-label">Quick Actions</h2>
                        <div class="launchpad-actions">
                            <a class="launchpad-action" href="<?= url('/plans') ?>">
                                <span class="launchpad-action-icon"><?= $icon('box') ?></span>
                                <span class="launchpad-action-label">Manage Plans</span>
                            </a>
                            <a class="launchpad-action" href="<?= url('/users') ?>">
                                <span class="launchpad-action-icon"><?= $icon('shield') ?></span>
                                <span class="launchpad-action-label">Manage Users</span>
                            </a>
                            <a class="launchpad-action" href="<?= url('/settings') ?>">
                                <span class="launchpad-action-icon"><?= $icon('gear') ?></span>
                                <span class="launchpad-action-label">System Settings</span>
                            </a>
                            <a class="launchpad-action" href="<?= url('/cms/dashboard') ?>">
                                <span class="launchpad-action-icon"><?= $icon('grid') ?></span>
                                <span class="launchpad-action-label">CMS Dashboard</span>
                            </a>
                            <a class="launchpad-action" href="<?= url('/reports/revenue') ?>">
                                <span class="launchpad-action-icon"><?= $icon('chart') ?></span>
                                <span class="launchpad-action-label">Revenue Report</span>
                            </a>
                            <a class="launchpad-action" href="<?= url('/activity-logs') ?>">
                                <span class="launchpad-action-icon"><?= $icon('list') ?></span>
                                <span class="launchpad-action-label">Activity Logs</span>
                            </a>
                        </div>
                    </div>
                    <div class="launchpad-main">
                        <h2 class="launchpad-section-label">Applications</h2>
                        <div class="launchpad-apps">
                            <?php foreach ($adminApps as $app): ?>
                                <a class="launchpad-tile" href="<?= htmlspecialchars($app['href']) ?>">
                                    <span class="launchpad-tile-icon"><?= $icon($app['icon']) ?></span>
                                    <span class="launchpad-tile-label"><?= htmlspecialchars($app['label']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="launchpad-panel" data-panel="overview" hidden>
            <div class="launchpad-overview">
                <div class="launchpad-kpis">
                    <div class="launchpad-kpi">
                        <div class="label">Customers</div>
                        <div class="value"><?= number_format($totalCustomers) ?></div>
                    </div>
                    <div class="launchpad-kpi">
                        <div class="label">Active Subs</div>
                        <div class="value"><?= number_format($activeSubscriptions) ?></div>
                    </div>
                    <div class="launchpad-kpi">
                        <div class="label">Outstanding</div>
                        <div class="value"><?= number_format($outstandingInvoices) ?></div>
                    </div>
                    <div class="launchpad-kpi">
                        <div class="label">Verified Revenue</div>
                        <div class="value">₱<?= number_format($verifiedRevenue, 2) ?></div>
                    </div>
                    <div class="launchpad-kpi">
                        <div class="label">Payments This Month</div>
                        <div class="value">₱<?= number_format($paymentsThisMonth, 2) ?></div>
                    </div>
                    <div class="launchpad-kpi">
                        <div class="label">Pending Apps</div>
                        <div class="value"><?= number_format($pendingApplications) ?></div>
                    </div>
                </div>

                <div class="launchpad-alerts">
                    <h3>Needs attention</h3>
                    <?php if (empty($alertItems)): ?>
                        <p class="launchpad-empty" style="margin:0;padding:14px;">No pending alerts right now.</p>
                    <?php else: ?>
                        <div class="launchpad-alert-list">
                            <?php foreach (array_slice($alertItems, 0, 8) as $item): ?>
                                <a class="launchpad-alert" href="<?= htmlspecialchars((string)($item['url'] ?? url('/dashboard'))) ?>">
                                    <strong><?= htmlspecialchars((string)($item['title'] ?? 'Alert')) ?></strong>
                                    <span><?= htmlspecialchars((string)($item['message'] ?? '')) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="launchpad-strip" aria-label="Key metrics">
            <a href="<?= url('/inquiries') ?>">
                <div class="label">Pending applications</div>
                <div class="value"><?= number_format($pendingApplications) ?></div>
            </a>
            <a href="<?= url('/payments') ?>">
                <div class="label">Pending payments</div>
                <div class="value"><?= number_format($pendingPaymentsCount) ?></div>
            </a>
            <a href="<?= url('/reports/outstanding') ?>">
                <div class="label">Overdue invoices</div>
                <div class="value"><?= number_format($overdueInvoicesCount) ?></div>
            </a>
            <div class="strip-item">
                <div class="label">This month</div>
                <div class="value">₱<?= number_format($paymentsThisMonth, 0) ?></div>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var root = document.getElementById('fusionlink-launchpad');
    if (!root) {
        return;
    }

    var tabs = root.querySelectorAll('.launchpad-tab');
    var panels = root.querySelectorAll('.launchpad-panel');

    function activate(name) {
        tabs.forEach(function (tab) {
            var active = tab.getAttribute('data-tab') === name;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-panel') !== name;
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            activate(tab.getAttribute('data-tab'));
        });
    });
})();
</script>
