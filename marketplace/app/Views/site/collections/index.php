<?php
use App\Modules\Storefront\CollectionController;
use App\Modules\Storefront\Seo;
use App\Modules\Storefront\Ui;

/** @var array $crumbs @var array<string,array> $active */
echo Ui::partial('page-head', ['crumbs' => $crumbs, 'h1' => 'Game and gaming gear collections', 'lead' => '']);
$total = array_sum(array_column($active, 'n'));
echo Seo::render('quick-answer', ['text' => $active
    ? 'These collections group what CyberGaming has in stock by price, genre, edition and type of gear. Each page lists real items with today\'s prices in US dollars, every item is inspected before sale, and we deliver across Lebanon. Pick a collection to see the matching items, or use the shop filters to search by title.'
    : 'Collections group what CyberGaming has in stock by price, genre and type of gear. None has enough items to show right now, so browse the whole shop instead.']);
$groups = [];
foreach ($active as $slug => $d) {
    $groups[$d['group']][$slug] = $d;
}
?>
<div class="container collection-hub">
    <?php foreach (CollectionController::GROUP_LABELS as $g => $label): if (empty($groups[$g])) { continue; } ?>
        <section aria-labelledby="grp-<?= e($g) ?>">
            <h2 id="grp-<?= e($g) ?>"><?= e($label) ?></h2>
            <ul class="tile-grid tiles-collection">
                <?php foreach ($groups[$g] as $slug => $d): ?>
                    <li><a class="tile" href="<?= e(url('/collections/' . $slug)) ?>"><span class="tile-name"><?= e($d['h1']) ?></span><span class="tile-count"><?= (int) $d['n'] ?> <?= (int) $d['n'] === 1 ? 'item' : 'items' ?></span></a></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endforeach; ?>
    <p class="see-also">Looking for something else? <a href="<?= e(url('/shop')) ?>">Shop everything</a> or read our <a href="<?= e(url('/guides')) ?>">guides</a>.</p>
</div>
