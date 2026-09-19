<?php
use App\Modules\Admin\Forms;

$pageTitle = 'Sellers';
$nav = 'sellers';
$here = Forms::here();
?>
<div class="page-head">
    <div>
        <h1>Sellers</h1>
        <p class="muted">Private directory. Buyers only ever see anonymous codes, never these details.</p>
    </div>
    <div class="actions"><a class="btn btn-primary" href="<?= e(url('/admin/sellers/create')) ?>">+ Add seller</a></div>
</div>

<?php if ($counts['pending'] > 0 && $status !== 'pending'): ?>
    <div class="alert alert-warn"><strong><?= (int) $counts['pending'] ?> seller application<?= $counts['pending'] === 1 ? '' : 's' ?> waiting for review.</strong> <a href="<?= e(url('/admin/sellers?status=pending')) ?>">Review applications</a></div>
<?php endif; ?>

<div class="tabs">
    <a href="<?= e(url('/admin/sellers' . ($type !== '' ? '?type=' . $type : ''))) ?>" class="<?= $status === '' ? 'active' : '' ?>">All</a>
    <a href="<?= e(url('/admin/sellers?status=pending' . ($type !== '' ? '&type=' . $type : ''))) ?>" class="<?= $status === 'pending' ? 'active' : '' ?>">Applications <?php if ($counts['pending']): ?><span class="tab-count hot"><?= (int) $counts['pending'] ?> new</span><?php endif; ?></a>
    <a href="<?= e(url('/admin/sellers?status=active' . ($type !== '' ? '&type=' . $type : ''))) ?>" class="<?= $status === 'active' ? 'active' : '' ?>">Active <span class="tab-count"><?= (int) $counts['active'] ?></span></a>
    <a href="<?= e(url('/admin/sellers?status=suspended' . ($type !== '' ? '&type=' . $type : ''))) ?>" class="<?= $status === 'suspended' ? 'active' : '' ?>">Suspended <span class="tab-count"><?= (int) $counts['suspended'] ?></span></a>
</div>

<div class="tabs tabs-sub">
    <?php $tq = static fn (string $t, bool $withQ = true): string => '/admin/sellers' . (http_build_query(array_filter(['status' => $status, 'type' => $t, 'q' => $withQ ? $q : ''])) ? '?' . http_build_query(array_filter(['status' => $status, 'type' => $t, 'q' => $withQ ? $q : ''])) : ''); ?>
    <a href="<?= e(url($tq(''))) ?>" class="<?= $type === '' ? 'active' : '' ?>">All types</a>
    <a href="<?= e(url($tq('store'))) ?>" class="<?= $type === 'store' ? 'active' : '' ?>">Stores <span class="tab-count"><?= (int) $typeCounts['store'] ?></span></a>
    <a href="<?= e(url($tq('member'))) ?>" class="<?= $type === 'member' ? 'active' : '' ?>">Members <span class="tab-count"><?= (int) $typeCounts['member'] ?></span></a>
</div>

<form method="get" action="<?= e(url('/admin/sellers')) ?>" class="card filters">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <?php if ($type !== ''): ?><input type="hidden" name="type" value="<?= e($type) ?>"><?php endif; ?>
    <div class="field grow-2">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Code, name, phone, area or linked customer">
    </div>
    <div class="filter-actions">
        <button class="btn btn-primary" type="submit">Search</button>
        <?php if ($q !== ''): ?><a class="btn btn-ghost" href="<?= e(url($tq($type, false))) ?>">Clear</a><?php endif; ?>
    </div>
</form>

