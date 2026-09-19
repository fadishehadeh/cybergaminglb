<?php
use App\Modules\Storefront\Shipping;
use App\Modules\Storefront\Ui;

$zones = Shipping::zones();
$minFee = Shipping::minFee();
$freeOver = Shipping::freeOver();
$zoneNames = implode(', ', array_column($zones, 'name'));
$zoneFees = implode(', ', array_map(static fn (array $z): string => $z['name'] . ' ' . money($z['fee']), $zones));
$faqs = [
    ['Where do you deliver?', 'Across Lebanon: ' . $zoneNames . '.'],
    ['How much does delivery cost?', 'The fee depends on your area: ' . $zoneFees . '.' . ($freeOver > 0 ? ' Orders of ' . money($freeOver) . ' or more get free delivery.' : '') . ' You see the exact fee at checkout, before you place your order.'],
    ['Can I pick my order up instead?', 'Yes. You can meet us at our pickup point. Mention it in the note when you order, or tell us on WhatsApp.'],
    ['How can I pay?', 'With store credit, cash on delivery, OMT or Whish. You can combine credit and cash: credit is taken off the total and you pay any gap on delivery. We never ask for card details on the site.'],
    ['Can I pay with store credit?', 'Yes. Sign in, and at checkout tick "Pay with my credit". 1 credit is worth $1. If your credit covers the whole order, including delivery, you pay nothing on delivery.'],
    ['What if the item is not as described?', 'Check your item when you receive it. If something is wrong, tell us straight away on WhatsApp and we will sort it out.'],
];
if (digital_enabled()) {
    $faqs[] = ['How do gift cards and digital codes work?', 'Pay by OMT or Whish first (no cash on delivery for digital items). We send your code on WhatsApp once we confirm the payment, usually within minutes during opening hours. There is no delivery fee for digital-only orders, store credit can not be used on them, and all digital sales are final once the code is delivered.'];
}
$meta['jsonld'][] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(
    static fn (array $f): array => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]],
    $faqs
)];
echo Ui::partial('page-head', ['crumbs' => $crumbs, 'h1' => 'Delivery & payment', 'lead' => 'Delivery across Lebanon from ' . money($minFee) . ', or pickup at our hub. Pay with credit, cash on delivery, OMT or Whish. No online card payment.']);
?>
<div class="container prose-wrap">
    <article class="prose">
        <h2>Delivery</h2>
        <ul class="icon-list">
            <li><?= Ui::icon('truck', 18) ?> Delivery to every region of Lebanon, with a fee that depends on your area (below).</li>
            <li><?= Ui::icon('pin', 18) ?> Or meet us at our pickup point (<?= e((string) setting('hub_address', 'Lebanon')) ?>). We share the exact spot when we confirm your order.</li>
            <li><?= Ui::icon('chat', 18) ?> We confirm delivery time with you on WhatsApp before we ship.</li>
        </ul>

        <h3>Delivery fee by area</h3>
        <?= Ui::partial('zone-table') ?>
        <p>You choose your area at checkout and see the exact fee before you place the order.</p>

        <h2>Payment</h2>
        <ul class="icon-list">
            <li><?= Ui::icon('wallet', 18) ?> <strong>Store credit</strong> from selling us games, taken off your order at checkout (1 credit = $1, no expiry). <a href="<?= e(url('/credit')) ?>">How credit works</a>.</li>
            <li><?= Ui::icon('wallet', 18) ?> <strong>Cash on delivery</strong> for whatever your credit does not cover, paid when you receive your order.</li>
            <li><?= Ui::icon('wallet', 18) ?> <strong>OMT</strong> or <strong>Whish</strong> transfer, if you prefer.</li>
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

        <h2>Buyer protection</h2>
        <p>Every item is inspected before sale, and every order is confirmed by a person on WhatsApp. If something is not as described, tell us and we will make it right.</p>
    </article>
    <?= Ui::partial('faq', ['faqs' => $faqs]) ?>
</div>
