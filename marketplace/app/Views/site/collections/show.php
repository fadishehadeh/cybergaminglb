<?php
use App\Modules\Storefront\Collections;
use App\Modules\Storefront\Guides;
use App\Modules\Storefront\Seo;
use App\Modules\Storefront\Ui;

/** @var array $def @var array $stats @var string $intro @var array $crumbs @var string $base @var array $related @var array $guides */
echo Ui::partial('page-head', ['crumbs' => $crumbs, 'h1' => (string) $def['h1'], 'lead' => '']);
echo Seo::render('quick-answer', ['text' => $intro]);
?>
<div class="container collection-page">
    <?php if ($stats['items']): ?>
    <p class="collection-count" aria-live="polite">Showing <?= count($stats['items']) ?> of <?= (int) $stats['n'] ?> <?= e($def['what']) ?></p>
    <div class="product-grid">
        <?php foreach ($stats['items'] as $p): ?><?= Ui::partial('product-card', ['p' => $p, 'level' => 'h2']) ?><?php endforeach; ?>
    </div>
    <?= Ui::pagination($base, [], $stats['page'], $stats['pages']) ?>
    <?php else: ?>
    <p class="empty-note">Nothing in this collection right now. <a href="<?= e(url('/shop')) ?>">Browse the whole shop</a> or see the <a href="<?= e(url('/collections')) ?>">other collections</a>.</p>
    <?php endif; ?>

    <?php if ($related): ?>
    <section class="collection-related" aria-labelledby="rel-c-h">
        <h2 id="rel-c-h">Related collections</h2>
        <ul class="chip-list">
            <?php foreach ($related as $r): ?><li><a href="<?= e(url('/collections/' . $r['slug'])) ?>"><?= e($r['title']) ?> <span><?= (int) $r['n'] ?></span></a></li><?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <?php if ($guides): ?>
    <section class="collection-guides" aria-labelledby="rel-g-h">
        <h2 id="rel-g-h">Guides that help you choose</h2>
        <ul class="guide-grid">
            <?php foreach ($guides as $g): ?>
                <li class="guide-card">
                    <p class="guide-tag"><?= e(Guides::tagLabel($g['tag'])) ?></p>
                    <h3><a href="<?= e(url('/guides/' . $g['slug'])) ?>"><?= e(Guides::text($g['title'])) ?></a></h3>
                    <p class="guide-excerpt"><?= e(Guides::text($g['excerpt'])) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <p class="see-also">Want to sell instead? Get an <a href="<?= e(url('/sell')) ?>">instant quote</a> or <a href="<?= e(url('/trade')) ?>">trade in for store credit</a>. See <a href="<?= e(url('/delivery-and-payment')) ?>">delivery and payment</a> for fees by area.</p>
</div>
