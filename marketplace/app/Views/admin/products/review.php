<?php
/**
 * Review page for a listing (built for pending member / store listings).
 * @var array $p product  @var ?array $seller  @var string $category  @var string $platform  @var array $photos
 * @var string[] $missing missing required photo kinds  @var bool $contact  @var string $next
 */
use App\Modules\Admin\Forms;
use App\Modules\Admin\ListingRules;
use App\Modules\Seller\ListingCondition;
use App\Support\ProductPhotos;

$pageTitle = 'Review: ' . $p['title'];
$nav       = 'products';
$id        = (int) $p['id'];
$isDigital = (int) $p['is_digital'] === 1;
$isUsed    = ListingRules::needsPhotos($p);
$isHw      = ListingRules::typeOf($p) === 'hardware';
$slotLabels = $isHw ? \App\Modules\Admin\Hardware::CAPTIONS : ProductPhotos::LABELS;
if ($isHw) {
    unset($slotLabels['powered_on']);
}
$noun      = $isHw ? 'item' : 'game';
$canPublish = in_array($p['status'], ['pending', 'hidden'], true);
$commission = round((float) $p['price'] - (float) $p['seller_price'], 2);

$byKind = [];
$extras = [];
foreach ($photos as $ph) {
    if ($ph['kind'] === 'extra') {
        $extras[] = $ph;
    } else {
        $byKind[$ph['kind']] = $ph;
    }
}
$reviewNext = $next;
?>
<div class="page-head">
    <div>
        <h1>Review listing <?= Forms::pill($p['status']) ?></h1>
        <p class="muted"><?= e($p['title']) ?> &middot; <?= e(trim($platform . ($platform && $category ? ' / ' : '') . $category)) ?></p>
    </div>
    <div class="actions">
        <a class="btn" href="<?= e(url('/admin/products/' . $id . '/edit')) ?>">Edit listing</a>
        <a class="btn btn-ghost" href="<?= e(url($reviewNext)) ?>">&larr; Pending listings</a>
    </div>
</div>

<?php if ($contact): ?>
    <div class="alert alert-warn"><strong>Possible contact details in this seller listing.</strong> The title or description looks like it contains a phone number, link, email or social handle. Sellers must not take buyers off-platform: edit the text before approving, or reject the listing.</div>
<?php endif; ?>

<?php if ($missing): ?>
    <div class="alert alert-warn review-block">
        <strong>Approval is blocked: missing photos.</strong>
        This used <?= $noun ?> has no <?= e(ListingRules::kindList($missing)) ?> photo<?= count($missing) === 1 ? '' : 's' ?>.
        A used listing needs its <?= $isHw ? 'product front, product back and box and accessories' : 'disc, box outside and box inside' ?> photos before it can go live.
        <a href="<?= e(url('/admin/products/' . $id . '/edit')) ?>">Add the missing photos</a>, or publish it as old stock with the tick box below.
    </div>
<?php endif; ?>

