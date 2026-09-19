<?php
use App\Modules\Storefront\Ui;

/** @var array $stats @var array $platforms @var array $categories @var array $steelbooks @var array $latest @var array $giftCards */
$giftCards = $giftCards ?? [];
$hero = array_slice(array_values(array_filter($latest, static fn (array $h): bool => !empty($h['image']))), 0, 3);
$wa = wa_link('Hi CyberGaming, I want to sell my games: ');
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
