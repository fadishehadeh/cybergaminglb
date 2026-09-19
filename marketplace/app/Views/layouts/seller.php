<?php
/** @var string $content  @var string|null $pageTitle  @var string|null $nav  @var array $seller */
$pageTitle = $pageTitle ?? 'Seller portal';
$nav       = $nav ?? '';
$siteName  = (string) setting('site_name', 'CyberGaming Lebanon');

// Items sold and waiting to be handed to our courier / hub (order lines only: no buyer data is read here).
$handover = (int) db()->fetchValue(
    "SELECT COALESCE(SUM(oi.qty), 0) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE oi.seller_id = ? AND o.status = 'confirmed'",
    [(int) ($seller['id'] ?? 0)]
);

$items = [
    ['dashboard', 'Dashboard', '/seller', 0],
    ['products', 'My products', '/seller/products', 0],
    ['sales', 'Sold items', '/seller/sales', $handover],
    ['payouts', 'Payouts', '/seller/payouts', 0],
    ['account', 'Account', '/seller/account', 0],
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
<meta name="theme-color" content="#101a3f">
<title><?= e($pageTitle) ?> | Seller portal | <?= e($siteName) ?></title>
<link rel="icon" type="image/png" href="<?= e(asset('img/favicon.png')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/seller.css')) ?>">
</head>
<body class="seller">
<a class="skip-link" href="#main">Skip to content</a>
<input type="checkbox" id="sp-nav-toggle" class="sp-nav-toggle" aria-hidden="true">
<header class="sp-header">
    <div class="sp-bar">
        <label for="sp-nav-toggle" class="sp-burger" aria-label="Menu"><span></span><span></span><span></span></label>
        <a class="sp-brand" href="<?= e(url('/seller')) ?>"><img src="<?= e(asset('img/logo-wide.png')) ?>" alt="<?= e($siteName) ?>" width="100" height="43"><span>Seller portal</span></a>
        <nav class="sp-nav" aria-label="Seller">
            <?php foreach ($items as [$key, $label, $href, $count]): ?>
                <a href="<?= e(url($href)) ?>" class="<?= $nav === $key ? 'active' : '' ?>"<?= $nav === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?><?php if ($count): ?><b class="sp-count"><?= (int) $count ?></b><?php endif; ?></a>
            <?php endforeach; ?>
            <form method="post" action="<?= e(url('/seller/logout')) ?>" class="sp-logout">
                <?= csrf_field() ?>
                <button type="submit">Logout</button>
            </form>
        </nav>
    </div>
</header>

<main id="main" class="sp-main" tabindex="-1">
    <?php if ($successFlash): ?><div class="sp-alert sp-alert-success" role="status"><?= nl2br(e($successFlash)) ?></div><?php endif; ?>
    <?php if ($errorFlash): ?><div class="sp-alert sp-alert-error" role="alert"><?= nl2br(e($errorFlash)) ?></div><?php endif; ?>
    <?= $content ?>
</main>

<footer class="sp-foot">
    <p><?= e($siteName) ?> is the only contact with buyers. Never share buyer or seller contact details, and never take a deal off the platform.</p>
</footer>
<script src="<?= e(asset('js/seller.js')) ?>" defer></script>
</body>
</html>
