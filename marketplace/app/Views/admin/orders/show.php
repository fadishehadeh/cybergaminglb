<?php
use App\Modules\Admin\Digital;
use App\Modules\Admin\Forms;
use App\Modules\Admin\OrderController;

$pageTitle = 'Order ' . $order['code'];
$nav = 'orders';
$siteName = (string) setting('site_name', 'CyberGaming Lebanon');
$waBuyer = Forms::waLink($order['buyer_phone'], 'Hi ' . $order['buyer_name'] . ', this is ' . $siteName . ' about your order ' . $order['code'] . '.');
$status = $order['status'];
$cancelled = $status === 'cancelled';
$kind = $split['kind'];                       // physical | digital | mixed
$hasDigital = $kind !== 'physical';
$flow = $kind === 'digital' ? OrderController::FLOW_DIGITAL : OrderController::FLOW;
$flowIdx = array_search($status, $flow, true);
$next = $allowed && $allowed[0] !== 'cancelled' ? $allowed[0] : null;
$skip = array_values(array_filter($allowed, static fn ($s) => $s !== 'cancelled' && $s !== $next));
$hasSeller = $totals['seller'] > 0;
$paymentConfirmed = in_array($status, ['confirmed', 'picked_up', 'delivered'], true);
$nextLabel = static function (string $st) use ($hasDigital): string {
    if ($hasDigital && $st === 'confirmed') {
        return 'Payment received: mark as confirmed';
    }
    return 'Mark as ' . strtolower(Forms::label($st));
};
?>
<div class="page-head">
    <div>
        <h1>Order <?= e($order['code']) ?> <?= Forms::pill($status) ?></h1>
        <p class="muted">Placed <?= e(date('j M Y, H:i', strtotime($order['created_at']))) ?> &middot; last update <?= e(date('j M Y, H:i', strtotime($order['updated_at']))) ?></p>
    </div>
    <div class="actions"><a class="btn btn-ghost" href="<?= e(url('/admin/orders')) ?>">&larr; All orders</a></div>
</div>

<?php if (!$cancelled && $kind === 'digital'): ?>
<section class="cash-banner banner-prepaid" aria-label="Prepaid order">
    <div>
        <span class="cash-label">PREPAID: collect via OMT/Whish</span>
        <strong class="cash-amount"><?= e(money($split['prepaid'])) ?></strong>
    </div>
    <div class="cash-math">
        <strong>Nothing to collect at the door.</strong> This order has digital items only: no courier, no pickup.
        <?= (float) $order['credit_used'] > 0 ? '<br>' . e(money($order['credit_used'])) . ' was paid with wallet credit.' : '' ?>
        <?= $split['prepaid'] <= 0 ? '<br><strong>Fully paid with credit: no OMT/Whish payment needed.</strong>' : '' ?>
    </div>
</section>
<?php elseif (!$cancelled): ?>
<section class="cash-banner" aria-label="Cash to collect">
    <div>
        <span class="cash-label">Cash to collect on delivery<?= $kind === 'mixed' ? ' (physical items only)' : '' ?></span>
        <strong class="cash-amount"><?= e(money($cashDue)) ?></strong>
    </div>
    <div class="cash-math">
        <?php if ($kind === 'mixed'): ?>
            <?= e(money((float) $split['grand'] - (float) $split['digital'])) ?> physical items<?= (float) $order['delivery_fee'] > 0 ? ' incl. ' . e(money($order['delivery_fee'])) . ' delivery' : '' ?><?= (float) $order['credit_used'] > $split['digital'] ? ' &minus; ' . e(money((float) $order['credit_used'] - (float) $split['digital'])) . ' paid with credit' : '' ?>
            <?= $cashDue <= 0 ? '<br><strong>Physical part fully paid: collect nothing at the door.</strong>' : '' ?>
        <?php else: ?>
            <?= e(money($order['total'])) ?> items<?= (float) $order['delivery_fee'] > 0 ? ' + ' . e(money($order['delivery_fee'])) . ' delivery' : '' ?><?= (float) $order['credit_used'] > 0 ? ' &minus; ' . e(money($order['credit_used'])) . ' paid with credit' : '' ?>
            <?= $cashDue <= 0 ? '<br><strong>Fully paid with credit: collect nothing.</strong>' : '' ?>
        <?php endif; ?>
    </div>
