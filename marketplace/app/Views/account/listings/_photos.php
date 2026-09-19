<?php
/**
 * The three photos of a used copy (+ up to 3 extras). Shared by the member listing form and the store-seller product form.
 * @var string $px CSS prefix ('ls' or 'sp')  @var array $photos rows from ProductPhotos::forProduct()  @var array $p product row (or [])
 */
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
?>
<section class="<?= e($px) ?>-card lc lc-photos" data-photos>
    <h2>Photos</h2>
    <p class="lc-why">Buyers trust listings with clear photos, and our team inspects every used copy before delivery.
        <strong>Used games need the three photos below. For new sealed games photos are optional (one or two of the sealed box is fine).</strong></p>
    <p class="lc-privacy">Photos are compressed and location data is removed automatically. JPG, PNG or WebP, up to 10 MB each.</p>

    <div class="lc-photo-grid">
        <?php foreach (ProductPhotos::LABELS as $kind => $label): $cur = $byKind[$kind] ?? null; ?>
            <div class="lc-photo<?= $cur ? ' lc-photo-have' : '' ?>">
                <label for="photo_<?= e($kind) ?>"><?= e($label) ?> <span class="lc-badge" data-req-badge>Required for used</span></label>
                <span class="lc-photo-hint"><?= e(ListingCondition::PHOTO_HINTS[$kind]) ?></span>
                <?php if ($cur): ?>
                    <img class="lc-thumb" src="<?= e(media($cur['path'])) ?>" alt="Current photo: <?= e($label) ?>" width="120" height="120" loading="lazy">
                    <small class="lc-have">Photo saved. Choose a file only if you want to replace it.</small>
                <?php endif; ?>
                <img class="lc-preview" alt="" width="120" height="120" hidden data-photo-preview>
                <input type="file" id="photo_<?= e($kind) ?>" name="photo_<?= e($kind) ?>" accept="image/jpeg,image/png,image/webp" data-photo data-max-bytes="10485760">
                <small class="lc-file" data-photo-name></small>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="lc-photo lc-photo-extra">
        <label for="photo_extra">More photos <span class="lc-badge lc-badge-opt">Optional, up to <?= (int) ListingCondition::MAX_EXTRA ?></span></label>
        <span class="lc-photo-hint">Scratches, the back cover, extras that come with it: anything a buyer would want to see.</span>
        <?php if ($extras): ?>
            <ul class="lc-extras">
                <?php foreach ($extras as $ex): ?>
                    <li>
                        <img class="lc-thumb" src="<?= e(media($ex['path'])) ?>" alt="Extra photo" width="96" height="96" loading="lazy">
                        <label class="lc-del"><input type="checkbox" name="delete_extra[]" value="<?= (int) $ex['id'] ?>"> <span>Delete</span></label>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <input type="file" id="photo_extra" name="photo_extra[]" accept="image/jpeg,image/png,image/webp" multiple data-photo data-max-files="<?= (int) ListingCondition::MAX_EXTRA ?>" data-max-bytes="10485760">
        <small class="lc-file" data-photo-name></small>
    </div>
</section>
