<?php
use App\Modules\Admin\Forms;
use App\Modules\Admin\ListingRules;

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
    <div class="kpi kpi-accent"><span class="kpi-label">Commission earned</span><span class="kpi-value"><?= e(money($kpi['commission_month'])) ?></span><span class="kpi-sub">this month, seller items</span></div>
    <div class="kpi"><span class="kpi-label">House sales</span><span class="kpi-value"><?= e(money($kpi['house_month'])) ?></span><span class="kpi-sub"><?= (int) $kpi['house_units'] ?> item<?= $kpi['house_units'] === 1 ? '' : 's' ?> this month</span></div>
    <a class="kpi <?= $kpi['pending_sellers'] ? 'kpi-alert' : '' ?>" href="<?= e(url('/admin/sellers?status=pending')) ?>"><span class="kpi-label">Seller applications</span><span class="kpi-value"><?= (int) $kpi['pending_sellers'] ?></span><span class="kpi-sub">waiting for review</span></a>
    <a class="kpi <?= $kpi['pending_products'] ? 'kpi-alert' : '' ?>" href="<?= e(url('/admin/products?status=pending')) ?>"><span class="kpi-label">Listings to approve</span><span class="kpi-value"><?= (int) $kpi['pending_products'] ?></span><span class="kpi-sub">waiting for review</span></a>
    <a class="kpi <?= $kpi['missing_photos'] ? 'kpi-alert' : '' ?>" href="<?= e(url('/admin/products?missing=1')) ?>"><span class="kpi-label">Used listings missing photos</span><span class="kpi-value"><?= (int) $kpi['missing_photos'] ?></span><span class="kpi-sub">need disc, box outside, box inside</span></a>
    <a class="kpi <?= $kpi['payouts_owed'] > 0 ? 'kpi-alert' : '' ?>" href="<?= e(url('/admin/payouts')) ?>"><span class="kpi-label">Owed to sellers</span><span class="kpi-value"><?= e(money($kpi['payouts_owed'])) ?></span><span class="kpi-sub">pending payouts</span></a>
    <a class="kpi <?= $kpi['requests_new'] ? 'kpi-alert' : '' ?>" href="<?= e(url('/admin/requests')) ?>"><span class="kpi-label">New requests</span><span class="kpi-value"><?= (int) $kpi['requests_new'] ?></span><span class="kpi-sub">buy-back, trade-in, swap</span></a>
    <a class="kpi kpi-accent" href="<?= e(url('/admin/wallet')) ?>"><span class="kpi-label">Credit owed to customers</span><span class="kpi-value"><?= e(money($kpi['credit_owed'])) ?></span><span class="kpi-sub">wallet balances</span></a>
    <a class="kpi <?= $kpi['sell_to_offer'] ? 'kpi-alert' : '' ?>" href="<?= e(url('/admin/requests?tab=buyback&status=toprice')) ?>"><span class="kpi-label">Sell requests to price</span><span class="kpi-value"><?= (int) $kpi['sell_to_offer'] ?></span><span class="kpi-sub">waiting for your offer</span></a>
    <a class="kpi" href="<?= e(url('/admin/requests?tab=buyback&status=offered')) ?>"><span class="kpi-label">Offers out</span><span class="kpi-value"><?= (int) $kpi['sell_offered'] ?></span><span class="kpi-sub">waiting for the customer</span></a>
    <a class="kpi <?= $kpi['sell_to_collect'] ? 'kpi-alert' : '' ?>" href="<?= e(url('/admin/requests?tab=buyback&status=accepted')) ?>"><span class="kpi-label">Games to collect</span><span class="kpi-value"><?= (int) $kpi['sell_to_collect'] ?></span><span class="kpi-sub">offer accepted</span></a>
    <a class="kpi <?= $kpi['sell_to_inspect'] ? 'kpi-alert' : '' ?>" href="<?= e(url('/admin/requests?tab=buyback&status=collected')) ?>"><span class="kpi-label">To inspect and pay</span><span class="kpi-value"><?= (int) $kpi['sell_to_inspect'] ?></span><span class="kpi-sub">collected games</span></a>
    <div class="kpi"><span class="kpi-label">Delivery fees</span><span class="kpi-value"><?= e(money($kpi['delivery_fees_month'])) ?></span><span class="kpi-sub">this month, delivered orders</span></div>
    <a class="kpi <?= $kpi['cash_to_collect'] > 0 ? 'kpi-alert' : '' ?>" href="<?= e(url('/admin/orders?status=picked_up')) ?>"><span class="kpi-label">Cash to collect</span><span class="kpi-value"><?= e(money($kpi['cash_to_collect'])) ?></span><span class="kpi-sub"><?= (int) $kpi['cash_orders'] ?> order<?= $kpi['cash_orders'] === 1 ? '' : 's' ?> with cash due, digital excluded</span></a>
    <a class="kpi <?= $kpi['digital_open'] ? 'kpi-alert' : '' ?>" href="<?= e(url('/admin/orders?kind=digital&open=1')) ?>"><span class="kpi-label">Digital orders to process</span><span class="kpi-value"><?= (int) $kpi['digital_open'] ?></span><span class="kpi-sub">waiting for payment or code<?= $kpi['digital_new'] > 0 ? ' (' . (int) $kpi['digital_new'] . ' unpaid)' : '' ?></span></a>
</div>

