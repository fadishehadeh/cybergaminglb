<?php
use App\Modules\Storefront\Ui;

/** @var string $next */
$meta = [
    'title'       => 'Sign in | CyberGaming Lebanon',
    'description' => 'Sign in to your CyberGaming account to see your credit, orders and sell offers.',
    'canonical'   => url('/account/login'),
];
$registerHref = url('/account/register') . ($next !== '' ? '?next=' . rawurlencode($next) : '');
?>
<link rel="stylesheet" href="<?= e(asset('css/account.css')) ?>">
<div class="container page-head">
    <?= Ui::breadcrumbs([['Home', '/'], ['Sign in', null]]) ?>
    <h1>Sign in</h1>
    <p class="lead-sm">See your credit, track orders and answer offers for your games.</p>
</div>

<div class="container acc-auth acc-auth-narrow">
    <form class="acc-card acc-form" method="post" action="<?= e(url('/account/login')) ?>" autocomplete="on" data-once>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <div class="form-row">
            <label for="l-id">Email or phone number</label>
            <input id="l-id" name="identifier" type="text" required maxlength="190" autocomplete="username" value="<?= e(old('identifier')) ?>" autofocus>
        </div>
        <div class="form-row">
            <label for="l-pass">Password</label>
            <input id="l-pass" name="password" type="password" required autocomplete="current-password">
        </div>
        <button class="btn btn-primary btn-lg btn-block" type="submit">Sign in</button>
        <p class="fine">New here? <a href="<?= e($registerHref) ?>">Create a free account</a> and earn credit for your games.</p>
        <p class="fine">Forgot your password? <a href="<?= e(wa_link('Hi CyberGaming, I forgot my account password.')) ?>" rel="noopener" target="_blank">Message us on WhatsApp</a> and we will help.</p>
    </form>
</div>
