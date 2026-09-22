<?php
use App\Modules\Admin\Digital;
use App\Modules\Admin\Forms;
use App\Modules\Admin\ListingRules;

$pageTitle = 'Products';
$nav = 'products';
$here = Forms::here();
$hasFilter = (bool) array_filter($filters, static fn ($v) => $v !== '' && $v !== 0);
$digitalOn = digital_enabled();
$statusOpts = ['' => 'Any status', 'pending' => 'Pending', 'active' => 'Active', 'sold' => 'Sold', 'hidden' => 'Hidden'];
?>
<div class="page-head">
    <div>
        <h1>Products</h1>
        <p class="muted"><?= (int) $pager->total ?> listing<?= $pager->total === 1 ? '' : 's' ?><?= $hasFilter ? ' match your filters' : '' ?></p>
    </div>
    <div class="actions">
        <?php if ($pendingCount > 0): ?>
            <form method="post" action="<?= e(url('/admin/products/approve-all')) ?>" class="inline-form" data-confirm="Approve all <?= (int) $pendingCount ?> pending listings and publish them to the shop?">
                <?= csrf_field() ?>
                <input type="hidden" name="return" value="<?= e($here) ?>">
                <button type="submit" class="btn">Approve all pending (<?= (int) $pendingCount ?>)</button>
            </form>
        <?php endif; ?>
        <a class="btn btn-primary" href="<?= e(url('/admin/products/create')) ?>">+ Add product</a>
    </div>
</div>

<?php if ($digital['total'] > 0 || !$digitalOn): ?>
    <div class="card bulk-bar digital-bar <?= $digitalOn ? 'is-on' : 'is-off' ?>">
        <div class="bulk-text">
            <strong>Digital goods: <span class="digital-state <?= $digitalOn ? 'on' : 'off' ?>"><?= $digitalOn ? 'ON' : 'OFF' ?></span></strong>
            <span class="muted"><?= (int) $digital['total'] ?> digital product<?= $digital['total'] === 1 ? '' : 's' ?> &middot; <?= (int) $digital['active'] ?> active &middot; <?= (int) $digital['drafts'] ?> draft<?= $digital['drafts'] === 1 ? '' : 's' ?>.
                <?= $digitalOn ? 'Active digital products are visible on the website.' : 'The digital goods switch is OFF: digital products are hidden on the website. You still see them here.' ?></span>
        </div>
        <div class="actions">
            <a class="btn btn-sm" href="<?= e(url('/admin/products?kind=digital')) ?>">View digital</a>
            <a class="btn btn-sm" href="<?= e(url('/admin/settings#digital')) ?>"><?= $digitalOn ? 'Digital settings' : 'Turn on / starter catalogue' ?></a>
        </div>
    </div>
<?php endif; ?>

<?php if ((int) $steelbooks['total'] > 0): ?>
    <div class="card bulk-bar">
        <div class="bulk-text">
            <strong>Steelbook editions</strong>
            <span class="muted"><?= (int) $steelbooks['total'] ?> in total &middot; <?= (int) $steelbooks['active'] ?> live &middot; <?= (int) $steelbooks['hidden'] ?> hidden with stock</span>
        </div>
        <div class="actions">
            <a class="btn btn-sm" href="<?= e(url('/admin/products?steelbook=1')) ?>">View steelbooks</a>
            <form method="post" action="<?= e(url('/admin/products/steelbooks/hide')) ?>" class="inline-form" data-confirm="Hide all <?= (int) $steelbooks['active'] ?> live steelbook products from the shop?">
                <?= csrf_field() ?>
                <input type="hidden" name="return" value="<?= e($here) ?>">
                <button type="submit" class="btn btn-sm" <?= (int) $steelbooks['active'] === 0 ? 'disabled' : '' ?>>Hide all steelbooks (<?= (int) $steelbooks['active'] ?>)</button>
            </form>
            <form method="post" action="<?= e(url('/admin/products/steelbooks/show')) ?>" class="inline-form" data-confirm="Show all <?= (int) $steelbooks['hidden'] ?> hidden steelbook products in the shop again?">
                <?= csrf_field() ?>
                <input type="hidden" name="return" value="<?= e($here) ?>">
                <button type="submit" class="btn btn-sm" <?= (int) $steelbooks['hidden'] === 0 ? 'disabled' : '' ?>>Show all steelbooks (<?= (int) $steelbooks['hidden'] ?>)</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($missingCount > 0 && $filters['missing'] !== '1'): ?>
    <div class="alert alert-warn missing-strip"><strong><?= (int) $missingCount ?> used listing<?= $missingCount === 1 ? '' : 's' ?> without all three photos</strong> (games: disc, box outside, box inside; hardware: product front, product back, box and accessories).
        <a href="<?= e(url('/admin/products?missing=1')) ?>">Show them</a></div>