</section>
<?php if ($kind === 'mixed'): ?>
<section class="cash-banner banner-prepaid" aria-label="Prepaid digital part">
    <div>
        <span class="cash-label">Digital part: PREPAID via OMT/Whish</span>
        <strong class="cash-amount"><?= e(money($split['prepaid'])) ?></strong>
    </div>
    <div class="cash-math"><?= e(money($split['digital'])) ?> of digital items. Not part of the cash the courier collects.<?= $split['prepaid'] <= 0 ? '<br><strong>Covered by wallet credit.</strong>' : '' ?></div>
</section>
<?php endif; ?>
<?php endif; ?>

<?php if ($digitalLines && !$cancelled): ?>
<section class="card digital-panel" aria-label="Digital items">
    <div class="card-head"><h2>DIGITAL: release the code only AFTER payment is confirmed</h2><span class="tag tag-digital"><?= count($digitalLines) ?> digital line<?= count($digitalLines) === 1 ? '' : 's' ?></span></div>
    <div class="card-body">
        <ol class="digital-steps">
            <li class="<?= $paymentConfirmed ? 'done' : 'todo' ?>">
                <strong>1. Confirm the payment</strong>
                <span>Check that the <?= e(money($split['prepaid'])) ?> arrived by OMT or Whish, then set the order to <em>confirmed</em>.<?= $paymentConfirmed ? ' <b class="text-good">Done.</b>' : '' ?></span>
            </li>
            <li class="<?= $status === 'delivered' ? 'done' : ($paymentConfirmed ? 'todo' : 'wait') ?>">
                <strong>2. Send the code on WhatsApp</strong>
                <?php if (!$paymentConfirmed): ?>
                    <span class="muted">Locked until the order is confirmed. The WhatsApp message appears here once payment is confirmed.</span>
                <?php else: ?>
                    <span>Open WhatsApp with the message ready, paste the code where it says <code>[PASTE CODE]</code> and send it.</span>
                    <?php foreach ($digitalLines as $it): ?>
                        <?php $wa = Forms::waLink($order['buyer_phone'], $messages[(int) $it['id']]); ?>
                        <div class="wa-line">
                            <div>
                                <strong><?= e($it['title']) ?></strong><?= (int) $it['qty'] > 1 ? ' <span class="tag">x' . (int) $it['qty'] . ': one code each</span>' : '' ?>
                                <br><small class="muted"><?= e(Digital::kindLabel($it['digital_kind'])) ?><?= $it['digital_region'] ? ' &middot; Region ' . e($it['digital_region']) : '' ?></small>
                            </div>
                            <?php if ($wa): ?>
                                <a class="btn btn-primary btn-sm" href="<?= e($wa) ?>" target="_blank" rel="noopener">WhatsApp the code</a>
                            <?php else: ?>
                                <span class="text-warn">No usable phone number for this buyer.</span>
                            <?php endif; ?>
                            <blockquote class="wa-preview"><?= e($messages[(int) $it['id']]) ?></blockquote>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </li>
            <li class="<?= $status === 'delivered' ? 'done' : 'wait' ?>">
                <strong>3. Mark as delivered</strong>
                <span><?= $kind === 'mixed'
                    ? 'The order is delivered once the physical items are handed over too. The code can be sent as soon as payment is confirmed.'
                    : 'After the code has been sent, set the order to <em>delivered</em>. Digital sales are final once delivered.' ?><?= $status === 'delivered' ? ' <b class="text-good">Done.</b>' : '' ?></span>
            </li>
        </ol>
    </div>
</section>
<?php endif; ?>

