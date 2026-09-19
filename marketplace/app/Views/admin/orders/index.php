<?php
use App\Modules\Admin\Forms;
use App\Modules\Admin\OrderController;

$pageTitle = 'Orders';
$nav = 'orders';
$allCount = array_sum($counts);
$hasFilter = $q !== '' || $status !== '' || $kind !== '' || $open;

/** Orders list URL with the current filters, overriding some of them. */
$link = static function (array $over = []) use ($status, $q, $kind, $open): string {
    $params = array_filter(['status' => $status, 'kind' => $kind, 'q' => $q, 'open' => $open ? '1' : ''] + [], static fn ($v) => $v !== '' && $v !== null);
    foreach ($over as $k => $v) {
        if ($v === '' || $v === null) {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    return url('/admin/orders') . ($params ? '?' . http_build_query($params) : '');
};
?>
<div class="page-head">
    <div>
        <h1>Orders</h1>
        <p class="muted"><?= (int) $allCount ?> order<?= $allCount === 1 ? '' : 's' ?> in total</p>
    </div>
</div>

<div class="tabs">
    <a href="<?= e($link(['status' => '', 'open' => ''])) ?>" class="<?= $status === '' && !$open ? 'active' : '' ?>">All <span class="tab-count"><?= (int) $allCount ?></span></a>
    <?php foreach (OrderController::STATUSES as $st): ?>
        <a href="<?= e($link(['status' => $st, 'open' => ''])) ?>" class="<?= $status === $st ? 'active' : '' ?>"><?= e(Forms::label($st)) ?> <span class="tab-count"><?= (int) ($counts[$st] ?? 0) ?></span></a>
    <?php endforeach; ?>
</div>

<form method="get" action="<?= e(url('/admin/orders')) ?>" class="card filters">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <?php if ($open): ?><input type="hidden" name="open" value="1"><?php endif; ?>
    <div class="field grow-2">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Order code, buyer name or phone">
    </div>
    <div class="field">
        <label for="f-kind">Type</label>
        <select id="f-kind" name="kind"><?= Forms::options(['' => 'All orders', 'physical' => 'Physical only', 'digital' => 'With digital items'], $kind) ?></select>
    </div>
    <div class="filter-actions">
        <button class="btn btn-primary" type="submit">Filter</button>
        <?php if ($hasFilter): ?><a class="btn btn-ghost" href="<?= e(url('/admin/orders')) ?>">Clear</a><?php endif; ?>
    </div>
</form>
<?php if ($open): ?>
    <p class="muted">Showing orders still to process: new (waiting for payment) and confirmed (waiting for the hand-over or code).</p>
<?php endif; ?>

<section class="card">
    <?php if (!$orders): ?>
        <div class="empty">
            <strong><?= $hasFilter ? 'No orders match' : 'No orders yet' ?></strong>
            <p><?= $hasFilter ? 'Try a different status, type or search.' : 'When customers check out on the shop, their orders appear here for you to confirm and arrange.' ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Order</th><th>Placed</th><th>Buyer</th><th>Phone</th><th class="num">Items</th><th class="num">Total</th><th class="num">Credit used</th><th class="num">Cash to collect</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($orders as $o): $sp = $o['split']; ?>
                    <tr>
                        <td><a href="<?= e(url('/admin/orders/' . $o['id'])) ?>"><strong><?= e($o['code']) ?></strong></a><?php if ($sp['kind'] !== 'physical'): ?> <span class="tag tag-digital" title="<?= $sp['kind'] === 'digital' ? 'Digital items only: prepaid' : 'Physical and digital items' ?>"><?= $sp['kind'] === 'digital' ? 'Digital' : 'Digital + physical' ?></span><?php endif; ?></td>
                        <td class="nowrap"><?= e(date('j M Y, H:i', strtotime($o['created_at']))) ?></td>
                        <td><?= $o['user_id'] ? '<a href="' . e(url('/admin/customers/' . $o['user_id'])) . '">' . e($o['buyer_name']) . '</a>' : e($o['buyer_name']) ?><br><small class="muted"><?= e($o['buyer_area']) ?></small></td>
                        <td class="nowrap"><?php $wa = Forms::waLink($o['buyer_phone']); ?><?= $wa ? '<a href="' . e($wa) . '" target="_blank" rel="noopener">' . e($o['buyer_phone']) . '</a>' : e($o['buyer_phone']) ?></td>
                        <td class="num"><?= (int) $o['units'] ?><?= (int) $o['seller_lines'] > 0 ? ' <small class="muted" title="Lines from sellers">(' . (int) $o['seller_lines'] . ' seller)</small>' : '' ?></td>
                        <td class="num"><strong><?= e(money($o['grand'])) ?></strong><?= (float) $o['delivery_fee'] > 0 ? '<br><small class="muted">incl. ' . e(money($o['delivery_fee'])) . ' delivery</small>' : '' ?></td>
                        <td class="num"><?= (float) $o['credit_used'] > 0 ? e(money($o['credit_used'])) : '<span class="muted">-</span>' ?></td>
                        <td class="num">
                            <?php if ($o['status'] === 'cancelled'): ?>
                                <span class="muted">-</span>
                            <?php elseif ($sp['kind'] === 'digital'): ?>
                                <span class="muted" title="Prepaid via OMT/Whish, nothing to collect at the door">prepaid</span>
                            <?php else: ?>
                                <strong class="cash-due"><?= e(money($sp['cash'])) ?></strong>
                                <?= $sp['kind'] === 'mixed' ? '<br><small class="muted">+ ' . e(money($sp['prepaid'])) . ' prepaid</small>' : '' ?>
                            <?php endif; ?>
                        </td>
                        <td><?= Forms::pill($o['status']) ?></td>
                        <td class="num"><a class="btn btn-sm" href="<?= e(url('/admin/orders/' . $o['id'])) ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= $pager->render() ?>
    <?php endif; ?>
</section>
