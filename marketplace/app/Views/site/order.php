<?php
use App\Modules\Storefront\Digital;
use App\Modules\Storefront\Ui;

/** @var array $order @var array $items @var string $waLink @var float $subtotal @var float $fee @var float $grand @var float $credit @var float $cash @var float $prepaid @var float $prepay @var bool $physPrepaid @var string $zoneMode @var bool $hasDigital @var bool $hasPhysical */
$isCustomerOrder = !empty($order['user_id']);
$digitalOnly = $hasDigital && !$hasPhysical;
$remote = $zoneMode === 'remote';
$received = ($order['payment_status'] ?? '') === 'received';
$prepayDue = $prepay > 0 && !$received;
?>
<div class="container page-head">
    <div class="thanks">
        <span class="thanks-icon"><?= Ui::icon('check', 34) ?></span>
        <h1>Thank you, <?= e(explode(' ', trim($order['buyer_name']))[0]) ?>! Your order is in.</h1>
        <p class="lead-sm">Order number <strong class="order-code"><?= e($order['code']) ?></strong>. Please keep it &ndash; it is the only way to view this page.</p>
    </div>
</div>

<div class="container cart-layout">
    <div>
        <?php if ($physPrepaid && $prepay > 0): ?>
        <section class="card-box prepay-box" aria-labelledby="prepay-h">
            <h2 id="prepay-h"><?= Ui::icon('wallet', 24) ?> PREPAY <?= e(money($prepay)) ?> via OMT or Whish</h2>
            <p><strong><?= $received ? 'Payment received: thank you!' : 'We ship as soon as your payment is confirmed.' ?></strong> <?= e((string) $order['zone']) ?> is served by a third-party courier who cannot inspect the game for you, so we inspect, photograph and seal it at our hub before it leaves, and you pay first.</p>
            <?php if (!$received): ?>
            <ol class="steps steps-vertical">
                <li><span class="step-num">1</span><div><h3>Message us on WhatsApp</h3><p>Tap the green button below. We reply with our OMT and Whish details.</p></div></li>
                <li><span class="step-num">2</span><div><h3>Send <?= e(money($prepay)) ?> by OMT or Whish</h3><p>Write your order number <strong><?= e($order['code']) ?></strong> in the transfer note<?= $hasDigital ? ' (this amount includes your digital items)' : '' ?>.</p></div></li>
                <li><span class="step-num">3</span><div><h3>We confirm, seal and ship</h3><p>Once your payment shows up we inspect, photograph and seal your game, and hand it to the courier.</p></div></li>
            </ol>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <section class="wa-cta card-box">
            <?php if ($digitalOnly): ?>
                <h2>One last step: message us on WhatsApp</h2>
                <p>We'll send your code on WhatsApp after we confirm your payment (OMT/Whish). Send us your order so we can reply with the payment details &mdash; usually within minutes during opening hours.</p>
            <?php elseif ($physPrepaid && $prepay > 0): ?>
                <h2>One last step: message us to pay</h2>
                <p>Send us your order on WhatsApp and we reply with the OMT / Whish details for <strong><?= e(money($prepay)) ?></strong>.<?= $cash > 0 ? '' : ' Nothing is due in cash on delivery.' ?><?= $hasDigital ? ' Your digital code comes on WhatsApp once we confirm the payment.' : '' ?></p>
            <?php else: ?>
                <h2>One last step: confirm on WhatsApp</h2>
                <p>Send us your order on WhatsApp so we can confirm availability and arrange delivery or pickup.<?= $cash > 0 ? ' You pay ' . e(money($cash)) . ' in cash on delivery (or by OMT / Whish).' : ' Nothing is due on delivery.' ?><?= $hasDigital ? ' Your digital items are prepaid: we send your code on WhatsApp after we confirm your payment (OMT/Whish).' : '' ?></p>
            <?php endif; ?>
            <a class="btn btn-wa btn-xl" href="<?= e($waLink) ?>" rel="noopener" target="_blank"><?= Ui::icon('whatsapp', 24) ?> <?= $digitalOnly ? 'Message us on WhatsApp' : ($physPrepaid && $prepay > 0 ? 'Get the payment details' : 'Confirm on WhatsApp') ?></a>
            <?php if ($isCustomerOrder): ?>
                <p class="fine"><a href="<?= e(url('/account/orders/' . $order['code'])) ?>">Track this order in your account</a></p>
            <?php endif; ?>
        </section>

        <section class="card-box next-steps">
            <h2>What happens next</h2>
            <?php if ($digitalOnly): ?>
            <ol class="steps steps-vertical">
                <li><span class="step-num">1</span><div><h3>We reply with payment details</h3><p>Pay by <?= e(Digital::PAYMENT_NOTE) ?></p></div></li>
                <li><span class="step-num">2</span><div><h3>We confirm your payment</h3><p>Usually within minutes during opening hours. For a Steam gift, add our Steam account as a friend when we contact you.</p></div></li>
                <li><span class="step-num">3</span><div><h3>We send your code on WhatsApp</h3><p>All digital sales are final once the code is delivered, and gift cards only work on the region shown on the product.</p></div></li>
            </ol>
            <?php elseif ($physPrepaid): ?>
            <ol class="steps steps-vertical">
                <li><span class="step-num">1</span><div><h3><?= $received ? 'Payment received' : 'You pay first' ?></h3><p><?= $prepay > 0 ? ($received ? 'Thank you, your payment is confirmed.' : 'OMT or Whish, using the details we send on WhatsApp.') : 'Your credit covers the whole order, so there is nothing to pay.' ?></p></div></li>
                <li><span class="step-num">2</span><div><h3>We inspect, photograph and seal</h3><p>Our courier cannot come to your area, so we check the game at our hub, photograph it and seal it before it ships.</p></div></li>
                <li><span class="step-num">3</span><div><h3>A courier delivers it</h3><p>We hand your sealed order to a third-party courier who delivers to <?= e((string) $order['zone']) ?>. Nothing to pay on delivery.</p></div></li>
            </ol>
            <?php if ($hasDigital): ?>
                <p class="pay-note pay-note-digital"><?= Ui::icon('gift', 20) ?> <span><strong>Your digital items are prepaid too.</strong> We'll send your code on WhatsApp after we confirm your payment (OMT/Whish), separately from the delivery of your physical items. All digital sales are final once the code is delivered.</span></p>
            <?php endif; ?>
            <?php else: ?>
            <ol class="steps steps-vertical">
                <li><span class="step-num">1</span><div><h3>We confirm</h3><p>We check your items again and reply on WhatsApp.</p></div></li>
                <li><span class="step-num">2</span><div><h3>Delivery or pickup</h3><p><?= $remote ? 'We inspect, photograph and seal your game at our hub, then a third-party courier delivers it to your area.' : 'Our own courier delivers to your area, or you meet us at our pickup point.' ?></p></div></li>
                <li><span class="step-num">3</span><div><h3><?= $cash > 0 ? 'You pay on receipt' : 'Nothing to pay' ?></h3><p><?= $cash > 0 ? 'Cash on delivery, OMT or Whish' . ($credit > 0 ? ' for the part your credit did not cover' : '') . '. ' . ($remote ? 'The courier cannot inspect it for you: we already did, and photographed it sealed.' : 'Inspect your game before you pay.') : 'Your credit covers the whole order. ' . ($remote ? 'We inspected and sealed it for you.' : 'Just inspect your game when it arrives.') ?></p></div></li>
            </ol>
            <?php if ($hasDigital): ?>
                <p class="pay-note pay-note-digital"><?= Ui::icon('gift', 20) ?> <span><strong>Your digital items are prepaid.</strong> We'll send your code on WhatsApp after we confirm your payment (OMT/Whish), separately from the delivery of your physical items. All digital sales are final once the code is delivered.</span></p>
            <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>

    <aside class="summary" aria-label="Order summary">
        <h2>Order <?= e($order['code']) ?></h2>
        <ul class="mini-lines no-img">
            <?php foreach ($items as $i): ?>
                <li><span><?= e($i['title']) ?><?= (int) $i['is_digital'] === 1 ? ' <span class="tag tag-digital">Digital</span>' : '' ?><small><?= (int) $i['qty'] ?> &times; <?= e(money($i['unit_price'])) ?></small></span><strong><?= e(money((float) $i['unit_price'] * (int) $i['qty'])) ?></strong></li>
            <?php endforeach; ?>
        </ul>
        <dl class="totals">
            <div><dt>Subtotal</dt><dd><?= e(money($subtotal)) ?></dd></div>
            <?php if ($hasPhysical): ?><div><dt>Delivery fee</dt><dd><?= $fee > 0 ? e(money($fee)) : 'Free' ?></dd></div><?php endif; ?>
            <div class="grand"><dt>Total</dt><dd><?= e(money($grand)) ?></dd></div>
            <?php if ($credit > 0): ?><div class="credit-row"><dt>Paid with credit</dt><dd>&minus;<?= e(money($credit)) ?></dd></div><?php endif; ?>
            <?php if ($prepay > 0): ?><div class="prepaid-due"><dt><?= $received ? 'Paid via OMT / Whish' : 'Prepay via OMT / Whish' ?></dt><dd><?= e(money($prepay)) ?></dd></div><?php endif; ?>
            <?php if ($hasPhysical): ?><div class="cash-due"><dt>Cash due on delivery</dt><dd><?= $cash > 0 ? e(money($cash)) : '$0' ?></dd></div><?php endif; ?>
        </dl>
        <?php if ($hasPhysical && $cash <= 0): ?><p class="fine cash-zero"><strong>Nothing to pay on delivery<?= $physPrepaid && $prepay > 0 ? ' &mdash; you prepay' : ($credit > 0 ? ' &mdash; paid with credit' : '') ?>.</strong></p><?php endif; ?>
        <?php if ($prepayDue): ?><p class="fine"><strong>Awaiting your payment.</strong> We ship as soon as it is confirmed.</p><?php endif; ?>
        <h3 class="summary-sub"><?= $digitalOnly ? 'Contact details' : 'Delivery details' ?></h3>
        <address>
            <?= e($order['buyer_name']) ?><br>
            <?= e($order['buyer_phone']) ?><br>
            <?php if ($digitalOnly): ?>
                Digital order &ndash; no delivery needed
            <?php else: ?>
                <?= e($order['buyer_area']) ?><?= $order['buyer_address'] ? ', ' . e($order['buyer_address']) : '' ?>
                <?php if ($zoneMode === 'local' || $remote): ?><br><small><?= $remote ? 'Remote zone: third-party courier' : 'Local zone: our own courier' ?></small><?php endif; ?>
            <?php endif; ?>
        </address>
        <?php if ($order['buyer_note']): ?><p class="fine">Note: <?= e($order['buyer_note']) ?></p><?php endif; ?>
    </aside>
</div>
