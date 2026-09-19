<?php
use App\Modules\Admin\Forms;

$pageTitle = 'Wallet';
$nav = 'wallet';
?>
<div class="page-head">
    <div>
        <h1>Wallet</h1>
        <p class="muted">Store credit we owe customers (1 credit = $1) and the audit trail behind it.</p>
    </div>
    <div class="actions"><a class="btn" href="<?= e(url('/admin/customers?sort=balance')) ?>">Customers by balance</a></div>
</div>

<div class="kpis kpis-small">
    <div class="kpi kpi-accent"><span class="kpi-label">Credit owed to customers</span><span class="kpi-value"><?= e(money($liability)) ?></span><span class="kpi-sub"><?= (int) $holders ?> wallet<?= $holders === 1 ? '' : 's' ?> with a balance</span></div>
    <div class="kpi"><span class="kpi-label">Credit added this month</span><span class="kpi-value"><?= e(money($flow['added'])) ?></span><span class="kpi-sub">offers, refunds, adjustments</span></div>
    <div class="kpi"><span class="kpi-label">Credit spent this month</span><span class="kpi-value"><?= e(money($flow['spent'])) ?></span><span class="kpi-sub">order payments, removals</span></div>
</div>

<section class="card" id="anonymity">
    <div class="card-head"><h2>Anonymity check</h2><a class="btn btn-sm" href="<?= e(url('/swap')) ?>" target="_blank" rel="noopener">Open the swap board &nearr;</a></div>
    <div class="card-body">
        <div class="digital-stats">
            <div><strong><?= (int) $anon['swaps'] ?></strong><span>published swap listings</span></div>
            <div><strong><?= (int) $anon['members'] ?></strong><span>live member listings</span></div>
        </div>
        <ul class="plain-list">
            <li>Members are shown by their <strong>alias</strong> only, never by real name.</li>
            <li>Real names, phones, emails and areas are visible to admins and to the account holder only.</li>
            <li>Before publishing a swap or approving a member listing, check the text for phone numbers, handles or other contact details.</li>
        </ul>
    </div>
</section>

<section class="card">
    <div class="card-head"><h2>Integrity check</h2></div>
    <div class="card-body">
        <?php if (!$problems): ?>
            <div class="alert alert-success alert-inline" role="status"><strong>Ledger matches balances.</strong> Every wallet balance equals the sum of its ledger entries.</div>
        <?php else: ?>
            <div class="alert alert-error alert-inline" role="alert"><strong><?= count($problems) ?> account<?= count($problems) === 1 ? '' : 's' ?> where the balance does not match the ledger.</strong> Do not adjust these by hand; find the cause first.</div>
            <div class="table-wrap">
                <table class="data compact">
                    <thead><tr><th>Account</th><th class="num">Cached balance</th><th class="num">Ledger sum</th><th class="num">Difference</th></tr></thead>
                    <tbody>
                    <?php foreach ($problems as $p): ?>
                        <tr>
                            <td><a href="<?= e(url('/admin/customers/' . $p['id'])) ?>"><strong><?= e($p['name']) ?></strong></a> <small class="muted">#<?= (int) $p['id'] ?></small></td>
                            <td class="num"><?= e(money($p['cached'])) ?></td>
                            <td class="num"><?= e(money($p['ledger'])) ?></td>
                            <td class="num"><strong class="text-warn"><?= Forms::signed((float) $p['cached'] - (float) $p['ledger']) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <?php if ($otherHeld > 0): ?>
            <p class="hint">Not included in the total above: <?= e(money($otherHeld)) ?> held by seller logins (from payouts paid as credit).</p>
        <?php endif; ?>
    </div>
</section>

<div class="cols-2">
    <section class="card">
        <div class="card-head"><h2>Top balances</h2></div>
        <?php if (!$top): ?>
            <div class="empty"><strong>No credit outstanding</strong></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data compact">
                    <thead><tr><th>Customer</th><th class="num">Balance</th></tr></thead>
                    <tbody>
                    <?php foreach ($top as $t): ?>
                        <tr>
                            <td><a href="<?= e(url('/admin/customers/' . $t['id'])) ?>"><strong><?= e($t['name']) ?></strong></a></td>
                            <td class="num"><strong><?= e(money($t['credit_balance'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <div class="card-head"><h2>How credit moves</h2></div>
        <div class="card-body">
            <ul class="plain-list">
                <li><strong>+ Sell offer credit</strong> when a collected request is completed as credit.</li>
                <li><strong>+ Sale payout credit</strong> when a member's payout is settled as credit.</li>
                <li><strong>+ Order refund</strong> when a cancelled order had credit applied.</li>
                <li><strong>- Order payment</strong> when a customer pays with credit at checkout.</li>
                <li><strong>+/- Admin adjustment</strong> manual corrections, always with a reason.</li>
            </ul>
            <p class="hint">The ledger is append-only: nothing is edited or deleted, corrections are new entries.</p>
        </div>
    </section>
</div>

<section class="card">
    <div class="card-head"><h2>Recent ledger entries</h2></div>
    <?php if (!$recent): ?>
        <div class="empty"><strong>No wallet activity yet</strong></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Date</th><th>Customer</th><th>Type</th><th class="num">Amount</th><th class="num">Balance after</th><th>Reference</th><th>Note</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $l): ?>
                    <tr>
                        <td class="nowrap"><?= e(date('j M Y, H:i', strtotime($l['created_at']))) ?></td>
                        <td><a href="<?= e(url('/admin/customers/' . $l['user_id'])) ?>"><strong><?= e($l['customer']) ?></strong></a></td>
                        <td><?= e(Forms::ledgerType($l['type'])) ?></td>
                        <td class="num nowrap"><strong><?= Forms::signed($l['amount']) ?></strong></td>
                        <td class="num"><?= e(money($l['balance_after'])) ?></td>
                        <td><?= Forms::ledgerRef($l['ref_type'], $l['ref_id']) ?: '<span class="muted">-</span>' ?></td>
                        <td><?= $l['note'] ? e($l['note']) : '<span class="muted">-</span>' ?><?= $l['admin_name'] ? '<br><small class="muted">by ' . e($l['admin_name']) . '</small>' : '' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= $pager->render() ?>
    <?php endif; ?>
</section>
