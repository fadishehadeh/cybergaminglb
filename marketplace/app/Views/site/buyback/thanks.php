<?php
use App\Modules\Storefront\Rules;
use App\Modules\Storefront\Ui;

/** @var string $mode @var array $req @var array $items @var array $wanted @var string $first @var float $cash @var float $credit @var string $method @var float $wantedTotal @var float $balance @var string $waLink @var float $pickupFee @var float $net @var float $estimate */
$isTrade = $mode === 'trade';
$unpriced = count(array_filter($items, static fn (array $i): bool => empty($i['matched'])));
$pickup = ($req['collection'] ?? 'dropoff') === 'pickup';
$offerUrl = url('/account/offers/' . $req['code']);
$remote = ($req['zone_mode'] ?? '') === 'remote';
?>
<div class="container page-head">
    <div class="thanks">
        <span class="thanks-icon"><?= Ui::icon('check', 34) ?></span>
        <h1>Request <?= e($req['code']) ?> received</h1>
        <p class="lead-sm">Thank you, <?= e($first) ?>! We will review it and send you an offer. You can track it in your account.</p>
        <p><a class="btn btn-primary btn-lg" href="<?= e($offerUrl) ?>">Track it in my account</a></p>
    </div>
</div>

<div class="container cart-layout">
    <div>
        <section class="wa-cta card-box">
            <h2>Want it faster? Message us on WhatsApp</h2>
            <p>Your list, estimate and request number are filled in for you.</p>
            <a class="btn btn-wa btn-xl" href="<?= e($waLink) ?>" rel="noopener" target="_blank"><?= Ui::icon('whatsapp', 24) ?> Send on WhatsApp</a>
        </section>

        <section class="card-box next-steps">
            <h2>What happens next</h2>
            <ol class="steps steps-vertical">
                <li><span class="step-num">1</span><div><h3>We send you an offer</h3><p>We review your list<?= $unpriced > 0 ? ' and price the games we could not quote automatically' : '' ?>, then post our offer in your account.</p></div></li>
                <li><span class="step-num">2</span><div><h3>You accept it</h3><p>Pick cash or store credit and accept. Nothing moves until you say yes.</p></div></li>
                <li><span class="step-num">3</span><div><h3><?= $pickup ? ($remote ? 'A courier brings them to our hub' : 'Our courier picks them up') : 'You bring them to our hub' ?></h3><p><?= $pickup ? ($remote ? 'We arrange the courier pickup with you on WhatsApp. Pack the games in their cases with any extras.' : 'Our own courier comes to your area and checks the games on the spot.') : 'Bring them to our hub, with the cases and any extras.' ?></p></div></li>
                <li><span class="step-num">4</span><div><h3>We inspect</h3><p><?= $pickup && $remote ? 'We check the discs and cases when they arrive. If the condition matches what you described, you get the offered amount. If an item is not acceptable, you choose: a new offer, return it for ' . e(money(Rules::returnFee())) . ' (paid in cash on delivery), or we recycle it for free.' : 'We check the discs and cases. If the condition matches what you described, you get exactly the offered amount.' ?></p></div></li>
                <li><span class="step-num">5</span><div><h3>Cash or credit</h3><p>We pay you in cash, or add store credit to your wallet, ready to spend at checkout.<?= $pickupFee > 0 ? ' The ' . e(money($pickupFee)) . ' pickup fee is deducted from your payout.' : '' ?></p></div></li>
            </ol>
        </section>
    </div>

    <aside class="summary" aria-label="Request summary">
        <h2>Request <?= e($req['code']) ?></h2>
        <h3 class="summary-sub"><?= $isTrade ? 'Games you give' : 'Your games' ?></h3>
        <ul class="mini-lines no-img">
            <?php foreach ($items as $i): ?>
                <li>
                    <span><?= e($i['title'] ?? '') ?><small><?= e(($i['platform'] ?? '') . ' · ' . ($i['condition'] ?? '')) ?></small><small>With: <?= e(\App\Modules\Storefront\Quoter::includesText($i)) ?></small></span>
                    <strong><?= !empty($i['matched']) ? 'Cash ' . e(money($i['cash'] ?? $i['offer'] ?? 0)) . '<br>Credit ' . e(money($i['credit'] ?? $i['offer'] ?? 0)) : 'To be priced' ?></strong>
                </li>
            <?php endforeach; ?>
        </ul>
        <dl>
            <div><dt>Estimate in cash</dt><dd><?= e(money($cash)) ?></dd></div>
            <div class="grand"><dt>Estimate in credit</dt><dd><?= e(money($credit)) ?></dd></div>
            <?php if ($req['zone']): ?><div><dt>Your zone</dt><dd><?= e($req['zone']) ?> (<?= $remote ? 'remote' : 'local' ?>)</dd></div><?php endif; ?>
            <?php if ($pickupFee > 0): ?><div><dt>Courier pickup fee</dt><dd>&minus;<?= e(money($pickupFee)) ?></dd></div><?php endif; ?>
            <div class="grand"><dt>You receive (estimate)</dt><dd><?= e(money($net)) ?> <small><?= $method === 'cash' ? 'cash' : 'credit' ?></small></dd></div>
        </dl>
        <?php if (!empty($photoCount)): ?><p class="fine"><?= Ui::icon('camera', 16) ?> <?= (int) $photoCount ?> <?= (int) $photoCount === 1 ? 'photo' : 'photos' ?> sent with your request.</p><?php endif; ?>
        <p class="fine">You prefer <strong><?= $method === 'cash' ? 'cash' : 'store credit' ?></strong>. <?= $pickup ? ($remote ? 'A courier will bring the games to our hub.' : 'Our courier will pick the games up.') : 'You will bring the games to our hub.' ?> You can change your mind when you accept our offer.</p>

        <?php if ($isTrade && $wanted): ?>
            <h3 class="summary-sub">What you want</h3>
            <ul class="mini-lines no-img">
                <?php foreach ($wanted as $w): ?>
                    <li><span><?= e($w['title'] ?? '') ?></span><strong><?= !empty($w['matched']) ? e(money($w['price'])) : 'To be checked' ?></strong></li>
                <?php endforeach; ?>
            </ul>
            <dl><div class="grand"><dt><?= $balance > 0 ? 'You pay' : ($balance < 0 ? 'You keep' : 'Balance') ?></dt><dd><?= e($balance == 0.0 ? money(0) : money(abs($balance))) ?><?= $balance < 0 ? ' credit' : '' ?></dd></div></dl>
        <?php endif; ?>
        <p class="fine"><button type="button" class="btn-link" data-copy="<?= e($req['code']) ?>" hidden>Copy code</button></p>
    </aside>
</div>
