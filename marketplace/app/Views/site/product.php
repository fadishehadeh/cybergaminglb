<?php
use App\Modules\Storefront\Cart;
use App\Modules\Storefront\Digital;
use App\Modules\Storefront\Ui;

/** @var array $p @var bool $available @var string $short @var string $what @var array $crumbs @var array $photos @var array $related */
$isDigital = Digital::is($p);
$region = $isDigital ? Digital::region($p) : '';
$kind = (string) ($p['digital_kind'] ?? '');
$genres = $isDigital ? [] : Ui::genres((string) ($p['genres'] ?? ''));
$maxQty = max(1, min((int) $p['stock'], Cart::MAX_LINE_QTY));
$inCart = (int) (Cart::raw()[(int) $p['id']] ?? 0);
$isUsed = Ui::isUsed($p);
$grade = (string) $p['item_condition'];
$isHardware = Ui::isHardware($p);
$gradeNote = $isHardware ? (Ui::GRADE_NOTES_HARDWARE[$grade] ?? '') : (Ui::GRADE_NOTES[$grade] ?? '');
$included = $isHardware ? [] : Ui::includes($p);
$boxItems = $isHardware ? Ui::lines((string) ($p['included_items'] ?? '')) : [];
$brand = trim((string) ($p['brand'] ?? ''));
$model = trim((string) ($p['model'] ?? ''));
$warranty = (int) ($p['warranty_months'] ?? 0);
// Real photos of this exact item, capped at 12 (the CSS-only gallery has 12 slots).
$gallery = [];
foreach (array_slice($photos, 0, 12) as $ph) {
    $pk = isset(Ui::PHOTO_LABELS[$ph['kind']]) ? $ph['kind'] : 'extra';
    $n = 0;
    foreach ($gallery as $g) { if ($g['kind'] === $pk) { $n++; } }
    $gallery[] = [
        'path'  => (string) $ph['path'],
        'kind'  => $pk,
        'label' => Ui::PHOTO_LABELS[$pk],
        'alt'   => $p['title'] . ' — ' . strtolower(Ui::PHOTO_LABELS[$pk]) . ($pk === 'extra' && $n > 0 ? ' ' . ($n + 1) : ''),
    ];
}
$waAsk = wa_link('Hi CyberGaming, I have a question about ' . $p['title'] . ($short !== '' ? " ($short)" : '') . ': ' . url('/product/' . $p['slug']));
?>
<div class="container page-head page-head-tight">
    <?= Ui::breadcrumbs($crumbs) ?>
</div>

