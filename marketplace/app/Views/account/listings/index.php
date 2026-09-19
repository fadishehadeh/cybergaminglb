<?php
use App\Modules\Account\ListingBase;
use App\Modules\Account\ListingController;

/** @var array $kinds @var array $seller @var float $commission @var array $products @var \App\Modules\Admin\Pagination $pager @var string $status @var array $counts */
$meta = ['title' => 'My listings | CyberGaming', 'description' => 'Your items for sale to other members.', 'noindex' => true];
$accountNav = 'listings';
$tabs = ['' => 'All', 'active' => 'Live', 'pending' => 'Waiting', 'hidden' => 'Hidden', 'sold' => 'Sold'];
?>
<?php require base_path('app/Views/account/_nav.php'); ?>
<link rel="stylesheet" href="<?= e(asset('css/listings.css')) ?>">
<div class="container ls-page">

    <div class="ls-head">
        <div>
            <h1>My listings</h1>
            <p class="ls-muted">You enter the price you want to receive. Buyers pay that price + <?= e(ListingBase::pct($commission)) ?> commission.</p>
        </div>
        <a class="ls-btn ls-btn-primary" href="<?= e(url('/account/listings/new')) ?>">+ Add a listing</a>
    </div>

    <nav class="ls-tabs" aria-label="Filter by status">
        <?php foreach ($tabs as $key => $label): ?>
            <a href="<?= e(url('/account/listings' . ($key !== '' ? '?status=' . $key : ''))) ?>"<?= $status === $key ? ' aria-current="page" class="active"' : '' ?>><?= e($label) ?> <small><?= (int) ($counts[$key] ?? 0) ?></small></a>
        <?php endforeach; ?>
    </nav>

    <?php if (!$products): ?>
        <section class="ls-card ls-empty">
            <strong><?= $status !== '' ? 'No listings with that status' : 'No listings yet' ?></strong>
            <p>Add an item with a photo and the price you want to receive. We review it and put it in the shop.</p>
            <a class="ls-btn ls-btn-primary" href="<?= e(url('/account/listings/new')) ?>">+ Add a listing</a>
        </section>
    <?php else: ?>
        <ul class="ls-list">
            <?php foreach ($products as $p):
                $pid = (int) $p['id'];
                [$label, $mod] = ListingController::STATUS_LABEL[$p['status']] ?? [ucfirst((string) $p['status']), 'hidden'];
            ?>
                <li class="ls-item">
                    <img class="ls-thumb" src="<?= e(media($p['image'])) ?>" alt="" width="64" height="86" loading="lazy">
                    <div class="ls-item-main">
                        <a class="ls-item-title" href="<?= e(url('/account/listings/' . $pid . '/edit')) ?>"><?= e($p['title']) ?></a>
                        <div class="ls-item-meta"><?= e($p['platform'] ?? 'No platform') ?> &middot; stock <?= (int) $p['stock'] ?></div>
                        <?php $tagRow = $p; $tagKinds = $kinds[$pid] ?? []; require __DIR__ . '/_tags.php'; ?>
                        <div class="ls-item-prices">
                            You receive <strong><?= e(money($p['seller_price'])) ?></strong>
                            <span class="ls-muted">&rarr; buyers pay <?= e(money($p['price'])) ?></span>
                        </div>
                        <div>
                            <span class="ls-pill ls-pill-<?= e($mod) ?>"><?= e($label) ?></span>
                            <?php if ($p['status'] === 'pending'): ?><span class="ls-hint">Not visible to buyers until we approve it.</span><?php endif; ?>
                        </div>
                    </div>
                    <div class="ls-item-actions">
                        <a class="ls-btn ls-btn-sm" href="<?= e(url('/account/listings/' . $pid . '/edit')) ?>">Edit</a>
                        <?php if ($p['status'] === 'active'): ?>
                            <form method="post" action="<?= e(url('/account/listings/' . $pid . '/withdraw')) ?>" data-confirm="Take this listing off the shop? You can relist it later.">
                                <?= csrf_field() ?><button class="ls-btn ls-btn-sm" type="submit">Withdraw</button>
                            </form>
                        <?php elseif ($p['status'] === 'hidden'): ?>
                            <form method="post" action="<?= e(url('/account/listings/' . $pid . '/relist')) ?>">
                                <?= csrf_field() ?><button class="ls-btn ls-btn-sm ls-btn-primary" type="submit">Relist</button>
                            </form>
                        <?php endif; ?>
                        <?php if ((int) $p['times_ordered'] === 0): ?>
                            <form method="post" action="<?= e(url('/account/listings/' . $pid . '/delete')) ?>" data-confirm="Delete this listing permanently?">
                                <?= csrf_field() ?><button class="ls-btn ls-btn-sm ls-btn-danger" type="submit">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <?= $pager->render() ?>
    <?php endif; ?>

    <?php require __DIR__ . '/_how.php'; ?>
</div>
<script src="<?= e(asset('js/listings.js')) ?>" defer></script>
