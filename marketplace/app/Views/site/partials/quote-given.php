<?php
/** Itemised "games you give" list, cash and credit side by side. @var array $given @var string $mode sell|trade */
?>
<ul class="quote-lines">
    <?php foreach ($given['lines'] as $l): ?>
        <li class="quote-line<?= $l['matched'] ? '' : ' is-unmatched' ?>">
            <span class="ql-main">
                <strong><?= e($l['title']) ?></strong>
                <small><?= e($l['platform']) ?> &middot; <?= e($l['condition']) ?></small>
                <small class="ql-incl">With: <?= e(\App\Modules\Storefront\Quoter::includesText($l)) ?></small>
                <?php if ($l['matched'] && mb_strtolower($l['matched_title']) !== mb_strtolower(\App\Modules\Storefront\Quoter::baseTitle($l['title']))): ?>
                    <small class="ql-match">Priced as: <?= e($l['matched_title']) ?></small>
                <?php endif; ?>
            </span>
            <?php if ($l['matched']): ?>
                <span class="ql-amounts">
                    <span class="ql-cash"><small>Cash</small> <?= e(money($l['cash'])) ?></span>
                    <span class="ql-credit"><small>Credit</small> <?= e(money($l['credit'])) ?></span>
                </span>
            <?php else: ?>
                <span class="ql-amount ql-wa">We'll price this one for you</span>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