<section class="card">
    <?php if (!$sellers): ?>
        <div class="empty">
            <strong><?= $q !== '' || $status !== '' ? 'No sellers match your filters' : 'No sellers yet' ?></strong>
            <p>Add the stores and individuals who list items with you. You earn a commission on each sale.</p>
            <a class="btn btn-primary" href="<?= e(url('/admin/sellers/create')) ?>">+ Add seller</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Code</th><th>Type</th><th>Name</th><th>Phone</th><th>Area</th><th>Commission</th><th class="num">Active listings</th><th class="num">Sales (their share)</th><th class="num">Payout balance</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($sellers as $s): ?>
                    <tr>
                        <td class="nowrap"><a href="<?= e(url('/admin/sellers/' . $s['id'])) ?>"><strong><?= e($s['code']) ?></strong></a></td>
                        <td><span class="tag <?= $s['type'] === 'member' ? 'tag-member' : '' ?>"><?= $s['type'] === 'member' ? 'Member' : 'Store' ?></span></td>
                        <td><?= e($s['name']) ?><?php if ($s['user_id'] && $s['type'] === 'member'): ?><br><small>Customer: <a href="<?= e(url('/admin/customers/' . $s['user_id'])) ?>"><?= e($s['user_name'] ?? 'account') ?></a></small><?php elseif ($s['user_id']): ?> <span class="tag" title="Has a portal login">login</span><?php endif; ?>
                            <?php if ($s['notes'] && ($s['status'] === 'pending' || $status === 'pending')): ?><br><small class="muted"><?= nl2br(e(mb_strimwidth((string) $s['notes'], 0, 400, '...'))) ?></small><?php endif; ?></td>
                        <td class="nowrap"><?php $wa = Forms::waLink($s['phone']); ?><?= $wa ? '<a href="' . e($wa) . '" target="_blank" rel="noopener">' . e($s['phone']) . '</a>' : e($s['phone'] ?? '-') ?></td>
                        <td><?= e($s['area'] ?? '-') ?></td>
                        <td><?= $s['commission_pct'] !== null ? '<strong>' . e(Forms::pct($s['commission_pct'])) . '</strong>' : '<span class="muted">default ' . e(Forms::pct($s['type'] === 'member' ? $memberDefault : $default)) . '</span>' ?></td>
                        <td class="num"><?= (int) $s['active_listings'] ?><?= $s['pending_listings'] ? ' <small class="muted">(+' . (int) $s['pending_listings'] . ' pending)</small>' : '' ?></td>
                        <td class="num"><?= e(money($s['sales_total'])) ?></td>
                        <td class="num"><?= (float) $s['balance'] > 0 ? '<strong class="text-warn">' . e(money($s['balance'])) . '</strong>' : '<span class="muted">$0</span>' ?></td>
                        <td><?= Forms::pill($s['status']) ?></td>
                        <td class="num"><div class="row-actions">
                            <?php if ($s['status'] !== 'active'): ?>
                                <form method="post" action="<?= e(url('/admin/sellers/' . $s['id'] . '/approve')) ?>" class="inline-form">
                                    <?= csrf_field() ?><input type="hidden" name="return" value="<?= e($here) ?>">
                                    <button class="btn btn-sm btn-primary" type="submit"><?= $s['status'] === 'pending' ? 'Approve' : 'Re-activate' ?></button>
                                </form>
                            <?php endif; ?>
                            <?php if ($s['status'] !== 'suspended'): ?>
                                <form method="post" action="<?= e(url('/admin/sellers/' . $s['id'] . '/suspend')) ?>" class="inline-form" data-confirm="<?= e(($s['status'] === 'pending' ? 'Reject the application from ' : 'Suspend ') . $s['name'] . '? They will not be able to sign in.') ?>">
                                    <?= csrf_field() ?><input type="hidden" name="return" value="<?= e($here) ?>">
                                    <button class="btn btn-sm btn-danger" type="submit"><?= $s['status'] === 'pending' ? 'Reject' : 'Suspend' ?></button>
                                </form>
                            <?php endif; ?>
                            <a class="btn btn-sm" href="<?= e(url('/admin/sellers/' . $s['id'])) ?>">View</a> <a class="btn btn-sm" href="<?= e(url('/admin/sellers/' . $s['id'] . '/edit')) ?>">Edit</a>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= $pager->render() ?>
    <?php endif; ?>
</section>
