<?php
/** @var array $p product row  @var bool $eager  @var string $level */
use App\Modules\Storefront\Digital;
use App\Modules\Storefront\Ui;

$eager = $eager ?? false;
$level = $level ?? 'h3';
$short = Ui::shortPlatform($p['platform_slug'] ?? null, $p['platform_name'] ?? null);
$isDigital = Digital::is($p);
$region = $isDigital ? Digital::region($p) : '';
$genres = $isDigital ? [] : array_slice(Ui::genres((string) ($p['genres'] ?? '')), 0, 2);
$meta = $isDigital
    ? array_values(array_filter([Digital::kindLabel($p['digital_kind'] ?? null), $short]))
    : array_values(array_filter([$short, $p['year'] ?: null]));
$condBadge = Ui::conditionBadge($p);
$complete = Ui::isComplete($p);
?>
<article class="card">
    <div class="card-media">
        <?= Ui::cover($p, $p['title'] . ($short !== '' ? ' – ' . $short : '') . ($isDigital ? '' : ' cover'), 300, 400, $eager ? 'fetchpriority="high"' : 'loading="lazy" decoding="async"') ?>
        <span class="price-tag"><?= e(money($p['price'])) ?></span>
        <?php if ($isDigital): ?><span class="badge badge-digital">Digital</span><?php elseif ((int) $p['is_steelbook'] === 1): ?><span class="badge badge-steel">Steelbook</span><?php endif; ?>
        <?php if ($condBadge !== ''): ?><span class="badge badge-cond <?= $condBadge === 'New' ? 'is-new' : 'is-used' ?>"><?= e($condBadge) ?></span><?php endif; ?>
    </div>
    <div class="card-body">
        <<?= $level ?> class="card-title"><a href="<?= e(url('/product/' . $p['slug'])) ?>"><?= e($p['title']) ?></a></<?= $level ?>>
        <p class="card-meta"><?= e(implode(' · ', $meta)) ?></p>
        <?php if ($complete): ?><p class="card-complete"><?= Ui::icon('check', 14) ?> Complete: box &middot; cover &middot; manual</p><?php endif; ?>
        <?php if ($region !== ''): ?>
            <ul class="chips" aria-label="Region"><li class="chip-region">Region: <?= e($region) ?></li></ul>
        <?php endif; ?>
        <?php if ($genres): ?>
            <ul class="chips" aria-label="Genres"><?php foreach ($genres as $g): ?><li><?= e($g) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <form class="card-cta" method="post" action="<?= e(url('/cart/add')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
            <input type="hidden" name="qty" value="1">
            <button class="btn btn-primary btn-block" type="submit"><?= Ui::icon('cart', 18) ?> Add to cart</button>
        </form>
    </div>
</article>
