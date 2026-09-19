<?php
use App\Modules\Storefront\Ui;

/** @var string $store */
$meta = [
    'title'       => 'Application received | CyberGaming',
    'description' => 'Your CyberGaming seller application was received.',
    'noindex'     => true,
];
?>
<link rel="stylesheet" href="<?= e(asset('css/seller.css')) ?>">
<div class="container seller-public">
    <div class="thanks card-box">
        <span class="thanks-icon"><?= Ui::icon('check', 34) ?></span>
        <h1>Thank you, <?= e($store) ?>!</h1>
        <p class="lead-sm">We received your seller application. We will review it and contact you on WhatsApp or email.</p>
        <p>Your account stays inactive until we approve it. Once approved you can sign in and add your first listing.</p>
        <p class="thanks-actions">
            <a class="btn btn-primary" href="<?= e(url('/')) ?>">Back to the shop</a>
            <a class="btn btn-ghost" href="<?= e(url('/seller/login')) ?>">Seller sign in</a>
        </p>
    </div>
</div>
