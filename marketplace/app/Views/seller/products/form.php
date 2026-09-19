<?php
use App\Modules\Admin\Forms;
use App\Support\Pricing;

/** @var array|null $product @var float $commission @var array $categories @var array $platforms @var bool $hasOrders @var array $photos */
$isEdit    = $product !== null;
$boxPath   = null; // the box-outside photo doubles as the shop picture when there is no separate cover photo
foreach ($photos as $ph) {
    if ($ph['kind'] === 'box_outside') {
        $boxPath = $ph['path'];
    }
}
$pageTitle = $isEdit ? 'Edit listing' : 'New listing';
$nav       = 'products';
$action    = $isEdit ? '/seller/products/' . $product['id'] . '/edit' : '/seller/products/new';
$p         = $product ?? [];
$pct       = (float) $commission;
$pctText   = rtrim(rtrim(number_format($pct, 2, '.', ''), '0'), '.');

$f = static fn (string $key, mixed $default = '') => Forms::val($key, $p[$key] ?? $default);

$curPrice = (string) Forms::val('seller_price', isset($p['seller_price']) ? rtrim(rtrim((string) $p['seller_price'], '0'), '.') : '');
$priceNum = is_numeric(str_replace(',', '.', $curPrice)) ? (float) str_replace(',', '.', $curPrice) : 0.0;
$buyer    = $priceNum > 0 ? Pricing::buyerPrice($priceNum, $pct) : 0.0;

$statusLabel = ['pending' => 'Waiting for approval', 'active' => 'Live', 'hidden' => 'Hidden', 'sold' => 'Sold out'];
?>
<div class="sp-head">
    <div>
        <h1><?= e($pageTitle) ?></h1>
        <?php if ($isEdit): ?><p class="sp-muted"><?= e($p['title']) ?> &middot; <span class="sp-pill sp-pill-<?= e($p['status']) ?>"><?= e($statusLabel[$p['status']] ?? $p['status']) ?></span></p><?php endif; ?>
    </div>
    <a class="sp-btn" href="<?= e(url('/seller/products')) ?>">&larr; My products</a>
</div>

<?php if ($isEdit && $p['status'] === 'active'): ?>
    <div class="sp-notice">This listing is live. If you change the title, description, price, condition, what is included, any photo or other details, it goes back to <strong>waiting for approval</strong> and is off the shop until we approve it. Changing only the stock does not.</div>
<?php elseif (!$isEdit): ?>
    <div class="sp-notice">New listings are checked by our team before they appear in the shop. Please do not put phone numbers, links or social handles in the text: CyberGaming is the only contact with buyers.</div>
<?php endif; ?>

