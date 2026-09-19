<?php
/**
 * Compact New/Used + what is included + photo indicator for list rows.
 * @var array $tagRow row with item_condition, includes_box, includes_cover_art, includes_manual
 * @var array $tagKinds photo kinds present for that product
 */
use App\Support\ProductPhotos;

$cond    = (string) ($tagRow['item_condition'] ?? '');
$isNew   = $cond === 'New';
$have    = array_values(array_intersect(array_keys(ProductPhotos::LABELS), $tagKinds));
$missing = array_values(array_diff(array_keys(ProductPhotos::LABELS), $tagKinds));
$inc     = ['includes_box' => 'Box', 'includes_cover_art' => 'Cover art', 'includes_manual' => 'Manual'];
?>
<div class="lc-tags">
    <span class="lc-tag <?= $isNew ? 'lc-tag-new' : 'lc-tag-used' ?>"><?= $isNew ? 'New (sealed)' : 'Used: ' . e($cond) ?></span>
    <?php if (!$isNew): ?>
        <?php foreach ($inc as $col => $label): $v = $tagRow[$col] ?? null; if ($v === null) { continue; } ?>
            <span class="lc-tag lc-tag-inc <?= (int) $v === 1 ? 'lc-yes' : 'lc-no' ?>" title="<?= e($label) ?>: <?= (int) $v === 1 ? 'included' : 'not included' ?>"><?= (int) $v === 1 ? '&#10003;' : '&#10007;' ?> <?= e($label) ?></span>
        <?php endforeach; ?>
        <?php if (!$missing): ?>
            <span class="lc-tag lc-tag-ok">Photos: <?= count($have) ?>/3</span>
        <?php else: ?>
            <span class="lc-tag lc-tag-warn">Photos: <?= count($have) ?>/3 &middot; missing: <?= e(implode(', ', array_map(static fn (string $k): string => strtolower(str_replace('_', ' ', $k)), $missing))) ?></span>
        <?php endif; ?>
    <?php elseif ($tagKinds): ?>
        <span class="lc-tag lc-tag-ok">Photos: <?= count($tagKinds) ?></span>
    <?php endif; ?>
</div>
