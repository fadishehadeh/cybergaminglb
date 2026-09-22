<?php
use App\Modules\Storefront\Collections;
use App\Modules\Storefront\Guides;
use App\Modules\Storefront\Rules;
use App\Modules\Storefront\Seo;
use App\Modules\Storefront\SeoCatalog;
use App\Modules\Storefront\Ui;

/** @var array $stats @var array $platforms @var array $categories @var array $steelbooks @var array $latest @var array $giftCards */
$giftCards = $giftCards ?? [];
$hero = array_slice(array_values(array_filter($latest, static fn (array $h): bool => !empty($h['image']))), 0, 3);
$wa = wa_link('Hi CyberGaming, I want to sell my games: ');

$buyPct = rtrim(rtrim(number_format((float) setting('buyback_pct', 45), 1), '0'), '.');
$tradePct = rtrim(rtrim(number_format((float) setting('tradein_pct', 50), 1), '0'), '.');
$rejected = array_values(array_filter(Rules::sellFaqs(), static fn (array $f): bool => str_starts_with($f[0], 'What happens if')));
$homeFaqs = [
    ['What is CyberGaming Lebanon?', 'CyberGaming Lebanon is an online marketplace in Lebanon to buy, sell, trade and swap used and new video games and gaming gear. Every item is inspected, prices are in US dollars, and we deliver across Lebanon. See [[/about|about us]] and [[/how-it-works|how it works]].'],
    ['How much do you pay for used PS4 games?', "We pay $buyPct% of our shop price in cash or $tradePct% as store credit, adjusted for the condition of your copy. The exact amount depends on the title, so add your games to the [[/sell|sell page]] to see an instant quote."],
    ['Do you deliver outside Beirut?', 'Yes, we deliver across Lebanon. Local areas (' . Rules::nameList(Rules::names('local')) . ') are served by our own courier, and other areas by a third-party courier. Fees start at ' . money(SeoCatalog::cheapestFee()) . '. See [[/delivery-and-payment|delivery and payment]].'],
    ['Can I pay cash on delivery?', 'Yes, in local areas: our own courier lets you check the item and you pay cash on delivery. Remote areas are prepaid by OMT or Whish' . (Rules::prepayOn() && Rules::codAfter() > 0 ? ', and cash on delivery unlocks there after ' . Rules::codAfter() . ' delivered orders' : '') . '. You can also pay with store credit.'],
    ...$rejected,
    ['How does store credit work?', 'Store credit is money in your wallet that you spend in the shop: 1 credit is always worth $1 and it never expires. You earn it by selling or trading in games, and apply it at checkout. Read [[/credit|how store credit works]].'],
    ['Is my identity shared with buyers or sellers?', 'No. Buyers and sellers never see each other. Only an anonymous ID is ever visible, and your name, phone number and address are known only to you and to CyberGaming. See [[/how-it-works#anonymous|how we keep you anonymous]].'],
];
$meta['description'] = Seo::clip('Buy, sell and trade used PS4, PS5, Switch and Xbox games in Lebanon. ' . ($stats['items'] > 0 ? $stats['items'] . ' inspected items in stock' . ($stats['min_price'] !== null ? ' from ' . money($stats['min_price']) : '') . ', ' : '') . 'delivery across Lebanon, pay cash, OMT or Whish.');
$meta['jsonld'][] = Seo::webPage('WebPage', 'CyberGaming Lebanon: buy, sell and trade games and gaming gear', url('/'), (string) $meta['description']);
$meta['jsonld'][] = Seo::faqLd($homeFaqs);
$priceLinks = Collections::activeIn('price');
$genreLinks = Collections::activeIn('genre');
$homeGuides = Guides::latest(3);
$homeZones = SeoCatalog::zones();
?>
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy">
            <p class="eyebrow">Lebanon's game marketplace</p>
            <h1>Buy, sell &amp; trade games and gaming gear in Lebanon</h1>
            <p class="lead">Inspected used and new games at fair dollar prices. Order online, we confirm on WhatsApp, and we deliver across Lebanon.</p>
            <div class="hero-actions">
                <a class="btn btn-primary btn-lg" href="<?= e(url('/shop')) ?>">Shop games</a>
                <a class="btn btn-outline btn-lg" href="<?= e(url('/sell')) ?>">Sell your games</a>
            </div>
            <ul class="hero-points">
                <li><?= Ui::icon('shield', 18) ?> Inspected before sale</li>
                <li><?= Ui::icon('truck', 18) ?> Delivery across Lebanon</li>
                <li><?= Ui::icon('wallet', 18) ?> Store credit, cash, OMT or Whish</li>
            </ul>
        </div>
        <?php if ($hero): ?>
        <div class="hero-covers" aria-hidden="true">
            <?php foreach ($hero as $i => $h): ?>
                <img class="cover-<?= $i + 1 ?>" src="<?= e(media($h['image'])) ?>" alt="" width="300" height="400" fetchpriority="<?= $i === 0 ? 'high' : 'low' ?>">
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?= Seo::quickAnswerHtml('home') ?>

