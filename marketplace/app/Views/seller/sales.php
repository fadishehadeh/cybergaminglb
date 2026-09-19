<?php
use App\Modules\Seller\SalesController;

/** @var array $lines @var \App\Modules\Admin\Pagination $pager */
$pageTitle = 'Sold items';
$nav = 'sales';
?>
<div class="sp-head">
    <div>
        <h1>Sold items</h1>
        <p class="sp-muted">Every item of yours that was ordered. You never see who bought it: CyberGaming handles the buyer.</p>
    </div>
</div>

<?php if (!$lines): ?>
    <section class="sp-card sp-empty">
        <strong>Nothing sold yet</strong>
        <p>When a buyer orders one of your items it shows up here, with what to do next.</p>
    </section>
<?php else: ?>
    <ul class="sp-list">
        <?php foreach ($lines as $l):
            [$label, $tone] = SalesController::STATUS[$l['status']] ?? [ucfirst(str_replace('_', ' ', (string) $l['status'])), 'info'];
            $payout = (float) $l['seller_price'] * (int) $l['qty'];
        ?>
            <li class="sp-item">
                <img class="sp-thumb" src="<?= e(media($l['image'])) ?>" alt="" width="64" height="86" loading="lazy">
                <div class="sp-item-main">
                    <span class="sp-item-title"><?= e($l['title']) ?></span>
                    <div class="sp-item-meta">Order <strong><?= e($l['code']) ?></strong> &middot; <?= e(date('j M Y, H:i', strtotime($l['created_at']))) ?> &middot; qty <?= (int) $l['qty'] ?></div>
                    <?php if ($l['item_condition'] !== null): $tagRow = $l; $tagKinds = $kinds[(int) $l['product_id']] ?? []; require base_path('app/Views/account/listings/_tags.php'); endif; ?>
                    <span class="sp-status sp-status-<?= e($tone) ?>"><?= e($label) ?></span>
                </div>
                <div class="sp-item-amount">
                    <span class="sp-kpi-label">Your payout</span>
                    <strong><?= e(money($payout)) ?></strong>
                    <?php if ($l['payout_status'] === 'paid'): ?><span class="sp-pill sp-pill-active">Paid</span>
                    <?php elseif ($l['payout_status'] === 'pending'): ?><span class="sp-pill sp-pill-pending">Scheduled</span><?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
    <?= $pager->render() ?>
<?php endif; ?>
