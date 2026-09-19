<?php
use App\Modules\Admin\Forms;

/** @var array $seller @var array $stats @var float $commission @var bool $isOverride */
$pageTitle = 'Dashboard';
$nav = 'dashboard';
?>
<div class="sp-head">
    <div>
        <h1>Hello, <?= e($seller['name']) ?></h1>
        <p class="sp-muted">Your seller code is <strong><?= e($seller['code']) ?></strong>. Buyers never see it or your name.</p>
    </div>
    <a class="sp-btn sp-btn-primary" href="<?= e(url('/seller/products/new')) ?>">+ Add a listing</a>
</div>

<?php if ($stats['handover'] > 0): ?>
    <div class="sp-notice sp-notice-action">
        <strong><?= (int) $stats['handover'] ?> sold item<?= $stats['handover'] === 1 ? '' : 's' ?> waiting to be handed over.</strong>
        Please give <?= $stats['handover'] === 1 ? 'it' : 'them' ?> to our courier or bring <?= $stats['handover'] === 1 ? 'it' : 'them' ?> to our hub. <a href="<?= e(url('/seller/sales')) ?>">See sold items</a>
    </div>
<?php endif; ?>
<?php if ($stats['pending'] > 0): ?>
    <div class="sp-notice">
        <strong><?= (int) $stats['pending'] ?> listing<?= $stats['pending'] === 1 ? ' is' : 's are' ?> waiting for approval.</strong>
        We check every listing before it goes live. <a href="<?= e(url('/seller/products?status=pending')) ?>">View them</a>
    </div>
<?php endif; ?>
<?php if ($stats['active'] + $stats['pending'] + $stats['hidden'] === 0): ?>
    <div class="sp-notice">
        <strong>You have no listings yet.</strong> <a href="<?= e(url('/seller/products/new')) ?>">Add your first item</a>. Set the price you want to receive and we add our commission on top.
    </div>
<?php endif; ?>

<div class="sp-kpis">
    <a class="sp-kpi" href="<?= e(url('/seller/products?status=active')) ?>"><span class="sp-kpi-label">Active listings</span><span class="sp-kpi-value"><?= (int) $stats['active'] ?></span><span class="sp-kpi-sub">live in the shop</span></a>
    <a class="sp-kpi <?= $stats['pending'] ? 'sp-kpi-warn' : '' ?>" href="<?= e(url('/seller/products?status=pending')) ?>"><span class="sp-kpi-label">Waiting for approval</span><span class="sp-kpi-value"><?= (int) $stats['pending'] ?></span><span class="sp-kpi-sub">being checked by us</span></a>
    <a class="sp-kpi" href="<?= e(url('/seller/sales')) ?>"><span class="sp-kpi-label">Sold items</span><span class="sp-kpi-value"><?= (int) $stats['sold'] ?></span><span class="sp-kpi-sub">confirmed orders</span></a>
    <a class="sp-kpi" href="<?= e(url('/seller/products?status=hidden')) ?>"><span class="sp-kpi-label">Hidden</span><span class="sp-kpi-value"><?= (int) $stats['hidden'] ?></span><span class="sp-kpi-sub">withdrawn by you</span></a>
</div>

<div class="sp-cols">
    <section class="sp-card">
        <h2>Payouts</h2>
        <div class="sp-pay">
            <div><span class="sp-kpi-label">Pending payout</span><span class="sp-big"><?= e(money($stats['pay_pending'])) ?></span><span class="sp-kpi-sub">delivered items we still owe you</span></div>
            <div><span class="sp-kpi-label">Paid so far</span><span class="sp-big sp-big-good"><?= e(money($stats['pay_paid'])) ?></span><span class="sp-kpi-sub">all time</span></div>
        </div>
        <p><a href="<?= e(url('/seller/payouts')) ?>">See payout details &rarr;</a></p>
    </section>
    <section class="sp-card">
        <h2>Your commission</h2>
        <p class="sp-big"><?= e(Forms::pct($commission)) ?></p>
        <p class="sp-muted">You set the price you want to <strong>receive</strong>. We add <?= e(Forms::pct($commission)) ?> on top (<?= $isOverride ? 'your agreed rate' : 'our standard rate' ?>), rounded up to the next $0.50, so buyers see one price. You always get exactly the price you entered.</p>
    </section>
</div>