<div class="cols-2">
    <section class="card">
        <div class="card-head"><h2>Needs attention</h2></div>
        <div class="card-body">
            <?php
            $queues = [
                [$kpi['sell_to_inspect'], 'Collected games to inspect and pay', '/admin/requests?tab=buyback&status=collected'],
                [$kpi['sell_to_collect'], 'Accepted offers: games to collect', '/admin/requests?tab=buyback&status=accepted'],
                [$kpi['sell_to_offer'], 'Sell requests waiting for your offer', '/admin/requests?tab=buyback&status=toprice'],
            ];
            $queues = array_filter($queues, static fn (array $q): bool => (int) $q[0] > 0);
            ?>
            <?php if ($queues): ?>
                <h3 class="sub-title">Sell requests <span class="muted">(<?= count($queues) ?> queue<?= count($queues) === 1 ? '' : 's' ?>)</span></h3>
                <ul class="list">
                    <?php foreach ($queues as [$n, $label, $href]): ?>
                        <li>
                            <span class="tab-count hot"><?= (int) $n ?></span>
                            <a href="<?= e(url($href)) ?>"><strong><?= e($label) ?></strong></a>
                            <span class="grow"></span>
                            <a class="btn btn-sm" href="<?= e(url($href)) ?>">Open</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <h3 class="sub-title">Digital orders waiting for payment / code <span class="muted">(<?= (int) $kpi['digital_open'] ?>)</span></h3>
            <?php if (!$digitalOrders): ?>
                <p class="empty-inline">No digital orders waiting.</p>
            <?php else: ?>
                <ul class="list">
                    <?php foreach ($digitalOrders as $o): ?>
                        <li>
                            <a href="<?= e(url('/admin/orders/' . $o['id'])) ?>"><strong><?= e($o['code']) ?></strong></a>
                            <?= Forms::pill($o['status']) ?>
                            <span class="muted"><?= $o['status'] === 'new' ? 'confirm payment' : 'send the code' ?></span>
                            <span class="grow"></span>
                            <strong><?= e(money($o['digital_total'])) ?></strong>
                            <a class="btn btn-sm" href="<?= e(url('/admin/orders/' . $o['id'])) ?>">Open</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($kpi['digital_open'] > count($digitalOrders)): ?>
                    <p><a href="<?= e(url('/admin/orders?kind=digital&open=1')) ?>">See all <?= (int) $kpi['digital_open'] ?> digital orders &rarr;</a></p>
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

            <h3 class="sub-title">Seller applications <span class="muted">(<?= (int) $kpi['pending_sellers'] ?>)</span></h3>
            <?php if (!$pendingSellers): ?>
                <p class="empty-inline">No applications waiting.</p>
            <?php else: ?>
                <ul class="list">
                    <?php foreach ($pendingSellers as $s): ?>
                        <li>
                            <a href="<?= e(url('/admin/sellers/' . $s['id'])) ?>"><strong><?= e($s['name']) ?></strong></a>
                            <span class="muted"><?= e($s['code']) ?> &middot; <?= e($s['area'] ?? '') ?></span>
                            <span class="grow"></span>
                            <form method="post" action="<?= e(url('/admin/sellers/' . $s['id'] . '/approve')) ?>" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="return" value="<?= e($here) ?>">
                                <button class="btn btn-sm btn-primary" type="submit">Approve</button>
                            </form>
                            <a class="btn btn-sm" href="<?= e(url('/admin/sellers/' . $s['id'])) ?>">Review</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($kpi['pending_sellers'] > count($pendingSellers)): ?>
                    <p><a href="<?= e(url('/admin/sellers?status=pending')) ?>">See all <?= (int) $kpi['pending_sellers'] ?> applications &rarr;</a></p>
                <?php endif; ?>
            <?php endif; ?>

            <h3 class="sub-title">Listings awaiting approval <span class="muted">(<?= (int) $kpi['pending_products'] ?>)</span></h3>
            <?php if (!$pendingProducts): ?>
                <p class="empty-inline">Nothing to approve.</p>
            <?php else: ?>
                <ul class="list">
                    <?php foreach ($pendingProducts as $p): ?>
                        <li>
                            <a href="<?= e(url('/admin/products/' . $p['id'] . '/review')) ?>"><strong><?= e($p['title']) ?></strong></a>
                            <span class="muted"><?= e($p['platform'] ?? '') ?> &middot; <?= $p['seller_code'] ? e($p['seller_code']) : 'House' ?></span>
                            <?= ListingRules::conditionTag($p) ?> <?= ListingRules::photoTag($p, (int) $p['photo_count']) ?>
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
                        <a href="<?= e(url('/admin/products?missing=1')) ?>"><strong><?= (int) $kpi['missing_photos'] ?> used listing<?= $kpi['missing_photos'] === 1 ? '' : 's' ?> need disc, box outside and box inside photos</strong></a>
                        <span class="grow"></span>
                        <a class="btn btn-sm" href="<?= e(url('/admin/products?missing=1')) ?>">Show them</a>
                    </li>
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
                            <td><a href="<?= e(url('/admin/orders/' . $o['id'])) ?>"><strong><?= e($o['code']) ?></strong></a><?= (int) $o['digital_lines'] > 0 ? ' <span class="tag tag-digital">Digital</span>' : '' ?><br><small class="muted"><?= e(date('j M, H:i', strtotime($o['created_at']))) ?></small></td>
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
