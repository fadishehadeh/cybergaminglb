<?php
/** @var array $kinds @var array $products @var \App\Modules\Admin\Pagination $pager @var string $status @var array $counts */
$pageTitle = 'My products';
$nav = 'products';

$statusLabel = ['pending' => 'Waiting for approval', 'active' => 'Live', 'hidden' => 'Hidden', 'sold' => 'Sold out'];
$tabs = ['' => 'All', 'active' => 'Live', 'pending' => 'Waiting for approval', 'hidden' => 'Hidden', 'sold' => 'Sold out'];
?>
<div class="sp-head">
    <div>
        <h1>My products</h1>
        <p class="sp-muted">You enter the price you want to receive. Buyers see that price plus our commission.</p>
    </div>
    <a class="sp-btn sp-btn-primary" href="<?= e(url('/seller/products/new')) ?>">+ Add a listing</a>
</div>

<div class="sp-tabs" role="tablist">
    <?php foreach ($tabs as $key => $label): ?>
        <a href="<?= e(url('/seller/products' . ($key !== '' ? '?status=' . $key : ''))) ?>" class="<?= $status === $key ? 'active' : '' ?>"><?= e($label) ?> <small><?= (int) ($counts[$key] ?? 0) ?></small></a>
    <?php endforeach; ?>
</div>

<?php if (!$products): ?>
    <section class="sp-card sp-empty">
        <strong><?= $status !== '' ? 'No listings with that status' : 'No listings yet' ?></strong>
        <p>Add an item with a photo and the price you want to receive. We review it and put it in the shop.</p>
        <a class="sp-btn sp-btn-primary" href="<?= e(url('/seller/products/new')) ?>">+ Add a listing</a>
    </section>
<?php else: ?>
    <ul class="sp-list">
        <?php foreach ($products as $p): $pid = (int) $p['id']; ?>
            <li class="sp-item">
                <img class="sp-thumb" src="<?= e(media($p['image'])) ?>" alt="" width="64" height="86" loading="lazy">
                <div class="sp-item-main">
                    <a class="sp-item-title" href="<?= e(url('/seller/products/' . $pid . '/edit')) ?>"><?= e($p['title']) ?></a>
                    <div class="sp-item-meta">
                        <?= e($p['platform'] ?? 'No platform') ?> &middot; stock <?= (int) $p['stock'] ?>
                    </div>
                    <?php $tagRow = $p; $tagKinds = $kinds[$pid] ?? []; require base_path('app/Views/account/listings/_tags.php'); ?>
                    <div class="sp-item-prices">
                        You receive <strong><?= e(money($p['seller_price'])) ?></strong>
                        <span class="sp-muted">&middot; buyers see <?= e(money($p['price'])) ?></span>
                    </div>
                    <span class="sp-pill sp-pill-<?= e($p['status']) ?>"><?= e($statusLabel[$p['status']] ?? $p['status']) ?></span>
                    <?php if ($p['status'] === 'pending'): ?><span class="sp-hint">Not visible to buyers until we approve it.</span><?php endif; ?>
                </div>
                <div class="sp-item-actions">
                    <a class="sp-btn sp-btn-sm" href="<?= e(url('/seller/products/' . $pid . '/edit')) ?>">Edit</a>
                    <?php if ($p['status'] === 'active'): ?>
                        <form method="post" action="<?= e(url('/seller/products/' . $pid . '/withdraw')) ?>" data-confirm="Withdraw this listing from the shop? You can relist it later.">
                            <?= csrf_field() ?><button class="sp-btn sp-btn-sm" type="submit">Withdraw</button>
                        </form>
                    <?php elseif ($p['status'] === 'hidden'): ?>
                        <form method="post" action="<?= e(url('/seller/products/' . $pid . '/relist')) ?>">
                            <?= csrf_field() ?><button class="sp-btn sp-btn-sm sp-btn-primary" type="submit">Relist</button>
                        </form>
                    <?php endif; ?>
                    <?php if ((int) $p['times_ordered'] === 0): ?>
                        <form method="post" action="<?= e(url('/seller/products/' . $pid . '/delete')) ?>" data-confirm="Delete this listing permanently?">
                            <?= csrf_field() ?><button class="sp-btn sp-btn-sm sp-btn-danger" type="submit">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
    <?= $pager->render() ?>
<?php endif; ?>
