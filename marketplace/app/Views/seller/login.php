<?php
use App\Modules\Storefront\Ui;

$crumbs = [['Home', '/'], ['Seller sign in', null]];
$meta = [
    'title'       => 'Seller sign in | CyberGaming',
    'description' => 'Sign in to your CyberGaming seller portal.',
    'noindex'     => true,
];
?>
<link rel="stylesheet" href="<?= e(asset('css/seller.css')) ?>">
<div class="container page-head">
    <?= Ui::breadcrumbs($crumbs) ?>
    <h1>Seller sign in</h1>
    <p class="lead-sm">Manage your listings, sold items and payouts.</p>
</div>

<div class="container seller-public">
    <form class="card-box seller-login-form" method="post" action="<?= e(url('/seller/login')) ?>" autocomplete="on">
        <?= csrf_field() ?>
        <div class="form-row">
            <label for="l-email">Email</label>
            <input id="l-email" name="email" type="email" required maxlength="190" autocomplete="username" value="<?= e(old('email')) ?>">
        </div>
        <div class="form-row">
            <label for="l-pass">Password</label>
            <input id="l-pass" name="password" type="password" required autocomplete="current-password">
        </div>
        <button class="btn btn-primary btn-lg btn-block" type="submit">Sign in</button>
        <p class="fine">New here? <a href="<?= e(url('/seller/apply')) ?>">Apply to sell on CyberGaming</a>.</p>
    </form>
</div>