<?php endif; ?>

<form method="get" action="<?= e(url('/admin/products')) ?>" class="card filters">
    <div class="field grow-2">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Title, slug, brand, model or serial">
    </div>
    <div class="field">
        <label for="f-status">Status</label>
        <select id="f-status" name="status"><?= Forms::options($statusOpts, $filters['status']) ?></select>
    </div>
    <div class="field">
        <label for="f-category">Category</label>
        <select id="f-category" name="category">
            <option value="">All</option>
            <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $filters['category'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?> (<?= e(ucfirst((string) $c['kind'])) ?>)<?= (int) $c['is_active'] ? '' : ' - disabled' ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-platform">Platform</label>
        <select id="f-platform" name="platform">
            <option value="">All</option>
            <?php foreach ($platforms as $p): ?><option value="<?= (int) $p['id'] ?>" <?= $filters['platform'] === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-seller">Seller</label>
        <select id="f-seller" name="seller">
            <option value="">All</option>
            <option value="house" <?= $filters['seller'] === 'house' ? 'selected' : '' ?>>House inventory</option>
            <?php foreach ($sellers as $s): ?><option value="<?= (int) $s['id'] ?>" <?= $filters['seller'] === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['code']) ?> &middot; <?= e($s['name']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-kind">Kind</label>
        <select id="f-kind" name="kind"><?= Forms::options(['' => 'All', 'game' => 'Game', 'hardware' => 'Hardware', 'digital' => 'Digital', 'physical' => 'Physical (game + hardware)'], $filters['kind']) ?></select>
    </div>
    <div class="field">
        <label for="f-steelbook">Steelbook</label>
        <select id="f-steelbook" name="steelbook"><?= Forms::options(['' => 'All', '1' => 'Steelbook only', '0' => 'Non-steelbook'], $filters['steelbook']) ?></select>
    </div>
    <div class="field">
        <label for="f-condition">Condition</label>
        <select id="f-condition" name="condition"><?= Forms::options(['' => 'All', 'new' => 'New (sealed)', 'used' => 'Used'], $filters['cond']) ?></select>
    </div>
    <div class="field">
        <label for="f-seo">SEO check</label>
        <select id="f-seo" name="seo"><?= Forms::options(['' => 'All', 'nodesc' => 'Live, no description', 'shortdesc' => 'Live, short description', 'noimage' => 'Live, no main image'], $filters['seo']) ?></select>
    </div>
    <div class="field">
        <label for="f-missing">Photos</label>
        <select id="f-missing" name="missing"><?= Forms::options(['' => 'All', '1' => 'Missing photos (' . (int) $missingCount . ')'], $filters['missing']) ?></select>
    </div>
    <div class="filter-actions">
        <button class="btn btn-primary" type="submit">Filter</button>
        <?php if ($hasFilter): ?><a class="btn btn-ghost" href="<?= e(url('/admin/products')) ?>">Clear</a><?php endif; ?>
    </div>
</form>

<section class="card">
    <?php if (!$products): ?>
        <div class="empty">
            <strong>No products found</strong>
            <p><?= $hasFilter ? 'Try clearing the filters.' : 'Add your first product to start selling.' ?></p>
            <a class="btn btn-primary" href="<?= e(url('/admin/products/create')) ?>">+ Add product</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                <tr><th></th><th>Title</th><th>Brand / model</th><th>Platform</th><th>Seller</th><th class="num">Seller price &rarr; Buyer price</th><th class="num">Stock</th><th>Status</th><th class="num">Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td class="thumb-cell"><?php if ($p['image']): ?><img class="thumb" src="<?= e(media($p['image'])) ?>" alt="" loading="lazy"><?php else: ?><span class="thumb thumb-empty"></span><?php endif; ?></td>
                        <td><a href="<?= e(url('/admin/products/' . $p['id'] . '/edit')) ?>"><strong><?= e($p['title']) ?></strong></a><?= $p['is_steelbook'] ? ' <span class="tag tag-steel">Steelbook</span>' : '' ?> <?= ListingRules::conditionTag($p) ?> <?= ListingRules::photoTag($p, (int) $p['photo_count']) ?><?= (int) $p['is_digital'] === 1 ? ' <span class="tag tag-digital" title="Digital item: prepaid, code sent on WhatsApp">Digital' . ($p['digital_kind'] ? ' &middot; ' . e(Digital::kindLabel($p['digital_kind'])) : '') . ($p['digital_region'] ? ' &middot; ' . e($p['digital_region']) : '') . '</span>' : '' ?><?= $p['seller_id'] && (\App\Support\ContactFilter::containsContact((string) $p['title']) || \App\Support\ContactFilter::containsContact((string) ($p['description'] ?? ''))) ? ' <span class="tag" title="Title or description looks like it contains contact details">contact info?</span>' : '' ?><?= $p['category_kind'] === 'hardware' && (int) $p['is_digital'] === 0 ? ' <span class="tag tag-hw" title="Hardware item">Hardware</span>' : '' ?><br><small class="muted"><?= e($p['category'] ?? '') ?></small><?= $p['serial_number'] ? '<br><small class="muted" title="Private: only visible in the admin">S/N ' . e($p['serial_number']) . '</small>' : '' ?><?= (int) $p['is_digital'] === 1 && !$digitalOn ? '<br><small class="text-warn">hidden: digital goods switch is off</small>' : '' ?></td>
                        <td><?= $p['brand'] || $p['model'] ? '<strong>' . e((string) $p['brand']) . '</strong>' . ($p['model'] ? '<br><small class="muted">' . e($p['model']) . '</small>' : '') : '<span class="muted">-</span>' ?></td>
                        <td><?= e($p['platform'] ?? '-') ?></td>
                        <td><?= $p['seller_code'] ? '<a href="' . e(url('/admin/sellers/' . $p['seller_id'])) . '">' . e($p['seller_code']) . '</a>' : '<span class="tag">House</span>' ?></td>
                        <td class="num">
                            <?php if ($p['seller_id']): ?>
                                <?= e(money($p['seller_price'])) ?> &rarr; <strong><?= e(money($p['price'])) ?></strong>
                                <br><small class="muted"><?= e(Forms::pct($p['commission_pct'])) ?> commission</small>
                            <?php else: ?>
                                <strong><?= e(money($p['price'])) ?></strong>
                            <?php endif; ?>
                        </td>
                        <td class="num"><?= (int) $p['stock'] ?></td>
                        <td><?= Forms::pill($p['status']) ?></td>
                        <td class="num nowrap">
                            <?php $action = static fn (string $act, string $label, string $cls = '', string $confirm = ''): string =>
                                '<form method="post" action="' . e(url('/admin/products/' . $p['id'] . '/' . $act)) . '" class="inline-form"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>'
                                . csrf_field() . '<input type="hidden" name="return" value="' . e($here) . '"><button type="submit" class="btn btn-sm ' . $cls . '">' . e($label) . '</button></form>'; ?>
                            <?php if ($p['status'] === 'pending'): ?><a class="btn btn-sm" href="<?= e(url('/admin/products/' . $p['id'] . '/review')) ?>">Review</a> <?= $action('approve', 'Approve', 'btn-primary') ?><?php endif; ?>
                            <?php if ($p['status'] === 'hidden'): ?><?= $action('approve', 'Show') ?><?php endif; ?>
                            <?php if (in_array($p['status'], ['active', 'pending'], true)): ?><?= $action('hide', 'Hide') ?><?php endif; ?>
                            <?php if ($p['status'] === 'active'): ?><?= $action('sold', 'Sold', '', 'Mark "' . $p['title'] . '" as sold? Stock will be set to 0.') ?><?php endif; ?>
                            <a class="btn btn-sm" href="<?= e(url('/admin/products/' . $p['id'] . '/edit')) ?>">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= $pager->render() ?>
    <?php endif; ?>
</section>
