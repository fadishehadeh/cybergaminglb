<?php
use App\Modules\Account\SalesController;

/** @var array|null $seller @var array $lines @var \App\Modules\Admin\Pagination|null $pager */
$meta = ['title' => 'Sold items | CyberGaming', 'description' => 'Items sold from your listings and your payouts.', 'noindex' => true];
$accountNav = 'sales';
$hub = trim((string) setting('hub_address', ''));
$hub = ($hub === '' || strcasecmp($hub, 'Lebanon') === 0) ? '' : $hub;
$fmtDate = static fn (?string $d): string => $d && strtotime($d) ? date('j M Y', strtotime($d)) : '-';
?>
<?php require base_path('app/Views/account/_nav.php'); ?>
<link rel="stylesheet" href="<?= e(asset('css/listings.css')) ?>">
<?php $kinds = $kinds ?? []; ?>
<div class="container ls-page">

    <div class="ls-head">
        <div>
            <h1>Sold items</h1>
            <p class="ls-muted">Every item of yours that was ordered. You never see who bought it: CyberGaming handles the buyer.</p>
        </div>
        <a class="ls-btn" href="<?= e(url('/account/listings')) ?>">My listings</a>
    </div>

    <?php if ($seller === null): ?>
        <section class="ls-card ls-empty">
            <strong>You are not selling yet</strong>
            <p>List an item for other members and your sales and payouts will show up here.</p>
            <a class="ls-btn ls-btn-primary" href="<?= e(url('/account/listings/new')) ?>">Start selling</a>
        </section>
    <?php else: ?>
        <div class="ls-kpis">
            <div class="ls-kpi"><span class="ls-kpi-label">On its way to you</span><span class="ls-kpi-value"><?= e(money($inProgress)) ?></span><span class="ls-kpi-sub">sold, not delivered yet</span></div>
            <div class="ls-kpi<?= $balance > 0 ? ' ls-kpi-warn' : '' ?>"><span class="ls-kpi-label">Pending payout</span><span class="ls-kpi-value"><?= e(money($balance)) ?></span><span class="ls-kpi-sub">delivered, not paid yet</span></div>
            <div class="ls-kpi"><span class="ls-kpi-label">Paid so far</span><span class="ls-kpi-value"><?= e(money($paidTotal)) ?></span><span class="ls-kpi-sub">all time</span></div>
        </div>

        <?php if (!$lines): ?>
            <section class="ls-card ls-empty">
                <strong>Nothing sold yet</strong>
                <p>When a buyer orders one of your items it shows up here, with what to do next.</p>
                <a class="ls-btn ls-btn-primary" href="<?= e(url('/account/listings')) ?>">See my listings</a>
            </section>
        <?php else: ?>
            <ul class="ls-list">
                <?php foreach ($lines as $l):
                    [$label, $tone] = SalesController::STATUS[$l['status']] ?? [ucfirst(str_replace('_', ' ', (string) $l['status'])), 'info'];
                    if ($l['status'] === 'delivered' && $l['payout_status'] === 'paid') {
                        $label = 'Delivered and paid';
                    }
                    $amount = (float) $l['seller_price'] * (int) $l['qty'];
                ?>
                    <li class="ls-item">
                        <img class="ls-thumb" src="<?= e(media($l['image'])) ?>" alt="" width="64" height="86" loading="lazy">
                        <div class="ls-item-main">
                            <span class="ls-item-title"><?= e($l['title']) ?></span>
                            <div class="ls-item-meta">Order <strong><?= e($l['code']) ?></strong> &middot; <?= e(date('j M Y, H:i', strtotime((string) $l['created_at']))) ?> &middot; qty <?= (int) $l['qty'] ?></div>
                            <?php if ($l['item_condition'] !== null): $tagRow = $l; $tagKinds = $kinds[(int) $l['product_id']] ?? []; require base_path('app/Views/account/listings/_tags.php'); endif; ?>
                            <span class="ls-status ls-status-<?= e($tone) ?>"><?= e($label) ?></span>
                            <?php if ($l['status'] === 'confirmed' && $hub !== ''): ?><div class="ls-hint">Our hub: <?= e($hub) ?></div><?php endif; ?>
                        </div>
                        <div class="ls-item-amount">
                            <span class="ls-kpi-label">You get</span>
                            <strong><?= e(money($amount)) ?></strong>
                            <?php if ($l['payout_status'] === 'paid'): ?><span class="ls-pill ls-pill-active">Paid</span>
                            <?php elseif ($l['payout_status'] === 'pending'): ?><span class="ls-pill ls-pill-pending">Payout coming</span><?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?= $pager->render() ?>
        <?php endif; ?>

        <section class="ls-card" id="payouts">
            <h2>Your payouts</h2>
            <p class="ls-muted">After an order is delivered, you get the price you entered, in cash or added to your wallet credit. We choose which when we pay.</p>

            <h3>Waiting to be paid</h3>
            <?php if (!$pending): ?>
                <p class="ls-muted">Nothing pending right now.</p>
            <?php else: ?>
                <ul class="ls-rows">
                    <?php foreach ($pending as $r): ?>
                        <li>
                            <div>
                                <strong><?= e($r['title'] ?? 'Item') ?><?= (int) ($r['qty'] ?? 1) > 1 ? ' &times; ' . (int) $r['qty'] : '' ?></strong>
                                <span class="ls-muted">Order <?= e($r['order_code'] ?? '-') ?> &middot; delivered <?= e($fmtDate($r['created_at'])) ?></span>
                            </div>
                            <strong class="ls-amt"><?= e(money($r['amount'])) ?></strong>
                        </li>
                    <?php endforeach; ?>
                    <li class="ls-rows-total"><span>Total pending</span><strong class="ls-amt"><?= e(money($balance)) ?></strong></li>
                </ul>
            <?php endif; ?>

            <h3>Payment history</h3>
            <?php if (!$paid): ?>
                <p class="ls-muted">No payments yet.</p>
            <?php else: ?>
                <ul class="ls-rows">
                    <?php foreach ($paid as $r):
                        $how = $r['method'] === 'cash' ? 'Paid in cash' : ($r['method'] === 'credit' ? 'Added to your wallet credit' : 'Paid');
                    ?>
                        <li>
                            <div>
                                <strong><?= e($r['title'] ?? 'Item') ?></strong>
                                <span class="ls-muted">Order <?= e($r['order_code'] ?? '-') ?> &middot; <?= e($fmtDate($r['paid_at'])) ?></span>
                                <span class="ls-how-paid"><?= e($how) ?></span>
                            </div>
                            <strong class="ls-amt"><?= e(money($r['amount'])) ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
