<?php
/** Re-posts the customer's rows (never prices) with the details form. @var array $given @var ?array $wanted */
foreach (array_values($given['lines']) as $i => $l):
    $inc = \App\Modules\Storefront\Quoter::includesOf($l); ?>
    <input type="hidden" name="game_platform[]" value="<?= e($l['platform_slug']) ?>">
    <input type="hidden" name="game_title[]" value="<?= e($l['title']) ?>">
    <input type="hidden" name="game_condition[]" value="<?= e($l['condition']) ?>">
    <?php if ($inc['box']): ?><input type="hidden" name="game_box[<?= $i ?>]" value="1"><?php endif; ?>
    <?php if ($inc['cover']): ?><input type="hidden" name="game_cover[<?= $i ?>]" value="1"><?php endif; ?>
    <?php if ($inc['manual']): ?><input type="hidden" name="game_manual[<?= $i ?>]" value="1"><?php endif; ?>
<?php endforeach;
foreach (($wanted['lines'] ?? []) as $l): ?>
    <input type="hidden" name="want_title[]" value="<?= e($l['matched'] ? $l['title'] : $l['typed']) ?>">
    <input type="hidden" name="want_slug[]" value="<?= e($l['slug']) ?>">
<?php endforeach; ?>
