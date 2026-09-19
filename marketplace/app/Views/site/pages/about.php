<?php
use App\Modules\Storefront\Ui;

echo Ui::partial('page-head', ['crumbs' => $crumbs, 'h1' => 'About CyberGaming Lebanon', 'lead' => 'A Lebanese marketplace for used and new games and gaming gear. Fair prices, inspected items, and a real person on WhatsApp.']);
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
    </article>
    <p class="see-also">Questions? <a href="<?= e(url('/contact')) ?>">Get in touch</a>.</p>
</div>