<div class="cols-form review-cols">
    <section class="card">
        <div class="card-head"><h2>Photos (<?= count($photos) ?>)</h2><?php if (!$isDigital): ?><?= ListingRules::photoTag($p, count(array_intersect(array_keys($byKind), ListingRules::requiredKinds($isHw ? 'hardware' : 'game')))) ?><?php endif; ?></div>
        <div class="card-body">
            <?php if ($isDigital): ?>
                <p class="muted">Digital item: no photos needed.</p>
            <?php else: ?>
                <div class="rv-photos">
                    <?php foreach ($slotLabels as $kind => $label): $cur = $byKind[$kind] ?? null; ?>
                        <figure class="rv-photo<?= $cur ? '' : ' rv-missing' ?>">
                            <?php if ($cur): ?>
                                <a href="<?= e(media($cur['path'])) ?>" target="_blank" rel="noopener" title="Open the full photo in a new tab"><img src="<?= e(media($cur['path'])) ?>" alt="<?= e($label) ?>" loading="lazy"></a>
                            <?php else: ?>
                                <div class="rv-none"><?= $isUsed ? 'Missing' : 'No photo' ?></div>
                            <?php endif; ?>
                            <figcaption><strong><?= e($label) ?></strong><?= $cur ? '' : ($isUsed ? ' <span class="text-warn">required</span>' : ' <span class="muted">optional</span>') ?></figcaption>
                        </figure>
                    <?php endforeach; ?>
                    <?php foreach ($extras as $i => $ex): ?>
                        <figure class="rv-photo">
                            <a href="<?= e(media($ex['path'])) ?>" target="_blank" rel="noopener" title="Open the full photo in a new tab"><img src="<?= e(media($ex['path'])) ?>" alt="Extra photo <?= $i + 1 ?>" loading="lazy"></a>
                            <figcaption><strong>Extra <?= $i + 1 ?></strong></figcaption>
                        </figure>
                    <?php endforeach; ?>
                </div>
                <?php if ($p['image'] && !in_array($p['image'], array_column($photos, 'path'), true)): ?>
                    <h3 class="sub-title">Main image</h3>
                    <a href="<?= e(media($p['image'])) ?>" target="_blank" rel="noopener"><img class="image-current" src="<?= e(media($p['image'])) ?>" alt="Main image"></a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <div class="stack">
        <section class="card">
            <div class="card-head"><h2>Condition &amp; included</h2></div>
            <dl class="kv">
                <?php if ($isDigital): ?>
                    <dt>Type</dt><dd><span class="tag tag-digital">Digital</span></dd>
                <?php else: ?>
                    <dt>Condition</dt><dd><?= ListingRules::conditionTag($p) ?> <?= (string) $p['item_condition'] !== 'New' ? '<small class="muted">' . e((string) (ListingCondition::GRADE_HELP[$p['item_condition']] ?? '')) . '</small>' : '' ?></dd>
                    <?php if ($isHw): ?>
                        <dt>Brand / model</dt><dd><?= e(trim((string) $p['brand'] . ' ' . (string) $p['model'])) ?: '-' ?></dd>
                        <dt>Warranty</dt><dd><?= $p['warranty_months'] !== null ? (int) $p['warranty_months'] . ' month' . ((int) $p['warranty_months'] === 1 ? '' : 's') : 'Not stated' ?></dd>
                        <dt>In the box</dt><dd><?= $p['included_items'] ? nl2br(e($p['included_items'])) : 'Not stated' ?></dd>
                        <dt>Specs</dt><dd><?= $p['specs'] ? nl2br(e($p['specs'])) : 'Not stated' ?></dd>
                        <dt>Serial number</dt><dd><?= $p['serial_number'] ? e($p['serial_number']) . ' <small class="muted">(private)</small>' : '-' ?></dd>
                    <?php else: ?>
                    <?php foreach (ListingCondition::INCLUDES as $col => $label): ?>
                        <dt><?= e($label) ?></dt>
                        <dd><span class="inc <?= e(ListingRules::includeClass($p[$col])) ?>"><?= ListingRules::includeText($p[$col]) === 'Yes' ? '&#10003; Yes' : (ListingRules::includeText($p[$col]) === 'No' ? '&#10007; No' : 'Not stated') ?></span></dd>
                    <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (!$isHw): ?><dt>Edition</dt><dd><?= e($p['edition']) ?><?= $p['is_steelbook'] ? ' <span class="tag tag-steel">Steelbook</span>' : '' ?></dd><?php endif; ?>
                <dt>Stock</dt><dd><?= (int) $p['stock'] ?></dd>
            </dl>
        </section>

        <section class="card">
            <div class="card-head"><h2>Seller &amp; price</h2></div>
            <dl class="kv">
                <dt>Seller</dt>
                <dd>
                    <?php if ($seller): ?>
                        <a href="<?= e(url('/admin/sellers/' . $seller['id'])) ?>"><strong><?= e($seller['code']) ?> &middot; <?= e($seller['name']) ?></strong></a>
                        <span class="tag <?= $seller['type'] === 'member' ? 'tag-member' : '' ?>"><?= $seller['type'] === 'member' ? 'Member' : 'Store' ?></span>
                        <?php if ($seller['status'] !== 'active'): ?><?= Forms::pill($seller['status']) ?><?php endif; ?>
                        <?php $wa = Forms::waLink($seller['phone'] ?? '', 'Hi, about your listing "' . $p['title'] . '" on ' . (string) setting('site_name', 'CyberGaming Lebanon') . '.'); ?>
                        <br><small class="muted"><?= e($seller['area'] ?? '') ?><?= $wa ? ' &middot; <a href="' . e($wa) . '" target="_blank" rel="noopener">WhatsApp</a>' : '' ?></small>
                    <?php else: ?>
                        <span class="tag">House inventory</span>
                    <?php endif; ?>
                </dd>
                <?php if ($seller): ?>
                    <dt>Seller asks</dt><dd><strong><?= e(money($p['seller_price'])) ?></strong></dd>
                    <dt>Buyer pays</dt><dd><strong><?= e(money($p['price'])) ?></strong></dd>
                    <dt>Commission</dt><dd><?= e(money($commission)) ?> <small class="muted">(<?= e(Forms::pct($p['commission_pct'])) ?>, rounded up to the next $0.50)</small></dd>
                <?php else: ?>
                    <dt>Buyer pays</dt><dd><strong><?= e(money($p['price'])) ?></strong> <small class="muted">no commission</small></dd>
                <?php endif; ?>
            </dl>
        </section>

        <section class="card">
            <div class="card-head"><h2>Description</h2></div>
            <div class="card-body"><?= $p['description'] ? nl2br(e($p['description'])) : '<span class="muted">No description.</span>' ?></div>
        </section>
    </div>