<section class="container stats" aria-label="Store at a glance">
    <div class="stat"><strong><?= number_format($stats['items']) ?></strong><span>items in stock</span></div>
    <div class="stat"><strong><?= number_format($stats['steelbooks']) ?></strong><span>collectable steelbooks</span></div>
    <div class="stat"><strong><?= (int) $stats['platforms'] ?></strong><span><?= $stats['platforms'] === 1 ? 'platform' : 'platforms' ?> with stock</span></div>
    <div class="stat"><strong><?= $stats['min_price'] !== null ? e(money($stats['min_price'])) : '–' ?></strong><span>prices from</span></div>
</section>

<section class="section container">
    <div class="section-head">
        <h2>Shop by platform</h2>
        <a class="link-more" href="<?= e(url('/shop')) ?>">View everything &rarr;</a>
    </div>
    <ul class="tile-grid tiles-platform">
        <?php foreach ($platforms as $pl): $n = (int) $pl['product_count']; ?>
            <li>
                <a class="tile<?= $n === 0 ? ' is-soon' : '' ?>" href="<?= e(url('/platform/' . $pl['slug'])) ?>">
                    <span class="tile-icon"><?= Ui::icon('gamepad', 26) ?></span>
                    <span class="tile-name"><?= e($pl['name']) ?></span>
                    <span class="tile-count"><?= $n > 0 ? $n . ($n === 1 ? ' item' : ' items') : 'Coming soon' ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<?php if ($steelbooks): ?>
<section class="section container">
    <div class="section-head">
        <div>
            <h2>Featured steelbooks</h2>
            <p class="section-sub">Collector's editions in metal cases. Limited copies.</p>
        </div>
        <a class="link-more" href="<?= e(url('/shop?edition=steelbook')) ?>">All steelbooks &rarr;</a>
    </div>
    <div class="product-grid">
        <?php foreach ($steelbooks as $i => $p): ?><?= Ui::partial('product-card', ['p' => $p, 'level' => 'h3']) ?><?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($giftCards): ?>
<section class="section container" aria-labelledby="gift-h">
    <div class="section-head">
        <div>
            <h2 id="gift-h">Gift cards &amp; digital codes</h2>
            <p class="section-sub">PSN, Xbox, Nintendo eShop and Steam. We send your code on WhatsApp after you pay by OMT or Whish.</p>
        </div>
        <a class="link-more" href="<?= e(url('/shop/gift-cards')) ?>">All gift cards &rarr;</a>
    </div>
    <div class="product-grid">
        <?php foreach ($giftCards as $p): ?><?= Ui::partial('product-card', ['p' => $p, 'level' => 'h3']) ?><?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="section container">
    <div class="section-head">
        <div>
            <h2>Latest arrivals</h2>
            <p class="section-sub">Freshly inspected and ready to ship.</p>
        </div>
        <a class="link-more" href="<?= e(url('/shop')) ?>">Shop all &rarr;</a>
    </div>
    <div class="product-grid">
        <?php foreach ($latest as $p): ?><?= Ui::partial('product-card', ['p' => $p, 'level' => 'h3']) ?><?php endforeach; ?>
    </div>
</section>

