<?php
use App\Modules\Storefront\Rules;
use App\Modules\Storefront\Seo;
use App\Modules\Storefront\Shipping;
use App\Modules\Storefront\Ui;

$faqs = [
    ['What is CyberGaming Lebanon?', 'CyberGaming Lebanon is an online shop in Lebanon selling used and new video games and gaming gear. Every item is inspected, prices are in US dollars, and we deliver across Lebanon. See [[/how-it-works|how it works]].'],
    ['What do you sell?', 'Inspected used and new games, collectable steelbook editions and gaming gear such as consoles, controllers and PC peripherals. Our stock changes as items arrive and sell, so the [[/shop|shop]] always shows what is available today.'],
    ['Do you inspect what you sell?', 'Yes. Every item is checked by our team before it is listed and again before it is delivered. Used games carry a condition grade, and every used listing shows photos of the exact copy.'],
    ['Where do you deliver?', 'Across Lebanon. Local areas (' . Rules::nameList(Rules::names('local')) . ') are served by our own courier from ' . Rules::feeRange('local') . '; other areas by a third-party courier from ' . (Rules::feeRange('remote') ?: money(Shipping::minFee())) . '. Details on [[/delivery-and-payment|delivery and payment]].'],
    ['How can I pay?', 'Cash on delivery in local areas, or OMT and Whish. Remote areas are prepaid by OMT or Whish' . (Rules::prepayOn() ? '' : ' (currently optional)') . '. Prices are in US dollars and we never take card details on the site.'],
    ['Do you buy, trade or swap games?', 'No. CyberGaming Lebanon is a straightforward shop: we sell our own stock of used and new games and gaming gear. We do not buy games from the public, take trade-ins, or run a swap board.'],
    ['Do I need an account to order?', 'No. Checkout is guest-only: just your name, phone number and delivery area. No registration, no card details.'],
    ['How do I contact you?', 'The fastest way is WhatsApp, and we usually reply within a few hours. See the [[/contact|contact page]].'],
];
$meta['jsonld'][] = Seo::webPage('AboutPage', 'About CyberGaming Lebanon', (string) ($meta['canonical'] ?? url('/about')), (string) ($meta['description'] ?? ''));
$meta['jsonld'][] = Seo::faqLd($faqs);

echo Ui::partial('page-head', ['crumbs' => $crumbs, 'h1' => 'About CyberGaming Lebanon', 'lead' => 'A Lebanese shop for used and new games and gaming gear. Fair prices, inspected items, and a real person on WhatsApp.']);
echo Seo::quickAnswerHtml('about');
?>
<div class="container prose-wrap">
    <article class="prose">
        <h2>Why we exist</h2>
        <p>Buying games in Lebanon has meant overpaying for new copies or gambling on strangers in social media groups. CyberGaming is a shop you can trust: every game is checked, prices are clear in US dollars, and a real person confirms every order.</p>

        <h2>What we sell</h2>
        <ul>
            <li><strong><a href="<?= e(url('/shop')) ?>">Games:</a></strong> a growing catalogue of inspected used and new games and steelbooks, priced in US dollars.</li>
            <li><strong><a href="<?= e(url('/shop/consoles')) ?>">Gear:</a></strong> consoles, controllers and PC gaming peripherals such as keyboards, mice and mousepads.</li>
        </ul>

        <h2>What you can count on</h2>
        <ul>
            <li><strong>Inspected before sale.</strong> Discs, cases and codes are checked by our team.</li>
            <li><strong>Human support.</strong> Every order is confirmed on WhatsApp by a real person.</li>
            <li><strong>No account needed.</strong> Checkout is guest-only. See <a href="<?= e(url('/how-it-works')) ?>">how it works</a>.</li>
            <li><strong>Simple payment.</strong> Cash on delivery, OMT or Whish. See <a href="<?= e(url('/delivery-and-payment')) ?>">delivery &amp; payment</a>.</li>
        </ul>

        <h2>Learn more</h2>
        <ul>
            <li>Read our <a href="<?= e(url('/guides')) ?>">guides</a> on buying and checking used games and gear.</li>
            <li>See <a href="<?= e(url('/collections')) ?>">collections</a> of what is in stock, by price and genre.</li>
        </ul>
    </article>
    <?= Seo::faqHtml($faqs) ?>
    <p class="see-also">Questions? <a href="<?= e(url('/contact')) ?>">Get in touch</a>.</p>
</div>
