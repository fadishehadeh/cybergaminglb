<?php
/** "How selling to members works" block. Needs $commission (percent as float). */
$commissionText = \App\Modules\Account\ListingBase::pct((float) $commission);
?>
<section class="ls-card ls-how" aria-labelledby="ls-how-title">
    <h2 id="ls-how-title">How selling to members works</h2>
    <ol class="ls-steps">
        <li><strong>List your item.</strong> Add a photo and details, and enter the price <em>you</em> want to receive.</li>
        <li><strong>We check it.</strong> Every listing is approved by our team before it appears in the shop.</li>
        <li><strong>A buyer orders.</strong> We handle the whole sale: you never chat with the buyer and never see their details.</li>
        <li><strong>Hand it over.</strong> Bring the item to our hub, or we contact you for a pickup. We inspect it before it is delivered.</li>
        <li><strong>Get paid.</strong> After delivery you receive your price, in cash or as credit in your wallet.</li>
    </ol>
    <ul class="ls-facts">
        <li><strong>Commission: <?= e($commissionText) ?></strong> added on top of your price. Buyers pay your price + <?= e($commissionText) ?>, rounded up to $0.50.</li>
        <li><strong>Delivery is paid by the buyer.</strong> It never comes out of your price.</li>
        <li><strong>Payout:</strong> you get exactly the price you entered, once the order is delivered.</li>
        <li><strong>Keep it on the platform.</strong> Never share contact details or deal off-platform.</li>
    </ul>
</section>
