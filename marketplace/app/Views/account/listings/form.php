<?php
use App\Modules\Account\ListingBase;
use App\Modules\Account\ListingController;
use App\Modules\Admin\Forms;
use App\Modules\Seller\ImageUpload;
use App\Support\Pricing;

/** @var array $seller @var array|null $product @var float $commission @var array $categories @var array $platforms @var bool $hasOrders @var array $photos @var array $errors */
$isEdit  = $product !== null;
$boxPath = null; // the box-outside photo doubles as the shop picture when there is no separate cover photo
foreach ($photos as $ph) {
    if ($ph['kind'] === 'box_outside') {
        $boxPath = $ph['path'];
    }
}
$meta    = ['title' => ($isEdit ? 'Edit listing' : 'New listing') . ' | CyberGaming', 'noindex' => true];
$accountNav = 'listings';
$action  = $isEdit ? '/account/listings/' . $product['id'] . '/edit' : '/account/listings/new';
$p       = $product ?? [];
$pct     = (float) $commission;
$pctText = ListingBase::pct($pct);

$f = static fn (string $key, mixed $default = '') => Forms::val($key, $p[$key] ?? $default);

$curPrice = (string) Forms::val('seller_price', isset($p['seller_price']) ? rtrim(rtrim((string) $p['seller_price'], '0'), '.') : '');
$priceNum = is_numeric(str_replace(',', '.', $curPrice)) ? (float) str_replace(',', '.', $curPrice) : 0.0;
$buyer    = $priceNum > 0 ? Pricing::buyerPrice($priceNum, $pct) : 0.0;

