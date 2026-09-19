<?php
use App\Modules\Storefront\Rules;
use App\Modules\Storefront\Shipping;
use App\Modules\Storefront\Ui;

$minFee = Shipping::minFee();
$freeOver = Shipping::freeOver();
$localNames = Rules::nameList(Rules::names('local'));
$remoteNames = Rules::nameList(Rules::names('remote'));
$localFee = Rules::feeRange('local');
$remoteFee = Rules::feeRange('remote');
$prepay = Rules::prepayOn();
$after = Rules::codAfter();
$pickupFee = money(Rules::pickupFee());
$returnFee = money(Rules::returnFee());
$minSell = Rules::minSell();
$days = Rules::holdDays();
$codRule = !$prepay
    ? 'Right now you can also pay cash on delivery outside the local area.'
    : ($after > 0
        ? 'After ' . $after . ' delivered orders, cash on delivery is also available in remote areas.'
        : 'Cash on delivery is not offered in remote areas: you always prepay there.');
$zoneFees = implode(', ', array_map(static fn (array $z): string => $z['name'] . ' ' . money($z['fee']) . ' (' . ($z['mode'] === 'local' ? 'local' : 'remote') . ')', Shipping::zones()));

$faqs = [
    ['Where do you deliver?', 'Across Lebanon. Local areas, served by our own courier: ' . $localNames . '. Remote areas, served by a third-party courier: ' . ($remoteNames !== '' ? $remoteNames : 'everywhere else') . '.'],
    ['How much does delivery cost?', 'The fee depends on your area: ' . $zoneFees . '.' . ($freeOver > 0 ? ' Orders of ' . money($freeOver) . ' or more get free delivery.' : '') . ' You see the exact fee at checkout, before you place your order.'],
    ['What is the difference between local and remote delivery?', 'In local areas our own courier delivers to you, can let you inspect the game at the door, and you can pay cash on delivery. In remote areas a third-party courier delivers, and that courier cannot inspect anything for you. That is why remote orders are inspected, photographed and sealed at our hub first and ' . ($prepay ? 'paid in advance by OMT or Whish.' : 'sent with the same care.')],
    ['Why do I have to pay first if I live outside the local area?', $prepay
        ? 'A third-party courier cannot inspect the game or take responsibility for a cash handover. So we inspect, photograph and seal your game at our hub, you pay by OMT or Whish, and we ship it as soon as your payment is confirmed.'
        : 'You do not have to at the moment: cash on delivery is currently available everywhere. We still inspect, photograph and seal every remote order at our hub.'],
    ['When can I pay cash on delivery outside the local area?', $codRule . ' In local areas cash on delivery is always available.'],
    ['How do I prepay?', 'Place your order. We message you on WhatsApp with our OMT and Whish details, you send the amount and your order number, and we ship as soon as the payment is confirmed. We never ask for card details on the site.'],
    ['Can I pick my order up instead?', 'Yes. You can meet us at our pickup point. Mention it in the note when you order, or tell us on WhatsApp.'],
    ['How can I pay?', 'With store credit, cash on delivery (local areas), OMT or Whish. You can combine credit and cash: credit is taken off the total and you pay any gap on delivery, or by OMT / Whish in advance for remote areas. We never ask for card details on the site.'],
    ['Can I pay with store credit?', 'Yes. Sign in, and at checkout tick "Pay with my credit". 1 credit is worth $1. If your credit covers the whole order, including delivery, you pay nothing more. If not, you pay the rest on delivery in local areas, or first by OMT / Whish in remote areas.'],
    ['I live outside the local area and want to sell games. How does it work?', 'Ship them to our hub by courier: a ' . $pickupFee . ' pickup fee is deducted from your payout (you never pay it separately)' . ($minSell > 0 ? ', and the shipment must be worth at least ' . money($minSell) . ' (or bring them to our hub yourself, no minimum)' : '') . '. We inspect on arrival. If an item is not acceptable you choose: take a revised offer, get it sent back for ' . $returnFee . ' (paid in cash to the courier on delivery), or let us recycle it for free. We hold it ' . $days . ' days for your answer.'],
    ['What if the item is not as described?', 'In local areas, check the item when the courier hands it over. In remote areas we have already inspected and photographed it before sealing it. If something is wrong when you receive it, tell us straight away on WhatsApp and we will sort it out.'],
];
if (digital_enabled()) {
    $faqs[] = ['How do gift cards and digital codes work?', 'Pay by OMT or Whish first (no cash on delivery for digital items). We send your code on WhatsApp once we confirm the payment, usually within minutes during opening hours. There is no delivery fee for digital-only orders, store credit can not be used on them, and all digital sales are final once the code is delivered.'];
}
$meta['jsonld'][] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(
    static fn (array $f): array => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]],
    $faqs
)];
echo Ui::partial('page-head', ['crumbs' => $crumbs, 'h1' => 'Delivery & payment', 'lead' => 'Delivery across Lebanon from ' . money($minFee) . '. Local areas: our own courier and cash on delivery. Other areas: a third-party courier, so you prepay by OMT or Whish. No online card payment.']);
?>
<div class="container prose-wrap">
    <article class="prose">
        <h2>Two ways we deliver</h2>
        <div class="worked cond-list">
            <div>
                <span><strong>Local delivery</strong> &mdash; <?= e($localNames) ?><small>Our own courier, flat <?= e($localFee) ?>. The courier can let you inspect the game at the door, and you pay cash on delivery.</small></span>
                <strong><?= e($localFee) ?></strong>
            </div>
            <div>
                <span><strong>Remote delivery</strong> &mdash; <?= e($remoteNames !== '' ? $remoteNames : 'everywhere else') ?><small>A third-party courier who cannot inspect. So we inspect, photograph and seal your game at our hub<?= $prepay ? ', and you pay first by OMT or Whish' : '' ?>.</small></span>
                <strong><?= e($remoteFee) ?></strong>
            </div>
        </div>

        <h3>Delivery fee and type by area</h3>
        <?= Ui::partial('zone-table') ?>
        <p>You choose your area at checkout and see the exact fee and how you pay before you place the order.</p>
        <ul class="icon-list">
            <li><?= Ui::icon('pin', 18) ?> Or meet us at our pickup point (<?= e((string) setting('hub_address', 'Lebanon')) ?>). We share the exact spot when we confirm your order.</li>
            <li><?= Ui::icon('chat', 18) ?> We confirm delivery time with you on WhatsApp before we ship.</li>
        </ul>

        <h2>Payment</h2>
        <ul class="icon-list">
            <li><?= Ui::icon('wallet', 18) ?> <span><strong>Local areas:</strong> cash on delivery after you inspect the game, or your store credit, OMT or Whish.</span></li>
            <li><?= Ui::icon('wallet', 18) ?> <span><strong>Remote areas:</strong> <?= $prepay ? 'pay first by OMT or Whish. We inspect, photograph and seal your game at our hub and ship as soon as your payment is confirmed. ' . e($codRule) : 'cash on delivery, OMT or Whish, as in local areas. We inspect, photograph and seal every remote order at our hub before it ships.' ?></span></li>
            <li><?= Ui::icon('wallet', 18) ?> <span><strong>Store credit</strong> from selling us games, taken off your order at checkout (1 credit = $1, no expiry). <a href="<?= e(url('/credit')) ?>">How credit works</a>.</span></li>
            <li><?= Ui::icon('lock', 18) ?> Prices are in US dollars. We never take card details on this site.</li>
        </ul>

        <?php if (digital_enabled()): ?>
        <h2>Gift cards &amp; digital codes</h2>
        <ul class="icon-list">
            <li><?= Ui::icon('wallet', 18) ?> <span><strong>Prepaid by OMT or Whish.</strong> We release your code only after we confirm your payment. No cash on delivery for digital items.</span></li>
            <li><?= Ui::icon('chat', 18) ?> <span><strong>Delivered on WhatsApp</strong>, usually within minutes during opening hours. No delivery fee and no address needed for digital-only orders.</span></li>
            <li><?= Ui::icon('lock', 18) ?> <span><strong>Final once delivered.</strong> Store credit can't be used on gift cards, and gift cards only work on the region shown on the product.</span></li>
        </ul>
        <?php endif; ?>

        <h2>Selling to us from a remote area</h2>
        <ul class="icon-list">
            <li><?= Ui::icon('truck', 18) ?> <span>Ship your games to our hub by courier. A <strong><?= e($pickupFee) ?> pickup fee</strong> is deducted from your payout: you never pay it separately.</span></li>
            <?php if ($minSell > 0): ?><li><?= Ui::icon('tag', 18) ?> <span>A courier shipment must be worth at least <strong><?= e(money($minSell)) ?></strong>. Bringing your games to our hub yourself has no minimum and no fee.</span></li><?php endif; ?>
            <li><?= Ui::icon('shield', 18) ?> <span>We inspect on arrival. If everything is fine you are paid in cash or credit, minus the pickup fee. If an item is not acceptable, <strong>you choose</strong>: a revised offer, sending it back (<?= e($returnFee) ?>, paid in cash to the courier on delivery) or letting us recycle it for free. We hold it <?= (int) $days ?> days for your answer, then recycle it. <a href="<?= e(url('/sell')) ?>">Sell your games</a>.</span></li>
        </ul>

        <h2>Buyer protection</h2>
        <p>Every item is inspected before sale, and every order is confirmed by a person on WhatsApp. If something is not as described, tell us and we will make it right.</p>
    </article>
    <?= Ui::partial('faq', ['faqs' => $faqs]) ?>
</div>
