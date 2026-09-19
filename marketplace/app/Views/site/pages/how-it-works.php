<?php
use App\Modules\Storefront\Rules;
use App\Modules\Storefront\Shipping;
use App\Modules\Storefront\Ui;

$prepay = Rules::prepayOn();
$after = Rules::codAfter();
$pickupFee = money(Rules::pickupFee());
$returnFee = money(Rules::returnFee());
$minSell = Rules::minSell();
$days = Rules::holdDays();
$localNames = Rules::nameList(Rules::names('local'));
$faqs = [
    ['How does delivery work?', 'In local areas (' . $localNames . ') our own courier delivers for ' . Rules::feeRange('local') . ', you can inspect the game at the door and pay cash on delivery. Everywhere else a third-party courier delivers for ' . Rules::feeRange('remote') . '; the courier cannot inspect, so we inspect, photograph and seal your game at our hub' . ($prepay ? ' and you pay first by OMT or Whish' . ($after > 0 ? ' (cash on delivery unlocks after ' . $after . ' delivered orders)' : '') : '') . '.'],
    ['How does selling work if I live outside the local area?', 'Ship your games to our hub by courier. A ' . $pickupFee . ' pickup fee is deducted from your payout' . ($minSell > 0 ? ', and the shipment must be worth at least ' . money($minSell) . ' (no minimum if you bring them to our hub yourself)' : '') . '. We inspect them on arrival and pay you in cash or credit. If an item is not acceptable you choose: a revised offer, sending it back for ' . $returnFee . ' (paid in cash on delivery), or a free recycle. We hold it ' . $days . ' days for your answer.'],
    ['Can other members see my name, phone number or address?', 'No. Nobody on CyberGaming can see who you are. Every account gets a random anonymous ID (for example CalmBear11) and swap listings show only a swapper number and a delivery zone. Real names, phone numbers, emails and addresses are seen only by you and by CyberGaming.'],
    ['Can members message each other?', 'No. There are no public profiles and no messaging between members. Every trade, sale and swap goes through CyberGaming, so nobody ever has to deal with a stranger.'],
    ['Why can I not put my phone number in a listing?', 'Listings are public, so contact details such as phone numbers, emails, links and social handles are blocked. Leave your details in the private fields; only our team sees them.'],
];
$meta['jsonld'][] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(
    static fn (array $f): array => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]],
    $faqs
)];
echo Ui::partial('page-head', ['crumbs' => $crumbs, 'h1' => 'How CyberGaming works', 'lead' => 'Simple for buyers, simple for sellers, and private for everyone.']);
$minFee = Shipping::minFee();
?>
<div class="container prose-wrap">
    <article class="prose">
        <h2>If you are buying</h2>
        <ol class="steps steps-vertical">
            <li><span class="step-num">1</span><div><h3>Browse and add to cart</h3><p>Every item is inspected before it is listed. Prices are in US dollars.</p></div></li>
            <li><span class="step-num">2</span><div><h3>Order on the site</h3><p>Enter your name, phone and delivery area. Guests are welcome; with a free account your details and store credit are ready at checkout. No card details, ever.</p></div></li>
            <li><span class="step-num">3</span><div><h3>We confirm on WhatsApp</h3><p>We message you to confirm the items and delivery.</p></div></li>
            <li><span class="step-num">4</span><div><h3>Delivery and payment</h3><p><strong>Local areas</strong> (<?= e($localNames) ?>): our own courier brings it for <?= e(Rules::feeRange('local')) ?>, you inspect it at the door and pay cash on delivery (or use credit). <strong>Everywhere else</strong> (<?= e(Rules::feeRange('remote')) ?>): a third-party courier cannot inspect, so we inspect, photograph and seal your game at our hub and <?= $prepay ? 'you pay first by OMT or Whish; we ship as soon as your payment is confirmed' . ($after > 0 ? ', and after ' . (int) $after . ' delivered orders you can pay cash on delivery there too' : '') : 'you can still pay cash on delivery' ?>. <a href="<?= e(url('/delivery-and-payment')) ?>">Delivery fees by area</a>.</p></div></li>
        </ol>

        <?php if (digital_enabled()): ?>
        <h3>Gift cards and digital codes</h3>
        <p>Gift cards and Steam gifts work a little differently: pay by OMT or Whish first, and we send your code on WhatsApp once the payment is confirmed, usually within minutes during opening hours. There is no cash on delivery for digital items, store credit can't be used on them, and all digital sales are final once the code is delivered. <a href="<?= e(url('/shop/gift-cards')) ?>">See gift cards</a>.</p>
        <?php endif; ?>

        <h2>If you are selling</h2>
        <ol class="steps steps-vertical">
            <li><span class="step-num">1</span><div><h3>Get an instant quote</h3><p>List your games on <a href="<?= e(url('/sell')) ?>">Sell</a> or <a href="<?= e(url('/trade')) ?>">Trade in</a> and see what they are worth in cash and in store credit. Credit is worth more.</p></div></li>
            <li><span class="step-num">2</span><div><h3>Send your request and accept our offer</h3><p>With a free account, send your request. We review it, make you an offer, and you accept it.</p></div></li>
            <li><span class="step-num">3</span><div><h3>We inspect</h3><p>Bring your games to our hub for free. Or have them picked up: in local areas our own courier checks them on the spot; elsewhere a courier ships them to our hub and we check them on arrival. A <?= e($pickupFee) ?> pickup fee is deducted from your payout.</p></div></li>
            <li><span class="step-num">4</span><div><h3>Get paid</h3><p>Cash, or store credit added to your wallet, minus the pickup fee if a courier collected the games. If an item is not acceptable you choose: a revised offer, sending it back (<?= e($returnFee) ?>, paid in cash on delivery) or a free recycle. Sellers who list through us are paid once the buyer has received the item. Prefer to swap? Try the <a href="<?= e(url('/swap')) ?>">swap board</a>.</p></div></li>
        </ol>

        <h2>Store credit in one minute</h2>
        <p>Credit is money in your CyberGaming wallet: 1 credit = $1, it never expires, and you spend it at checkout. Whatever it does not cover is paid in cash on delivery. <a href="<?= e(url('/credit')) ?>">Read how store credit works</a>.</p>

        <h2 id="anonymous">How we keep you anonymous</h2>
        <p>Buying, selling, trading and swapping here never means revealing who you are to another member.</p>
        <ul class="icon-list">
            <li><?= Ui::icon('user', 18) ?> <span><strong>Only an anonymous ID is ever visible.</strong> Every account gets a random ID like <em>CalmBear11</em>. Swap listings show a swapper number and a delivery zone, and nothing else.</span></li>
            <li><?= Ui::icon('lock', 18) ?> <span><strong>No public profiles.</strong> Your name, phone, email and address are seen only by you and by CyberGaming.</span></li>
            <li><?= Ui::icon('chat', 18) ?> <span><strong>No messaging between members.</strong> Every trade goes through us, so there is nobody to chase and nobody who can chase you.</span></li>
            <li><?= Ui::icon('shield', 18) ?> <span><strong>Contact details are blocked in listings.</strong> Phone numbers, emails, links and social handles are rejected before a listing can be published.</span></li>
        </ul>

        <h2>Trust and safety</h2>
        <p><strong>We never share buyer or seller contact details.</strong> Buyers only ever deal with CyberGaming, and sellers only ever deal with CyberGaming. Nobody gets anyone else's name, phone number or address.</p>
        <ul>
            <li><strong>Every item is inspected</strong> by us before it reaches you. Where our courier cannot inspect, we photograph and seal it first.</li>
            <li><strong>Money goes through us</strong>, so neither side has to trust a stranger.</li>
            <li><strong>Problems get fixed.</strong> If something is not as described, tell us on WhatsApp straight away and we will make it right.</li>
        </ul>
    </article>
    <?= Ui::partial('faq', ['faqs' => $faqs]) ?>
</div>