[$statusLabel, $statusMod] = $isEdit ? (ListingController::STATUS_LABEL[$p['status']] ?? [$p['status'], 'hidden']) : ['', ''];
?>
<?php require base_path('app/Views/account/_nav.php'); ?>
<link rel="stylesheet" href="<?= e(asset('css/listings.css')) ?>">
<div class="container ls-page">

    <div class="ls-head">
        <div>
            <h1><?= $isEdit ? 'Edit listing' : 'New listing' ?></h1>
            <?php if ($isEdit): ?><p class="ls-muted"><?= e($p['title']) ?> &middot; <span class="ls-pill ls-pill-<?= e($statusMod) ?>"><?= e($statusLabel) ?></span></p><?php endif; ?>
        </div>
        <a class="ls-btn" href="<?= e(url('/account/listings')) ?>">&larr; My listings</a>
    </div>

    <?php if ($errors): ?>
        <div class="ls-alert ls-alert-error" role="alert">
            <strong>Please fix the following:</strong>
            <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <?php if ($isEdit && $p['status'] === 'active'): ?>
        <div class="ls-notice">This listing is live. If you change the title, description, price, condition, what is included, any photo, other details, or increase the stock, it goes back to <strong>waiting for approval</strong> and is off the shop until we approve it.</div>
    <?php elseif (!$isEdit): ?>
        <div class="ls-notice">New listings are checked by our team before they appear in the shop. Please do not put phone numbers, emails, links or social handles in the text: CyberGaming is the only contact with buyers.</div>
    <?php endif; ?>

    <form method="post" action="<?= e(url($action)) ?>" enctype="multipart/form-data" class="ls-form" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="_form" value="1">

        <div class="ls-cols">
            <section class="ls-card">
                <h2>Details</h2>
                <div class="ls-fields">
                    <div class="ls-field ls-span-2">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" value="<?= e($f('title')) ?>" maxlength="190" required placeholder="e.g. God of War (2018)">
                    </div>
                    <div class="ls-field">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Choose...</option>
                            <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (string) $f('category_id') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ls-field">
                        <label for="platform_id">Platform</label>
                        <select id="platform_id" name="platform_id">
                            <option value="">Not platform specific</option>
                            <?php foreach ($platforms as $pl): ?><option value="<?= (int) $pl['id'] ?>" <?= (string) $f('platform_id') === (string) $pl['id'] ? 'selected' : '' ?>><?= e($pl['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <?php $px = 'ls'; require __DIR__ . '/_condition.php'; ?>
                    <div class="ls-field">
                        <label for="edition">Edition</label>
                        <input type="text" id="edition" name="edition" value="<?= e($f('edition', 'Standard')) ?>" maxlength="30" placeholder="Standard, Deluxe, GOTY...">
                    </div>
                    <div class="ls-field">
                        <label for="year">Release year</label>
                        <input type="number" id="year" name="year" value="<?= e($f('year')) ?>" min="1970" max="<?= (int) date('Y') + 1 ?>" placeholder="2018">
                    </div>
                    <div class="ls-field ls-field-check">
                        <label class="ls-check"><input type="checkbox" name="is_steelbook" value="1" <?= Forms::checked('is_steelbook', !empty($p['is_steelbook'])) ? 'checked' : '' ?>> <span>Steelbook edition</span></label>
                    </div>
                    <div class="ls-field ls-span-2">
                        <label for="genres">Genres</label>
                        <input type="text" id="genres" name="genres" value="<?= e($f('genres')) ?>" maxlength="255" placeholder="Action, RPG, Open World">
                        <small>Comma separated.</small>
                    </div>
                    <div class="ls-field ls-span-2">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="5" maxlength="3000" placeholder="Anything else a buyer should know (region, DLC codes, small marks)..."><?= e($f('description')) ?></textarea>
                        <small>Describe the item only. No phone numbers, emails, links or social handles.</small>
                    </div>
                </div>
            </section>

            <div class="ls-stack">
                <section class="ls-card" data-price-preview data-pct="<?= e((string) $pct) ?>">
                    <h2>Price &amp; stock</h2>
                    <div class="ls-fields ls-fields-one">
                        <div class="ls-field">
                            <label for="seller_price">Price you receive ($) *</label>
                            <input type="text" inputmode="decimal" id="seller_price" name="seller_price" value="<?= e($curPrice) ?>" required data-pp-price placeholder="20" autocomplete="off">
                            <small>This is exactly what you get after the item is sold and delivered (minimum $1).</small>
                        </div>
                        <div class="ls-preview" aria-live="polite">
                            <span data-pp-text>
                                <?php if ($priceNum > 0): ?>Buyers will pay <strong><?= e(money($buyer)) ?></strong> (your price + <?= e($pctText) ?> commission, rounded up to $0.50, delivery is paid by the buyer)<?php else: ?>Enter your price to see what buyers will pay (your price + <?= e($pctText) ?> commission, rounded up to $0.50, delivery is paid by the buyer).<?php endif; ?>
                            </span>
                        </div>
                        <div class="ls-field">
                            <label for="stock">Stock *</label>
                            <input type="number" id="stock" name="stock" value="<?= e($f('stock', '1')) ?>" min="<?= $isEdit ? 0 : 1 ?>" max="99" required>
                            <small>How many copies you have.</small>
                        </div>
                    </div>
                </section>

                <section class="ls-card">
                    <h2>Shop picture <small>(optional)</small></h2>
                    <?php $hasOwnImage = $isEdit && $p['image'] && $p['image'] !== $boxPath; ?>
                    <?php if ($hasOwnImage): ?>
                        <img class="ls-current-img" src="<?= e(media($p['image'])) ?>" alt="Current shop picture" width="160">
                        <label class="ls-check"><input type="checkbox" name="remove_image" value="1"> <span>Remove current picture</span></label>
                    <?php endif; ?>
                    <div class="ls-field">
                        <label for="image"><?= $hasOwnImage ? 'Replace picture' : 'Upload a picture' ?></label>
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" data-max-bytes="<?= (int) ImageUpload::MAX_BYTES ?>">
                        <small>Only if you have a nicer picture for the shop card (JPG, PNG or WebP, up to 3 MB). Otherwise we use your photo of the box from the outside.</small>
                    </div>
                </section>
            </div>
        </div>

        <?php $px = 'ls'; require __DIR__ . '/_photos.php'; ?>

        <div class="ls-actions">
            <button type="submit" class="ls-btn ls-btn-primary ls-btn-lg"><?= $isEdit ? 'Save changes' : 'Submit for approval' ?></button>
            <a class="ls-btn ls-btn-lg" href="<?= e(url('/account/listings')) ?>">Cancel</a>
        </div>
    </form>

    <?php if ($isEdit && $hasOrders): ?>
        <p class="ls-muted">This item has been ordered before, so it cannot be deleted. You can withdraw it from the listings page instead.</p>
    <?php endif; ?>
</div>
<script src="<?= e(asset('js/listings.js')) ?>" defer></script>
