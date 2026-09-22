<?php
/** @var array $categories @var array $platforms @var array $kinds @var bool $digitalOn */
use App\Modules\Admin\CatalogController;
use App\Modules\Admin\Forms;

$pageTitle = 'Categories & platforms';
$nav = 'catalog';
$formMode = (bool) old('_form');
$oldId    = (string) old('id', '');
$disableText = static fn (string $what, int $n): string =>
    'Disabling hides this ' . $what . ' AND its ' . $n . ' product' . ($n === 1 ? '' : 's') . ' everywhere on the website: shop, search, sitemap, direct links. Nothing is deleted.';
?>
<div class="page-head">
    <div>
        <h1>Categories &amp; platforms</h1>
        <p class="muted">Control what is on the website. The <strong>kind</strong> of a category decides which fields its products need. The SEO title, description and intro text are what Google and shoppers see on each category page.</p>
    </div>
</div>

<?php foreach ($categories as $c):
    $id = (int) $c['id'];
    $on = (int) $c['is_active'] === 1;
    $old = $formMode && $oldId === (string) $id && old('section') === 'category';
    $val =static fn (string $key, mixed $default) => $old ? (string) old($key, '') : $default;
    $kind = $old && isset($kinds[(string) old('kind')]) ? (string) old('kind') : (string) $c['kind'];
    $member = $old ? !empty(old('member_listing')) : (int) $c['member_listing'] === 1;
    $n = (int) $c['products'];
?>
    <section class="card cat-card<?= $on ? '' : ' is-off' ?>" id="cat-<?= $id ?>">
        <div class="card-head">
            <h2><?= e($c['name']) ?> <span class="tag">/<?= e($c['slug']) ?></span> <span class="tag tag-kind-<?= e($c['kind']) ?>"><?= e($kinds[$c['kind']] ?? $c['kind']) ?></span></h2>
            <div class="cat-head-tools">
                <a class="muted" href="<?= e(url('/admin/products?category=' . $id)) ?>" title="Open these products in the product list"><?= (int) $c['active_products'] ?> active / <?= $n ?> product<?= $n === 1 ? '' : 's' ?></a>
                <form method="post" action="<?= e(url('/admin/catalog/category/' . $id . '/toggle')) ?>" class="inline-form"
                      <?= $on ? 'data-confirm="' . e($disableText('category', $n)) . '"' : '' ?>>
                    <?= csrf_field() ?>
                    <input type="hidden" name="to" value="<?= $on ? '0' : '1' ?>">
                    <button type="submit" class="switch <?= $on ? 'is-on' : 'is-off' ?>" role="switch" aria-checked="<?= $on ? 'true' : 'false' ?>"
                            title="<?= $on ? 'Click to disable this category' : 'Click to enable this category' ?>"><span class="switch-knob" aria-hidden="true"></span><span class="switch-text"><?= $on ? 'Enabled' : 'Disabled' ?></span></button>
                </form>
            </div>
        </div>
        <?php if (!$on): ?>
            <div class="alert alert-warn cat-off-note"><strong>Disabled.</strong> This category and its <?= $n ?> product<?= $n === 1 ? ' is' : 's are' ?> hidden on the website (shop, search, sitemap and direct links). You still see everything here in the admin.</div>
        <?php endif; ?>
        <?php if ($c['kind'] === 'digital'): ?>
            <div class="alert alert-inline cat-digital-note">Digital category: it also follows the master <a href="<?= e(url('/admin/settings#digital')) ?>">Digital switch</a>, which is currently <strong><?= $digitalOn ? 'ON' : 'OFF' ?></strong>. Members cannot list here.</div>
        <?php endif; ?>
        <form method="post" action="<?= e(url('/admin/catalog')) ?>" class="card-body form-grid" data-kind-form data-original-kind="<?= e($c['kind']) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="category">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="_form" value="1">
            <div class="field">
                <label for="c-name-<?= $id ?>">Name</label>
                <input type="text" id="c-name-<?= $id ?>" name="name" value="<?= e($val('name', $c['name'])) ?>" maxlength="60" required>
            </div>
            <div class="field">
                <label for="c-sort-<?= $id ?>">Sort order</label>
                <input type="number" id="c-sort-<?= $id ?>" name="sort_order" value="<?= e($val('sort_order', $c['sort_order'])) ?>">
            </div>
            <div class="field">
                <label for="c-kind-<?= $id ?>">Kind</label>
                <select id="c-kind-<?= $id ?>" name="kind" data-kind-select data-help="<?= e((string) json_encode(CatalogController::KIND_HELP)) ?>">
                    <?= Forms::options($kinds, $kind) ?>
                </select>
                <small class="hint" data-kind-help><?= e(CatalogController::KIND_HELP[$kind] ?? '') ?></small>
                <div class="kind-warning" data-kind-warning <?= $kind !== $c['kind'] ? '' : 'hidden' ?>>
                    <strong>Careful: changing the kind changes which fields and photos this category's <?= $n ?> product<?= $n === 1 ? '' : 's' ?> need.</strong>
                    Existing products are not converted.
                    <label class="check"><input type="checkbox" name="kind_confirm" value="1" <?= $old && old('kind_confirm') === '1' ? 'checked' : '' ?>> I understand, change the kind</label>
                </div>
            </div>
            <div class="field field-check">
                <label class="check"><input type="checkbox" name="member_listing" value="1" data-member-check <?= $member && $kind !== 'digital' ? 'checked' : '' ?> <?= $kind === 'digital' ? 'disabled' : '' ?>> Members may list here</label>
                <small class="hint">Members and stores can add their own items to this category. Games only for now: hardware and digital stay admin-only.</small>
            </div>
            <div class="field span-2">
                <label for="c-seot-<?= $id ?>">SEO title <small class="muted counter" data-count-for="c-seot-<?= $id ?>" data-max="160"></small></label>
                <input type="text" id="c-seot-<?= $id ?>" name="seo_title" value="<?= e($val('seo_title', $c['seo_title'] ?? '')) ?>" maxlength="160">
            </div>
            <div class="field span-2">
                <label for="c-seod-<?= $id ?>">SEO description <small class="muted counter" data-count-for="c-seod-<?= $id ?>" data-max="320"></small></label>
                <textarea id="c-seod-<?= $id ?>" name="seo_description" rows="2" maxlength="320"><?= e($val('seo_description', $c['seo_description'] ?? '')) ?></textarea>
                <small class="hint">Aim for 140-160 characters: it is the grey text under your link in Google.</small>
            </div>
            <div class="field span-2">
                <label for="c-intro-<?= $id ?>">Intro text</label>
                <textarea id="c-intro-<?= $id ?>" name="intro_text" rows="4"><?= e($val('intro_text', $c['intro_text'] ?? '')) ?></textarea>
                <small class="hint">Shown at the top of the category page. Write for people first, keywords second.</small>
            </div>
            <div class="field span-2"><div><button type="submit" class="btn btn-primary">Save "<?= e($c['name']) ?>"</button></div></div>
        </form>
    </section>
