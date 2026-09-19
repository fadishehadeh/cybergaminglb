<?php
/** @var float $commission */
$meta = ['title' => 'Sell to members | CyberGaming', 'description' => 'Member selling terms.', 'noindex' => true];
$accountNav = 'listings';
$commissionText = \App\Modules\Account\ListingBase::pct((float) $commission);
?>
<?php require base_path('app/Views/account/_nav.php'); ?>
<link rel="stylesheet" href="<?= e(asset('css/listings.css')) ?>">
<div class="container ls-page">

    <div class="ls-head">
        <div>
            <h1>Sell to other members</h1>
            <p class="ls-muted">List your games and gear. We take care of the sale, the inspection and the delivery.</p>
        </div>
    </div>

    <section class="ls-card ls-narrow">
        <h2>Member selling terms</h2>
        <ul class="ls-terms">
            <li><strong>We handle every sale and delivery.</strong> You never deal with buyers directly, and buyers never see who you are.</li>
            <li><strong>We inspect items before delivering.</strong> An item that is not as described can be refused.</li>
            <li><strong>Never share contact details or deal off-platform.</strong> Phone numbers, emails, links or social handles are not allowed. Violations end your account and forfeit pending payouts.</li>
            <li><strong>Commission is <?= e($commissionText) ?>.</strong> It is added on top of the price you enter: you receive exactly that price after delivery. The buyer pays the delivery fee.</li>
            <li><strong>You are paid after delivery</strong>, in cash or as credit in your wallet.</li>
        </ul>

        <form method="post" action="<?= e(url('/account/listings/join')) ?>" class="ls-form">
            <?= csrf_field() ?>
            <label class="ls-check ls-check-big">
                <input type="checkbox" name="terms" value="1" required>
                <span>I have read and accept the member selling terms.</span>
            </label>
            <div class="ls-actions">
                <button type="submit" class="ls-btn ls-btn-primary ls-btn-lg">Accept and continue</button>
                <a class="ls-btn ls-btn-lg" href="<?= e(url('/account')) ?>">Not now</a>
            </div>
        </form>
    </section>
</div>