</div>

<section class="card action-panel review-actions">
    <div class="card-head"><h2>Decision</h2></div>
    <div class="card-body">
        <?php if ($canPublish): ?>
            <form method="post" action="<?= e(url('/admin/products/' . $id . '/approve')) ?>" class="review-form" data-legacy-gate>
                <?= csrf_field() ?>
                <input type="hidden" name="return" value="<?= e($reviewNext) ?>">
                <?php if ($missing): ?>
                    <label class="check legacy-check"><input type="checkbox" name="legacy_ok" value="1" data-legacy-check> Publish without photos (legacy stock)</label>
                <?php endif; ?>
                <?php if ((int) $p['stock'] < 1): ?>
                    <p class="text-warn">Stock is 0: set the stock above 0 in the edit page before publishing.</p>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary btn-lg" data-legacy-approve <?= $missing ? 'disabled' : '' ?>><?= $p['status'] === 'pending' ? 'Approve &amp; publish' : 'Show in shop' ?></button>
            </form>
        <?php endif; ?>
        <?php if (in_array($p['status'], ['pending', 'active'], true)): ?>
            <form method="post" action="<?= e(url('/admin/products/' . $id . '/hide')) ?>" class="inline-form" data-confirm="<?= $p['status'] === 'pending' ? 'Reject this listing? It will be hidden from the shop.' : 'Hide this listing from the shop?' ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="return" value="<?= e($reviewNext) ?>">
                <button type="submit" class="btn btn-danger btn-lg"><?= $p['status'] === 'pending' ? 'Reject (hide)' : 'Hide from shop' ?></button>
            </form>
        <?php endif; ?>
        <?php if (!$canPublish && !in_array($p['status'], ['pending', 'active'], true)): ?>
            <p class="muted">This listing is <?= e($p['status']) ?>: nothing to approve.</p>
        <?php endif; ?>
    </div>
</section>
