<?php
use App\Modules\Account\AccountUi;
use App\Modules\Storefront\Ui;

/** @var array $me @var array $offers */
$meta = ['title' => 'My sell offers | CyberGaming Lebanon', 'description' => 'Your sell and trade-in requests and our offers.', 'noindex' => true];
$accountNav = 'offers';
require base_path('app/Views/account/_nav.php');
?>
<div class="container acc-page">
    <div class="acc-title-row">
        <h1>Sell offers</h1>
        <a class="btn btn-primary btn-sm" href="<?= e(url('/sell')) ?>"><?= Ui::icon('tag', 18) ?> Sell more games</a>
    </div>
    <p class="lead-sm">Your sell requests and what we offer for them. Take your money as cash, or as wallet credit for a better deal.</p>

    <?php if (!$offers): ?>
        <div class="empty">
            <?= Ui::icon('gamepad', 40) ?>
            <h2>No sell requests yet</h2>
            <p>Get an instant quote for your games. When you send a request while signed in, it shows up here.</p>
            <a class="btn btn-primary" href="<?= e(url('/sell')) ?>">Get a quote</a>
        </div>
    <?php else: ?>
        <ul class="acc-cards">
            <?php foreach ($offers as $o):
                [$label, $mod] = AccountUi::offerStatus($o['status'], $o['reject_choice']);
                $needsChoice = $o['status'] === 'rejected' && $o['reject_choice'] === null;
                $ec = (float) $o['estimate_cash'] > 0 ? (float) $o['estimate_cash'] : (float) $o['offered_total'];
                ?>
                <li class="acc-row-card<?= $o['status'] === 'offered' || $needsChoice ? ' is-attention' : '' ?>">
                    <div class="acc-row-main">
                        <a class="acc-row-title" href="<?= e(url('/account/offers/' . $o['code'])) ?>"><?= e($o['code']) ?></a>
                        <small><?= e(AccountUi::date($o['created_at'])) ?> &middot; <?= (int) $o['item_count'] ?> <?= (int) $o['item_count'] === 1 ? 'game' : 'games' ?><?= $o['kind'] === 'trade_in' ? ' &middot; trade-in' : '' ?></small>
                    </div>
                    <?= AccountUi::pill($label, $mod) ?>
                    <div class="acc-row-amount">
                        <?php if ($o['status'] === 'offered'): ?>
                            <strong><?= $o['offer_credit'] !== null ? e(AccountUi::amount($o['offer_credit'])) . ' credit' : e(AccountUi::amount($o['offer_cash'])) . ' cash' ?></strong>
                            <small>our offer</small>
                        <?php elseif ($needsChoice): ?>
                            <strong><?= $o['revised_amount'] !== null ? e(AccountUi::amount($o['revised_amount'])) : 'Return or recycle' ?></strong>
                            <small><?= $o['revised_amount'] !== null ? 'revised offer' : 'please choose' ?></small>
                        <?php elseif ($o['status'] === 'completed' && $o['final_amount'] !== null): ?>
                            <strong><?= e(AccountUi::amount($o['final_amount'])) ?></strong>
                            <small><?= $o['final_method'] === 'credit' ? 'added as credit' : 'paid in cash' ?></small>
                        <?php elseif ($ec > 0): ?>
                            <strong>~<?= e(AccountUi::amount($ec)) ?></strong>
                            <small>estimate</small>
                        <?php endif; ?>
                    </div>
                    <a class="btn <?= $o['status'] === 'offered' || $needsChoice ? 'btn-primary' : 'btn-ghost' ?> btn-sm" href="<?= e(url('/account/offers/' . $o['code'])) ?>"><?= $o['status'] === 'offered' ? 'Review offer' : ($needsChoice ? 'Choose' : 'View') ?><span class="sr-only"> request <?= e($o['code']) ?></span></a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
