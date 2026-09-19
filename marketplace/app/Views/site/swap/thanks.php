<?php
use App\Modules\Storefront\Ui;

/** @var array $swap @var string $fee @var string $waLink */
?>
<div class="container page-head">
    <div class="thanks">
        <span class="thanks-icon"><?= Ui::icon('check', 34) ?></span>
        <h1>Thank you! Your swap is submitted.</h1>
        <p class="lead-sm">Your swap number is <strong class="order-code"><?= e($swap['code']) ?></strong>. Keep it and quote it whenever you contact us.</p>
    </div>
</div>

<div class="container cart-layout">
    <div>
        <section class="wa-cta card-box">
            <h2>Want it moving faster? Message us</h2>
            <p>Your listing will appear on the swap board after our team reviews it. Send us your swap number on WhatsApp so we can find you a match sooner.</p>
            <a class="btn btn-wa btn-xl" href="<?= e($waLink) ?>" rel="noopener" target="_blank"><?= Ui::icon('whatsapp', 24) ?> Message us on WhatsApp</a>
        </section>

        <section class="card-box next-steps">
            <h2>What happens next</h2>
            <ol class="steps steps-vertical">
                <li><span class="step-num">1</span><div><h3>Review</h3><p>We check your listing and publish it on the <a href="<?= e(url('/swap')) ?>">swap board</a>. Only the platform, the games, an anonymous swapper number and your delivery zone are shown.</p></div></li>
                <li><span class="step-num">2</span><div><h3>Match</h3><p>When another player is interested, we contact you privately on WhatsApp. Nobody sees your phone number.</p></div></li>
                <li><span class="step-num">3</span><div><h3>Hub and inspection</h3><p>Both games come to our hub. We inspect them and hand each player the game they wanted. The flat fee is <?= e($fee) ?> per side, charged when the swap completes.</p></div></li>
            </ol>
        </section>
    </div>

    <aside class="summary" aria-label="Swap summary">
        <h2>Swap <?= e($swap['code']) ?></h2>
        <dl>
            <?php if ($swap['platform_name']): ?><div><dt>Platform</dt><dd><?= e($swap['platform_name']) ?></dd></div><?php endif; ?>
            <div><dt>You have</dt><dd><?= e($swap['offering']) ?></dd></div>
            <div><dt>You want</dt><dd><?= e($swap['wanting']) ?></dd></div>
            <div><dt>Status</dt><dd>Waiting for review</dd></div>
        </dl>
        <p class="fine"><button type="button" class="btn-link" data-copy="<?= e($swap['code']) ?>" hidden>Copy code</button></p>
    </aside>
</div>
