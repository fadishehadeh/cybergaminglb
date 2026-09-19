<?php
/**
 * Up to 8 rows of (platform, title, condition, box / cover art / manual ticks). The first three are always shown; the rest sit in a <details>
 * that works without JavaScript and opens by itself when it contains input.
 *
 * @var array $rows @var array $platforms @var string $listId datalist id
 */
$visible = 3;
$openMore = false;
foreach (array_slice($rows, $visible) as $r) {
    if ($r['title'] !== '') {
        $openMore = true;
    }
}
$renderRow = static function (int $i, array $r) use ($platforms, $listId): void { ?>
    <fieldset class="game-row">
        <legend>Game <?= $i + 1 ?></legend>
        <div class="form-row row-title">
            <label for="gt-<?= $i ?>">Game title</label>
            <input id="gt-<?= $i ?>" name="game_title[]" type="text" list="<?= e($listId) ?>" maxlength="120" autocomplete="off" placeholder="e.g. Red Dead Redemption 2" value="<?= e($r['title']) ?>"<?= $i === 0 ? ' required' : '' ?>>
        </div>
        <div class="form-row">
            <label for="gp-<?= $i ?>">Platform</label>
            <select id="gp-<?= $i ?>" name="game_platform[]"<?= $i === 0 ? ' required' : '' ?>>
                <option value="">Select…</option>
                <?php foreach ($platforms as $pl): ?><option value="<?= e($pl['slug']) ?>"<?= $r['platform'] === $pl['slug'] ? ' selected' : '' ?>><?= e($pl['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="gc-<?= $i ?>">Condition</label>
            <select id="gc-<?= $i ?>" name="game_condition[]">
                <?php foreach (\App\Modules\Storefront\Quoter::CONDITIONS as $c): ?><option value="<?= e($c) ?>"<?= $r['condition'] === $c ? ' selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="row-includes" role="group" aria-label="Comes with game <?= $i + 1 ?>">
            <span class="incl-legend">Comes with:</span>
            <label class="check"><input type="checkbox" name="game_box[<?= $i ?>]" value="1"<?= !empty($r['box']) ? ' checked' : '' ?>> Original box</label>
            <label class="check"><input type="checkbox" name="game_cover[<?= $i ?>]" value="1"<?= !empty($r['cover']) ? ' checked' : '' ?>> Cover art</label>
            <label class="check"><input type="checkbox" name="game_manual[<?= $i ?>]" value="1"<?= !empty($r['manual']) ? ' checked' : '' ?>> Manual</label>
        </div>
    </fieldset>
<?php };
foreach (array_slice($rows, 0, $visible, true) as $i => $r) { $renderRow($i, $r); } ?>
<details class="more-rows"<?= $openMore ? ' open' : '' ?>>
    <summary>Add more games (up to <?= count($rows) ?> in total)</summary>
    <?php foreach (array_slice($rows, $visible, null, true) as $i => $r) { $renderRow($i, $r); } ?>
</details>