<!-- Progress -->
<section class="card">
    <div class="card-body">
        <?php if ($cancelled): ?>
            <div class="stepper stepper-cancelled"><div class="step done"><i>&times;</i><span>Cancelled</span></div></div>
            <p class="muted">This order was cancelled. Stock was returned to the shop and any pending seller payouts were removed.<?= $refunded ? ' ' . e(money($order['credit_used'])) . ' credit was refunded to the customer wallet.' : '' ?></p>
        <?php else: ?>
            <ol class="stepper">
                <?php foreach ($flow as $i => $st): ?>
                    <li class="step <?= $i < $flowIdx ? 'done' : ($i === $flowIdx ? 'current' : '') ?>"><i><?= $i < $flowIdx ? '&check;' : $i + 1 ?></i><span><?= e(Forms::label($st)) ?></span></li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>

        <?php if ($allowed): ?>
            <div class="status-actions">
                <?php if ($next): ?>
                    <?php
                    $nextConfirm = '';
                    if ($hasDigital && $next === 'confirmed') {
                        $nextConfirm = 'Confirm only if the OMT/Whish payment has really arrived. The code must not be sent before that.';
                    } elseif ($hasDigital && $next === 'delivered') {
                        $nextConfirm = 'Mark as delivered? Do this after the code has been sent on WhatsApp.' . ($hasSeller ? ' This also queues the seller payouts.' : '');
                    } elseif ($next === 'delivered' && $hasSeller) {
                        $nextConfirm = 'Mark as delivered? This queues the seller payouts.';
                    }
                    ?>
                    <form method="post" action="<?= e(url('/admin/orders/' . $order['id'] . '/status')) ?>" class="inline-form" <?= $nextConfirm !== '' ? 'data-confirm="' . e($nextConfirm) . '"' : '' ?>>
                        <?= csrf_field() ?><input type="hidden" name="status" value="<?= e($next) ?>">
                        <button class="btn btn-primary btn-lg" type="submit"><?= e($nextLabel($next)) ?> &rarr;</button>
                    </form>
                <?php endif; ?>
                <?php foreach ($skip as $st): ?>
                    <form method="post" action="<?= e(url('/admin/orders/' . $order['id'] . '/status')) ?>" class="inline-form" data-confirm="Skip ahead and mark this order as <?= e(strtolower(Forms::label($st))) ?>?">
                        <?= csrf_field() ?><input type="hidden" name="status" value="<?= e($st) ?>">
                        <button class="btn" type="submit">Skip to <?= e(strtolower(Forms::label($st))) ?></button>
                    </form>
                <?php endforeach; ?>
                <form method="post" action="<?= e(url('/admin/orders/' . $order['id'] . '/status')) ?>" class="inline-form push-right" data-confirm="Cancel order <?= e($order['code']) ?>?<?= $kind === 'digital' ? ' Digital stock is managed by hand and is not changed.' : ' Items go back into stock and pending seller payouts are removed.' ?><?= (float) $order['credit_used'] > 0 && $order['user_id'] ? ' ' . e(money($order['credit_used'])) . ' credit is refunded to the customer.' : '' ?><?= $hasDigital && $paymentConfirmed ? ' If a code was already sent on WhatsApp, cancelling does not take it back.' : '' ?>">
                    <?= csrf_field() ?><input type="hidden" name="status" value="cancelled">
                    <button class="btn btn-danger" type="submit">Cancel order</button>
                </form>
            </div>
        <?php elseif (!$cancelled): ?>
            <p class="muted">Delivered.<?= $hasSeller ? ' Seller payouts have been queued on the <a href="' . e(url('/admin/payouts')) . '">Payouts</a> page.' : '' ?><?= $hasDigital ? ' Digital sales are final once delivered.' : '' ?></p>
        <?php endif; ?>
    </div>
</section>

<div class="cols-2">
    <section class="card">
        <div class="card-head"><h2>Buyer</h2><?php if ($waBuyer): ?><a class="btn btn-sm" href="<?= e($waBuyer) ?>" target="_blank" rel="noopener">WhatsApp buyer</a><?php endif; ?></div>
        <dl class="kv">
            <dt>Name</dt><dd><?= $customer ? '<a href="' . e(url('/admin/customers/' . $customer['id'])) . '"><strong>' . e($order['buyer_name']) . '</strong></a> <span class="tag">account</span>' : e($order['buyer_name']) . ' <span class="tag">guest</span>' ?></dd>
            <?php if ($customer): ?><dt>Wallet</dt><dd><?= e(money($customer['credit_balance'])) ?> credit now</dd><?php endif; ?>
            <dt>Phone</dt><dd><?= $waBuyer ? '<a href="' . e($waBuyer) . '" target="_blank" rel="noopener">' . e($order['buyer_phone']) . '</a>' : e($order['buyer_phone']) ?></dd>
            <dt>Area</dt><dd><?= e($order['buyer_area']) ?></dd>
            <dt>Address</dt><dd><?= $order['buyer_address'] ? e($order['buyer_address']) : '<span class="muted">-</span>' ?></dd>
            <dt>Buyer's note</dt><dd><?= $order['buyer_note'] ? nl2br(e($order['buyer_note'])) : '<span class="muted">-</span>' ?></dd>
        </dl>
    </section>

    <section class="card">
        <div class="card-head"><h2>Totals</h2></div>
        <dl class="kv kv-totals">
            <dt>Items subtotal</dt><dd><?= e(money($order['total'])) ?></dd>
            <dt>Delivery fee (<?= e($order['buyer_area']) ?>)</dt><dd><?= e(money($order['delivery_fee'])) ?></dd>
            <dt>Order total</dt><dd><strong><?= e(money((float) $order['grand_total'] > 0 ? $order['grand_total'] : (float) $order['total'] + (float) $order['delivery_fee'])) ?></strong></dd>
            <dt>Paid with credit</dt><dd><?= (float) $order['credit_used'] > 0 ? '&minus;' . e(money($order['credit_used'])) . ($refunded ? ' <small class="muted">(refunded)</small>' : '') : '<span class="muted">$0</span>' ?></dd>
            <dt>Cash to collect</dt><dd><strong class="big cash-due"><?= $cancelled ? '-' : e(money($cashDue)) ?></strong><?= !$cancelled && $kind === 'digital' ? '<br><small class="muted">prepaid: nothing at the door</small>' : '' ?></dd>
            <?php if ($hasDigital): ?><dt>Prepaid digital (OMT/Whish)</dt><dd><strong><?= $cancelled ? '-' : e(money($split['prepaid'])) ?></strong></dd><?php endif; ?>
            <dt>Owed to sellers</dt><dd><?= e(money($totals['seller'])) ?></dd>
            <dt>Our commission</dt><dd class="text-good"><strong><?= e(money($totals['commission'])) ?></strong></dd>
            <dt>House stock revenue</dt><dd><?= e(money($totals['house'])) ?></dd>
            <?php if (abs($totals['buyer'] - (float) $order['total']) > 0.009): ?>
                <dt class="text-warn">Lines add up to</dt><dd class="text-warn"><?= e(money($totals['buyer'])) ?> (differs from the items subtotal)</dd>
            <?php endif; ?>
        </dl>
    </section>
