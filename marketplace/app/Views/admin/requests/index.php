<?php
use App\Modules\Admin\Forms;
use App\Modules\Admin\RequestController;

$pageTitle = 'Requests';
$nav = 'requests';
$here = Forms::here();
$isSwap = $tab === 'swaps';
$siteName = (string) setting('site_name', 'CyberGaming Lebanon');
$actionCount = (int) $extra['action'];
$qs = static fn (array $extra): string => '/admin/requests?' . http_build_query(array_filter(['tab' => 'buyback', 'q' => $q] + $extra, static fn ($v) => $v !== '' && $v !== null));

?>
<div class="page-head">
    <div>
        <h1>Requests</h1>
        <p class="muted">Sell and trade-in offers from customers, and game swaps.</p>
    </div>
</div>

<div class="tabs">
    <a href="<?= e(url('/admin/requests?tab=buyback')) ?>" class="<?= !$isSwap ? 'active' : '' ?>">Sell / Trade-in <?php if ($counts['buyback']): ?><span class="tab-count hot"><?= (int) $counts['buyback'] ?> to do</span><?php endif; ?></a>
    <a href="<?= e(url('/admin/requests?tab=swaps')) ?>" class="<?= $isSwap ? 'active' : '' ?>">Swaps <?php if ($counts['swaps']): ?><span class="tab-count hot"><?= (int) $counts['swaps'] ?> new</span><?php endif; ?></a>
</div>

<?php if (!$isSwap): ?>
    <div class="tabs tabs-sub">
        <a href="<?= e(url($qs([]))) ?>" class="<?= $status === '' ? 'active' : '' ?>">All</a>
        <a href="<?= e(url($qs(['status' => 'action']))) ?>" class="<?= $status === 'action' ? 'active' : '' ?>">Needs action <span class="tab-count<?= $actionCount ? ' hot' : '' ?>"><?= (int) $actionCount ?></span></a>
        <?php foreach ($statuses as $st): ?>
            <a href="<?= e(url($qs(['status' => $st]))) ?>" class="<?= $status === $st ? 'active' : '' ?>"><?= e(Forms::label($st)) ?> <span class="tab-count"><?= (int) $buybackCounts[$st] ?></span></a>
        <?php endforeach; ?>
        <a href="<?= e(url($qs(['status' => 'undecided']))) ?>" class="<?= $status === 'undecided' ? 'active' : '' ?>" title="Rejected on inspection, the customer has not decided yet">Awaiting decision <span class="tab-count"><?= (int) $extra['undecided'] ?></span></a>
        <a href="<?= e(url($qs(['status' => 'overdue']))) ?>" class="<?= $status === 'overdue' ? 'active' : '' ?>" title="Rejected, no decision, deadline passed: recycle">Overdue <span class="tab-count<?= $extra['overdue'] ? ' hot' : '' ?>"><?= (int) $extra['overdue'] ?></span></a>
    </div>
<?php endif; ?>

<form method="get" action="<?= e(url('/admin/requests')) ?>" class="card filters">
    <input type="hidden" name="tab" value="<?= e($tab) ?>">
    <?php if ($status !== '' && !$isSwap): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <?php if ($isSwap): ?>
        <div class="field">
            <label for="f-status">Status</label>
            <select id="f-status" name="status"><option value="">Any status</option><?= Forms::options(array_combine($statuses, array_map([Forms::class, 'label'], $statuses)), $status) ?></select>
        </div>
    <?php endif; ?>
    <div class="field grow-2">
        <label for="f-q">Search</label>
        <input type="search" id="f-q" name="q" value="<?= e($q) ?>" placeholder="Code, name or phone">
    </div>
    <div class="filter-actions">
        <button class="btn btn-primary" type="submit">Filter</button>
        <?php if ($status !== '' || $q !== ''): ?><a class="btn btn-ghost" href="<?= e(url('/admin/requests?tab=' . $tab)) ?>">Clear</a><?php endif; ?>
    </div>
</form>

