<?php
use App\Modules\Account\AccountUi;
use App\Modules\Storefront\Ui;

/** @var array $me @var float $balance @var array $rows @var int $total @var int $page @var int $pages */
$meta = ['title' => 'My wallet | CyberGaming Lebanon', 'description' => 'Your CyberGaming credit balance and history.', 'noindex' => true];
$accountNav = 'wallet';
require base_path('app/Views/account/_nav.php');
?>
<div class="container acc-page">
    <div class="acc-grid acc-grid-top">
        <section class="wallet-card" aria-labelledby="bal-h">
            <h1 id="bal-h" class="wallet-title">Wallet</h1>
            <p class="wallet-balance"><strong><?= e(AccountUi::amount($balance)) ?></strong> <span>credit</span></p>
            <p class="wallet-sub">1 credit = $1. Your credit never expires.</p>
            <div class="wallet-actions">
                <a class="btn btn-primary" href="<?= e(url('/shop')) ?>"><?= Ui::icon('cart', 18) ?> Spend it in the shop</a>
                <a class="btn btn-outline-light" href="<?= e(url('/sell')) ?>"><?= Ui::icon('tag', 18) ?> Earn more</a>
            </div>
        </section>

        <section class="acc-card" aria-labelledby="how-h">
            <h2 id="how-h">How credit works</h2>
            <dl class="acc-how">
                <div><dt>Earn</dt><dd>Sell us your games and choose credit instead of cash (credit is the better deal), or sell games to other members and take your earnings as credit.</dd></div>
                <div><dt>Spend</dt><dd>Use credit at checkout. If your order costs more, pay the difference in cash on delivery.</dd></div>
                <div><dt>Keep</dt><dd>Credit never expires and is not tied to any single order. <a href="<?= e(url('/credit')) ?>">Learn more about credit</a>.</dd></div>
            </dl>
        </section>
    </div>

    <section class="acc-card" aria-labelledby="hist-h">
        <h2 id="hist-h">History</h2>
        <?php if (!$rows): ?>
            <p class="acc-empty">Nothing here yet. Every time credit is added or spent, it shows up in this list.</p>
        <?php else: ?>
            <div class="acc-table-wrap">
                <table class="acc-table">
                    <caption class="sr-only">Wallet history, newest first. Page <?= (int) $page ?> of <?= (int) $pages ?>.</caption>
                    <thead>
                        <tr><th scope="col">Date</th><th scope="col">Description</th><th scope="col" class="num">Amount</th><th scope="col" class="num">Balance</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $r): $neg = (float) $r['amount'] < 0; ?>
                        <tr>
                            <td data-label="Date"><?= e(AccountUi::date($r['created_at'], true)) ?></td>
                            <td data-label="Description">
                                <?php if ($r['link']): ?><a href="<?= e(url($r['link'])) ?>"><?= e($r['label']) ?></a><?php else: ?><?= e($r['label']) ?><?php endif; ?>
                            </td>
                            <td data-label="Amount" class="num"><strong class="amt <?= $neg ? 'amt-neg' : 'amt-pos' ?>"><?= e(AccountUi::signed($r['amount'])) ?></strong></td>
                            <td data-label="Balance" class="num"><?= e(AccountUi::amount($r['balance_after'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="fine"><?= (int) $total ?> <?= $total === 1 ? 'entry' : 'entries' ?> in total.</p>
            <?= AccountUi::pager($page, $pages, '/account/wallet') ?>
        <?php endif; ?>
    </section>
</div>
