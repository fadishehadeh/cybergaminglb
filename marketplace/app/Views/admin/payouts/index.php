<?php
use App\Modules\Admin\Forms;

$pageTitle = 'Payouts';
$nav = 'payouts';
?>
<div class="page-head">
    <div>
        <h1>Payouts</h1>
        <p class="muted">What you owe sellers for delivered orders. Pay them, then mark it paid here.</p>
    </div>
</div>

<div class="kpis kpis-small">
    <div class="kpi <?= $owed > 0 ? 'kpi-alert' : '' ?>"><span class="kpi-label">Total owed</span><span class="kpi-value"><?= e(money($owed)) ?></span><span class="kpi-sub"><?= count($balances) ?> seller<?= count($balances) === 1 ? '' : 's' ?></span></div>
    <div class="kpi"><span class="kpi-label">Paid out (all time)</span><span class="kpi-value"><?= e(money($paidTotal)) ?></span><span class="kpi-sub"><?= e(money($paidCredit)) ?> of it as wallet credit</span></div>
</div>

<section class="card">
    <div class="card-head"><h2>Pending balances</h2></div>
    <?php if (!$balances): ?>
        <div class="empty"><strong>Nothing to pay right now</strong><p>Sellers appear here once an order containing their item is marked delivered.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Seller</th><th>Send to</th><th class="num">Balance</th><th>Mark as paid</th></tr></thead>
                <tbody>
                <?php foreach ($balances as $b): ?>
                    <tr>
                        <td>
                            <a href="<?= e(url('/admin/sellers/' . $b['id'])) ?>"><strong><?= e($b['code']) ?></strong></a> &middot; <?= e($b['name']) ?> <span class="tag"><?= e($b['type']) ?></span><br>
                            <small class="muted"><?= (int) $b['line_count'] ?> item line<?= (int) $b['line_count'] === 1 ? '' : 's' ?> &middot; since <?= e(date('j M Y', strtotime($b['oldest']))) ?></small>
                            <details class="lines"><summary>Show lines</summary>
                                <ul>
                                    <?php foreach ($lines[$b['id']] ?? [] as $ln): ?>
                                        <li><?= $ln['order_id'] ? '<a href="' . e(url('/admin/orders/' . $ln['order_id'])) . '">' . e($ln['order_code']) . '</a>' : '-' ?> &middot; <?= e($ln['title'] ?? 'item') ?> <span class="muted">x<?= (int) ($ln['qty'] ?? 1) ?></span> <strong><?= e(money($ln['amount'])) ?></strong></li>
                                    <?php endforeach; ?>
                                </ul>
                            </details>
                        </td>
                        <td>
                            <strong><?= e($b['payout_method'] ?? 'No method set') ?></strong><br>
                            <?php $wa = Forms::waLink($b['phone']); ?>
                            <?= $wa ? '<a href="' . e($wa) . '" target="_blank" rel="noopener">' . e($b['phone']) . '</a>' : e($b['phone'] ?? '-') ?>
                            <?= $b['area'] ? '<br><small class="muted">' . e($b['area']) . '</small>' : '' ?>
                        </td>
                        <td class="num"><strong class="big"><?= e(money($b['balance'])) ?></strong></td>
                        <td>
                            <form method="post" action="<?= e(url('/admin/payouts/' . $b['id'] . '/pay')) ?>" class="pay-form" data-confirm="Mark <?= e(money($b['balance'])) ?> as paid to <?= e($b['code']) ?>? If you chose credit it is added to their wallet straight away.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="expected" value="<?= e(number_format((float) $b['balance'], 2, '.', '')) ?>">
                                <select name="method" aria-label="Payment method">
                                    <option value="cash" <?= $b['type'] === 'member' && $b['credit_ok'] ? '' : 'selected' ?>>Cash</option>
                                    <?php if ($b['credit_ok']): ?><option value="credit" <?= $b['type'] === 'member' ? 'selected' : '' ?>>Wallet credit</option><?php endif; ?>
                                </select>
                                <input type="text" name="note" maxlength="255" placeholder="e.g. OMT ref 1234" aria-label="Payment note">
                                <button class="btn btn-primary" type="submit">Mark paid</button>
                            </form>
                            <?php if (!$b['credit_ok']): ?><small class="muted">Credit needs a linked customer account.</small><?php else: ?><small class="muted">Credit goes to <?= e($b['user_name']) ?>.</small><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card">
    <div class="card-head"><h2>Payment history</h2></div>
    <?php if (!$history): ?>
        <div class="empty"><strong>No payments recorded yet</strong></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Paid on</th><th>Seller</th><th class="num">Amount</th><th>Method</th><th class="num">Lines</th><th>Note</th></tr></thead>
                <tbody>
                <?php foreach ($history as $h): ?>
                    <tr>
                        <td class="nowrap"><?= e(date('j M Y, H:i', strtotime($h['paid_at']))) ?></td>
                        <td><a href="<?= e(url('/admin/sellers/' . $h['seller_id'])) ?>"><strong><?= e($h['code']) ?></strong></a> &middot; <?= e($h['name']) ?></td>
                        <td class="num"><strong><?= e(money($h['amount'])) ?></strong></td>
                        <td><?= $h['method'] ? '<span class="pill pill-' . e($h['method']) . '">' . e(ucfirst($h['method'])) . '</span>' : '<span class="muted">-</span>' ?></td>
                        <td class="num"><?= (int) $h['line_count'] ?></td>
                        <td><?= $h['note'] ? e($h['note']) : '<span class="muted">-</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= $pager->render() ?>
    <?php endif; ?>
</section>
