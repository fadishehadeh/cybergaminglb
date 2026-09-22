<?php
/** @var string $content  @var string|null $pageTitle  @var string|null $nav */
$pageTitle = $pageTitle ?? 'Admin';
$nav       = $nav ?? '';
$user      = auth()->user();
$siteName  = (string) setting('site_name', 'CyberGaming Lebanon');

$badge = [
    'orders'   => (int) db()->fetchValue("SELECT COUNT(*) FROM orders WHERE status = 'new'"),
    'products' => (int) db()->fetchValue("SELECT COUNT(*) FROM products WHERE status = 'pending'"),
    'requests' => (int) db()->fetchValue('SELECT COUNT(*) FROM buyback_requests r WHERE ' . \App\Modules\Admin\RequestController::actionSql('r'))
                + (int) db()->fetchValue("SELECT COUNT(*) FROM swap_requests WHERE status = 'new'"),
    'payouts'  => (int) db()->fetchValue("SELECT COUNT(DISTINCT seller_id) FROM payouts WHERE status = 'pending'"),
];
$digitalOn = digital_enabled();
$waMissing = preg_replace('/\D+/', '', (string) setting('whatsapp_number', '961')) === '961';

$icons = [
    'dashboard' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
    'orders'    => '<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>',
    'products'  => '<path d="M16.5 9.4l-9-5.19M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
    'sellers'   => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'payouts'   => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
    'requests'  => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
    'customers' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>',
    'wallet'    => '<path d="M21 12V7a2 2 0 0 0-2-2H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/>',
    'catalog'   => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>',
    'guides'    => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><line x1="9" y1="7" x2="15" y2="7"/><line x1="9" y1="11" x2="15" y2="11"/>',
    'seo'       => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><path d="M8 11l2 2 4-4"/>',
    'settings'  => '<line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>',
    'account'   => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
];
$items = [
    ['dashboard', 'Dashboard', '/admin', null],
    ['orders', 'Orders', '/admin/orders', $badge['orders']],
    ['products', 'Products', '/admin/products', $badge['products']],
    ['sellers', 'Sellers', '/admin/sellers', null],
    ['payouts', 'Payouts', '/admin/payouts', $badge['payouts']],
    ['customers', 'Customers', '/admin/customers', null],
    ['wallet', 'Wallet', '/admin/wallet', null],
    ['requests', 'Requests', '/admin/requests', $badge['requests']],
    ['catalog', 'Categories & platforms', '/admin/catalog', null],
    ['guides', 'Guides', '/admin/guides', null],
    ['seo', 'SEO & AI search', '/admin/seo', null],
    ['settings', 'Settings', '/admin/settings', null],
    ['account', 'My account', '/admin/account', null],
];
$errorFlash   = flash('error');
$successFlash = flash('success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($pageTitle) ?> | Admin | <?= e($siteName) ?></title>
<link rel="icon" href="<?= e(asset('img/logo.jpg')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin">
<input type="checkbox" id="nav-toggle" class="nav-toggle" aria-hidden="true">
<div class="app">
    <aside class="sidebar">
        <a class="brand" href="<?= e(url('/admin')) ?>"><img src="<?= e(asset('img/logo-wide.png')) ?>" alt="<?= e($siteName) ?>"></a>
        <nav class="nav" aria-label="Admin">
            <?php foreach ($items as [$key, $label, $href, $count]): ?>
                <a href="<?= e(url($href)) ?>" class="<?= $nav === $key ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $icons[$key] ?></svg>
                    <span><?= e($label) ?></span>
                    <?php if ($count): ?><b class="count"><?= (int) $count ?></b><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <a class="digital-pill <?= $digitalOn ? 'on' : 'off' ?>" href="<?= e(url('/admin/settings#digital')) ?>" title="Master switch for gift cards and Steam gifts on the website. Click to change it.">
            <span class="dot" aria-hidden="true"></span> Digital: <?= $digitalOn ? 'ON' : 'OFF' ?>
        </a>
        <div class="sidebar-foot">
            <div class="who"><strong><?= e($user['name'] ?? '') ?></strong><small><?= e($user['email'] ?? '') ?></small></div>
            <form method="post" action="<?= e(url('/admin/logout')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-ghost btn-sm btn-block">Sign out</button>
            </form>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <label for="nav-toggle" class="menu-btn" aria-label="Menu"><span></span><span></span><span></span></label>
            <a class="topbar-brand" href="<?= e(url('/admin')) ?>"><?= e($siteName) ?> admin</a>
            <a class="digital-pill digital-pill-top <?= $digitalOn ? 'on' : 'off' ?>" href="<?= e(url('/admin/settings#digital')) ?>"><span class="dot" aria-hidden="true"></span> Digital: <?= $digitalOn ? 'ON' : 'OFF' ?></a>
            <a class="topbar-link" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">View shop &nearr;</a>
        </header>
        <label for="nav-toggle" class="nav-backdrop"></label>

        <main class="content">
            <?php if ($waMissing): ?>
                <div class="alert alert-warn"><strong>WhatsApp number not set.</strong> Customers cannot message you yet. <a href="<?= e(url('/admin/settings#whatsapp')) ?>">Add it in Settings</a>.</div>
            <?php endif; ?>
            <?php if ($successFlash): ?><div class="alert alert-success" role="status"><?= e($successFlash) ?></div><?php endif; ?>
            <?php if ($errorFlash): ?><div class="alert alert-error" role="alert"><?= nl2br(e($errorFlash)) ?></div><?php endif; ?>

            <?= $content ?>
        </main>
    </div>
</div>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
