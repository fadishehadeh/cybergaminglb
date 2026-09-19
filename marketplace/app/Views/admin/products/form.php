<?php
use App\Modules\Admin\Digital;
use App\Modules\Admin\Forms;
use App\Modules\Admin\ListingRules;

$isEdit    = $product !== null;
$pageTitle = $isEdit ? 'Edit product' : 'Add product';
$nav       = 'products';
$action    = $isEdit ? '/admin/products/' . $product['id'] . '/edit' : '/admin/products/create';
$p         = $product ?? [];
$defaultPct = (float) setting('commission_pct', 15);

$f = static fn (string $key, mixed $default = '') => Forms::val($key, $p[$key] ?? $default);

// Digital item state (server-rendered so the form is right before the script runs)
$isDigital = Forms::checked('is_digital', !empty($p['is_digital']));
if (old('_form')) {
    $regionSel   = (string) old('digital_region', '');
    $regionOther = (string) old('digital_region_other', '');
} else {
    [$regionSel, $regionOther] = Digital::regionChoice($p['digital_region'] ?? '');
}
$digitalKind = (string) Forms::val('digital_kind', $p['digital_kind'] ?? '');

$curSeller = (string) Forms::val('seller_id', isset($p['seller_id']) && $p['seller_id'] !== null ? (string) $p['seller_id'] : ($isEdit ? '' : ($preselect ? (string) $preselect : '')));
$curPrice  = Forms::val('seller_price', isset($p['seller_price']) ? (string) $p['seller_price'] : '');
if ($isEdit && !old('_form') && $curSeller === '') {
    $curPrice = (string) $p['price'];
}

// Initial server-side preview (JS keeps it live)
$pct = 0.0;
foreach ($sellers as $s) {
    if ((string) $s['id'] === $curSeller) {
        $pct = (float) $s['pct'];
    }
}
$priceNum   = is_numeric(str_replace(',', '.', (string) $curPrice)) ? (float) str_replace(',', '.', (string) $curPrice) : 0.0;
$previewBuy = $priceNum > 0 ? \App\Support\Pricing::buyerPrice($priceNum, $pct) : 0.0;
?>
<div class="page-head">
    <div>
        <h1><?= e($pageTitle) ?></h1>
        <?php if ($isEdit): ?><p class="muted"><?= e($p['title']) ?> &middot; <?= Forms::pill($p['status']) ?></p><?php endif; ?>
    </div>
    <div class="actions"><a class="btn btn-ghost" href="<?= e(url('/admin/products')) ?>">&larr; All products</a></div>
</div>

<?php if ($isEdit && $p['seller_id'] && (\App\Support\ContactFilter::containsContact((string) $p['title']) || \App\Support\ContactFilter::containsContact((string) ($p['description'] ?? '')))): ?>
    <div class="alert alert-warn"><strong>Possible contact details in this seller listing.</strong> The title or description looks like it contains a phone number, link, email or social handle. Sellers must not take buyers off-platform: review and edit the text before approving.</div>
<?php endif; ?>

<?php if ($isEdit && $p['status'] === 'pending'): ?>
    <div class="alert alert-warn"><strong>Waiting for approval.</strong> <a href="<?= e(url('/admin/products/' . $p['id'] . '/review')) ?>">Open the review page</a> to check the condition, included items and photos before you approve it.</div>
<?php endif; ?>