<?php endforeach; ?>

<section class="card">
    <div class="card-head"><h2>Add a category</h2></div>
    <form method="post" action="<?= e(url('/admin/catalog')) ?>" class="card-body inline-fields">
        <?= csrf_field() ?>
        <input type="hidden" name="section" value="new_category">
        <div class="field grow-2"><label for="new-cat">Name</label><input type="text" id="new-cat" name="new_name" maxlength="60" required placeholder="e.g. Collectibles"></div>
        <div class="field">
            <label for="new-kind">Kind *</label>
            <select id="new-kind" name="new_kind" required>
                <option value="">Choose...</option>
                <?= Forms::options($kinds, '') ?>
            </select>
        </div>
        <label class="check"><input type="checkbox" name="new_member_listing" value="1"> Members may list here</label>
        <button class="btn btn-primary" type="submit">Add category</button>
    </form>
    <div class="card-body"><small class="hint"><strong>Game:</strong> <?= e(CatalogController::KIND_HELP['game']) ?> <strong>Hardware:</strong> <?= e(CatalogController::KIND_HELP['hardware']) ?> <strong>Digital:</strong> <?= e(CatalogController::KIND_HELP['digital']) ?></small></div>
</section>

<h2 class="section-title" id="platforms">Platforms</h2>
<section class="card">
    <div class="plat-head" aria-hidden="true"><span>Name</span><span>Slug</span><span>Products</span><span>Sort</span><span>Enabled</span><span></span></div>
    <?php foreach ($platforms as $p):
        $id = (int) $p['id'];
        $on = (int) $p['is_active'] === 1;
        $old = $formMode && $oldId === (string) $id && old('section') === 'platform';
        $n = (int) $p['products'];
    ?>
        <div class="plat-row<?= $on ? '' : ' is-off' ?>" id="plat-<?= $id ?>">
            <label class="sr-only" for="p-name-<?= $id ?>">Name</label>
            <input type="text" id="p-name-<?= $id ?>" name="name" form="pf-<?= $id ?>" value="<?= e($old ? old('name', '') : $p['name']) ?>" maxlength="60" required>
            <span><span class="tag">/<?= e($p['slug']) ?></span></span>
            <a href="<?= e(url('/admin/products?platform=' . $id)) ?>" class="muted" title="Open these products"><?= (int) $p['active_products'] ?> / <?= $n ?></a>
            <label class="sr-only" for="p-sort-<?= $id ?>">Sort order</label>
            <input type="number" id="p-sort-<?= $id ?>" class="narrow" name="sort_order" form="pf-<?= $id ?>" value="<?= e($old ? old('sort_order', '') : $p['sort_order']) ?>">
            <button type="submit" form="pt-<?= $id ?>" class="switch <?= $on ? 'is-on' : 'is-off' ?>" role="switch" aria-checked="<?= $on ? 'true' : 'false' ?>"
                    title="<?= $on ? 'Click to disable this platform' : 'Click to enable this platform' ?>"><span class="switch-knob" aria-hidden="true"></span><span class="switch-text"><?= $on ? 'Enabled' : 'Disabled' ?></span></button>
            <button type="submit" form="pf-<?= $id ?>" class="btn btn-sm">Save</button>
            <form id="pf-<?= $id ?>" method="post" action="<?= e(url('/admin/catalog')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="section" value="platform">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="_form" value="1">
            </form>
            <form id="pt-<?= $id ?>" method="post" action="<?= e(url('/admin/catalog/platform/' . $id . '/toggle')) ?>"
                  <?= $on ? 'data-confirm="' . e($disableText('platform', $n)) . '"' : '' ?>>
                <?= csrf_field() ?>
                <input type="hidden" name="to" value="<?= $on ? '0' : '1' ?>">
            </form>
        </div>
    <?php endforeach; ?>
    <div class="card-body"><small class="hint">Products shown as "active / total". Disabling a platform hides it and its products on the website; nothing is deleted.</small></div>
</section>

<section class="card">
    <div class="card-head"><h2>Add a platform</h2></div>
    <form method="post" action="<?= e(url('/admin/catalog')) ?>" class="card-body inline-fields">
        <?= csrf_field() ?>
        <input type="hidden" name="section" value="new_platform">
        <div class="field grow-2"><label for="new-plat">Name</label><input type="text" id="new-plat" name="new_name" maxlength="60" required placeholder="e.g. Nintendo 3DS"></div>
        <button class="btn btn-primary" type="submit">Add platform</button>
    </form>
</section>
