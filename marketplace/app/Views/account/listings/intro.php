<?php
/** @var float $commission */
$meta = ['title' => 'My listings | CyberGaming', 'description' => 'Sell your games to other members.', 'noindex' => true];
$accountNav = 'listings';
?>
<?php require base_path('app/Views/account/_nav.php'); ?>
<link rel="stylesheet" href="<?= e(asset('css/listings.css')) ?>">
<div class="container ls-page">

    <div class="ls-head">
        <div>
            <h1>My listings</h1>
            <p class="ls-muted">Sell your used games and gear to other members, without the hassle.</p>
        </div>
        <a class="ls-btn ls-btn-primary" href="<?= e(url('/account/listings/new')) ?>">Start selling</a>
    </div>

    <section class="ls-card ls-empty">
        <strong>You have not listed anything yet</strong>
        <p>Accept the member terms once, then add your first item. We do the selling, the inspection and the delivery.</p>
        <a class="ls-btn ls-btn-primary" href="<?= e(url('/account/listings/new')) ?>">Start selling</a>
    </section>

    <?php require __DIR__ . '/_how.php'; ?>
</div>
