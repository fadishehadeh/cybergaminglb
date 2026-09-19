<?php
use App\Modules\Admin\Forms;
use App\Support\Phone;

$pageTitle = 'Customers';
$nav = 'customers';
$sorts = ['joined' => 'Newest first', 'oldest' => 'Oldest first', 'balance' => 'Highest credit', 'name' => 'Name A-Z'];
?>
<div class="page-head">
    <div>
        <h1>Customers</h1>
        <p class="muted">Accounts with a credit wallet. Real identities are visible to admins only: everyone else sees just the alias.</p>
    </div>
    <div class="actions"><a class="btn" href="<?= e(url('/admin/wallet')) ?>">Wallet audit</a></div>
</div>

<div class="kpis kpis-small">
    <div class="kpi"><span class="kpi-label">Customers</span><span class="kpi-value"><?= (int) $summary['n'] ?></span><span class="kpi-sub">registered accounts</span></div>
    <a class="kpi" href="<?= e(url('/admin/wallet')) ?>"><span class="kpi-label">Credit owed</span><span class="kpi-value"><?= e(money($summary['credit'])) ?></span><span class="kpi-sub">across all wallets</span></a>
</div>

<form method="get" action="<?= e(url('/admin/customers')) ?>" class="card filters">
    <div class="field grow-2">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Alias, name, phone or email">
    </div>
    <div class="field">
        <label for="f-sort">Sort by</label>
        <select id="f-sort" name="sort"><?= Forms::options($sorts, $sort) ?></select>
    </div>
    <div class="field">
        <label for="f-status">Status</label>
        <select id="f-status" name="status"><option value="">Any status</option><?= Forms::options(['active' => 'Active', 'disabled' => 'Suspended'], $status) ?></select>
    </div>
    <div class="filter-actions">
        <button class="btn btn-primary" type="submit">Filter</button>
        <?php if ($q !== '' || $status !== '' || $sort !== 'joined'): ?><a class="btn btn-ghost" href="<?= e(url('/admin/customers')) ?>">Clear</a><?php endif; ?>
    </div>
</form>

<section class="card">
    <?php if (!$customers): ?>
        <div class="empty">
            <strong><?= ($q !== '' || $status !== '') ? 'No customers match' : 'No customers yet' ?></strong>
            <p><?= ($q !== '' || $status !== '') ? 'Try a different search or filter.' : 'Customers appear here once they create an account on the shop.' ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Alias (public)</th><th>Real name (private)</th><th>Phone</th><th>Area</th><th class="num">Credit</th><th class="num">Orders</th><th class="num">Sell requests</th><th>Joined</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($customers as $c): $wa = Forms::waLink($c['phone']); ?>
                    <tr>
                        <td><a href="<?= e(url('/admin/customers/' . $c['id'])) ?>" class="alias-tag"><?= e($c['alias']) ?></a></td>
                        <td><strong><?= e($c['name']) ?></strong><br><small class="muted"><?= e($c['email']) ?></small></td>
                        <td class="nowrap"><?php $pretty = Phone::pretty($c['phone']); ?><?= $wa ? '<a href="' . e($wa) . '" target="_blank" rel="noopener">' . e($pretty) . '</a>' : '<span class="muted">-</span>' ?></td>
                        <td><?= e($c['area'] ?? '-') ?></td>
                        <td class="num"><?= (float) $c['credit_balance'] > 0 ? '<strong>' . e(money($c['credit_balance'])) . '</strong>' : '<span class="muted">$0</span>' ?></td>
                        <td class="num"><?= (int) $c['orders_count'] ?></td>
                        <td class="num"><?= (int) $c['requests_count'] ?></td>
                        <td class="nowrap"><?= e(date('j M Y', strtotime($c['created_at']))) ?></td>
                        <td><?= $c['status'] === 'active' ? Forms::pill('active') : Forms::pill('disabled') ?></td>
                        <td class="num"><a class="btn btn-sm" href="<?= e(url('/admin/customers/' . $c['id'])) ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= $pager->render() ?>
    <?php endif; ?>
</section>
