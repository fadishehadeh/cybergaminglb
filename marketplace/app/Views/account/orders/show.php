<?php
use App\Modules\Account\AccountUi;
use App\Modules\Storefront\Ui;

/** @var array $me @var array $order @var array $items @var float $subtotal @var float $delivery @var float $grand @var float $credit @var float $cashDue @var string $waLink */
$meta = ['title' => 'Order ' . $order['code'] . ' | CyberGaming Lebanon', 'description' => 'Your order details.', 'noindex' => true];
$accountNav = 'orders';
require base_path('app/Views/account/_nav.php');

[$statusLabel, $statusMod] = AccountUi::orderStatus($order['status']);
$steps = [
    'new'       => ['Received', 'We got your order.'],
    'confirmed' => ['Confirmed', 'We called you to confirm delivery.'],
    'picked_up' => ['On its way', 'Your order is out for delivery.'],
    'delivered' => ['Delivered', 'Enjoy your games!'],
];
$order_keys = array_keys($steps);
$reached = array_search($order['status'], $order_keys, true);
$cancelled = $order['status'] === 'cancelled';
?>
<div class="container acc-page">
    <?= Ui::breadcrumbs([['My account', '/account'], ['My orders', '/account/orders'], [$order['code'], null]]) ?>
    <div class="acc-title-row">
        <h1>Order <span class="order-code"><?= e($order['code']) ?></span></h1>
        <?= AccountUi::pill($statusLabel, $statusMod) ?>
    </div>
    <p class="lead-sm">Placed on <?= e(AccountUi::date($order['created_at'], true)) ?>.</p>

    <div class="acc-grid acc-grid-order">
        <div>
            <section class="acc-card" aria-labelledby="track-h">
                <h2 id="track-h">Status</h2>
                <?php if ($cancelled): ?>
                    <p class="acc-note acc-note-off">This order was cancelled. If you paid part of it with credit, the credit goes back to your wallet. Questions? Message us on WhatsApp.</p>
                <?php else: ?>
                    <ol class="timeline">
                        <?php foreach ($steps as $key => [$title, $text]):
                            $idx = array_search($key, $order_keys, true);
                            $finished = $order['status'] === 'delivered';
                            $state = $reached !== false && ($idx < $reached || ($finished && $idx === $reached)) ? 'done' : ($idx === $reached ? 'current' : 'todo');
                            ?>
                            <li class="tl-<?= $state ?>"<?= $state === 'current' ? ' aria-current="step"' : '' ?>>
                                <span class="tl-dot" aria-hidden="true"><?= $state === 'todo' ? '' : Ui::icon('check', 14) ?></span>
                                <div><strong><?= e($title) ?></strong><small><?= e($text) ?></small><span class="sr-only"> (<?= $state === 'done' ? 'completed' : ($state === 'current' ? 'current step' : 'not yet') ?>)</span></div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
                <p><a class="btn btn-wa" href="<?= e($waLink) ?>" rel="noopener" target="_blank"><?= Ui::icon('whatsapp', 20) ?> Message us about this order</a></p>
            </section>

            <section class="acc-card" aria-labelledby="items-h">
                <h2 id="items-h">Items</h2>
                <ul class="acc-list">
                    <?php foreach ($items as $i): ?>
                        <li>
                            <span class="acc-list-main"><?= e($i['title']) ?><small><?= (int) $i['qty'] ?> &times; <?= e(AccountUi::amount($i['unit_price'])) ?></small></span>
                            <strong><?= e(AccountUi::amount((float) $i['unit_price'] * (int) $i['qty'])) ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </div>

        <aside class="acc-card acc-summary" aria-labelledby="sum-h">
            <h2 id="sum-h">Payment</h2>
            <dl class="acc-dl">
                <div><dt>Items</dt><dd><?= e(AccountUi::amount($subtotal)) ?></dd></div>
                <div><dt>Delivery</dt><dd><?= $delivery > 0 ? e(AccountUi::amount($delivery)) : 'Free' ?></dd></div>
                <div class="acc-dl-total"><dt>Order total</dt><dd><?= e(AccountUi::amount($grand)) ?></dd></div>
                <?php if ($credit > 0): ?>
                    <div class="acc-dl-credit"><dt>Paid with credit</dt><dd>-<?= e(AccountUi::amount($credit)) ?></dd></div>
                <?php endif; ?>
                <div class="acc-dl-due">
                    <dt><?= $order['status'] === 'delivered' ? 'Paid in cash' : ($cancelled ? 'Cash due' : 'Cash due on delivery') ?></dt>
                    <dd><?= e(AccountUi::amount($cashDue)) ?></dd>
                </div>
            </dl>
            <h3 class="summary-sub">Delivery to</h3>
            <address class="acc-address">
                <?= e($order['buyer_area']) ?><?= $order['buyer_address'] ? '<br>' . e($order['buyer_address']) : '' ?>
            </address>
            <?php if ($order['buyer_note']): ?><p class="fine">Your note: <?= e($order['buyer_note']) ?></p><?php endif; ?>
        </aside>
    </div>
</div>
