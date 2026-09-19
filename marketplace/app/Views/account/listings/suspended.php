<?php
$meta = ['title' => 'Selling suspended | CyberGaming', 'noindex' => true];
$accountNav = 'listings';
?>
<?php require base_path('app/Views/account/_nav.php'); ?>
<link rel="stylesheet" href="<?= e(asset('css/listings.css')) ?>">
<div class="container ls-page">
    <section class="ls-card ls-empty ls-empty-bad" role="alert">
        <strong>Your selling is suspended</strong>
        <p>You cannot add or change listings right now. Please contact us on WhatsApp and we will help.</p>
        <a class="ls-btn ls-btn-primary" href="<?= e(wa_link('Hi CyberGaming, my member selling account is suspended.')) ?>" rel="noopener" target="_blank">Contact us</a>
    </section>
</div>
