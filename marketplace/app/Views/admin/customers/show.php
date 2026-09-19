<?php
use App\Modules\Admin\CustomerController;
use App\Modules\Admin\Forms;
use App\Support\Phone;

$pageTitle = $customer['name'];
$nav = 'customers';
$siteName = (string) setting('site_name', 'CyberGaming Lebanon');
$wa = Forms::waLink($customer['phone'], 'Hi ' . $customer['name'] . ', this is ' . $siteName . '.');
$active = $customer['status'] === 'active';
$large = CustomerController::LARGE_ADJUSTMENT;
$isCustomer = $customer['role'] === 'customer';
?>
<div class="page-head">
    <div>
        <h1><?= e($customer['name']) ?> <?= $active ? Forms::pill('active') : Forms::pill('disabled') ?><?= $isCustomer ? '' : ' <span class="tag">' . e(ucfirst($customer['role'])) . ' login</span>' ?></h1>
        <p class="alias-line">Public alias: <span class="alias-tag alias-big"><?= e($customer['alias']) ?></span> <small class="muted">Everyone else on the site sees only this. Real identities are visible to admins only.</small></p>
        <p class="muted">Joined <?= e(date('j M Y', strtotime($customer['created_at']))) ?> &middot; last sign-in <?= $customer['last_login_at'] ? e(date('j M Y, H:i', strtotime($customer['last_login_at']))) : 'never' ?></p>
    </div>
    <div class="actions">
        <a class="btn btn-ghost" href="<?= e(url('/admin/customers')) ?>">&larr; All customers</a>
        <?php if ($wa): ?><a class="btn" href="<?= e($wa) ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
        <form method="post" action="<?= e(url('/admin/customers/' . $customer['id'] . '/status')) ?>" class="inline-form"
              <?= $active ? 'data-confirm="' . e('Suspend ' . $customer['name'] . '? They will not be able to sign in or spend credit. Their balance is kept.') . '"' : '' ?>>
            <?= csrf_field() ?><input type="hidden" name="to" value="<?= $active ? 'disabled' : 'active' ?>">
            <button class="btn <?= $active ? 'btn-danger' : 'btn-primary' ?>" type="submit"><?= $active ? 'Suspend account' : 'Reactivate account' ?></button>
        </form>
    </div>
</div>

<?php if ($tempPassword): ?>
    <div class="alert alert-warn temp-password" role="status">
        <strong>Temporary password (shown once):</strong>
        <code class="secret"><?= e($tempPassword) ?></code>
        <span class="muted">Copy it now and send it to the customer. It cannot be shown again.</span>
    </div>
<?php endif; ?>

<div class="kpis kpis-small">
    <div class="kpi kpi-accent"><span class="kpi-label">Wallet balance</span><span class="kpi-value"><?= e(money($customer['credit_balance'])) ?></span><span class="kpi-sub">1 credit = $1</span></div>
    <div class="kpi"><span class="kpi-label">Orders</span><span class="kpi-value"><?= (int) $ordersCount ?></span><span class="kpi-sub">placed while signed in</span></div>
    <div class="kpi"><span class="kpi-label">Sell requests</span><span class="kpi-value"><?= count($requests) ?></span><span class="kpi-sub">buy-back / trade-in</span></div>
    <div class="kpi"><span class="kpi-label">Member listings</span><span class="kpi-value"><?= (int) $listings['total'] ?></span><span class="kpi-sub"><?= $seller ? (int) $listings['active'] . ' live, ' . (int) $listings['pending'] . ' pending' : 'not a member seller' ?></span></div>
</div>

<div class="cols-2">
    <section class="card">
        <div class="card-head"><h2>Profile</h2></div>
        <dl class="kv">
            <dt>Public alias</dt><dd><span class="alias-tag"><?= e($customer['alias']) ?></span> <small class="muted">shown to other members</small></dd>
            <dt>Real name</dt><dd><?= e($customer['name']) ?> <small class="muted">private</small></dd>
            <dt>Email</dt><dd><?= e($customer['email']) ?></dd>
            <dt>Phone</dt><dd><?= $customer['phone'] ? ($wa ? '<a href="' . e($wa) . '" target="_blank" rel="noopener">' . e(Phone::pretty($customer['phone'])) . '</a>' : e($customer['phone'])) : '<span class="muted">-</span>' ?></dd>
            <dt>Area</dt><dd><?= e($customer['area'] ?? '-') ?></dd>
            <dt>Address</dt><dd><?= $customer['address'] ? e($customer['address']) : '<span class="muted">-</span>' ?></dd>
            <dt>Seller profile</dt><dd><?= $seller ? '<a href="' . e(url('/admin/sellers/' . $seller['id'])) . '"><strong>' . e($seller['code']) . '</strong></a> <span class="tag">' . e($seller['type']) . '</span> ' . Forms::pill($seller['status']) : '<span class="muted">none</span>' ?></dd>
        </dl>
    </section>

    <section class="card" id="wallet">
        <div class="card-head"><h2>Adjust credit</h2></div>
        <div class="card-body">
            <form method="post" action="<?= e(url('/admin/customers/' . $customer['id'] . '/adjust')) ?>" class="adjust-form" data-large="<?= e((string) $large) ?>">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <div class="field">
                        <label for="adj-amount">Amount ($)</label>
                        <input type="text" inputmode="decimal" id="adj-amount" name="amount" value="<?= e(Forms::val('amount', '')) ?>" placeholder="+10 or -5.50" required autocomplete="off">
                        <small class="hint">Positive adds credit, negative removes it. The balance can never go below $0.</small>
                    </div>
                    <div class="field">
                        <label for="adj-reason">Reason (kept in the ledger)</label>
                        <input type="text" id="adj-reason" name="reason" value="<?= e(Forms::val('reason', '')) ?>" maxlength="200" placeholder="e.g. goodwill for late delivery" required>
                    </div>
                    <div class="field span-2 adj-sure" data-adj-sure>
                        <label class="check"><input type="checkbox" name="sure" value="1"> I'm sure. Required for amounts over <?= e(money($large)) ?></label>
                    </div>
                </div>
                <p class="hint adj-hint" data-adj-hint>Current balance <strong><?= e(money($customer['credit_balance'])) ?></strong>.</p>
                <button class="btn btn-primary" type="submit">Apply adjustment</button>
            </form>
        </div>
    </section>