<noscript><style>[data-digital-only][hidden], [data-physical-only][hidden] { display: block !important; }</style></noscript>
<form method="post" action="<?= e(url($action)) ?>" enctype="multipart/form-data" class="product-form">
    <?= csrf_field() ?>
    <input type="hidden" name="_form" value="1">

    <section class="card digital-item-card">
        <div class="card-body">
            <label class="check check-lg"><input type="checkbox" name="is_digital" value="1" data-digital-toggle <?= $isDigital ? 'checked' : '' ?>> Digital item (gift card / Steam gift)</label>
            <small class="hint">Sold from house stock, paid in advance by OMT/Whish; the code is sent on WhatsApp after payment. Condition is always New; steelbook, year, genres and edition are not used.
                <?= digital_enabled() ? '' : ' <strong>The digital goods switch is OFF, so digital items stay hidden on the website until you turn it on in Settings.</strong>' ?></small>
            <div class="form-grid digital-fields" data-digital-only <?= $isDigital ? '' : 'hidden' ?>>
                <div class="field">
                    <label for="digital_kind">Kind *</label>
                    <select id="digital_kind" name="digital_kind">
                        <option value="">Choose...</option>
                        <?= Forms::options(Digital::KINDS, $digitalKind) ?>
                    </select>
                </div>
                <div class="field">
                    <label for="digital_region">Region *</label>
                    <select id="digital_region" name="digital_region" data-region-select>
                        <option value="">Choose...</option>
                        <?php foreach ([...Digital::REGIONS, 'Other'] as $r): ?><option value="<?= e($r) ?>" <?= $regionSel === $r ? 'selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="field span-2" data-region-other <?= $regionSel === 'Other' ? '' : 'hidden' ?>>
                    <label for="digital_region_other">Other region</label>
                    <input type="text" id="digital_region_other" name="digital_region_other" value="<?= e($regionOther) ?>" maxlength="40" placeholder="e.g. Brazil, Japan">
                </div>
            </div>
        </div>
    </section>

    <div class="cols-form">
        <section class="card">
            <div class="card-head"><h2>Details</h2></div>
            <div class="card-body form-grid">
                <div class="field span-2">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" value="<?= e($f('title')) ?>" maxlength="190" required>
                </div>
                <div class="field">
                    <label for="category_id">Category *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Choose...</option>
                        <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>" data-slug="<?= e($c['slug']) ?>" <?= (string) $f('category_id') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="platform_id">Platform</label>
                    <select id="platform_id" name="platform_id">
                        <option value="">None / not platform specific</option>
                        <?php foreach ($platforms as $pl): ?><option value="<?= (int) $pl['id'] ?>" <?= (string) $f('platform_id') === (string) $pl['id'] ? 'selected' : '' ?>><?= e($pl['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <?php require __DIR__ . '/_condition.php'; ?>
                <div class="field" data-physical-only <?= $isDigital ? 'hidden' : '' ?>>
                    <label for="edition">Edition</label>
                    <input type="text" id="edition" name="edition" value="<?= e($f('edition', 'Standard')) ?>" maxlength="30" placeholder="Standard, Deluxe, GOTY...">
                </div>
                <div class="field" data-physical-only <?= $isDigital ? 'hidden' : '' ?>>
                    <label for="year">Release year</label>
                    <input type="number" id="year" name="year" value="<?= e($f('year')) ?>" min="1970" max="<?= (int) date('Y') + 1 ?>" placeholder="2019">
                </div>
                <div class="field field-check" data-physical-only <?= $isDigital ? 'hidden' : '' ?>>
                    <label class="check"><input type="checkbox" name="is_steelbook" value="1" <?= Forms::checked('is_steelbook', !empty($p['is_steelbook'])) ? 'checked' : '' ?>> Steelbook edition</label>
                </div>
                <div class="field span-2" data-physical-only <?= $isDigital ? 'hidden' : '' ?>>
                    <label for="genres">Genres</label>
                    <input type="text" id="genres" name="genres" value="<?= e($f('genres')) ?>" maxlength="255" placeholder="Action, RPG, Open World">
                    <small class="hint">Comma separated.</small>
                </div>
                <div class="field span-2" data-digital-only <?= $isDigital ? '' : 'hidden' ?>>
                    <small class="hint">Digital items are always <strong>New</strong>, edition Standard, no steelbook, year or genres. Platform is optional (leave it empty for cards that work on several consoles).</small>
                </div>
                <div class="field span-2">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" maxlength="5000"><?= e($f('description')) ?></textarea>
                </div>
            </div>
        </section>

        <div class="stack">
            <section class="card">
                <div class="card-head"><h2>Seller &amp; price</h2></div>
                <div class="card-body form-grid one" data-price-preview data-default-pct="<?= e((string) $defaultPct) ?>">
                    <div class="field">
                        <label for="seller_id">Seller</label>
                        <select id="seller_id" name="seller_id" data-pp-seller <?= $isDigital ? 'disabled' : '' ?>>
                            <option value="" data-pct="0" <?= $curSeller === '' ? 'selected' : '' ?>>House inventory (our own stock)</option>
                            <?php foreach ($sellers as $s): ?>
                                <option value="<?= (int) $s['id'] ?>" data-pct="<?= e((string) $s['pct']) ?>" <?= $curSeller === (string) $s['id'] ? 'selected' : '' ?>>
                                    <?= e($s['code']) ?> &middot; <?= e($s['name']) ?> (<?= e(Forms::pct($s['pct'])) ?>)<?= $s['status'] !== 'active' ? ' [' . e($s['status']) . ']' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <small class="hint" data-digital-only <?= $isDigital ? '' : 'hidden' ?>>Digital items are house stock only.</small>
                    <div class="field">
                        <label for="seller_price"><span data-pp-label><?= $curSeller === '' ? 'Price the buyer pays ($) *' : 'Seller price ($) *' ?></span></label>
                        <input type="text" inputmode="decimal" id="seller_price" name="seller_price" value="<?= e($curPrice) ?>" required data-pp-price placeholder="10.00">
                    </div>
                    <div class="preview" aria-live="polite">
                        <div class="preview-row"><span>Buyer pays</span><strong data-pp-buyer><?= $priceNum > 0 ? e(money($previewBuy)) : '-' ?></strong></div>
                        <div class="preview-row"><span>Seller receives</span><span data-pp-seller-gets><?= $priceNum > 0 ? e(money($curSeller === '' ? $previewBuy : $priceNum)) : '-' ?></span></div>
                        <div class="preview-row"><span>Our commission <small data-pp-pct>(<?= e(Forms::pct($pct)) ?>)</small></span><span data-pp-commission><?= $priceNum > 0 ? e(money($curSeller === '' ? 0 : $previewBuy - $priceNum)) : '-' ?></span></div>
                    </div>
                    <small class="hint">Buyer price = seller price + commission, rounded up to the next $0.50. House stock has no commission; the price is rounded up to $0.50.</small>
                </div>
            </section>

            <section class="card">
                <div class="card-head"><h2>Stock &amp; visibility</h2></div>
                <div class="card-body form-grid one">
                    <div class="field">
                        <label for="stock">Stock *</label>
                        <input type="number" id="stock" name="stock" value="<?= e($f('stock', $isDigital ? (string) Digital::DEFAULT_STOCK : '1')) ?>" min="0" max="9999" required>
                        <small class="hint" data-digital-only <?= $isDigital ? '' : 'hidden' ?>>Digital stock is managed manually (default <?= (int) Digital::DEFAULT_STOCK ?>). Cancelling an order never changes it.</small>
                    </div>
                    <div class="field">
                        <label for="status">Status *</label>
                        <select id="status" name="status"><?= Forms::options(['pending' => 'Pending (not visible)', 'active' => 'Active (live in shop)', 'sold' => 'Sold', 'hidden' => 'Hidden'], $f('status', $isEdit ? 'active' : 'active')) ?></select>
                        <small class="hint">Active products with 0 stock are saved as Sold.</small>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-head"><h2>Image</h2></div>
                <div class="card-body">
                    <?php if ($isEdit && $p['image']): ?>
                        <img class="image-current" src="<?= e(media($p['image'])) ?>" alt="Current image">
                        <label class="check"><input type="checkbox" name="remove_image" value="1"> Remove current image</label>
                    <?php endif; ?>
                    <div class="field">
                        <label for="image"><?= $isEdit && $p['image'] ? 'Replace image' : 'Upload image' ?></label>
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                        <small class="hint">JPG, PNG or WebP, up to 3 MB.<span data-digital-only <?= $isDigital ? '' : 'hidden' ?>> Optional for digital items.</span></small>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <?php require __DIR__ . '/_photos.php'; ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-lg"><?= $isEdit ? 'Save changes' : 'Create product' ?></button>
        <a class="btn btn-ghost btn-lg" href="<?= e(url('/admin/products')) ?>">Cancel</a>
    </div>
</form>

<?php if ($isEdit): ?>
    <section class="card danger-zone">
        <div class="card-head"><h2>Quick actions</h2></div>
        <div class="card-body actions">
            <?php foreach ([['approve', in_array($p['status'], ['pending', 'hidden'], true), $p['status'] === 'pending' ? 'Approve &amp; publish' : 'Show in shop', 'btn-primary', ''],
                            ['hide', in_array($p['status'], ['active', 'pending'], true), 'Hide from shop', '', ''],
                            ['sold', $p['status'] === 'active', 'Mark as sold', '', 'Mark this product as sold? Stock will be set to 0.'],
                            ['delete', true, 'Delete product', 'btn-danger', 'Delete "' . $p['title'] . '" permanently? Its image is removed too; past orders keep their line items.']] as [$act, $show, $label, $cls, $confirm]): ?>
                <?php if ($show): ?>
                    <form method="post" action="<?= e(url('/admin/products/' . $p['id'] . '/' . $act)) ?>" class="inline-form" <?= $confirm ? 'data-confirm="' . e($confirm) . '"' : '' ?>>
                        <?= csrf_field() ?>
                        <input type="hidden" name="return" value="<?= e('/admin/products/' . $p['id'] . '/edit') ?>">
                        <button type="submit" class="btn <?= e($cls) ?>"><?= $label ?></button>
                    </form>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