<?php if (!$rows): ?>
    <section class="card">
        <div class="empty">
            <strong>No <?= $isSwap ? 'swap' : 'sell or trade-in' ?> requests<?= ($status !== '' || $q !== '') ? ' match' : ' yet' ?></strong>
            <p><?= $isSwap
                ? 'When customers post a game swap on the shop, it will show up here so you can list it, match it and arrange the hand-over.'
                : 'When customers ask to sell or trade in their games, their list shows up here so you can make an offer.' ?></p>
        </div>
    </section>
<?php elseif (!$isSwap): ?>
    <section class="card">
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Request</th><th>Customer</th><th class="num">Games</th><th class="num">Estimate</th><th class="num">Offer / net paid</th><th>Customer chose</th><th>Status</th><th>Next step</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): [$nextText, $todo] = RequestController::nextStep($r); $fee = (float) $r['pickup_fee']; ?>
                    <tr class="<?= $todo ? 'row-todo' : '' ?>">
                        <td class="nowrap"><a href="<?= e(url('/admin/requests/buyback/' . $r['id'])) ?>"><strong><?= e($r['code']) ?></strong></a><br><small class="muted"><?= e(date('j M Y, H:i', strtotime($r['created_at']))) ?></small></td>
                        <td>
                            <?= $r['user_id'] ? '<a href="' . e(url('/admin/customers/' . $r['user_id'])) . '">' . e($r['name']) . '</a>' : e($r['name']) . ' <span class="tag" title="Sent without an account">guest</span>' ?>
                            <br><small class="muted"><?= e(Forms::label($r['kind'])) ?> &middot; <?= e($r['zone'] ?: ($r['area'] ?? '-')) ?></small> <?= Forms::modeBadge($r['zone_mode']) ?>
                            <br><small class="muted"><?= e(RequestController::collectionLabel($r)) ?></small>
                        </td>
                        <td class="num"><?= count($r['items_list']) ?></td>
                        <td class="num"><?php if ((float) $r['estimate_cash'] > 0 || (float) $r['estimate_credit'] > 0): ?><?= e(money($r['estimate_cash'])) ?><br><small class="muted"><?= e(money($r['estimate_credit'])) ?> credit</small><?php else: ?><?= e(money($r['offered_total'])) ?><?php endif; ?></td>
                        <td class="num"><?php if ($r['final_amount'] !== null): ?><strong><?= e(money(RequestController::net((float) $r['final_amount'], $fee))) ?></strong><br><small class="muted">paid <?= e($r['final_method']) ?><?= $fee > 0 ? ' (' . e(money($r['final_amount'])) . ' &minus; ' . e(money($fee)) . ' fee)' : '' ?></small><?php elseif ($r['status'] === 'rejected' && $r['revised_amount'] !== null): ?><?= e(money($r['revised_amount'])) ?><br><small class="muted">revised offer</small><?php elseif ($r['offer_cash'] !== null || $r['offer_credit'] !== null): ?><?= e(money($r['offer_cash'] ?? 0)) ?><br><small class="muted"><?= e(money($r['offer_credit'] ?? 0)) ?> credit</small><?php else: ?><span class="muted">-</span><?php endif; ?></td>
                        <td><?= $r['accepted_method'] ? '<span class="pill pill-' . e($r['accepted_method']) . '">' . e(ucfirst($r['accepted_method'])) . '</span>' : '<span class="muted">-</span>' ?></td>
                        <td><?= Forms::pill($r['status']) ?></td>
                        <td><?= $nextText !== '' ? '<span class="' . ($todo ? 'next-todo' : 'muted') . '">' . e($nextText) . '</span>' : '<span class="muted">-</span>' ?></td>
                        <td class="num"><a class="btn btn-sm<?= $todo ? ' btn-primary' : '' ?>" href="<?= e(url('/admin/requests/buyback/' . $r['id'])) ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= $pager->render() ?>
    </section>
