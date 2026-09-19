<?php
use App\Modules\Storefront\Rules;
use App\Modules\Storefront\Shipping;
use App\Modules\Storefront\Ui;

/**
 * Zone + "how do we get your games" block of the sell / trade request form (also explains what happens to a rejected item).
 * The server decides everything (zone mode, pickup fee, minimum); the data-ship attributes only feed the live preview in site.js.
 * @var array $options zone / zone_mode / collection / method  @var float $cash @var float $credit
 */
$zones = Shipping::zones();
$zone = (string) ($options['zone'] ?? '');
$zmode = $zone !== '' ? Shipping::mode($zone) : '';
$collection = (string) $options['collection'];
$method = (string) $options['method'];
$fee = Rules::pickupFee();
$min = Rules::minSell();
$ret = Rules::returnFee();
$days = Rules::holdDays();
$hub = trim((string) setting('hub_address', ''));
$estimate = $method === 'cash' ? $cash : $credit;
$pickup = $collection === 'pickup';
$net = Rules::net($estimate, $pickup ? $fee : 0.0);
$tooLow = $zmode === 'remote' && $pickup && $min > 0 && $estimate + 0.004 < $min;
$key = $zmode === '' ? 'none' : $zmode;
$show = static fn (string $k, string $cur): string => $k === $cur ? '' : ' hidden';
?>
<div class="ship-block" data-ship data-cash="<?= e(number_format($cash, 2, '.', '')) ?>" data-credit="<?= e(number_format($credit, 2, '.', '')) ?>" data-fee="<?= e(number_format($fee, 2, '.', '')) ?>" data-min="<?= e(number_format($min, 2, '.', '')) ?>">
    <div class="form-row">
        <label for="o-zone">Where are you? <abbr title="required">*</abbr></label>
        <select id="o-zone" name="zone" required data-ship-zone>
            <option value="" data-mode="">Choose your area…</option>
            <?php foreach ($zones as $z): ?>
                <option value="<?= e($z['name']) ?>" data-mode="<?= e($z['mode']) ?>"<?= $zone === $z['name'] ? ' selected' : '' ?>><?= e($z['name']) ?> (<?= $z['mode'] === 'local' ? 'local' : 'remote' ?>)</option>
            <?php endforeach; ?>
        </select>
        <small>Local areas (<?= e(Rules::nameList(Rules::names('local'))) ?>) are served by our own courier. Everywhere else, your games travel by third-party courier.</small>
    </div>

    <fieldset class="choice-set">
        <legend>How do we get your games?</legend>
        <label class="choice"><input type="radio" name="collection" value="dropoff"<?= $collection === 'dropoff' ? ' checked' : '' ?> data-ship-collection><span><strong>I'll bring it to your hub</strong> <em>Free</em><small><?= $hub !== '' ? e($hub) . '. ' : '' ?>We confirm a time on WhatsApp and inspect it with you.</small></span></label>
        <label class="choice"><input type="radio" name="collection" value="pickup"<?= $pickup ? ' checked' : '' ?> data-ship-collection><span>
            <strong data-ship-for="none"<?= $show('none', $key) ?>>A courier picks it up</strong>
            <strong data-ship-for="local"<?= $show('local', $key) ?>>Our courier picks it up and checks it on the spot</strong>
            <strong data-ship-for="remote"<?= $show('remote', $key) ?>>Ship it by courier</strong>
            <em>&minus;<?= e(money($fee)) ?></em>
            <small data-ship-for="none"<?= $show('none', $key) ?>>Choose your area above to see how pickup works where you are. A <?= e(money($fee)) ?> pickup fee is deducted from your payout.</small>
            <small data-ship-for="local"<?= $show('local', $key) ?>>Our own courier comes to you and inspects the games on the spot. A <?= e(money($fee)) ?> pickup fee is deducted from your payout. If the courier declines an item on the spot, there is no return trip and no fee.</small>
            <small data-ship-for="remote"<?= $show('remote', $key) ?>>A third-party courier brings it to our hub and we inspect it on arrival. A <?= e(money($fee)) ?> pickup fee is deducted from your payout (you never pay it separately).<?= $min > 0 ? ' Shipments must be worth at least ' . e(money($min)) . '.' : '' ?></small>
        </span></label>
    </fieldset>

    <div class="pay-note ship-net" data-ship-net aria-live="polite"><?= Ui::icon('wallet', 20) ?> <span data-ship-net-text><strong>You'll receive <?= e(money($net)) ?></strong> <?= $method === 'cash' ? 'in cash' : 'as store credit' ?>: <?= $pickup ? 'estimate ' . e(money($estimate)) . ' minus the ' . e(money($fee)) . ' pickup fee' : 'estimate ' . e(money($estimate)) . ', no pickup fee' ?>.</span></div>
    <p class="form-errors ship-min" role="alert" data-ship-min<?= $tooLow ? '' : ' hidden' ?>>Shipments from your area must be worth at least <?= e(money($min)) ?>: add more games, or bring them to our hub.</p>

    <details class="ship-rules"<?= $zmode === 'remote' && $pickup ? ' open' : '' ?>>
        <summary>What if you can't accept my games?</summary>
        <div data-ship-rule="local"<?= $show('local', $key === 'none' ? 'remote' : $key) ?>>
            <p>Our courier inspects on the spot. If an item is not acceptable, they simply leave it with you: no return trip, no fee. Bringing it to our hub works the same as always.</p>
        </div>
        <div data-ship-rule="remote"<?= $show('remote', $key === 'none' ? 'remote' : $key) ?>>
            <p>We inspect your games when they reach our hub. If everything is as described, you are paid (cash or credit) minus the pickup fee. If an item is not acceptable, <strong>you choose</strong>:</p>
            <ul>
                <li><strong>Take a new offer.</strong> We suggest a revised, lower price for it.</li>
                <li><strong>Get it sent back.</strong> You pay the <?= e(money($ret)) ?> return fee (pickup and return trip) in cash to the courier when it arrives.</li>
                <li><strong>Let us recycle it.</strong> Free, nothing to pay.</li>
            </ul>
            <p class="fine">We hold it for <?= (int) $days ?> days while you decide. If we hear nothing by then, we recycle it.</p>
        </div>
    </details>
</div>