<form method="post" action="<?= e(url($action)) ?>" enctype="multipart/form-data" class="sp-form" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="_form" value="1">

    <div class="sp-cols sp-cols-form">
        <section class="sp-card">
            <h2>Details</h2>
            <div class="sp-fields">
                <div class="sp-field sp-span-2">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" value="<?= e($f('title')) ?>" maxlength="190" required placeholder="e.g. God of War (2018)">
                </div>
                <div class="sp-field">
                    <label for="category_id">Category *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Choose...</option>
                        <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (string) $f('category_id') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="sp-field">
                    <label for="platform_id">Platform</label>
                    <select id="platform_id" name="platform_id">
                        <option value="">Not platform specific</option>
                        <?php foreach ($platforms as $pl): ?><option value="<?= (int) $pl['id'] ?>" <?= (string) $f('platform_id') === (string) $pl['id'] ? 'selected' : '' ?>><?= e($pl['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <?php $px = 'sp'; require base_path('app/Views/account/listings/_condition.php'); ?>
                <div class="sp-field">
                    <label for="edition">Edition</label>
                    <input type="text" id="edition" name="edition" value="<?= e($f('edition', 'Standard')) ?>" maxlength="30" placeholder="Standard, Deluxe, GOTY...">
                </div>
                <div class="sp-field">
                    <label for="year">Release year</label>
                    <input type="number" id="year" name="year" value="<?= e($f('year')) ?>" min="1970" max="<?= (int) date('Y') + 1 ?>" placeholder="2018">
                </div>
                <div class="sp-field sp-field-check">
                    <label class="sp-check"><input type="checkbox" name="is_steelbook" value="1" <?= Forms::checked('is_steelbook', !empty($p['is_steelbook'])) ? 'checked' : '' ?>> Steelbook edition</label>
                </div>
                <div class="sp-field sp-span-2">
                    <label for="genres">Genres</label>
                    <input type="text" id="genres" name="genres" value="<?= e($f('genres')) ?>" maxlength="255" placeholder="Action, RPG, Open World">
                    <small>Comma separated.</small>
                </div>
                <div class="sp-field sp-span-2">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" maxlength="3000" placeholder="Anything else a buyer should know (region, DLC codes, small marks)..."><?= e($f('description')) ?></textarea>
                    <small>Describe the item only. No phone numbers, emails, links or social handles.</small>
                </div>
            </div>
        </section>

        <div class="sp-stack">
            <section class="sp-card" data-price-preview data-pct="<?= e((string) $pct) ?>">
                <h2>Price &amp; stock</h2>
                <div class="sp-fields sp-fields-one">
                    <div class="sp-field">
                        <label for="seller_price">Price you receive ($) *</label>
                        <input type="text" inputmode="decimal" id="seller_price" name="seller_price" value="<?= e($curPrice) ?>" required data-pp-price placeholder="20">
                        <small>This is exactly what we pay you when the item is sold and delivered.</small>
                    </div>
                    <div class="sp-preview" aria-live="polite">
                        <span data-pp-text>
                            <?php if ($priceNum > 0): ?>Buyers will see <strong><?= e(money($buyer)) ?></strong> (your price + <?= e($pctText) ?>% commission, rounded up to $0.50)<?php else: ?>Enter your price to see what buyers will pay (your price + <?= e($pctText) ?>% commission, rounded up to $0.50).<?php endif; ?>
                        </span>
                    </div>
                    <div class="sp-field">
                        <label for="stock">Stock *</label>
                        <input type="number" id="stock" name="stock" value="<?= e($f('stock', '1')) ?>" min="<?= $isEdit ? 0 : 1 ?>" max="99" required>
                        <small>How many copies you have.</small>
                    </div>
                </div>
            </section>

            <section class="sp-card">
                <h2>Shop picture <small class="sp-muted">(optional)</small></h2>
                <?php $hasOwnImage = $isEdit && $p['image'] && $p['image'] !== $boxPath; ?>
                <?php if ($hasOwnImage): ?>
                    <img class="sp-current-img" src="<?= e(media($p['image'])) ?>" alt="Current shop picture" width="160">
                    <label class="sp-check"><input type="checkbox" name="remove_image" value="1"> Remove current picture</label>
                <?php endif; ?>
                <div class="sp-field">
                    <label for="image"><?= $hasOwnImage ? 'Replace picture' : 'Upload a picture' ?></label>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" data-max-bytes="<?= (int) \App\Modules\Seller\ImageUpload::MAX_BYTES ?>">
                    <small>Only if you have a nicer picture for the shop card (JPG, PNG or WebP, up to 3 MB). Otherwise we use your photo of the box from the outside.</small>
                </div>
            </section>
        </div>
    </div>

    <?php $px = 'sp'; require base_path('app/Views/account/listings/_photos.php'); ?>

    <div class="sp-actions">
        <button type="submit" class="sp-btn sp-btn-primary sp-btn-lg"><?= $isEdit ? 'Save changes' : 'Submit for approval' ?></button>
        <a class="sp-btn sp-btn-lg" href="<?= e(url('/seller/products')) ?>">Cancel</a>
    </div>
</form>

<?php if ($isEdit && $hasOrders): ?>
    <p class="sp-muted">This item has been ordered before, so it cannot be deleted. You can withdraw it from the products list instead.</p>
<?php endif; ?>
