<?php
use App\Modules\Account\AccountUi;
use App\Modules\Storefront\Ui;

/** @var array $me @var array $orders */
$meta = ['title' => 'My orders | CyberGaming Lebanon', 'description' => 'Your CyberGaming orders.', 'noindex' => true];
$accountNav = 'orders';
require base_path('app/Views/account/_nav.php');
?>
<div class="container acc-page">
    <h1>My orders</h1>
    <?php if (!$orders): ?>
        <div class="empty">
            <?= Ui::icon('box', 40) ?>
            <h2>No orders yet</h2>
            <p>When you check out while signed in, your orders appear here.</p>
            <a class="btn btn-primary" href="<?= e(url('/shop')) ?>">Browse the shop</a>
        </div>
    <?php else: ?>
        <ul class="acc-cards">
            <?php foreach ($orders as $o):
                [$label, $mod] = AccountUi::orderStatus($o['status']);
                $grand = (float) $o['grand_total'] > 0 ? (float) $o['grand_total'] : (float) $o['total'] + (float) $o['delivery_fee'];
                ?>
                <li class="acc-row-card">
                    <div class="acc-row-main">
                        <a class="acc-row-title" href="<?= e(url('/account/orders/' . $o['code'])) ?>"><?= e($o['code']) ?></a>
                        <small><?= e(AccountUi::date($o['created_at'])) ?> &middot; <?= (int) $o['item_count'] ?> <?= (int) $o['item_count'] === 1 ? 'item' : 'items' ?></small>
                    </div>
                    <?= AccountUi::pill($label, $mod) ?>
                    <?php if (($o['payment_status'] ?? '') === 'awaiting' && $o['status'] !== 'cancelled'): ?><?= AccountUi::pill('Awaiting your payment', 'warn') ?><?php endif; ?>
                    <div class="acc-row-amount">
                        <strong><?= e(AccountUi::amount($grand)) ?></strong>
                        <?php if ((float) $o['credit_used'] > 0): ?><small><?= e(AccountUi::amount($o['credit_used'])) ?> paid with credit</small><?php endif; ?>
                    </div>
                    <a class="btn btn-ghost btn-sm" href="<?= e(url('/account/orders/' . $o['code'])) ?>">View<span class="sr-only"> order <?= e($o['code']) ?></span></a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
