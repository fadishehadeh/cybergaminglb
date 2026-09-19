<?php
use App\Modules\Storefront\Ui;

$meta = ['title' => 'Page not found | CyberGaming', 'noindex' => true, 'description' => 'The page you were looking for could not be found.'];
?>
<div class="container error-page">
    <p class="error-code">404</p>
    <h1>We couldn't find that page</h1>
    <p class="lead-sm">The link may be old, or the game may have sold. Try a search, or jump to one of these:</p>
    <form class="search search-big" action="<?= e(url('/shop')) ?>" method="get" role="search">
        <label class="sr-only" for="err-search">Search games</label>
        <input id="err-search" type="search" name="q" placeholder="Search for a game…" maxlength="80">
        <button type="submit" aria-label="Search"><?= Ui::icon('search') ?></button>
    </form>
    <ul class="pill-nav center">
        <li><a class="pill" href="<?= e(url('/')) ?>">Home</a></li>
        <li><a class="pill" href="<?= e(url('/shop')) ?>">Shop all</a></li>
        <li><a class="pill" href="<?= e(url('/platform/ps4')) ?>">PS4 games</a></li>
        <li><a class="pill" href="<?= e(url('/shop?edition=steelbook')) ?>">Steelbooks</a></li>
        <li><a class="pill" href="<?= e(url('/sell')) ?>">Sell your games</a></li>
        <li><a class="pill" href="<?= e(url('/contact')) ?>">Contact us</a></li>
    </ul>
</div>
