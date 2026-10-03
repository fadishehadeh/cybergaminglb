<?php
use App\Modules\Admin\Forms;

$pageTitle = 'Dashboard';
$nav = 'dashboard';
$here = Forms::here();
?>
<div class="page-head">
    <div>
        <h1>Dashboard</h1>
        <p class="muted"><?= e(date('l, j F Y')) ?></p>
    </div>
    <div class="actions">
        <a class="btn btn-primary" href="<?= e(url('/admin/products/create')) ?>">+ Add product</a>
    </div>
</div>

<div class="kpis">
    <a class="kpi" href="<?= e(url('/admin/orders')) ?>"><span class="kpi-label">Orders today</span><span class="kpi-value"><?= (int) $kpi['orders_today'] ?></span><span class="kpi-sub">excluding cancelled</span></a>
    <a class="kpi" href="<?= e(url('/admin/orders')) ?>"><span class="kpi-label">Orders this month</span><span class="kpi-value"><?= (int) $kpi['orders_month'] ?></span><span class="kpi-sub">excluding cancelled</span></a>
    <div class="kpi"><span class="kpi-label">Revenue this month</span><span class="kpi-value"><?= e(money($kpi['revenue_month'])) ?></span><span class="kpi-sub">delivered orders</span></div>
    <div class="kpi kpi-accent"><span class="kpi-label">Profit this month</span><span class="kpi-value"><?= e(money($kpi['profit_month'])) ?></span><span class="kpi-sub"><?= (int) $kpi['units_month'] ?> item<?= $kpi['units_month'] === 1 ? '' : 's' ?> delivered<?= $kpi['profit_uncosted'] > 0 ? '; ' . (int) $kpi['profit_uncosted'] . ' line' . ($kpi['profit_uncosted'] === 1 ? '' : 's') . ' had no cost set (counted as $0 profit)' : '' ?></span></div>
    <div class="kpi"><span class="kpi-label">Profit all time</span><span class="kpi-value"><?= e(money($kpi['profit_all_time'])) ?></span><span class="kpi-sub">delivered orders, lifetime</span></div>
    <div class="kpi"><span class="kpi-label">Profit sitting in stock</span><span class="kpi-value"><?= e(money($kpi['stock_profit'])) ?></span><span class="kpi-sub"><?= (int) $kpi['stock_units'] ?> unit<?= $kpi['stock_units'] === 1 ? '' : 's' ?> in stock, worth <?= e(money($kpi['stock_value'])) ?><?= $kpi['stock_uncosted'] > 0 ? '; ' . (int) $kpi['stock_uncosted'] . ' with no cost set' : '' ?></span></div>
    <a class="kpi <?= $kpi['pending_products'] ? 'kpi-alert' : '' ?>" href="<?= e(url('/admin/products?status=pending')) ?>"><span class="kpi-label">Listings to approve</span><span class="kpi-value"><?= (int) $kpi['pending_products'] ?></span><span class="kpi-sub">waiting for review</span></a>
    <a class="kpi <?= $kpi['missing_photos'] ? 'kpi-alert' : '' ?>" href="<?= e(url('/admin/products?missing=1')) ?>"><span class="kpi-label">Used listings missing photos</span><span class="kpi-value"><?= (int) $kpi['missing_photos'] ?></span><span class="kpi-sub">games: disc + box; hardware: unit + box photos</span></a>
    <a class="kpi <?= $kpi['out_of_stock'] ? 'kpi-alert' : '' ?>" href="<?= e(url('/admin/products?status=sold')) ?>"><span class="kpi-label">Out of stock</span><span class="kpi-value"><?= (int) $kpi['out_of_stock'] ?></span><span class="kpi-sub">sold out, needs restocking or hiding</span></a>
    <a class="kpi <?= $kpi['articles_draft'] ? 'kpi-alert' : '' ?>" href="<?= e(url('/admin/guides?status=draft')) ?>"><span class="kpi-label">Articles in draft</span><span class="kpi-value"><?= (int) $kpi['articles_draft'] ?></span><span class="kpi-sub">guides not yet published</span></a>
    <a class="kpi <?= $kpi['cash_to_collect'] > 0 ? 'kpi-alert' : '' ?>" href="<?= e(url('/admin/orders?status=picked_up')) ?>"><span class="kpi-label">Cash to collect</span><span class="kpi-value"><?= e(money($kpi['cash_to_collect'])) ?></span><span class="kpi-sub"><?= (int) $kpi['cash_orders'] ?> order<?= $kpi['cash_orders'] === 1 ? '' : 's' ?> with cash due, prepaid excluded</span></a>
    <a class="kpi <?= $kpi['orders_awaiting_payment'] ? 'kpi-alert' : '' ?>" href="<?= e(url('/admin/orders?payment=awaiting')) ?>"><span class="kpi-label">Orders awaiting payment</span><span class="kpi-value"><?= (int) $kpi['orders_awaiting_payment'] ?></span><span class="kpi-sub">prepaid: waiting for OMT/Whish</span></a>
</div>

