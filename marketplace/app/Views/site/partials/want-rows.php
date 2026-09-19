<?php
/**
 * "What you want from our shop" rows (up to 6). @var array $wantRows @var string $listId
 */
$visible = 2;
$openMore = false;
foreach (array_slice($wantRows, $visible) as $r) {
    if ($r['title'] !== '') {
        $openMore = true;
    }
}
$renderWant = static function (int $i, array $r) use ($listId): void { ?>
    <div class="form-row want-row">
        <label for="wt-<?= $i ?>">Item <?= $i + 1 ?><?= $i === 0 ? ' <span class="opt">(optional)</span>' : '' ?></label>
        <input id="wt-<?= $i ?>" name="want_title[]" type="text" list="<?= e($listId) ?>" maxlength="190" autocomplete="off" placeholder="Start typing a game in our shop" value="<?= e($r['title']) ?>">
        <input type="hidden" name="want_slug[]" value="<?= e($r['slug']) ?>">
    </div>
<?php };
foreach (array_slice($wantRows, 0, $visible, true) as $i => $r) { $renderWant($i, $r); } ?>
<details class="more-rows"<?= $openMore ? ' open' : '' ?>>
    <summary>Add more items (up to <?= count($wantRows) ?> in total)</summary>
    <?php foreach (array_slice($wantRows, $visible, null, true) as $i => $r) { $renderWant($i, $r); } ?>
</details>
