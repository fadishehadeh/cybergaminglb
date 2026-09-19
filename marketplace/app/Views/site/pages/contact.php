<?php
use App\Modules\Storefront\Ui;

$ig = (string) setting('instagram_url', '');
$email = (string) setting('contact_email', '');
$number = Ui::waNumber();
echo Ui::partial('page-head', ['crumbs' => $crumbs, 'h1' => 'Contact CyberGaming', 'lead' => 'The fastest way to reach us is WhatsApp. We usually reply within a few hours.']);
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
</div>
