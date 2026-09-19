<?php
use App\Modules\Admin\Forms;

$pageTitle = 'Seller ' . $seller['code'];
$nav = 'sellers';
$wa = Forms::waLink($seller['phone'], 'Hi ' . $seller['name'] . ', this is ' . setting('site_name', 'CyberGaming Lebanon') . '.');
$effective = \App\Support\Pricing::commissionPct($seller);
?>
<div class="page-head">
    <div>
        <h1><?= e($seller['name']) ?> <span class="tag"><?= e($seller['code']) ?></span> <span class="tag <?= $seller['type'] === 'member' ? 'tag-member' : '' ?>"><?= $seller['type'] === 'member' ? 'Member' : 'Store' ?></span></h1>
        <p class="muted"><?= Forms::pill($seller['status']) ?> &middot; joined <?= e(date('j M Y', strtotime($seller['created_at']))) ?></p>
    </div>
    <div class="actions">
        <?php if ($seller['status'] !== 'active'): ?>
            <form method="post" action="<?= e(url('/admin/sellers/' . $seller['id'] . '/approve')) ?>" class="inline-form">
                <?= csrf_field() ?><input type="hidden" name="return" value="<?= e('/admin/sellers/' . $seller['id']) ?>">
                <button class="btn btn-primary" type="submit"><?= $seller['status'] === 'pending' ? 'Approve application' : 'Re-activate' ?></button>
            </form>
        <?php endif; ?>
        <?php if ($seller['status'] !== 'suspended'): ?>
            <form method="post" action="<?= e(url('/admin/sellers/' . $seller['id'] . '/suspend')) ?>" class="inline-form" data-confirm="<?= e(($seller['status'] === 'pending' ? 'Reject this application' : 'Suspend this seller') . '? They will not be able to sign in.') ?>">
                <?= csrf_field() ?><input type="hidden" name="return" value="<?= e('/admin/sellers/' . $seller['id']) ?>">
                <button class="btn btn-danger" type="submit"><?= $seller['status'] === 'pending' ? 'Reject' : 'Suspend' ?></button>
            </form>
        <?php endif; ?>
        <?php if ($wa): ?><a class="btn" href="<?= e($wa) ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
        <a class="btn" href="<?= e(url('/admin/products/create?seller=' . $seller['id'])) ?>">+ Add product</a>
        <a class="btn btn-primary" href="<?= e(url('/admin/sellers/' . $seller['id'] . '/edit')) ?>">Edit seller</a>
    </div>
</div>

<div class="kpis kpis-small">
    <div class="kpi"><span class="kpi-label">Sold (their share)</span><span class="kpi-value"><?= e(money($stats['sold'])) ?></span><span class="kpi-sub">delivered orders</span></div>
    <div class="kpi <?= $stats['pending'] > 0 ? 'kpi-alert' : '' ?>"><span class="kpi-label">Owed to seller</span><span class="kpi-value"><?= e(money($stats['pending'])) ?></span><span class="kpi-sub">pending payouts</span></div>
    <div class="kpi"><span class="kpi-label">Paid so far</span><span class="kpi-value"><?= e(money($stats['paid'])) ?></span><span class="kpi-sub">all time</span></div>
    <div class="kpi"><span class="kpi-label">Commission</span><span class="kpi-value"><?= e(Forms::pct($effective)) ?></span><span class="kpi-sub"><?= $seller['commission_pct'] !== null ? 'seller override' : 'global default' ?></span></div>
</div>