<article class="container product" itemscope>
    <div class="product-gallery">
        <?php $badges = ($isDigital ? '<span class="badge badge-digital">Digital</span>' : ((int) $p['is_steelbook'] === 1 ? '<span class="badge badge-steel">Steelbook</span>' : ''))
            . (!$available ? '<span class="badge badge-sold">' . ($isDigital ? 'Unavailable' : 'Sold out') . '</span>' : ''); ?>
        <?php if ($gallery): ?>
        <div class="gallery" role="group" aria-label="Photos of this <?= $isHardware ? 'item' : 'copy' ?>">
            <?php foreach ($gallery as $i => $g): ?>
                <input class="gal-radio" type="radio" name="gal" id="gal-<?= $i ?>" value="<?= $i ?>"<?= $i === 0 ? ' checked' : '' ?> aria-label="<?= e($g['label']) ?>">
            <?php endforeach; ?>
            <div class="product-cover gal-main<?= $isHardware ? ' is-hw' : '' ?>">
                <?php foreach ($gallery as $i => $g): ?>
                    <figure class="gal-slide gal-s<?= $i ?>">
                        <?= Ui::picture($g['path'], $g['alt'], Ui::SIZES_MAIN, 800, 800, $i > 1, $i === 0) ?>
                        <figcaption><?= e($g['label']) ?></figcaption>
                    </figure>
                <?php endforeach; ?>
                <?= $badges ?>
            </div>
            <?php if (count($gallery) > 1): ?>
            <ul class="thumbs gal-thumbs" aria-label="Choose a photo">
                <?php foreach ($gallery as $i => $g): ?>
                    <li><label for="gal-<?= $i ?>"><?= Ui::picture($g['path'], $g['alt'], '72px', 72, 72, $i >= 4) ?><span><?= e($g['label']) ?></span></label></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="product-cover<?= $isHardware ? ' is-hw' : '' ?>">
            <?= Ui::cover($p, $p['title'] . ($short !== '' ? ' – ' . $short : '') . ($isDigital ? '' : ' cover'), 600, 800, 'fetchpriority="high"', 'product-main-img') ?>
            <?= $badges ?>
        </div>
        <?php if (!$isDigital): ?><p class="gal-caption"><?= Ui::icon('camera', 16) ?> <?= $isHardware ? 'Photos of this exact item on request' : 'Cover art shown &mdash; photos of this exact copy on request' ?></p><?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="product-info">
        <p class="product-kicker">
            <?php if ($isDigital): ?><span class="kicker-digital">Digital</span> &middot; <?php endif; ?>
            <?php if ($p['platform_slug']): ?><a href="<?= e(url('/platform/' . $p['platform_slug'])) ?>"><?= e($p['platform_name']) ?></a> &middot; <?php endif; ?>
            <a href="<?= e(url('/shop/' . $p['category_slug'])) ?>"><?= e($p['category_name']) ?></a>
        </p>
        <h1><?= e($p['title']) ?></h1>
        <?php if ($isHardware && Ui::brandModel($p) !== ''): ?><p class="product-brand"><?= e(Ui::brandModel($p)) ?></p><?php endif; ?>
        <?php if ($isDigital && $region !== ''): ?>
            <ul class="chips chips-region" aria-label="Region"><li class="chip-region">Region: <?= e($region) ?></li></ul>
        <?php endif; ?>

        <p class="product-price"><?= e(money($p['price'])) ?> <span class="price-note">USD</span></p>
        <p class="stock <?= $available ? 'in' : 'out' ?>">
            <?php if ($available): ?>
                <?= Ui::icon('check', 16) ?> <?= $isDigital ? 'Available' : 'In stock' ?><?= !$isDigital && (int) $p['stock'] <= 3 ? ' &ndash; only ' . (int) $p['stock'] . ' left' : '' ?>
            <?php else: ?>
                <?= $isDigital ? 'Currently unavailable' : 'Sold out &ndash; this copy has found a new home' ?>
            <?php endif; ?>
        </p>

        <?php if (!$isDigital): ?>
        <section class="cond-block <?= $isUsed ? 'is-used' : 'is-new' ?>" aria-labelledby="cond-h">
            <h2 id="cond-h" class="cond-title"><span class="cond-pill"><?= $isUsed ? 'Used &mdash; ' . e($grade) : 'New &mdash; sealed' ?></span></h2>
            <p class="cond-note"><?= $isUsed ? e($gradeNote) : 'Brand new, factory sealed and never opened.' ?></p>
            <?php if ($isHardware && $isUsed): ?>
                <details class="grade-key">
                    <summary>What do the grades mean?</summary>
                    <ul>
                        <?php foreach (Ui::GRADE_NOTES_HARDWARE as $gName => $gNote): ?><li<?= $gName === $grade ? ' class="is-current"' : '' ?>><?= e($gNote) ?></li><?php endforeach; ?>
                    </ul>
                </details>
            <?php endif; ?>
            <?php if ($included): ?>
                <h3 class="incl-h">What&rsquo;s included</h3>
                <ul class="incl-list">
                    <?php foreach ($included as [$key, $label, $yes]): ?>
                        <li class="<?= $yes ? 'is-yes' : 'is-no' ?>"><?= Ui::icon($yes ? 'check' : 'cross', 18) ?> <span><?= e($label) ?><span class="sr-only"><?= $yes ? ': included' : ': not included' ?></span></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ($isHardware): ?>
                <p class="cond-trust"><?= Ui::icon('shield', 18) ?> <span>Every used peripheral and accessory is tested by us before delivery.</span></p>
            <?php elseif ($isUsed): ?>
                <p class="cond-trust"><?= Ui::icon('shield', 18) ?> <span>Every used game is inspected by us before delivery.</span></p>
            <?php endif; ?>
        </section>
        <?php endif; ?>
        <?php if ($isDigital && $region !== ''): ?>
            <p class="region-warning" role="note"><?= Ui::icon('shield', 18) ?> <span><strong><?= e(Digital::regionNote($region)) ?></strong><?= strcasecmp($region, 'global') === 0 ? '' : ' Check that your account region matches before you buy: we cannot refund a code bought for the wrong region.' ?></span></p>
        <?php endif; ?>

        <?php if ($available): ?>
            <form class="buy-form" method="post" action="<?= e(url('/cart/add')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                <?php if ($maxQty > 1): ?>
                    <div class="qty">
                        <label for="qty">Qty</label>
                        <select id="qty" name="qty"><?php for ($q = 1; $q <= $maxQty; $q++): ?><option value="<?= $q ?>"><?= $q ?></option><?php endfor; ?></select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="qty" value="1">
                <?php endif; ?>
                <button class="btn btn-primary btn-lg" type="submit"><?= Ui::icon('cart', 20) ?> Add to cart</button>
            </form>
            <?php if ($inCart > 0): ?><p class="in-cart"><?= Ui::icon('check', 16) ?> <?= $inCart ?> in your cart &middot; <a href="<?= e(url('/cart')) ?>">View cart</a></p><?php endif; ?>
        <?php else: ?>
            <div class="sold-note">
                <?php if ($isDigital): ?>
                    <p>This one is not available right now. Have a look at similar items below, or ask us on WhatsApp when it will be back.</p>
                    <p><a class="btn btn-wa" href="<?= e(wa_link('Hi CyberGaming, when will ' . $p['title'] . ' be available again?')) ?>" rel="noopener" target="_blank"><?= Ui::icon('whatsapp', 18) ?> Ask on WhatsApp</a></p>
                <?php else: ?>
                    <p>This one is gone, but we get new stock often. Have a look at similar titles below, or ask us to find you another copy.</p>
                    <p><a class="btn btn-wa" href="<?= e(wa_link('Hi CyberGaming, do you have another copy of ' . $p['title'] . ($short !== '' ? " ($short)" : '') . '?')) ?>" rel="noopener" target="_blank"><?= Ui::icon('whatsapp', 18) ?> Ask for another copy</a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <p><a class="link-wa" href="<?= e($waAsk) ?>" rel="noopener" target="_blank"><?= Ui::icon('whatsapp', 18) ?> Questions? Ask us on WhatsApp</a></p>
        <?php if ($available && !$isDigital): ?><p><a class="link-wa" href="<?= e(url('/trade?want=' . rawurlencode($p['slug']))) ?>"><?= Ui::icon('swap', 18) ?> Trade in games for this</a></p><?php endif; ?>

        <?php if ($isDigital): ?>
            <dl class="specs">
                <div><dt>Type</dt><dd><?= e(Digital::kindLabel($kind)) ?></dd></div>
                <?php if ($p['platform_name']): ?><div><dt>Platform</dt><dd><?= e($p['platform_name']) ?></dd></div><?php endif; ?>
                <?php if ($region !== ''): ?><div><dt>Region</dt><dd><?= e($region) ?></dd></div><?php endif; ?>
                <div><dt>Delivery</dt><dd>Code sent on WhatsApp</dd></div>
            </dl>
        <?php elseif ($isHardware): ?>
            <?php
            $facts = [];
            if ($brand !== '') { $facts['Brand'] = $brand; }
            if ($model !== '') { $facts['Model'] = $model; }
            if ($p['platform_name']) { $facts['Platform'] = (string) $p['platform_name']; }
            $facts['Condition'] = $isUsed ? 'Used, ' . $grade : 'New (sealed)';
            if ($warranty > 0) { $facts['Warranty'] = $warranty . '-month warranty'; }
            $facts['Availability'] = $available ? ((int) $p['stock'] <= 3 ? 'In stock, only ' . (int) $p['stock'] . ' left' : 'In stock') : 'Sold out';
            $taken = array_map('strtolower', array_keys($facts));
            $extraFacts = array_values(array_filter(Ui::specRows((string) ($p['specs'] ?? '')), static fn (array $r): bool => !in_array(strtolower($r[0]), $taken, true)));
            ?>
            <section class="quick-facts" aria-labelledby="qf-h">
                <h2 id="qf-h">Quick facts</h2>
                <dl class="specs specs-hw">
                    <?php foreach ($facts as $label => $value): ?><div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div><?php endforeach; ?>
                    <?php foreach ($extraFacts as [$label, $value]): ?><div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div><?php endforeach; ?>
                </dl>
            </section>
            <?php if ($boxItems): ?>
            <section class="box-block" aria-labelledby="box-h">
                <h2 id="box-h">What&rsquo;s in the box</h2>
                <ul class="incl-list"><?php foreach ($boxItems as $item): ?><li class="is-yes"><?= Ui::icon('check', 18) ?> <span><?= e($item) ?></span></li><?php endforeach; ?></ul>
            </section>
            <?php endif; ?>
        <?php else: ?>
            <dl class="specs">
                <?php if ($p['platform_name']): ?><div><dt>Platform</dt><dd><?= e($p['platform_name']) ?></dd></div><?php endif; ?>
                <div><dt>Condition</dt><dd><?= e($p['item_condition']) ?></dd></div>
                <div><dt>Edition</dt><dd><?= e($p['edition']) ?><?= (int) $p['is_steelbook'] === 1 && stripos($p['edition'], 'steel') === false ? ' (Steelbook)' : '' ?></dd></div>
                <?php if ($p['year']): ?><div><dt>Year</dt><dd><?= (int) $p['year'] ?></dd></div><?php endif; ?>
                <?php if ($genres): ?>
                    <div><dt>Genres</dt><dd>
                        <ul class="chips"><?php foreach ($genres as $g): ?><li><a href="<?= e(url('/shop?genre=' . rawurlencode($g))) ?>"><?= e($g) ?></a></li><?php endforeach; ?></ul>
                    </dd></div>
                <?php endif; ?>
            </dl>
        <?php endif; ?>

        <?php if (trim((string) $p['description']) !== ''): ?>
            <section class="prose product-desc">
                <h2>About this item</h2>
                <p><?= nl2br(e($p['description'])) ?></p>
            </section>
        <?php endif; ?>

        <?php if ($isDigital): ?>
        <section class="digital-info card-box" aria-labelledby="dig-h">
            <h2 id="dig-h"><?= Ui::icon('gift', 22) ?> How you receive it</h2>
            <p>We send your code on WhatsApp after we confirm your payment &mdash; usually within minutes during opening hours.</p>
            <ul class="icon-list">
                <li><?= Ui::icon('wallet', 18) ?> <span><strong>Payment:</strong> <?= e(Digital::PAYMENT_NOTE) ?></span></li>
                <li><?= Ui::icon('lock', 18) ?> <span><strong>All digital sales are final once the code is delivered.</strong> Codes cannot be returned or exchanged.</span></li>
                <?php if ($region !== ''): ?><li><?= Ui::icon('pin', 18) ?> <span><strong><?= e(Digital::regionNote($region)) ?></strong></span></li><?php endif; ?>
            </ul>
            <?php if ($kind === 'steam_gift'): ?>
                <h3>How Steam gifts work</h3>
                <ol class="mini-steps">
                    <li>Place your order and pay by OMT or Whish when we message you.</li>
                    <li>Add our Steam account as a friend when we contact you.</li>
                    <li>We send the game as a Steam gift and you accept it in your Steam client.</li>
                </ol>
                <p class="fine">Gifts follow Steam's own regional rules, so we confirm eligibility for your account before you pay.</p>
            <?php endif; ?>
            <p class="fine">Store credit can't be used on gift cards and digital items.</p>
        </section>
        <?php else: ?>
        <aside class="trust" aria-label="Buying with CyberGaming">
            <h2>Sold by CyberGaming &ndash; <?= $isHardware ? 'tested before delivery' : 'inspected before sale' ?></h2>
            <ul>
                <li><?= Ui::icon('shield', 22) ?><div><strong><?= $isHardware ? 'Tested &amp; protected' : 'Inspected &amp; protected' ?></strong><span>We check every item and confirm your order on WhatsApp before anything ships.</span></div></li>
                <li><?= Ui::icon('truck', 22) ?><div><strong>Delivery across Lebanon</strong><span>Fee by area, shown at checkout. Or meet us at our pickup point.</span></div></li>
                <li><?= Ui::icon('wallet', 22) ?><div><strong>Credit, cash, OMT or Whish</strong><span>Use store credit, pay the rest on delivery. No card details needed.</span></div></li>
            </ul>
        </aside>
        <?php endif; ?>
    </div>
</article>

<?php if ($related): ?>
<section class="section container" aria-labelledby="related-h">
    <div class="section-head">
        <h2 id="related-h"><?= $available ? 'You may also like' : ($isDigital ? 'Similar items' : 'Similar titles in stock') ?></h2>
        <?php if ($p['platform_slug']): ?><a class="link-more" href="<?= e(url('/platform/' . $p['platform_slug'] . '/' . $p['category_slug'])) ?>">See all &rarr;</a><?php endif; ?>
    </div>
    <div class="product-grid">
        <?php foreach ($related as $r): ?><?= Ui::partial('product-card', ['p' => $r, 'level' => 'h3']) ?><?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