</div>

<section class="card">
    <div class="card-head"><h2>Wallet ledger</h2><small class="muted"><?= (int) $ledgerTotal ?> entr<?= $ledgerTotal === 1 ? 'y' : 'ies' ?>, newest first</small></div>
    <?php if (!$ledger): ?>
        <div class="empty"><strong>No wallet activity yet</strong><p>Credit from sell offers, refunds and adjustments will be listed here.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Date</th><th>Type</th><th class="num">Amount</th><th class="num">Balance after</th><th>Reference</th><th>Note</th></tr></thead>
                <tbody>
                <?php foreach ($ledger as $l): ?>
                    <tr>
                        <td class="nowrap"><?= e(date('j M Y, H:i', strtotime($l['created_at']))) ?></td>
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

<div class="cols-2">
    <section class="card">
        <div class="card-head"><h2>Orders</h2><small class="muted"><?= (int) $ordersCount ?> total</small></div>
        <?php if (!$orders): ?>
            <div class="empty"><strong>No orders</strong></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data compact">
                    <thead><tr><th>Order</th><th class="num">Total</th><th class="num">Credit used</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><a href="<?= e(url('/admin/orders/' . $o['id'])) ?>"><strong><?= e($o['code']) ?></strong></a><br><small class="muted"><?= e(date('j M Y', strtotime($o['created_at']))) ?></small></td>
                            <td class="num"><?= e(money($o['grand'])) ?></td>
                            <td class="num"><?= (float) $o['credit_used'] > 0 ? e(money($o['credit_used'])) : '<span class="muted">-</span>' ?></td>
                            <td><?= Forms::pill($o['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <div class="card-head"><h2>Sell requests &amp; offers</h2></div>
        <?php if (!$requests): ?>
            <div class="empty"><strong>No sell requests</strong></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data compact">
                    <thead><tr><th>Request</th><th class="num">Offer</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td><a href="<?= e(url('/admin/requests/buyback/' . $r['id'])) ?>"><strong><?= e($r['code']) ?></strong></a> <span class="tag"><?= e(Forms::label($r['kind'])) ?></span><br><small class="muted"><?= e(date('j M Y', strtotime($r['created_at']))) ?></small></td>
                            <td class="num">
                                <?php if ($r['final_amount'] !== null): ?>
                                    <strong><?= e(money($r['final_amount'])) ?></strong> <small class="muted"><?= e($r['final_method']) ?></small>
                                <?php elseif ($r['offer_cash'] !== null || $r['offer_credit'] !== null): ?>
                                    <?= e(money($r['offer_cash'] ?? 0)) ?> cash<br><small class="muted"><?= e(money($r['offer_credit'] ?? 0)) ?> credit</small>
                                <?php else: ?>
                                    <span class="muted">est. <?= e(money($r['offered_total'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= Forms::pill($r['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<section class="card" id="security">
    <div class="card-head"><h2>Reset password</h2></div>
    <div class="card-body">
        <p class="muted">Set a temporary password for a customer who is locked out. It is shown once after saving; ask them to change it when they sign in.</p>
        <form method="post" action="<?= e(url('/admin/customers/' . $customer['id'] . '/password')) ?>" class="inline-fields" autocomplete="off" data-confirm="Replace <?= e($customer['name']) ?>'s password? Their current password stops working.">
            <?= csrf_field() ?>
            <div class="field">
                <label for="new_password">Temporary password (10+ characters)</label>
                <input type="text" id="new_password" name="new_password" minlength="10" maxlength="200" required autocomplete="off">
            </div>
            <button class="btn" type="submit">Reset password</button>
        </form>
    </div>
</section>