<div class="cols-2">
    <section class="card">
        <div class="card-head"><h2>Contact &amp; payout</h2></div>
        <dl class="kv">
            <dt>Phone</dt><dd><?= $wa ? '<a href="' . e($wa) . '" target="_blank" rel="noopener">' . e($seller['phone']) . '</a>' : e($seller['phone'] ?? '-') ?></dd>
            <dt>Area</dt><dd><?= e($seller['area'] ?? '-') ?></dd>
            <dt>Payout method</dt><dd><?= e($seller['payout_method'] ?? '-') ?></dd>
            <?php if ($seller['type'] === 'member' && $login): ?><dt>Customer account</dt><dd><a href="<?= e(url('/admin/customers/' . $login['id'])) ?>"><strong><?= e($login['name']) ?></strong></a> <span class="muted">(payouts can be added to their wallet)</span></dd><?php endif; ?>
            <dt>Portal login</dt><dd><?= $login ? e($login['email']) . ' <span class="muted">(' . e($login['status']) . ($login['last_login_at'] ? ', last seen ' . e(date('j M Y', strtotime($login['last_login_at']))) : ', never signed in') . ')</span>' : '<span class="muted">none</span>' ?></dd>
            <dt>Notes</dt><dd><?= $seller['notes'] ? nl2br(e($seller['notes'])) : '<span class="muted">-</span>' ?></dd>
        </dl>
    </section>

    <section class="card">
        <div class="card-head"><h2>Payouts</h2><a href="<?= e(url('/admin/payouts')) ?>">Payouts page</a></div>
        <?php if (!$payouts): ?>
            <div class="empty"><strong>No payouts yet</strong><p>A payout is created for each sold item once its order is delivered.</p></div>
        <?php else: ?>
            <div class="table-wrap"><table class="data">
                <thead><tr><th>Order</th><th>Item</th><th class="num">Amount</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($payouts as $pa): ?>
                    <tr>
                        <td><?= $pa['order_id'] ? '<a href="' . e(url('/admin/orders/' . $pa['order_id'])) . '">' . e($pa['order_code']) . '</a>' : '-' ?></td>
                        <td><?= e($pa['title'] ?? '-') ?></td>
                        <td class="num"><?= e(money($pa['amount'])) ?></td>
                        <td><?= Forms::pill($pa['status']) ?><?= $pa['paid_at'] ? '<br><small class="muted">' . e(date('j M Y', strtotime($pa['paid_at']))) . ($pa['note'] ? ' &middot; ' . e($pa['note']) : '') . '</small>' : '' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table></div>
        <?php endif; ?>
    </section>
</div>

<section class="card">
    <div class="card-head"><h2>Products (<?= count($products) ?>)</h2></div>
    <?php if (!$products): ?>
        <div class="empty"><strong>No products yet</strong><p>Listings for this seller will appear here.</p><a class="btn btn-primary" href="<?= e(url('/admin/products/create?seller=' . $seller['id'])) ?>">+ Add product</a></div>
    <?php else: ?>
        <div class="table-wrap"><table class="data">
            <thead><tr><th></th><th>Title</th><th>Platform</th><th class="num">Seller price &rarr; Buyer price</th><th class="num">Stock</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td class="thumb-cell"><?php if ($p['image']): ?><img class="thumb" src="<?= e(media($p['image'])) ?>" alt="" loading="lazy"><?php else: ?><span class="thumb thumb-empty"></span><?php endif; ?></td>
                    <td><a href="<?= e(url('/admin/products/' . $p['id'] . '/edit')) ?>"><strong><?= e($p['title']) ?></strong></a></td>
                    <td><?= e($p['platform'] ?? '-') ?></td>
                    <td class="num"><?= e(money($p['seller_price'])) ?> &rarr; <strong><?= e(money($p['price'])) ?></strong></td>
                    <td class="num"><?= (int) $p['stock'] ?></td>
                    <td><?= Forms::pill($p['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table></div>
    <?php endif; ?>
</section>

<section class="card">
    <div class="card-head"><h2>Order lines sold (<?= count($lines) ?>)</h2></div>
    <?php if (!$lines): ?>
        <div class="empty"><strong>Nothing sold yet</strong></div>
    <?php else: ?>
        <div class="table-wrap"><table class="data">
            <thead><tr><th>Order</th><th>Item</th><th class="num">Qty</th><th class="num">Buyer paid</th><th class="num">Seller gets</th><th>Order status</th><th>Payout</th></tr></thead>
            <tbody>
            <?php foreach ($lines as $l): ?>
                <tr>
                    <td><a href="<?= e(url('/admin/orders/' . $l['order_id'])) ?>"><strong><?= e($l['code']) ?></strong></a><br><small class="muted"><?= e(date('j M Y', strtotime($l['created_at']))) ?></small></td>
                    <td><?= e($l['title']) ?></td>
                    <td class="num"><?= (int) $l['qty'] ?></td>
                    <td class="num"><?= e(money($l['unit_price'] * $l['qty'])) ?></td>
                    <td class="num"><?= e(money($l['seller_price'] * $l['qty'])) ?></td>
                    <td><?= Forms::pill($l['status']) ?></td>
                    <td><?= $l['payout_status'] ? Forms::pill($l['payout_status']) : '<span class="muted">-</span>' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table></div>
    <?php endif; ?>
</section>
