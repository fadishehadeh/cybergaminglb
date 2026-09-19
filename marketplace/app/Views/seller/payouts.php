<?php
/** @var array $seller @var array $pending @var array $paid @var \App\Modules\Admin\Pagination $pager @var float $balance @var float $paidTotal */
$pageTitle = 'Payouts';
$nav = 'payouts';
?>
<div class="sp-head">
    <div>
        <h1>Payouts</h1>
        <p class="sp-muted">A payout is scheduled for each item once its order is delivered. We pay you by <?= $seller['payout_method'] ? '<strong>' . e($seller['payout_method']) . '</strong>' : 'the method we agreed' ?>. <a href="<?= e(url('/seller/account')) ?>">Change payout method</a></p>
    </div>
</div>

<div class="sp-kpis sp-kpis-2">
    <div class="sp-kpi <?= $balance > 0 ? 'sp-kpi-warn' : '' ?>"><span class="sp-kpi-label">Pending balance</span><span class="sp-kpi-value"><?= e(money($balance)) ?></span><span class="sp-kpi-sub">delivered, not paid yet</span></div>
    <div class="sp-kpi"><span class="sp-kpi-label">Paid so far</span><span class="sp-kpi-value"><?= e(money($paidTotal)) ?></span><span class="sp-kpi-sub">all time</span></div>
</div>

<section class="sp-card">
    <h2>Waiting to be paid</h2>
    <?php if (!$pending): ?>
        <p class="sp-muted">Nothing pending right now.</p>
    <?php else: ?>
        <div class="sp-table-wrap"><table class="sp-table">
            <thead><tr><th>Item</th><th>Order</th><th>Scheduled</th><th class="num">Amount</th></tr></thead>
            <tbody>
            <?php foreach ($pending as $r): ?>
                <tr>
                    <td><?= e($r['title'] ?? 'Item') ?><?= (int) ($r['qty'] ?? 1) > 1 ? ' &times; ' . (int) $r['qty'] : '' ?></td>
                    <td><?= e($r['order_code'] ?? '-') ?></td>
                    <td><?= e(date('j M Y', strtotime($r['created_at']))) ?></td>
                    <td class="num"><strong><?= e(money($r['amount'])) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr><td colspan="3">Total pending</td><td class="num"><strong><?= e(money($balance)) ?></strong></td></tr></tfoot>
        </table></div>
    <?php endif; ?>
</section>

<section class="sp-card">
    <h2>Payment history</h2>
    <?php if (!$paid): ?>
        <p class="sp-muted">No payments yet.</p>
    <?php else: ?>
        <div class="sp-table-wrap"><table class="sp-table">
            <thead><tr><th>Paid on</th><th>Item</th><th>Order</th><th>Note</th><th class="num">Amount</th></tr></thead>
            <tbody>
            <?php foreach ($paid as $r): ?>
                <tr>
                    <td><?= $r['paid_at'] ? e(date('j M Y', strtotime($r['paid_at']))) : '-' ?></td>
                    <td><?= e($r['title'] ?? 'Item') ?></td>
                    <td><?= e($r['order_code'] ?? '-') ?></td>
                    <td><?= e($r['note'] ?? '') ?></td>
                    <td class="num"><strong><?= e(money($r['amount'])) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?= $pager->render() ?>
    <?php endif; ?>
</section>
