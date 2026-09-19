<?php
use App\Modules\Storefront\Ui;

/** @var array $given @var ?array $wanted @var float $balance @var array $options @var array $errors @var array $crumbs @var ?array $customer @var string $loginUrl @var string $registerUrl */
$hasWanted = $wanted && $wanted['matched'] > 0;
$unmatchedGiven = count($given['lines']) - $given['matched'];
$unmatchedWanted = $wanted ? count($wanted['lines']) - $wanted['matched'] : 0;
echo Ui::partial('page-head', ['crumbs' => $crumbs, 'h1' => 'Your trade-in quote', 'lead' => 'Your games in cash or store credit, what you want from the shop, and the balance. Send your request and we will make you an offer.']);
?>
<div class="container cart-layout">
    <div>
        <section class="card-box" aria-labelledby="give-h">
            <h2 id="give-h">Games you give</h2>
            <?= Ui::partial('quote-given', ['given' => $given, 'mode' => 'trade']) ?>
            <?php if ($given['matched'] > 0): ?><?= Ui::partial('offer-compare', ['given' => $given]) ?><?php endif; ?>
            <?php if ($unmatchedGiven > 0): ?>
                <p class="fine"><?= $unmatchedGiven === 1 ? 'One game is' : $unmatchedGiven . ' games are' ?> not in our catalogue yet, so we price <?= $unmatchedGiven === 1 ? 'it' : 'them' ?> for you after you send the request. They are not included in the totals above.</p>
            <?php endif; ?>
        </section>

        <section class="card-box" aria-labelledby="want-h">
            <h2 id="want-h">What you want</h2>
            <?php if (!$wanted || !$wanted['lines']): ?>
                <p>You did not pick anything from the shop, so all of your credit stays as store credit. You can choose items on WhatsApp later.</p>
            <?php else: ?>
                <ul class="quote-lines">
                    <?php foreach ($wanted['lines'] as $l): ?>
                        <li class="quote-line<?= $l['matched'] ? '' : ' is-unmatched' ?>">
                            <span class="ql-main">
                                <strong><?= e($l['matched'] ? $l['title'] : $l['typed']) ?></strong>
                                <?php if ($l['matched'] && $l['platform'] !== ''): ?><small><?= e($l['platform']) ?></small><?php endif; ?>
                                <?php if (!$l['matched']): ?><small class="ql-match">Not found in stock. We will help you find it on WhatsApp.</small><?php endif; ?>
                            </span>
                            <?php if ($l['matched']): ?>
                                <span class="ql-amount"><?= e(money($l['price'])) ?></span>
                            <?php else: ?>
                                <span class="ql-amount ql-wa">Check with us</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($hasWanted): ?><p class="quote-total"><span>Items total</span> <strong><?= e(money($wanted['total'])) ?></strong></p><?php endif; ?>
                <?php if ($unmatchedWanted > 0): ?><p class="fine">Items we could not find are not included in the balance.</p><?php endif; ?>
            <?php endif; ?>
        </section>

        <?php if ($hasWanted || $given['total_credit'] > 0): ?>
            <section class="balance <?= $balance > 0 ? 'is-pay' : 'is-keep' ?>" aria-label="Your balance">
                <?php if (!$hasWanted): ?>
                    <span>You keep</span> <strong><?= e(money($given['total_credit'])) ?> store credit</strong>
                <?php elseif ($balance > 0): ?>
                    <span>You pay</span> <strong><?= e(money($balance)) ?></strong>
                    <small><?= e(money($wanted['total'])) ?> items minus <?= e(money($given['total_credit'])) ?> credit</small>
                <?php elseif ($balance < 0): ?>
                    <span>You keep</span> <strong><?= e(money(abs($balance))) ?> store credit</strong>
                    <small><?= e(money($given['total_credit'])) ?> credit minus <?= e(money($wanted['total'])) ?> items</small>
                <?php else: ?>
                    <span>Even trade</span> <strong>You pay nothing</strong>
                    <small><?= e(money($given['total_credit'])) ?> credit covers your items exactly</small>
                <?php endif; ?>
            </section>
        <?php endif; ?>
        <p class="fine">Figures are estimates based on the condition you selected. We inspect your games in person and you always accept our final offer before anything changes hands.</p>
        <?= Ui::partial('edit-list-form', ['mode' => 'trade', 'given' => $given, 'wanted' => $wanted]) ?>
    </div>

    <aside class="summary" aria-labelledby="details-h">
        <?= Ui::partial('offer-panel', [
            'mode' => 'trade', 'given' => $given, 'wanted' => $wanted, 'options' => $options, 'errors' => $errors,
            'customer' => $customer, 'loginUrl' => $loginUrl, 'registerUrl' => $registerUrl,
        ]) ?>
    </aside>
</div>
