<?php
use App\Modules\Storefront\Rules;
use App\Modules\Storefront\Seo;
use App\Modules\Storefront\Shipping;
use App\Modules\Storefront\Ui;

$faqs = [
    ['What is CyberGaming Lebanon?', 'CyberGaming Lebanon is an online marketplace in Lebanon where you can buy, sell, trade and swap used and new video games and gaming gear. We inspect every item, handle the payment and protect both sides of the deal. See [[/how-it-works|how it works]].'],
    ['What do you sell?', 'Inspected used and new games, collectable steelbook editions and gaming gear such as consoles, controllers and PC peripherals. Our stock changes as items arrive and sell, so the [[/shop|shop]] always shows what is available today.'],
    ['Do you inspect what you sell?', 'Yes. Every item is checked by our team before it is listed and again before it is delivered. Used games carry a condition grade, and every used listing shows photos of the exact copy.'],
    ['Where do you deliver?', 'Across Lebanon. Local areas (' . Rules::nameList(Rules::names('local')) . ') are served by our own courier from ' . Rules::feeRange('local') . '; other areas by a third-party courier from ' . (Rules::feeRange('remote') ?: money(Shipping::minFee())) . '. Details on [[/delivery-and-payment|delivery and payment]].'],
    ['How can I pay?', 'With store credit, cash on delivery in local areas, or OMT and Whish. Remote areas are prepaid by OMT or Whish' . (Rules::prepayOn() ? '' : ' (currently optional)') . '. Prices are in US dollars and we never take card details on the site.'],
    ['Can I sell my games to you?', 'Yes. Get an instant quote on the [[/sell|sell page]]: cash pays ' . (int) setting('buyback_pct', 45) . '% of our shop price and store credit pays ' . (int) setting('tradein_pct', 50) . '%. You can also [[/trade|trade in]] games for shop items or [[/swap|swap]] with another player.'],
    ['Is my identity shared with buyers or sellers?', 'No. Buyers and sellers never see each other. Members only ever have an anonymous ID, and names, phone numbers and addresses are known only to you and to CyberGaming. Read [[/how-it-works#anonymous|how we keep you anonymous]].'],
    ['How do I contact you?', 'The fastest way is WhatsApp, and we usually reply within a few hours. See the [[/contact|contact page]].'],
];
$meta['jsonld'][] = Seo::webPage('AboutPage', 'About CyberGaming Lebanon', (string) ($meta['canonical'] ?? url('/about')), (string) ($meta['description'] ?? ''));
$meta['jsonld'][] = Seo::faqLd($faqs);

echo Ui::partial('page-head', ['crumbs' => $crumbs, 'h1' => 'About CyberGaming Lebanon', 'lead' => 'A Lebanese marketplace for used and new games and gaming gear. Fair prices, inspected items, and a real person on WhatsApp.']);
echo Seo::quickAnswerHtml('about');
?>
<div class="container prose-wrap">
    <article class="prose">
        <h2>Why we exist</h2>
        <p>Buying games in Lebanon has meant overpaying for new copies or gambling on strangers in social media groups. CyberGaming puts a trusted store in the middle. We check every game, we handle the money, and we protect both sides of the deal.</p>

        <h2>What we do</h2>
        <ul>
            <li><strong><a href="<?= e(url('/shop')) ?>">Sell to you:</a></strong> a growing catalogue of inspected used and new games, steelbooks and gear, priced in US dollars.</li>
            <li><strong><a href="<?= e(url('/sell')) ?>">Buy from you:</a></strong> we pay cash or store credit for games you no longer play.</li>
            <li><strong><a href="<?= e(url('/trade')) ?>">Trade-ins</a> and <a href="<?= e(url('/swap')) ?>">swaps:</a></strong> move on to your next game with little or no cash.</li>
        </ul>

        <h2>What you can count on</h2>
        <ul>
            <li><strong>Inspected before sale.</strong> Discs, cases and codes are checked by our team.</li>
            <li><strong>Privacy.</strong> We never share buyer or seller contact details. Read more on <a href="<?= e(url('/how-it-works')) ?>">how it works</a>.</li>
            <li><strong>Human support.</strong> Every order is confirmed on WhatsApp by a real person.</li>
            <li><strong>Simple payment.</strong> Store credit, cash on delivery, OMT or Whish. See <a href="<?= e(url('/delivery-and-payment')) ?>">delivery &amp; payment</a>.</li>
        </ul>

        <h2>Learn more</h2>
        <ul>
            <li>Read our <a href="<?= e(url('/guides')) ?>">guides</a> on buying, selling and checking used games and gear.</li>
            <li>See <a href="<?= e(url('/collections')) ?>">collections</a> of what is in stock, by price and genre.</li>
        </ul>
    </article>
    <?= Seo::faqHtml($faqs) ?>
    <p class="see-also">Questions? <a href="<?= e(url('/contact')) ?>">Get in touch</a>.</p>
</div>
