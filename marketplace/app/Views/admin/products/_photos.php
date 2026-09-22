<?php
/**
 * Photo slots of the admin product form: disc, box outside, box inside (+ up to 3 extras).
 * @var array $photos rows from ProductPhotos::forProduct()  @var bool $isEdit  @var array $p  @var array $missing kinds still missing (edit only)
 * @var bool $alreadyLive is this a used listing that is already live (old stock)  @var bool $isDigital
 */
use App\Modules\Admin\Forms;
use App\Modules\Admin\ListingRules;
use App\Modules\Seller\ListingCondition;
use App\Support\ProductPhotos;

$byKind = [];
$extras = [];
foreach ($photos as $ph) {
    if ($ph['kind'] === 'extra') {
        $extras[] = $ph;
    } else {
        $byKind[$ph['kind']] = $ph;
    }
}
$isUsed = (string) Forms::val('condition_type', $isEdit ? (($p['item_condition'] ?? '') === 'New' ? 'new' : 'used') : '') === 'used';
?>
<section class="card photos-card" data-kind="game" <?= ($mode ?? 'game') === 'game' ? '' : 'hidden' ?>>
    <div class="card-head"><h2>Photos</h2></div>
    <div class="card-body">
        <p class="hint ph-intro">A <strong>used</strong> game needs the three photos below before it can go live (you can still save it as Hidden or Pending without them).
            For <strong>new sealed</strong> games photos are optional. Photos are compressed and location data is removed. JPG, PNG or WebP, up to 10 MB each, at least 400 px.</p>

        <?php if ($isEdit && $missing && ListingRules::typeOf($p) !== 'hardware'): ?>
            <div class="alert alert-warn" data-used-only <?= $isUsed ? '' : 'hidden' ?>>
                <strong>Missing photos:</strong> <?= e(ListingRules::kindList($missing)) ?>.
                <?= $alreadyLive ? 'This used game is live without them (old stock). Add them when you can.' : 'This used game cannot go live until they are added.' ?>
                <?php if (!$alreadyLive): ?>
                    <label class="check legacy-check"><input type="checkbox" name="legacy_ok" value="1" <?= Forms::checked('legacy_ok', false) ? 'checked' : '' ?>> Publish without photos (legacy stock)</label>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="ph-grid">
            <?php foreach (ProductPhotos::LABELS as $kind => $label): $cur = $byKind[$kind] ?? null; ?>
                <div class="ph-slot<?= $cur ? ' ph-have' : '' ?>">
                    <label for="photo_<?= e($kind) ?>"><?= e($label) ?> <span class="tag tag-photos-low" data-used-only <?= $isUsed ? '' : 'hidden' ?>>Required for used</span></label>
                    <small class="hint"><?= e(ListingCondition::PHOTO_HINTS[$kind]) ?></small>
                    <?php if ($cur): ?>
                        <a href="<?= e(media($cur['path'])) ?>" target="_blank" rel="noopener" title="Open the full photo"><img class="ph-thumb" src="<?= e(media($cur['path'])) ?>" alt="Current photo: <?= e($label) ?>" width="120" height="120" loading="lazy"></a>
                        <small class="hint">Saved. Choose a file only to replace it.</small>
                    <?php else: ?>
                        <span class="ph-empty" aria-hidden="true">No photo yet</span>
                    <?php endif; ?>
                    <img class="ph-preview" alt="" width="120" height="120" hidden data-photo-preview>
                    <input type="file" id="photo_<?= e($kind) ?>" name="photo_<?= e($kind) ?>" accept="image/jpeg,image/png,image/webp" data-photo data-max-bytes="10485760">
                    <small class="hint" data-photo-note></small>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="ph-slot ph-extra">
            <label for="photo_extra">More photos <span class="tag">Optional, up to <?= (int) ListingCondition::MAX_EXTRA ?></span></label>
            <small class="hint">Scratches, the back cover, extras that come with it.</small>
            <?php if ($extras): ?>
                <ul class="ph-extras">
                    <?php foreach ($extras as $ex): ?>
                        <li>
                            <a href="<?= e(media($ex['path'])) ?>" target="_blank" rel="noopener"><img class="ph-thumb" src="<?= e(media($ex['path'])) ?>" alt="Extra photo" width="96" height="96" loading="lazy"></a>
                            <label class="check"><input type="checkbox" name="delete_extra[]" value="<?= (int) $ex['id'] ?>"> Delete</label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <input type="file" id="photo_extra" name="photo_extra[]" accept="image/jpeg,image/png,image/webp" multiple data-photo data-max-files="<?= (int) ListingCondition::MAX_EXTRA ?>" data-max-bytes="10485760">
            <small class="hint" data-photo-note></small>
        </div>
    </div>
</section>
