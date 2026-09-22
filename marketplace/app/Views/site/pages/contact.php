<?php
use App\Modules\Storefront\Seo;
use App\Modules\Storefront\Ui;

$ig = (string) setting('instagram_url', '');
$email = (string) setting('contact_email', '');
$number = Ui::waNumber();
$faqs = [
    ['What is the fastest way to contact CyberGaming?', 'WhatsApp. Message us from the button on this page and a person replies, usually within a few hours.'],
    ['Can I ask about an item before I order?', 'Yes. Send us the product name or link on WhatsApp and we will confirm it is in stock, its condition and the delivery fee to your area. Every order is confirmed on WhatsApp before we deliver anyway.'],
    ['Where is your pickup point?', 'We share the exact location on WhatsApp when we confirm your order. You can also bring games to our hub for free when you sell or trade them in. See [[/delivery-and-payment|delivery and payment]].'],
    ['I want to sell or trade my games. Where do I start?', 'Start with the [[/sell|instant quote]] or the [[/trade|trade-in calculator]]. You get a price in cash and store credit straight away, and you only need an account when you send the request.'],
    ['Do you take card payments?', 'No. We never ask for card details on the site. You pay with store credit, cash on delivery in local areas, or OMT and Whish.'],
    ['Is my phone number shared with other customers?', 'No. Buyers and sellers never see each other, and your name, phone number and address are known only to you and to CyberGaming. See [[/how-it-works#anonymous|how we keep you anonymous]].'],
];
$meta['jsonld'][] = Seo::webPage('ContactPage', 'Contact CyberGaming Lebanon', (string) ($meta['canonical'] ?? url('/contact')), (string) ($meta['description'] ?? ''));
$meta['jsonld'][] = Seo::faqLd($faqs);
echo Ui::partial('page-head', ['crumbs' => $crumbs, 'h1' => 'Contact CyberGaming', 'lead' => 'The fastest way to reach us is WhatsApp. We usually reply within a few hours.']);
echo Seo::quickAnswerHtml('contact');
?>
<div class="container prose-wrap">
    <div class="contact-grid">
        <div class="card-box">
            <h2><?= Ui::icon('whatsapp', 22) ?> WhatsApp</h2>
            <p>Questions about an item, an order, selling, trading or swapping? Message us.<?= $number !== '' ? ' Our number is <strong>' . e($number) . '</strong>.' : '' ?></p>
            <a class="btn btn-wa btn-lg" href="<?= e(wa_link('Hi CyberGaming!')) ?>" rel="noopener" target="_blank">Chat on WhatsApp</a>
        </div>
        <?php if ($ig !== ''): ?>
        <div class="card-box">
            <h2><?= Ui::icon('instagram', 22) ?> Instagram</h2>
            <p>See new arrivals and steelbooks first.</p>
            <a class="btn btn-dark btn-lg" href="<?= e($ig) ?>" rel="noopener" target="_blank">Follow us</a>
        </div>
        <?php endif; ?>
        <?php if ($email !== ''): ?>
        <div class="card-box">
            <h2><?= Ui::icon('mail', 22) ?> Email</h2>
            <p>For anything longer than a chat message.</p>
            <a class="btn btn-dark btn-lg" href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
        </div>
        <?php endif; ?>
        <div class="card-box">
            <h2><?= Ui::icon('pin', 22) ?> Pickup point</h2>
            <p><?= e((string) setting('hub_address', 'Lebanon')) ?>. We share the exact location on WhatsApp when we confirm your order.</p>
            <a class="btn btn-ghost" href="<?= e(url('/delivery-and-payment')) ?>">Delivery &amp; payment</a>
        </div>
    </div>
    <?= Seo::faqHtml($faqs) ?>
</div>
