<?php
declare(strict_types=1);

use App\Modules\Storefront\Cart;
use App\Modules\Storefront\Catalog;
use App\Modules\Storefront\Collections;
use App\Modules\Storefront\Guides;
use App\Modules\Storefront\Seo;
use App\Modules\Storefront\SeoCatalog;
use App\Modules\Storefront\Ui;

/** @var string $content rendered view (provided by View::render) */
$meta = isset($meta) && is_array($meta) ? $meta : [];
$siteName = (string) setting('site_name', 'CyberGaming Lebanon');
$pageTitle = Seo::fitTitle((string) ($meta['title'] ?? $siteName));
$pageDesc = (string) ($meta['description'] ?? setting('tagline', 'Buy, sell & trade games and gaming gear in Lebanon'));
$canonical = $meta['canonical'] ?? null;
$ogImage = (string) ($meta['image'] ?? Seo::defaultImage());
$noindex = !empty($meta['noindex']);
$activeNav = isset($nav) && is_string($nav) ? $nav : '';
$cartCount = Cart::count();
$navPlatforms = Catalog::platforms();
$navCategories = array_values(array_filter(Catalog::categories(), static fn (array $c): bool => (int) $c['product_count'] > 0));
$waUrl = wa_link('Hi CyberGaming!');
$igUrl = (string) setting('instagram_url', '');
$email = (string) setting('contact_email', '');
// account state: customers see their name + credit, guests see sign in / register, staff see nothing account-specific
$customer = auth()->hasRole('customer') ? auth()->user() : null;
$isStaff = $customer === null && auth()->check();
$acctFirst = $customer ? (explode(' ', trim((string) $customer['name']))[0] ?: 'there') : '';
$acctCredit = $customer ? '$' . number_format((float) $customer['credit_balance'], 2) : '';
$searchValue = is_string(request()->query('q')) ? (string) request()->query('q') : '';

$metaLd = is_array($meta['jsonld'] ?? null) ? $meta['jsonld'] : [];
// Category / platform / shop listings (the catalogue view exposes $result and $basePath): CollectionPage + ItemList unless the controller already supplied one.
if (isset($result, $basePath) && is_array($result) && !empty($result['items']) && !$noindex && empty($meta['no_listing_ld'])) {
    $hasList = false;
    foreach ($metaLd as $node) {
        if (is_array($node) && in_array($node['@type'] ?? '', ['CollectionPage', 'ItemList'], true)) {
            $hasList = true;
        }
    }
    if (!$hasList) {
        $metaLd[] = Seo::listingLd(isset($h1) && is_string($h1) ? $h1 : $pageTitle, (string) $canonical, $pageDesc, $result['items'], (int) ($result['page'] ?? 1), (int) ($perPage ?? 24));
    }
}
$jsonld = array_merge([Seo::organization(), Seo::website()], $metaLd);
$twitterCard = isset($meta['image']) || trim((string) setting('seo_default_og_image', '')) !== '' ? 'summary_large_image' : 'summary';
$googleVerify = trim((string) setting('seo_google_verification', ''));
$bingVerify = trim((string) setting('seo_bing_verification', ''));
$footerGuides = Guides::latest(4);
$footerCollections = array_slice(Collections::active(), 0, 6, true);
$footerZones = SeoCatalog::zones();

