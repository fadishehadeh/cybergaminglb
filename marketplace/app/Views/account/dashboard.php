<?php
use App\Modules\Account\AccountUi;
use App\Modules\Storefront\Ui;

/** @var array $me @var float $balance @var array $offers @var int $openOffers @var array $ledger @var array $orders @var string[] $missing */
$meta = ['title' => 'My account | CyberGaming Lebanon', 'description' => 'Your CyberGaming account.', 'noindex' => true];
$accountNav = 'dashboard';
require base_path('app/Views/account/_nav.php');
?>
<div class="container acc-page">
    <h1 class="sr-only">My account</h1>

    <?php foreach ($offers as $o): ?>
        <div class="acc-alert" role="status">
            <div>
                <strong>We made you an offer</strong>
                <span>for your <?= $o['kind'] === 'trade_in' ? 'trade-in' : 'sell' ?> request <?= e($o['code']) ?>:
                    <?php if ($o['offer_credit'] !== null): ?><strong><?= e(AccountUi::amount($o['offer_credit'])) ?> credit</strong><?php endif; ?>
                    <?php if ($o['offer_credit'] !== null && $o['offer_cash'] !== null): ?> or <?php endif; ?>
                    <?php if ($o['offer_cash'] !== null): ?><strong><?= e(AccountUi::amount($o['offer_cash'])) ?> cash</strong><?php endif; ?>.</span>
            </div>
            <a class="btn btn-primary btn-sm" href="<?= e(url('/account/offers/' . $o['code'])) ?>">Review offer</a>
        </div>
    <?php endforeach; ?>

    <div class="acc-grid acc-grid-top">
        <section class="wallet-card" aria-labelledby="bal-h">
            <h2 id="bal-h">Your wallet</h2>
            <p class="wallet-balance"><strong><?= e(AccountUi::amount($balance)) ?></strong> <span>credit</span></p>
            <p class="wallet-sub">1 credit = $1. Never expires. Spend it in the shop and pay any difference in cash.</p>
            <div class="wallet-actions">
                <a class="btn btn-primary" href="<?= e(url('/sell')) ?>"><?= Ui::icon('tag', 18) ?> Sell games</a>
                <a class="btn btn-outline-light" href="<?= e(url('/shop')) ?>"><?= Ui::icon('cart', 18) ?> Shop</a>
                <a class="btn btn-outline-light" href="<?= e(url('/account/wallet')) ?>">Wallet history</a>
            </div>
        </section>

        <section class="acc-card" aria-labelledby="quick-h">
            <h2 id="quick-h">At a glance</h2>
            <ul class="acc-stats">
                <li><a href="<?= e(url('/account/offers')) ?>"><strong><?= (int) $openOffers + count($offers) ?></strong><span>open sell requests</span></a></li>
                <li><a href="<?= e(url('/account/orders')) ?>"><strong><?= count($orders) > 0 ? e($orders[0]['code']) : '&ndash;' ?></strong><span>latest order</span></a></li>
            </ul>
            <?php if ($missing): ?>
                <div class="acc-hint">
                    <?= Ui::icon('pin', 20) ?>
                    <p>Add <?= e(implode(' and ', $missing)) ?> to check out faster. <a href="<?= e(url('/account/profile')) ?>">Complete your profile</a>.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div class="acc-grid">
        <section class="acc-card" aria-labelledby="led-h">
            <div class="acc-card-head">
                <h2 id="led-h">Recent wallet activity</h2>
                <a href="<?= e(url('/account/wallet')) ?>">See all</a>
            </div>
            <?php if (!$ledger): ?>
                <p class="acc-empty">No credit yet. <a href="<?= e(url('/sell')) ?>">Sell a game</a> to earn your first credit.</p>
            <?php else: ?>
                <ul class="acc-list">
                    <?php foreach ($ledger as $r): ?>
                        <li>
                            <span class="acc-list-main">
                                <?php if ($r['link']): ?><a href="<?= e(url($r['link'])) ?>"><?= e($r['label']) ?></a><?php else: ?><?= e($r['label']) ?><?php endif; ?>
                                <small><?= e(AccountUi::date($r['created_at'])) ?></small>
                            </span>
                            <strong class="amt <?= (float) $r['amount'] < 0 ? 'amt-neg' : 'amt-pos' ?>"><?= e(AccountUi::signed($r['amount'])) ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="acc-card" aria-labelledby="ord-h">
            <div class="acc-card-head">
                <h2 id="ord-h">Latest orders</h2>
                <a href="<?= e(url('/account/orders')) ?>">See all</a>
            </div>
            <?php if (!$orders): ?>
                <p class="acc-empty">No orders yet. <a href="<?= e(url('/shop')) ?>">Browse the shop</a>.</p>
            <?php else: ?>
                <ul class="acc-list">
                    <?php foreach ($orders as $o): [$label, $mod] = AccountUi::orderStatus($o['status']); ?>
                        <li>
                            <span class="acc-list-main">
                                <a href="<?= e(url('/account/orders/' . $o['code'])) ?>"><?= e($o['code']) ?></a>
                                <small><?= e(AccountUi::date($o['created_at'])) ?></small>
                            </span>
                            <?= AccountUi::pill($label, $mod) ?>
                            <strong><?= e(AccountUi::amount((float) $o['grand_total'] > 0 ? $o['grand_total'] : (float) $o['total'] + (float) $o['delivery_fee'])) ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>
