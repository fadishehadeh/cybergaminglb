<?php
use App\Modules\Storefront\Rules;
use App\Modules\Storefront\Seo;
use App\Modules\Storefront\Shipping;
use App\Modules\Storefront\Ui;

$prepay = Rules::prepayOn();
$after = Rules::codAfter();
$localNames = Rules::nameList(Rules::names('local'));
$faqs = [
    ['How do I buy a game on CyberGaming?', 'Add items to your cart, enter your name, phone and delivery area, and place the order. A person confirms it with you on WhatsApp, then we deliver or you collect it. No account or registration is needed. See the [[/shop|shop]].'],
    ['Is everything inspected?', 'Yes. Every item is inspected by our team before it is listed and before it is delivered. Used items show a condition grade and photos of the exact copy.'],
    ['How does delivery work?', 'In local areas (' . $localNames . ') our own courier delivers for ' . Rules::feeRange('local') . ', you can inspect the game at the door and pay cash on delivery. Everywhere else a third-party courier delivers for ' . Rules::feeRange('remote') . '; the courier cannot inspect, so we inspect, photograph and seal your item at our hub' . ($prepay ? ' and you pay first by OMT or Whish' . ($after > 0 ? ' (cash on delivery unlocks after ' . $after . ' delivered orders)' : '') : '') . '.'],
    ['Do you buy, trade or swap games?', 'No. CyberGaming is a straightforward shop: we sell our own stock of used and new games and gaming gear. We do not buy games from the public, take trade-ins, or run a swap board.'],
    ['Do I need an account to order?', 'No. Checkout is guest-only: just your name, phone number and delivery area. No registration, no card details.'],
];
$meta['jsonld'][] = Seo::webPage('WebPage', 'How CyberGaming works', (string) ($meta['canonical'] ?? url('/how-it-works')), (string) ($meta['description'] ?? ''));
$meta['jsonld'][] = Seo::faqLd($faqs);
echo Ui::partial('page-head', ['crumbs' => $crumbs, 'h1' => 'How CyberGaming works', 'lead' => 'Browse, order, we confirm on WhatsApp, we deliver.']);
echo Seo::quickAnswerHtml('how');
$minFee = Shipping::minFee();
?>
<div class="container prose-wrap">
    <article class="prose">
        <h2>Buying from CyberGaming</h2>
        <ol class="steps steps-vertical">
            <li><span class="step-num">1</span><div><h3>Browse and add to cart</h3><p>Every item is inspected before it is listed. Prices are in US dollars.</p></div></li>
            <li><span class="step-num">2</span><div><h3>Order on the site</h3><p>Enter your name, phone and delivery area. No account needed, and no card details, ever.</p></div></li>
            <li><span class="step-num">3</span><div><h3>We confirm on WhatsApp</h3><p>We message you to confirm the items and delivery.</p></div></li>
            <li><span class="step-num">4</span><div><h3>Delivery and payment</h3><p><strong>Local areas</strong> (<?= e($localNames) ?>): our own courier brings it for <?= e(Rules::feeRange('local')) ?>, you inspect it at the door and pay cash on delivery. <strong>Everywhere else</strong> (<?= e(Rules::feeRange('remote')) ?>): a third-party courier cannot inspect, so we inspect, photograph and seal your item at our hub and <?= $prepay ? 'you pay first by OMT or Whish; we ship as soon as your payment is confirmed' . ($after > 0 ? ', and after ' . (int) $after . ' delivered orders you can pay cash on delivery there too' : '') : 'you can still pay cash on delivery' ?>. <a href="<?= e(url('/delivery-and-payment')) ?>">Delivery fees by area</a>.</p></div></li>
        </ol>

        <?php if (digital_enabled()): ?>
        <h3>Gift cards and digital codes</h3>
        <p>Gift cards and Steam gifts work a little differently: pay by OMT or Whish first, and we send your code on WhatsApp once the payment is confirmed, usually within minutes during opening hours. There is no cash on delivery for digital items, and all digital sales are final once the code is delivered. <a href="<?= e(url('/shop/gift-cards')) ?>">See gift cards</a>.</p>
        <?php endif; ?>

        <h2>Trust and safety</h2>
        <ul>
            <li><strong>Every item is inspected</strong> by us before it reaches you. Where our courier cannot inspect, we photograph and seal it first.</li>
            <li><strong>No account needed.</strong> Checkout only asks for what delivery requires: your name, phone and area.</li>
            <li><strong>Problems get fixed.</strong> If something is not as described, tell us on WhatsApp straight away and we will make it right.</li>
        </ul>
    </article>
    <?= Seo::faqHtml($faqs) ?>
</div>