<div class="cols-2">
    <section class="card">
        <div class="card-head"><h2>Needs attention</h2></div>
        <div class="card-body">
            <h3 class="sub-title">Orders awaiting payment <span class="muted">(<?= (int) $kpi['orders_awaiting_payment'] ?>)</span></h3>
            <?php if (!$awaitingOrders): ?>
                <p class="empty-inline">No orders waiting for payment.</p>
            <?php else: ?>
                <ul class="list">
                    <?php foreach ($awaitingOrders as $o): ?>
                        <li>
                            <a href="<?= e(url('/admin/orders/' . $o['id'])) ?>"><strong><?= e($o['code']) ?></strong></a>
                            <?= Forms::modeBadge($o['zone_mode']) ?>
                            <span class="muted"><?= e($o['buyer_name']) ?><?= $o['zone'] ? ', ' . e($o['zone']) : '' ?></span>
                            <span class="grow"></span>
                            <strong><?= e(money(max(0, (float) $o['due']))) ?></strong>
                            <a class="btn btn-sm" href="<?= e(url('/admin/orders/' . $o['id'])) ?>">Open</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($kpi['orders_awaiting_payment'] > count($awaitingOrders)): ?>
                    <p><a href="<?= e(url('/admin/orders?payment=awaiting')) ?>">See all <?= (int) $kpi['orders_awaiting_payment'] ?> orders awaiting payment &rarr;</a></p>
                <?php endif; ?>
            <?php endif; ?>

            <h3 class="sub-title">New orders <span class="muted">(<?= count($newOrders) ?>)</span></h3>
            <?php if (!$newOrders): ?>
                <p class="empty-inline">No new orders waiting. You are all caught up.</p>
            <?php else: ?>
                <ul class="list">
                    <?php foreach ($newOrders as $o): ?>
                        <li>
                            <a href="<?= e(url('/admin/orders/' . $o['id'])) ?>"><strong><?= e($o['code']) ?></strong></a>
                            <span class="muted"><?= e($o['buyer_name']) ?>, <?= e($o['buyer_area']) ?></span>
                            <span class="grow"></span>
                            <strong><?= e(money($o['total'])) ?></strong>
                            <a class="btn btn-sm" href="<?= e(url('/admin/orders/' . $o['id'])) ?>">Open</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <h3 class="sub-title">Listings awaiting approval <span class="muted">(<?= (int) $kpi['pending_products'] ?>)</span></h3>
            <?php if (!$pendingProducts): ?>
                <p class="empty-inline">Nothing to approve.</p>
            <?php else: ?>
                <ul class="list">
                    <?php foreach ($pendingProducts as $p): ?>
                        <li>
                            <a href="<?= e(url('/admin/products/' . $p['id'] . '/review')) ?>"><strong><?= e($p['title']) ?></strong></a>
                            <span class="muted"><?= e($p['platform'] ?? '') ?></span>
                            <?= \App\Modules\Admin\ListingRules::conditionTag($p) ?> <?= \App\Modules\Admin\ListingRules::photoTag($p, (int) $p['photo_count']) ?>
                            <span class="grow"></span>
                            <span><?= e(money($p['price'])) ?></span>
                            <a class="btn btn-sm" href="<?= e(url('/admin/products/' . $p['id'] . '/review')) ?>">Review</a>
                            <form method="post" action="<?= e(url('/admin/products/' . $p['id'] . '/approve')) ?>" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="return" value="<?= e($here) ?>">
                                <button class="btn btn-sm btn-primary" type="submit">Approve</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($kpi['pending_products'] > count($pendingProducts)): ?>
                    <p><a href="<?= e(url('/admin/products?status=pending')) ?>">See all <?= (int) $kpi['pending_products'] ?> pending listings &rarr;</a></p>
                <?php endif; ?>
            <?php endif; ?>

            <h3 class="sub-title">Used listings missing photos <span class="muted">(<?= (int) $kpi['missing_photos'] ?>)</span></h3>
            <?php if ($kpi['missing_photos'] < 1): ?>
                <p class="empty-inline">Every used listing has its three photos.</p>
            <?php else: ?>
                <ul class="list">
                    <li>
                        <span class="tag tag-photos-low">Photos &lt; 3/3</span>
                        <a href="<?= e(url('/admin/products?missing=1')) ?>"><strong><?= (int) $kpi['missing_photos'] ?> used listing<?= $kpi['missing_photos'] === 1 ? '' : 's' ?> need their photos</strong></a>
                        <span class="grow"></span>
                        <a class="btn btn-sm" href="<?= e(url('/admin/products?missing=1')) ?>">Show them</a>
                    </li>
                </ul>
            <?php endif; ?>

            <h3 class="sub-title">Low stock <span class="muted">(<?= count($lowStock) ?>)</span></h3>
            <?php if (!$lowStock): ?>
                <p class="empty-inline">Nothing is down to its last couple of units.</p>
            <?php else: ?>
                <ul class="list">
                    <?php foreach ($lowStock as $p): ?>
                        <li>
                            <a href="<?= e(url('/admin/products/' . $p['id'] . '/edit')) ?>"><strong><?= e($p['title']) ?></strong></a>
                            <span class="muted"><?= (int) $p['stock'] ?> left</span>
                            <span class="grow"></span>
                            <span><?= e(money($p['price'])) ?></span>
                            <a class="btn btn-sm" href="<?= e(url('/admin/products/' . $p['id'] . '/edit')) ?>">Edit</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>

    <section class="card">
        <div class="card-head"><h2>Recent orders</h2><a href="<?= e(url('/admin/orders')) ?>">All orders</a></div>
        <?php if (!$recentOrders): ?>
            <div class="empty"><strong>No orders yet</strong><p>Orders placed on the shop will show up here.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Order</th><th>Buyer</th><th class="num">Total</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentOrders as $o): ?>
                        <tr>
                            <td><a href="<?= e(url('/admin/orders/' . $o['id'])) ?>"><strong><?= e($o['code']) ?></strong></a><br><small class="muted"><?= e(date('j M, H:i', strtotime($o['created_at']))) ?></small></td>
                            <td><?= e($o['buyer_name']) ?><br><small class="muted"><?= e($o['buyer_area']) ?> &middot; <?= (int) $o['units'] ?> item<?= (int) $o['units'] === 1 ? '' : 's' ?></small></td>
                            <td class="num"><?= e(money($o['total'])) ?></td>
                            <td><?= Forms::pill($o['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
