<?php
use App\Modules\Admin\Forms;

$pageTitle = 'Categories & platforms';
$nav = 'catalog';
$catMode = (bool) old('cat');
$platMode = (bool) old('plat');
$cv = static function (int $id, string $key, mixed $default) use ($catMode) {
    if (!$catMode) {
        return $default;
    }
    $all = old('cat', []);
    return $all[$id][$key] ?? '';
};
$pv = static function (int $id, string $key, mixed $default) use ($platMode) {
    if (!$platMode) {
        return $default;
    }
    $all = old('plat', []);
    return $all[$id][$key] ?? '';
};
?>
<div class="page-head">
    <div>
        <h1>Categories &amp; platforms</h1>
        <p class="muted">The SEO title, description and intro text below are what Google and shoppers see on each category page.</p>
    </div>
</div>

<form method="post" action="<?= e(url('/admin/catalog')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="section" value="categories">
    <input type="hidden" name="_form" value="1">
    <?php foreach ($categories as $c): $id = (int) $c['id']; ?>
        <section class="card">
            <div class="card-head">
                <h2><?= e($c['name']) ?> <span class="tag">/<?= e($c['slug']) ?></span></h2>
                <span class="muted"><?= (int) $c['products'] ?> product<?= (int) $c['products'] === 1 ? '' : 's' ?></span>
            </div>
            <div class="card-body form-grid">
                <div class="field">
                    <label for="c-name-<?= $id ?>">Name</label>
                    <input type="text" id="c-name-<?= $id ?>" name="cat[<?= $id ?>][name]" value="<?= e($cv($id, 'name', $c['name'])) ?>" maxlength="60" required>
                </div>
                <div class="field field-inline">
                    <div class="field">
                        <label for="c-sort-<?= $id ?>">Sort order</label>
                        <input type="number" id="c-sort-<?= $id ?>" name="cat[<?= $id ?>][sort_order]" value="<?= e($cv($id, 'sort_order', $c['sort_order'])) ?>">
                    </div>
                    <label class="check"><input type="checkbox" name="cat[<?= $id ?>][is_active]" value="1" <?= ($catMode ? !empty(old('cat', [])[$id]['is_active']) : (bool) $c['is_active']) ? 'checked' : '' ?>> Visible in shop</label>
                </div>
                <div class="field span-2">
                    <label for="c-seot-<?= $id ?>">SEO title <small class="muted counter" data-count-for="c-seot-<?= $id ?>" data-max="160"></small></label>
                    <input type="text" id="c-seot-<?= $id ?>" name="cat[<?= $id ?>][seo_title]" value="<?= e($cv($id, 'seo_title', $c['seo_title'] ?? '')) ?>" maxlength="160">
                </div>
                <div class="field span-2">
                    <label for="c-seod-<?= $id ?>">SEO description <small class="muted counter" data-count-for="c-seod-<?= $id ?>" data-max="320"></small></label>
                    <textarea id="c-seod-<?= $id ?>" name="cat[<?= $id ?>][seo_description]" rows="2" maxlength="320"><?= e($cv($id, 'seo_description', $c['seo_description'] ?? '')) ?></textarea>
                    <small class="hint">Aim for 140-160 characters: it is the grey text under your link in Google.</small>
                </div>
                <div class="field span-2">
                    <label for="c-intro-<?= $id ?>">Intro text</label>
                    <textarea id="c-intro-<?= $id ?>" name="cat[<?= $id ?>][intro_text]" rows="4"><?= e($cv($id, 'intro_text', $c['intro_text'] ?? '')) ?></textarea>
                    <small class="hint">Shown at the top of the category page. Write for people first, keywords second.</small>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
    <div class="form-actions"><button type="submit" class="btn btn-primary btn-lg">Save categories</button></div>
</form>

<section class="card">
    <div class="card-head"><h2>Add a category</h2></div>
    <form method="post" action="<?= e(url('/admin/catalog')) ?>" class="card-body inline-fields">
        <?= csrf_field() ?>
        <input type="hidden" name="section" value="new_category">
        <div class="field grow-2"><label for="new-cat">Name</label><input type="text" id="new-cat" name="new_name" maxlength="60" required placeholder="e.g. Collectibles"></div>
        <button class="btn btn-primary" type="submit">Add category</button>
    </form>
</section>

<h2 class="section-title">Platforms</h2>
<form method="post" action="<?= e(url('/admin/catalog')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="section" value="platforms">
    <input type="hidden" name="_form" value="1">
    <section class="card">
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Name</th><th>Slug</th><th class="num">Products</th><th>Sort order</th><th>Active</th></tr></thead>
                <tbody>
                <?php foreach ($platforms as $p): $id = (int) $p['id']; ?>
                    <tr>
                        <td><label class="sr-only" for="p-name-<?= $id ?>">Name</label><input type="text" id="p-name-<?= $id ?>" name="plat[<?= $id ?>][name]" value="<?= e($pv($id, 'name', $p['name'])) ?>" maxlength="60" required></td>
                        <td><span class="tag">/<?= e($p['slug']) ?></span></td>
                        <td class="num"><?= (int) $p['products'] ?></td>
                        <td><label class="sr-only" for="p-sort-<?= $id ?>">Sort order</label><input type="number" id="p-sort-<?= $id ?>" class="narrow" name="plat[<?= $id ?>][sort_order]" value="<?= e($pv($id, 'sort_order', $p['sort_order'])) ?>"></td>
                        <td><label class="check"><input type="checkbox" name="plat[<?= $id ?>][is_active]" value="1" <?= ($platMode ? !empty(old('plat', [])[$id]['is_active']) : (bool) $p['is_active']) ? 'checked' : '' ?>> Active</label></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <div class="form-actions"><button type="submit" class="btn btn-primary btn-lg">Save platforms</button></div>
</form>

<section class="card">
    <div class="card-head"><h2>Add a platform</h2></div>
    <form method="post" action="<?= e(url('/admin/catalog')) ?>" class="card-body inline-fields">
        <?= csrf_field() ?>
        <input type="hidden" name="section" value="new_platform">
        <div class="field grow-2"><label for="new-plat">Name</label><input type="text" id="new-plat" name="new_name" maxlength="60" required placeholder="e.g. Nintendo 3DS"></div>
        <button class="btn btn-primary" type="submit">Add platform</button>
    </form>
</section>