$primaryNav = [
    'how-it-works' => ['How it works', '/how-it-works'],
    'credit'       => ['Store credit', '/credit'],
    'guides'       => ['Guides', '/guides'],
    'about'        => ['About', '/about'],
];
$sellNav = ['sell' => ['Sell your games', '/sell'], 'trade' => ['Trade in for credit', '/trade'], 'swap' => ['Swap board', '/swap']];
$sellActive = isset($sellNav[$activeNav]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDesc) ?>">
<meta name="robots" content="<?= $noindex ? 'noindex,follow' : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1' ?>">
<?php if ($googleVerify !== ''): ?><meta name="google-site-verification" content="<?= e($googleVerify) ?>">
<?php endif; ?>
<?php if ($bingVerify !== ''): ?><meta name="msvalidate.01" content="<?= e($bingVerify) ?>">
<?php endif; ?>
<?php if ($canonical): ?><link rel="canonical" href="<?= e($canonical) ?>">
<?php endif; ?>
<link rel="alternate" type="application/rss+xml" title="<?= e($siteName) ?> products (Merchant Center feed)" href="<?= e(url('/feeds/products.xml')) ?>">
<link rel="alternate" type="application/json" title="<?= e($siteName) ?> products (JSON feed)" href="<?= e(url('/feeds/products.json')) ?>">
<?php if (!empty($meta['prev'])): ?><link rel="prev" href="<?= e($meta['prev']) ?>">
<?php endif; ?>
<?php if (!empty($meta['next'])): ?><link rel="next" href="<?= e($meta['next']) ?>">
<?php endif; ?>
<meta name="theme-color" content="#101a3f">
<meta name="format-detection" content="telephone=no">
<link rel="icon" type="image/png" href="<?= e(asset('img/favicon.png')) ?>">
<link rel="apple-touch-icon" href="<?= e(asset('img/favicon.png')) ?>">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:locale" content="en_US">
<meta property="og:type" content="<?= e($meta['og_type'] ?? 'website') ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($pageDesc) ?>">
<?php if ($canonical): ?><meta property="og:url" content="<?= e($canonical) ?>">
<?php endif; ?>
<meta property="og:image" content="<?= e($ogImage) ?>">
<?php foreach (($meta['og_extra'] ?? []) as $prop => $val): ?><meta property="<?= e($prop) ?>" content="<?= e($val) ?>">
<?php endforeach; ?>
<meta name="twitter:card" content="<?= $twitterCard ?>">
<meta name="twitter:title" content="<?= e($pageTitle) ?>">
<meta name="twitter:description" content="<?= e($pageDesc) ?>">
<meta name="twitter:image" content="<?= e($ogImage) ?>">
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/seo.css')) ?>">
<?php foreach ($jsonld as $ld): ?>
<script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endforeach; ?>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header">
    <input type="checkbox" id="nav-toggle" class="nav-toggle" aria-label="Show or hide the menu">
    <div class="container topbar">
        <label for="nav-toggle" class="burger" aria-hidden="true"><span></span></label>
        <a class="brand" href="<?= e(url('/')) ?>" aria-label="<?= e($siteName) ?> – home">
            <img src="<?= e(asset('img/logo-wide.png')) ?>" alt="<?= e($siteName) ?>" width="134" height="58">
        </a>
        <form class="search" action="<?= e(url('/shop')) ?>" method="get" role="search">
            <label class="sr-only" for="site-search">Search games</label>
            <input id="site-search" type="search" name="q" value="<?= e($searchValue) ?>" placeholder="Search games, e.g. God of War" maxlength="80" autocomplete="off">
            <button type="submit" aria-label="Search"><?= Ui::icon('search') ?></button>
        </form>
        <a class="wa-top" href="<?= e($waUrl) ?>" rel="noopener" target="_blank"><?= Ui::icon('whatsapp', 18) ?> <span>WhatsApp</span></a>
        <?php if ($customer): ?>
            <a class="acct-link is-in<?= $activeNav === 'account' ? ' is-active' : '' ?>" href="<?= e(url('/account')) ?>" aria-label="My account: <?= e($acctFirst) ?>, <?= e($acctCredit) ?> credit">
                <?= Ui::icon('user', 22) ?><span class="acct-text">Hi <?= e($acctFirst) ?> <span class="acct-sep">&middot;</span> <strong><?= e($acctCredit) ?></strong> credit</span>
            </a>
            <form class="logout-form" method="post" action="<?= e(url('/account/logout')) ?>">
                <?= csrf_field() ?>
                <button class="btn-link" type="submit">Log out</button>
            </form>
        <?php elseif (!$isStaff): ?>
            <a class="acct-link" href="<?= e(url('/account/login')) ?>" aria-label="Sign in or register">
                <?= Ui::icon('user', 22) ?><span class="acct-text">Sign in / Register</span>
            </a>
        <?php endif; ?>
        <a class="cart-link" href="<?= e(url('/cart')) ?>" aria-label="Cart, <?= $cartCount ?> <?= $cartCount === 1 ? 'item' : 'items' ?>">
            <?= Ui::icon('cart', 24) ?>
            <?php if ($cartCount > 0): ?><span class="cart-badge"><?= $cartCount ?></span><?php endif; ?>
        </a>
    </div>

    <nav class="mainnav" aria-label="Primary">
        <div class="container">
            <ul>
                <li class="has-sub<?= $activeNav === 'shop' ? ' is-active' : '' ?>">
                    <a href="<?= e(url('/shop')) ?>">Shop</a>
                    <ul class="sub" aria-label="Shop by platform">
                        <li><a href="<?= e(url('/shop')) ?>">All products</a></li>
                        <?php foreach ($navCategories as $c): ?>
                            <li><a href="<?= e(url('/shop/' . $c['slug'])) ?>"><?= e($c['name']) ?></a></li>
                        <?php endforeach; ?>
                        <?php foreach ($navPlatforms as $pl): ?>
                            <li><a href="<?= e(url('/platform/' . $pl['slug'])) ?>"><?= e($pl['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li class="has-sub<?= $sellActive ? ' is-active' : '' ?>">
                    <a href="<?= e(url('/sell')) ?>">Sell &amp; Trade</a>
                    <ul class="sub" aria-label="Sell, trade or swap">
                        <?php foreach ($sellNav as $key => [$label, $path]): ?>
                            <li><a href="<?= e(url($path)) ?>"<?= $activeNav === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php foreach ($primaryNav as $key => [$label, $path]): ?>
                    <li<?= $activeNav === $key ? ' class="is-active"' : '' ?>><a href="<?= e(url($path)) ?>"<?= $activeNav === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
                <?php endforeach; ?>
                <?php if ($customer): ?>
                    <li class="nav-account"><a href="<?= e(url('/account')) ?>">Hi <?= e($acctFirst) ?> &middot; <?= e($acctCredit) ?> credit</a></li>
                    <li class="nav-account">
                        <form method="post" action="<?= e(url('/account/logout')) ?>"><?= csrf_field() ?><button class="nav-logout" type="submit">Log out</button></form>
                    </li>
                <?php elseif (!$isStaff): ?>
                    <li class="nav-account"><a href="<?= e(url('/account/login')) ?>">Sign in</a></li>
                    <li class="nav-account"><a href="<?= e(url('/account/register')) ?>">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
</header>

<main id="main" tabindex="-1">
<?php if ($msg = flash('success')): ?>
    <div class="container"><div class="flash flash-success" role="status"><?= e($msg) ?></div></div>
<?php endif; ?>
<?php if ($msg = flash('error')): ?>
    <div class="container"><div class="flash flash-error" role="alert"><?= e($msg) ?></div></div>
<?php endif; ?>
<?= $content ?>
</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a href="<?= e(url('/')) ?>" class="footer-logo"><img src="<?= e(asset('img/logo-wide.png')) ?>" alt="<?= e($siteName) ?>" width="134" height="58" loading="lazy"></a>
            <p>Lebanon's marketplace to buy, sell, trade and swap games and gaming gear. Every item inspected, every order confirmed on WhatsApp.</p>
            <p class="footer-social">
                <a class="btn btn-wa btn-sm" href="<?= e($waUrl) ?>" rel="noopener" target="_blank"><?= Ui::icon('whatsapp', 18) ?> WhatsApp us</a>
                <?php if ($igUrl !== ''): ?><a class="btn btn-ghost btn-sm" href="<?= e($igUrl) ?>" rel="noopener" target="_blank"><?= Ui::icon('instagram', 18) ?> Instagram</a><?php endif; ?>
            </p>
        </div>
        <nav aria-label="Shop links">
            <h2>Shop</h2>
            <ul>
                <li><a href="<?= e(url('/shop')) ?>">All products</a></li>
                <?php foreach ($navCategories as $c): ?><li><a href="<?= e(url('/shop/' . $c['slug'])) ?>"><?= e($c['name']) ?></a></li><?php endforeach; ?>
                <?php foreach ($navPlatforms as $pl): ?><li><a href="<?= e(url('/platform/' . $pl['slug'])) ?>"><?= e($pl['name']) ?></a></li><?php endforeach; ?>
            </ul>
        </nav>
        <nav aria-label="Sell and trade links">
            <h2>Sell &amp; trade</h2>
            <ul>
                <li><a href="<?= e(url('/sell')) ?>">Sell your games</a></li>
                <li><a href="<?= e(url('/trade')) ?>">Trade in for credit</a></li>
                <li><a href="<?= e(url('/credit')) ?>">How store credit works</a></li>
                <li><a href="<?= e(url('/swap')) ?>">Swap with players</a></li>
                <li><a href="<?= e(url('/seller/apply')) ?>">Sell on CyberGaming (stores)</a></li>
            </ul>
        </nav>
        <nav aria-label="Information">
            <h2>Information</h2>
            <ul>
                <li><a href="<?= e(url('/how-it-works')) ?>">How it works</a></li>
                <li><a href="<?= e(url('/account')) ?>">My account</a></li>
                <li><a href="<?= e(url('/credit')) ?>">Store credit</a></li>
                <li><a href="<?= e(url('/delivery-and-payment')) ?>">Delivery &amp; payment</a></li>
                <li><a href="<?= e(url('/about')) ?>">About us</a></li>
                <li><a href="<?= e(url('/contact')) ?>">Contact</a></li>
                <?php if ($email !== ''): ?><li><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li><?php endif; ?>
            </ul>
        </nav>
    </div>
    <div class="container footer-seo">
        <nav aria-label="Guides">
            <h2>Guides</h2>
            <ul>
                <?php foreach ($footerGuides as $g): ?><li><a href="<?= e(url('/guides/' . $g['slug'])) ?>"><?= e(Guides::text($g['title'])) ?></a></li><?php endforeach; ?>
                <li><a href="<?= e(url('/guides')) ?>">All guides</a></li>
            </ul>
        </nav>
        <nav aria-label="Collections">
            <h2>Collections</h2>
            <ul>
                <?php foreach ($footerCollections as $slug => $c): ?><li><a href="<?= e(url('/collections/' . $slug)) ?>"><?= e($c['title']) ?></a></li><?php endforeach; ?>
                <?php if ($footerCollections): ?><li><a href="<?= e(url('/collections')) ?>">All collections</a></li><?php endif; ?>
            </ul>
        </nav>
        <nav aria-label="Delivery areas" class="footer-zones">
            <h2>Delivery areas</h2>
            <ul>
                <?php foreach ($footerZones as $z): ?><li><a href="<?= e(url('/delivery-to/' . $z['slug'])) ?>"><?= e($z['short']) ?></a></li><?php endforeach; ?>
            </ul>
        </nav>
        <nav aria-label="For AI assistants">
            <h2>For AI assistants</h2>
            <ul>
                <li><a href="<?= e(url('/llms.txt')) ?>">llms.txt</a></li>
                <li><a href="<?= e(url('/llms-full.txt')) ?>">llms-full.txt</a></li>
                <li><a href="<?= e(url('/feeds/products.json')) ?>">Product feed (JSON)</a></li>
                <li><a href="<?= e(url('/sitemap.xml')) ?>">Sitemap</a></li>
            </ul>
        </nav>
    </div>
    <div class="container footer-bottom">
        <p>&copy; <?= date('Y') ?> <?= e($siteName) ?>. Prices in US dollars.</p>
        <p>Store credit &middot; Cash on delivery &middot; OMT &middot; Whish</p>
    </div>
</footer>

<a class="wa-float" href="<?= e($waUrl) ?>" rel="noopener" target="_blank" aria-label="Chat with us on WhatsApp"><?= Ui::icon('whatsapp', 30) ?></a>
<script src="<?= e(asset('js/site.js')) ?>" defer></script>
</body>
</html>