<?php else: ?>
    <?php foreach ($rows as $r):
        $wa = Forms::waLink($r['phone'], 'Hi ' . $r['name'] . ', this is ' . $siteName . ' about your request ' . $r['code'] . '.');
        $formAction = url('/admin/requests/swap/' . $r['id']);
    ?>
        <article class="card request">
            <div class="card-head">
                <h2><?= e($r['code']) ?> <span class="tag">Swap</span> <?= Forms::pill($r['status']) ?></h2>
                <small class="muted"><?= e(date('j M Y, H:i', strtotime($r['created_at']))) ?></small>
            </div>
            <div class="card-body request-grid">
                <div>
                    <dl class="kv">
                        <dt>Customer</dt><dd><?= e($r['name']) ?></dd>
                        <dt>Phone</dt><dd><?= $wa ? '<a href="' . e($wa) . '" target="_blank" rel="noopener">' . e($r['phone']) . '</a>' : e($r['phone']) ?></dd>
                        <dt>Area</dt><dd><?= e($r['area'] ?? '-') ?></dd>
                    </dl>
                    <?php if ($wa): ?><a class="btn btn-sm" href="<?= e($wa) ?>" target="_blank" rel="noopener">WhatsApp customer</a><?php endif; ?>
                </div>
                <div>
                    <dl class="kv">
                        <dt>Offering</dt><dd><?= e($r['offering']) ?></dd>
                        <dt>Wants</dt><dd><?= e($r['wanting']) ?></dd>
                        <dt>Platform</dt><dd><?= e($r['platform'] ?? '-') ?></dd>
                        <dt>Notes</dt><dd><?= $r['notes'] ? nl2br(e($r['notes'])) : '<span class="muted">-</span>' ?></dd>
                    </dl>
                </div>
            </div>
            <form method="post" action="<?= e($formAction) ?>" class="request-form">
                <?= csrf_field() ?>
                <input type="hidden" name="return" value="<?= e($here) ?>">
                <div class="field">
                    <label for="st-<?= (int) $r['id'] ?>">Status</label>
                    <select id="st-<?= (int) $r['id'] ?>" name="status"><?= Forms::options(array_combine($statuses, array_map([Forms::class, 'label'], $statuses)), $r['status']) ?></select>
                </div>
                <div class="field">
                    <label for="fee-<?= (int) $r['id'] ?>">Fee per side ($)</label>
                    <input type="text" inputmode="decimal" id="fee-<?= (int) $r['id'] ?>" name="fee" value="<?= e($r['fee'] ?? '') ?>" placeholder="<?= e(setting('swap_fee', 3)) ?>">
                </div>
                <div class="field board-status">
                    <label>Swap board</label>
                    <span><?= $r['is_public'] ? '<span class="pill pill-active">Published</span>' : '<span class="pill pill-hidden">Not published</span>' ?></span>
                </div>
                <button class="btn btn-primary" type="submit">Update</button>
            </form>
            <div class="request-form swap-board-actions">
                <span class="muted">Public board shows the first name and area only. The fee is charged to <strong>each</strong> of the two customers (default <?= e(money(setting('swap_fee', 3))) ?>) and is recorded automatically when the swap is marked completed.</span>
                <?php if ($r['is_public'] && $r['status'] === 'listed'): ?>
                    <form method="post" action="<?= e(url('/admin/requests/swap/' . $r['id'] . '/unpublish')) ?>" class="inline-form">
                        <?= csrf_field() ?><input type="hidden" name="return" value="<?= e($here) ?>">
                        <button class="btn" type="submit">Unpublish</button>
                    </form>
                <?php elseif (in_array($r['status'], ['new', 'listed'], true)): ?>
                    <form method="post" action="<?= e(url('/admin/requests/swap/' . $r['id'] . '/publish')) ?>" class="inline-form">
                        <?= csrf_field() ?><input type="hidden" name="return" value="<?= e($here) ?>">
                        <button class="btn btn-primary" type="submit">Publish to board</button>
                    </form>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    <?= $pager->render() ?>
<?php endif; ?>
