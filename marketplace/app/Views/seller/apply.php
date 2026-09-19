<?php
use App\Modules\Storefront\Seo;
use App\Modules\Storefront\Ui;
use App\Support\Pricing;

/** @var float $commission */
$pct     = (float) $commission;
$pctText = rtrim(rtrim(number_format($pct, 2, '.', ''), '0'), '.');
$example = 20.0;
$exBuyer = Pricing::buyerPrice($example, $pct);
$crumbs  = [['Home', '/'], ['Sell on CyberGaming', null]];
$meta = [
    'title'       => 'Sell on CyberGaming Lebanon: List Your Games & Gaming Gear | CyberGaming',
    'description' => Seo::clip('Open a CyberGaming seller account in Lebanon. You set the price you want, we handle buyers, delivery and payment, and pay you once the item is delivered.'),
    'canonical'   => url('/seller/apply'),
    'jsonld'      => [Seo::breadcrumbs($crumbs)],
];
$field = static fn (string $key): string => e(old($key));
?>
<link rel="stylesheet" href="<?= e(asset('css/seller.css')) ?>">
<div class="container page-head">
    <?= Ui::breadcrumbs($crumbs) ?>
    <h1>Sell on CyberGaming</h1>
    <p class="lead-sm">Have games or gaming gear to sell? List them with us and reach buyers all over Lebanon, without dealing with a single buyer yourself.</p>
    <p class="sp-cta"><a class="btn btn-primary" href="#apply-form">Apply now</a> <a class="btn btn-ghost" href="<?= e(url('/seller/login')) ?>">Seller sign in</a></p>
</div>

<div class="container seller-public seller-apply">
    <div class="seller-apply-grid">
        <div class="seller-apply-info">
            <section class="card-box">
                <h2>How it works</h2>
                <ol class="sp-steps">
                    <li><span class="sp-step-num">1</span><div><h3>Apply</h3><p>Fill in the short form. We review every application and contact you on WhatsApp or email.</p></div></li>
                    <li><span class="sp-step-num">2</span><div><h3>List your items</h3><p>Add a photo and the price <em>you</em> want to receive. We check each listing before it goes live.</p></div></li>
                    <li><span class="sp-step-num">3</span><div><h3>We sell and deliver</h3><p>Buyers order from CyberGaming only. We collect the item from you (or you drop it at our hub), inspect it and deliver it.</p></div></li>
                    <li><span class="sp-step-num">4</span><div><h3>You get paid</h3><p>Once the buyer has the item, your payout is scheduled and we pay you the full price you asked for.</p></div></li>
                </ol>
            </section>

            <section class="card-box">
                <h2>Simple, transparent pricing</h2>
                <p>You set the price you want to <strong>receive</strong>. We add our commission (default <strong><?= e($pctText) ?>%</strong>) on top, so buyers see one clean price. You never pay anything upfront and never pay fees out of your own price.</p>
                <div class="worked">
                    <div><span>You want to receive</span><strong><?= e(money($example)) ?></strong></div>
                    <div><span>Our commission (<?= e($pctText) ?>%)</span><span>+ <?= e(money($exBuyer - $example)) ?> (rounded up to $0.50)</span></div>
                    <div class="grand"><span>Buyers see</span><strong><?= e(money($exBuyer)) ?></strong></div>
                </div>
            </section>

            <section class="card-box">
                <h2>Why sell with us</h2>
                <ul class="sp-benefits">
                    <li><strong>Buyers never contact you.</strong> We are the only contact with buyers. Your name and number stay private.</li>
                    <li><strong>We handle delivery and payment</strong> across Lebanon: cash on delivery, OMT and Whish.</li>
                    <li><strong>No listing fees.</strong> We only earn when your item sells.</li>
                    <li><strong>Track everything</strong> in your seller portal: listings, sold items and payouts.</li>
                </ul>
            </section>
        </div>

        <form id="apply-form" class="card-box seller-apply-form" method="post" action="<?= e(url('/seller/apply')) ?>" novalidate>
            <?= csrf_field() ?>
            <h2>Apply for a seller account</h2>
            <p class="fine">Already approved? <a href="<?= e(url('/seller/login')) ?>">Sign in</a>.</p>

            <div class="form-row">
                <label for="a-store">Store or seller name <abbr title="required">*</abbr></label>
                <input id="a-store" name="store_name" type="text" required maxlength="150" autocomplete="organization" value="<?= $field('store_name') ?>">
            </div>
            <div class="form-row">
                <label for="a-contact">Contact person <abbr title="required">*</abbr></label>
                <input id="a-contact" name="contact_name" type="text" required maxlength="120" autocomplete="name" value="<?= $field('contact_name') ?>">
            </div>
            <div class="form-row">
                <label for="a-phone">Phone / WhatsApp <abbr title="required">*</abbr></label>
                <input id="a-phone" name="phone" type="tel" required maxlength="40" inputmode="tel" autocomplete="tel" placeholder="+961 70 123 456" value="<?= $field('phone') ?>">
                <small>Only CyberGaming sees this. We use it to reach you about pickups and payouts.</small>
            </div>
            <div class="form-row">
                <label for="a-area">Area <abbr title="required">*</abbr></label>
                <input id="a-area" name="area" type="text" required maxlength="120" placeholder="e.g. Beirut, Jounieh, Tripoli" value="<?= $field('area') ?>">
            </div>
            <div class="form-row">
                <label for="a-email">Email <abbr title="required">*</abbr></label>
                <input id="a-email" name="email" type="email" required maxlength="190" autocomplete="username" value="<?= $field('email') ?>">
                <small>You will sign in with this email.</small>
            </div>
            <div class="form-row">
                <label for="a-pass">Password <abbr title="required">*</abbr></label>
                <input id="a-pass" name="password" type="password" required minlength="8" autocomplete="new-password">
                <small>At least 8 characters.</small>
            </div>
            <div class="form-row">
                <label for="a-about">What do you sell? <abbr title="required">*</abbr></label>
                <textarea id="a-about" name="about" rows="4" required minlength="10" maxlength="2000" placeholder="e.g. Used PS4 and PS5 games, about 40 titles, plus a few controllers."><?= $field('about') ?></textarea>
            </div>
            <div class="hp" aria-hidden="true"><label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

            <label class="sp-terms">
                <input type="checkbox" name="terms" value="1" required <?= old('terms') === '1' ? 'checked' : '' ?>>
                <span>I understand CyberGaming is the only contact with buyers; I won't share contact details or take deals off-platform; violating this ends my account and forfeits pending payouts.</span>
            </label>

            <button class="btn btn-primary btn-lg btn-block" type="submit">Send my application</button>
            <p class="fine">We review every application and contact you on WhatsApp or email.</p>
        </form>
    </div>
</div>
