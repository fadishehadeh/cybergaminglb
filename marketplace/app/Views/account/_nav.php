<?php
/**
 * Account header + sub-navigation. Include at the top of every account page:
 *   <?php $accountNav = 'wallet'; require base_path('app/Views/account/_nav.php'); ?>
 * Keys: dashboard, wallet, orders, offers, listings, sales, profile. It also loads css/account.css, so a page
 * that includes it needs nothing else. Runs in a closure so it never leaks variables into the page or layout.
 */
(static function (string $current): void {
    $items = [
        'dashboard' => ['/account', 'Overview'],
        'wallet'    => ['/account/wallet', 'Wallet'],
        'orders'    => ['/account/orders', 'My orders'],
        'offers'    => ['/account/offers', 'Sell offers'],
        'listings'  => ['/account/listings', 'My listings'],
        'sales'     => ['/account/sales', 'Sold items'],
        'profile'   => ['/account/profile', 'Profile'],
    ];
    ?>
<link rel="stylesheet" href="<?= e(asset('css/account.css')) ?>">
<div class="container acc-bar">
    <nav class="account-nav" aria-label="My account">
        <?php foreach ($items as $key => [$path, $label]): ?>
            <a href="<?= e(url($path)) ?>"<?= $current === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
</div>
<?php
})((string) ($accountNav ?? ''));
