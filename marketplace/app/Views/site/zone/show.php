<?php
use App\Modules\Storefront\Rules;
use App\Modules\Storefront\Seo;
use App\Modules\Storefront\Shipping;
use App\Modules\Storefront\Ui;

/** @var array $z @var array $zones @var array $crumbs @var array $faqs @var array $products */
$isLocal = $z['mode'] === 'local';
$short = $z['short'];
$fee = money($z['fee']);
$free = Shipping::freeOver();
$prepay = Rules::prepayOn();
$after = Rules::codAfter();
$pickup = money(Rules::pickupFee());
$minSell = Rules::minSell();
$localZones = array_values(array_filter($zones, static fn (array $x): bool => $x['mode'] === 'local' && $x['slug'] !== $z['slug']));
$remoteZones = array_values(array_filter($zones, static fn (array $x): bool => $x['mode'] === 'remote' && $x['slug'] !== $z['slug']));

echo Ui::partial('page-head', ['crumbs' => $crumbs, 'h1' => 'Delivery to ' . $short, 'lead' => $z['detail'] !== '' ? 'Covers: ' . $z['detail'] . '.' : '']);
echo Seo::quickAnswerHtml('zone', ['zone' => $z]);
?>
<div class="container prose-wrap zone-page">
    <article class="prose">
        <h2>Delivery to <?= e($short) ?> at a glance</h2>
        <dl class="zone-facts">
            <div><dt>Delivery fee</dt><dd><?= e($fee) ?><?= $free > 0 ? ' (free over ' . e(money($free)) . ')' : '' ?></dd></div>
            <div><dt>Type of delivery</dt><dd><?= $isLocal ? 'Local: our own courier' : 'Remote: third-party courier' ?></dd></div>
            <div><dt>How you pay</dt><dd><?php if ($isLocal): ?>Cash on delivery, store credit, OMT or Whish<?php else: ?><?= $prepay ? 'Prepay by OMT or Whish, or use store credit' . ($after > 0 ? '; cash on delivery after ' . (int) $after . ' delivered orders' : '') : 'Cash on delivery, store credit, OMT or Whish' ?><?php endif; ?></dd></div>
            <div><dt>Inspection</dt><dd><?= $isLocal ? 'At the door, before you pay' : 'At our hub, with photos, before we seal it' ?></dd></div>
            <div><dt>Timing</dt><dd>Handed to the courier within a day of confirmation, arrives in 1 to 3 days</dd></div>
        </dl>

        <h2>How delivery works in <?= e($short) ?></h2>
        <?php if ($isLocal): ?>
            <p><?= e($short) ?> is one of our local areas, so our own courier delivers your order for a flat <?= e($fee) ?>. Because the courier works for us, you can look the item over at the door and pay cash on delivery once you are happy with it. If you would rather pay in another way, store credit, OMT and Whish all work too.</p>
            <ol class="steps steps-vertical">
                <li><span class="step-num">1</span><div><h3>Order on the site</h3><p>Add items to your cart, choose <?= e($z['name']) ?> as your area and see the exact fee before you order.</p></div></li>
                <li><span class="step-num">2</span><div><h3>We confirm on WhatsApp</h3><p>A person checks availability with you and agrees a delivery time.</p></div></li>
                <li><span class="step-num">3</span><div><h3>Our courier delivers</h3><p>You inspect the item at the door and pay cash on delivery, or the balance after any store credit.</p></div></li>
            </ol>
        <?php else: ?>
            <p><?= e($short) ?> is one of our remote areas, so a third-party courier delivers your order for a flat <?= e($fee) ?>. That courier cannot inspect anything for you, so we inspect, photograph and seal your order at our hub before it ships<?= $prepay ? ', and you pay in advance by OMT or Whish' : '' ?>.</p>
            <ol class="steps steps-vertical">
                <li><span class="step-num">1</span><div><h3>Order on the site</h3><p>Add items to your cart, choose <?= e($z['name']) ?> as your area and see the exact fee before you order.</p></div></li>
                <li><span class="step-num">2</span><div><h3>We confirm on WhatsApp</h3><p><?= $prepay ? 'We send our OMT and Whish details. You pay, and we ship as soon as the payment is confirmed.' : 'We confirm availability and agree the delivery details with you.' ?></p></div></li>
                <li><span class="step-num">3</span><div><h3>Inspected, sealed and shipped</h3><p>We check the item at our hub, photograph it and hand it to the courier.<?= $prepay && $after > 0 ? ' After ' . (int) $after . ' delivered orders you can pay cash on delivery in remote areas too.' : '' ?></p></div></li>
            </ol>
        <?php endif; ?>

        <h2>Selling games from <?= e($short) ?></h2>
        <?php if ($isLocal): ?>
            <p>You can sell or trade in games from <?= e($short) ?> in two ways. Bring them to our hub yourself, which is free. Or ask for a pickup: our own courier collects them in <?= e($short) ?> and checks them on the spot, and a <?= e($pickup) ?> pickup fee is deducted from your payout, so you never pay it separately. If the courier declines an item there is no return trip and no fee.</p>
        <?php else: ?>
            <p>You can sell or trade in games from <?= e($short) ?> by courier. A third-party courier brings them to our hub, we inspect them on arrival, and a <?= e($pickup) ?> pickup fee is deducted from your payout, so you never pay it separately.<?= $minSell > 0 ? ' A courier shipment from a remote area must be worth at least ' . e(money($minSell)) . '; bringing games to our hub yourself has no minimum.' : '' ?></p>
        <?php endif; ?>
        <p><a class="btn btn-primary" href="<?= e(url('/sell')) ?>">Get an instant sell quote</a> <a class="btn btn-ghost" href="<?= e(url('/trade')) ?>">Trade in for credit</a></p>
    </article>

    <?php if ($products): ?>
    <section class="zone-shelf" aria-labelledby="shelf-h">
        <div class="section-head"><h2 id="shelf-h">In stock now, delivered to <?= e($short) ?></h2><a class="link-more" href="<?= e(url('/shop')) ?>">Shop everything &rarr;</a></div>
        <div class="product-grid">
            <?php foreach ($products as $p): ?><?= Ui::partial('product-card', ['p' => $p, 'level' => 'h3']) ?><?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?= Seo::faqHtml($faqs, 'Delivery to ' . $short . ': questions') ?>

    <section class="zone-others" aria-labelledby="others-h">
        <h2 id="others-h">Delivery in other areas of Lebanon</h2>
        <p>We deliver across Lebanon, and every area has a flat fee shown at checkout. If your town is not named here, pick the area that contains it, or message us on WhatsApp before you order. See the full table on <a href="<?= e(url('/delivery-and-payment')) ?>">delivery and payment</a>.</p>
        <?php if ($localZones): ?>
        <h3>Local areas (our own courier)</h3>
        <ul class="chip-list"><?php foreach ($localZones as $x): ?><li><a href="<?= e(url('/delivery-to/' . $x['slug'])) ?>"><?= e($x['short']) ?> <span><?= e(money($x['fee'])) ?></span></a></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <?php if ($remoteZones): ?>
        <h3>Remote areas (third-party courier)</h3>
        <ul class="chip-list"><?php foreach ($remoteZones as $x): ?><li><a href="<?= e(url('/delivery-to/' . $x['slug'])) ?>"><?= e($x['short']) ?> <span><?= e(money($x['fee'])) ?></span></a></li><?php endforeach; ?></ul>
        <?php endif; ?>
    </section>
</div>