<section class="section container">
    <div class="section-head"><h2>Browse by category</h2></div>
    <ul class="tile-grid tiles-category">
        <?php foreach ($categories as $c): $n = (int) $c['product_count']; ?>
            <li>
                <a class="tile<?= $n === 0 ? ' is-soon' : '' ?>" href="<?= e(url('/shop/' . $c['slug'])) ?>">
                    <span class="tile-name"><?= e($c['name']) ?></span>
                    <span class="tile-count"><?= $n > 0 ? $n . ($n === 1 ? ' item' : ' items') : 'Coming soon' ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="section container home-links" aria-label="More ways to browse">
    <?php if ($priceLinks): ?>
    <div>
        <h2>Browse by price</h2>
        <ul class="link-list"><?php foreach ($priceLinks as $slug => $c): ?><li><a href="<?= e(url('/collections/' . $slug)) ?>"><?= e($c['title']) ?></a> <span><?= (int) $c['n'] ?></span></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>
    <?php if ($genreLinks): ?>
    <div>
        <h2>Browse by genre</h2>
        <ul class="link-list"><?php foreach ($genreLinks as $slug => $c): ?><li><a href="<?= e(url('/collections/' . $slug)) ?>"><?= e($c['title']) ?></a> <span><?= (int) $c['n'] ?></span></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>
    <?php if ($homeGuides): ?>
    <div>
        <h2>Latest guides</h2>
        <ul class="link-list"><?php foreach ($homeGuides as $g): ?><li><a href="<?= e(url('/guides/' . $g['slug'])) ?>"><?= e(Guides::text($g['title'])) ?></a></li><?php endforeach; ?>
            <li><a class="link-more" href="<?= e(url('/guides')) ?>">All guides &rarr;</a></li></ul>
    </div>
    <?php endif; ?>
    <?php if ($homeZones): ?>
    <div>
        <h2>Delivery areas</h2>
        <ul class="link-list"><?php foreach ($homeZones as $z): ?><li><a href="<?= e(url('/delivery-to/' . $z['slug'])) ?>"><?= e($z['short']) ?></a> <span><?= e(money($z['fee'])) ?></span></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>
</section>

<section class="section how">
    <div class="container">
        <div class="section-head"><h2>How it works</h2><a class="link-more" href="<?= e(url('/how-it-works')) ?>">The full story &rarr;</a></div>
        <ol class="steps">
            <li><span class="step-num">1</span><h3>Browse</h3><p>Pick your games and add them to your cart. Every title is inspected.</p></li>
            <li><span class="step-num">2</span><h3>Order on the site</h3><p>Leave your name, phone and delivery area. No account needed, and delivery fees are shown up front.</p></li>
            <li><span class="step-num">3</span><h3>We confirm on WhatsApp</h3><p>We message you to confirm availability, price and delivery.</p></li>
            <li><span class="step-num">4</span><h3>Delivery or pickup</h3><p>We deliver across Lebanon or you meet us at our pickup point. Pay with store credit and cash on delivery, or OMT / Whish.</p></li>
        </ol>
    </div>
</section>

<div class="container home-faq"><?= Seo::faqHtml($homeFaqs) ?></div>

<section class="container section">
    <div class="cta-block">
        <div>
            <h2>Got games to sell or trade?</h2>
            <p>We pay <strong><?= (int) setting('buyback_pct', 45) ?>%</strong> of resale value in cash, or <strong><?= (int) setting('tradein_pct', 50) ?>%</strong> as store credit that you spend in the shop (<a class="cta-link" href="<?= e(url('/credit')) ?>">how credit works</a>). Prefer another player's game? We run private swaps too.</p>
        </div>
        <div class="cta-actions">
            <a class="btn btn-primary btn-lg" href="<?= e(url('/sell')) ?>">Sell your games</a>
            <a class="btn btn-outline-light btn-lg" href="<?= e(url('/trade')) ?>">Trade in</a>
            <a class="btn btn-wa btn-lg" href="<?= e($wa) ?>" rel="noopener" target="_blank"><?= Ui::icon('whatsapp', 20) ?> WhatsApp us</a>
        </div>
    </div>
</section>
