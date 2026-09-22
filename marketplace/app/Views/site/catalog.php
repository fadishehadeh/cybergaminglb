<?php
use App\Modules\Storefront\Catalog;
use App\Modules\Storefront\Ui;

/** @var string $h1 @var string $intro @var array $seoBlock @var array $crumbs @var array $result @var array $filters
 *  @var array $genres @var string $basePath @var array $pageQuery @var bool $filtered @var ?array $platform @var ?array $category
 *  @var array $platforms @var array $categories @var int $perPage */
$total = $result['total'];
$from = $total === 0 ? 0 : ($result['page'] - 1) * $perPage + 1;
$to = min($total, $result['page'] * $perPage);
$hasFilters = $filtered || $filters['sort'] !== 'newest';
$isGift = !empty($isGift);
$isHardware = !empty($isHardware);
$brands = $brands ?? [];
$platformChoices = $platformChoices ?? [];
?>
<div class="container page-head">
    <?= Ui::breadcrumbs($crumbs) ?>
    <h1><?= e($h1) ?></h1>
    <?php if ($intro !== ''): ?><p class="lead-sm"><?= e($intro) ?></p><?php endif; ?>
</div>

<div class="container">
    <?php if (!$platform || !$category): ?>
    <nav class="pill-nav" aria-label="Browse by platform and category">
        <?php if (!$platform): ?>
            <?php foreach ($pillPlatforms as $pl): if ((int) $pl['product_count'] === 0) { continue; } ?>
                <a class="pill" href="<?= e(url('/platform/' . $pl['slug'] . ($category ? '/' . $category['slug'] : ''))) ?>"><?= e(Ui::shortPlatform($pl['slug'], $pl['name'])) ?></a>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if (!$category): ?>
            <?php foreach ($pillCategories as $c): if ((int) $c['product_count'] === 0) { continue; } ?>
                <a class="pill" href="<?= e(url(($platform ? '/platform/' . $platform['slug'] . '/' : '/shop/') . $c['slug'])) ?>"><?= e($c['name']) ?></a>
            <?php endforeach; ?>
        <?php endif; ?>
    </nav>
    <?php endif; ?>

    <form class="filters" method="get" action="<?= e(url($basePath)) ?>" role="search" aria-label="Filter products">
        <div class="field field-q">
            <label for="f-q">Search</label>
            <input id="f-q" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Title…" maxlength="80">
        </div>
        <?php if ($genres): ?>
        <div class="field">
            <label for="f-genre">Genre</label>
            <select id="f-genre" name="genre">
                <option value="">All genres</option>
                <?php foreach ($genres as $g): ?><option value="<?= e($g) ?>"<?= $filters['genre'] === $g ? ' selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <?php if ($isHardware && $brands): ?>
        <div class="field">
            <label for="f-brand">Brand</label>
            <select id="f-brand" name="brand" data-autosubmit>
                <option value="">All brands</option>
                <?php foreach ($brands as $b): ?><option value="<?= e($b['brand']) ?>"<?= $filters['brand'] === $b['brand'] ? ' selected' : '' ?>><?= e($b['brand']) ?> (<?= (int) $b['n'] ?>)</option><?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <?php if ($platformChoices): ?>
        <div class="field">
            <label for="f-platform">Platform</label>
            <select id="f-platform" name="platform" data-autosubmit>
                <option value="">All platforms</option>
                <?php foreach ($platformChoices as $pc): ?><option value="<?= e($pc['slug']) ?>"<?= $filters['platform'] === $pc['slug'] ? ' selected' : '' ?>><?= e($pc['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <?php if (!$isGift && !$isHardware): ?>
        <div class="field">
            <label for="f-edition">Edition</label>
            <select id="f-edition" name="edition">
                <option value="">All editions</option>
                <option value="steelbook"<?= $filters['edition'] === 'steelbook' ? ' selected' : '' ?>>Steelbook only</option>
            </select>
        </div>
        <?php endif; ?>
        <?php if (!$isGift): ?>
        <div class="field">
            <label for="f-cond">Condition</label>
            <select id="f-cond" name="cond" data-autosubmit>
                <option value="">All</option>
                <option value="new"<?= $filters['cond'] === 'new' ? ' selected' : '' ?>>New</option>
                <option value="used"<?= $filters['cond'] === 'used' ? ' selected' : '' ?>>Used</option>
            </select>
        </div>
        <?php endif; ?>
        <div class="field">
            <label for="f-sort">Sort by</label>
            <select id="f-sort" name="sort">
                <?php foreach (Catalog::SORTS as $key => [$label]): ?><option value="<?= e($key) ?>"<?= $filters['sort'] === $key ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="field field-actions">
            <button class="btn btn-dark" type="submit">Apply</button>
            <?php if ($hasFilters): ?><a class="btn btn-ghost" href="<?= e(url($basePath)) ?>">Reset</a><?php endif; ?>
        </div>
    </form>

    <p class="result-count" role="status">
        <?php if ($total > 0): ?>Showing <strong><?= $from ?>–<?= $to ?></strong> of <strong><?= number_format($total) ?></strong> <?= $total === 1 ? 'item' : 'items' ?><?php else: ?>No items found<?php endif; ?>
    </p>

    <?php if ($result['items']): ?>
        <div class="product-grid">
            <?php foreach ($result['items'] as $i => $p): ?><?= Ui::partial('product-card', ['p' => $p, 'level' => 'h2', 'eager' => $i < 4]) ?><?php endforeach; ?>
        </div>
        <?= Ui::pagination($basePath, $pageQuery, $result['page'], $result['pages']) ?>
    <?php else: ?>
        <div class="empty">
            <?= Ui::icon('gamepad', 44) ?>
            <?php if ($hasFilters): ?>
                <h2>Nothing matches those filters</h2>
                <p>Try a shorter title, a different genre or condition, or clear the filters to see everything in stock.</p>
                <p><a class="btn btn-primary" href="<?= e(url($basePath)) ?>">Clear filters</a></p>
            <?php else: ?>
                <h2>Nothing in stock here right now</h2>
                <p>New items arrive often. Browse everything we have, or tell us what you are looking for and we will find it.</p>
                <p>
                    <a class="btn btn-primary" href="<?= e(url('/shop')) ?>">Browse all products</a>
                    <a class="btn btn-wa" href="<?= e(wa_link('Hi CyberGaming, are you getting ' . ($platform['name'] ?? 'games') . ' ' . ($category['name'] ?? 'items') . ' soon?')) ?>" rel="noopener" target="_blank">Ask on WhatsApp</a>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($seoBlock): ?>
    <section class="seo-block" aria-label="About these products">
        <?php foreach ($seoBlock as $heading => $text): ?>
            <h2><?= e($heading) ?></h2>
            <p><?= e($text) ?></p>
        <?php endforeach; ?>
        <p><?= $isGift ? 'New to digital orders? Read ' : 'New to CyberGaming? Read ' ?><a href="<?= e(url('/how-it-works')) ?>">how it works</a> or our <a href="<?= e(url('/delivery-and-payment')) ?>">delivery and payment</a> details.</p>
    </section>
    <?php endif; ?>
</div>