</div>

<section class="card">
    <div class="card-head"><h2>Items</h2><small class="muted">Seller details are visible to admins only</small></div>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Product</th><th class="num">Qty</th><th class="num">Buyer price</th><th class="num">Seller price</th><th class="num">Commission</th><th>Seller (arrange pickup)</th><th>Payout</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= $it['product_id'] ? '<a href="' . e(url('/admin/products/' . $it['product_id'] . '/edit')) . '">' . e($it['title']) . '</a>' : e($it['title']) . ' <small class="muted">(deleted)</small>' ?><?= (int) $it['is_digital'] === 1 ? ' <span class="tag tag-digital">Digital</span>' : '' ?></td>
                    <td class="num"><?= (int) $it['qty'] ?></td>
                    <td class="num"><?= e(money($it['unit_price'])) ?><?= (int) $it['qty'] > 1 ? '<br><small class="muted">= ' . e(money($it['unit_price'] * $it['qty'])) . '</small>' : '' ?></td>
                    <?php if ($it['seller_id']): ?>
                        <td class="num"><?= e(money($it['seller_price'])) ?><?= (int) $it['qty'] > 1 ? '<br><small class="muted">= ' . e(money($it['seller_price'] * $it['qty'])) . '</small>' : '' ?></td>
                        <td class="num"><?= e(money(($it['unit_price'] - $it['seller_price']) * $it['qty'])) ?></td>
                        <td>
                            <a href="<?= e(url('/admin/sellers/' . $it['seller_id'])) ?>"><strong><?= e($it['seller_code']) ?></strong></a> &middot; <?= e($it['seller_name']) ?><br>
                            <small class="muted"><?= e($it['seller_area'] ?? '-') ?></small>
                            <?php $waS = Forms::waLink($it['seller_phone'], 'Hi ' . $it['seller_name'] . ', your item "' . $it['title'] . '" was sold on ' . $siteName . '. When can we pick it up?'); ?>
                            <?php if ($waS): ?><br><a href="<?= e($waS) ?>" target="_blank" rel="noopener"><?= e($it['seller_phone']) ?></a><?php elseif ($it['seller_phone']): ?><br><?= e($it['seller_phone']) ?><?php endif; ?>
                        </td>
                        <td><?= $it['payout_status'] ? Forms::pill($it['payout_status']) : '<span class="muted">not yet</span>' ?></td>
                    <?php else: ?>
                        <td class="num muted">-</td><td class="num muted">-</td>
                        <td><span class="tag">House stock</span><?= (int) $it['is_digital'] === 1 ? ' <small class="muted">prepaid, no pickup</small>' : '' ?></td><td class="muted">-</td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <div class="card-head"><h2>Admin note</h2></div>
    <div class="card-body">
        <form method="post" action="<?= e(url('/admin/orders/' . $order['id'] . '/note')) ?>">
            <?= csrf_field() ?>
            <div class="field">
                <label for="admin_note" class="sr-only">Admin note</label>
                <textarea id="admin_note" name="admin_note" rows="3" maxlength="5000" placeholder="Pickup time, delivery arrangements, payment received..."><?= e($order['admin_note'] ?? '') ?></textarea>
            </div>
            <button class="btn" type="submit">Save note</button>
        </form>
    </div>
</section>
