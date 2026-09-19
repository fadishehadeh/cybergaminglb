<?php
use App\Modules\Admin\Forms;
use App\Support\Phone;

$pageTitle = 'Request ' . $r['code'];
$nav = 'requests';
$siteName = (string) setting('site_name', 'CyberGaming Lebanon');
$status = $r['status'];
$phone = $customer['phone'] ?? $r['phone'];
$wa = Forms::waLink($phone, 'Hi ' . $r['name'] . ', this is ' . $siteName . ' about your sell request ' . $r['code'] . '.');
$isGuest = $r['user_id'] === null;
$id = (int) $r['id'];
$post = static fn (string $action): string => url('/admin/requests/buyback/' . $id . '/' . $action);
$hasOffer = $r['offer_cash'] !== null || $r['offer_credit'] !== null;
$estimateShown = (float) $r['estimate_cash'] > 0 || (float) $r['estimate_credit'] > 0;

// Complete-form defaults: the offer for the method the customer accepted (cash for guests).
$method = $r['accepted_method'] ?: ($r['preferred_method'] ?: 'credit');
if ($isGuest) {
    $method = 'cash';
}
$offerFor = static fn (string $m): float => (float) ($m === 'credit' ? $r['offer_credit'] : $r['offer_cash']);
$fMethod = Forms::val('final_method', $method);
$fAmount = Forms::val('final_amount', number_format($offerFor($fMethod), 2, '.', ''));

$timeline = [
    ['Request sent', $r['created_at']],
    ['Offer made', $r['offered_at']],
    ['Customer accepted' . ($r['accepted_method'] ? ' (' . $r['accepted_method'] . ')' : ''), $r['accepted_at']],
    ['Games collected', $r['collected_at']],
    ['Completed' . ($r['final_method'] ? ' (paid ' . $r['final_method'] . ')' : ''), $r['completed_at']],
];
$closed = in_array($status, ['declined', 'cancelled'], true);
$photoCount = count($photos);
$photoLabels = ['disc' => 'Disc', 'box_outside' => 'Box, outside', 'box_inside' => 'Box, inside', 'extra' => 'Photo'];
$incLabels = ['box' => 'Box', 'cover' => 'Cover art', 'manual' => 'Manual'];
?>
<div class="page-head">
    <div>
        <h1>Request <?= e($r['code']) ?> <?= Forms::pill($status) ?> <span class="tag"><?= e(Forms::label($r['kind'])) ?></span></h1>
        <p class="muted">Sent <?= e(date('j M Y, H:i', strtotime($r['created_at']))) ?></p>
    </div>
    <div class="actions"><a class="btn btn-ghost" href="<?= e(url('/admin/requests?tab=buyback')) ?>">&larr; All requests</a></div>
</div>

<!-- Action panel -->
<section class="card action-panel status-<?= e($status) ?>">
    <div class="card-head"><h2>
        <?php if (in_array($status, ['new', 'contacted'], true)): ?>Next: make an offer
        <?php elseif ($status === 'offered'): ?>Waiting for the customer to accept
        <?php elseif ($status === 'accepted'): ?>Next: collect the games
        <?php elseif ($status === 'collected'): ?>Next: inspect the games and pay the customer
        <?php elseif ($status === 'completed'): ?>Completed
        <?php else: ?>This request is <?= e(strtolower(Forms::label($status))) ?>
        <?php endif; ?>
    </h2></div>
    <div class="card-body">

    <?php if (in_array($status, ['new', 'contacted', 'offered'], true)): ?>
        <?php if ($status === 'offered'): ?>
            <p>Offer sent <?= $r['offered_at'] ? e(date('j M, H:i', strtotime($r['offered_at']))) : '' ?>: <strong><?= e(money($r['offer_cash'])) ?> cash</strong> or <strong><?= e(money($r['offer_credit'])) ?> credit</strong>. The customer picks one online. You can revise the offer below; they will then see the new amounts.</p>
        <?php else: ?>
            <p class="muted">Check the games below, then set what you will pay. The amounts start from the instant estimate<?= $estimateShown ? '' : ' (this older request has one total only)' ?>. Credit is normally worth more than cash to encourage customers to keep money in the shop.</p>
        <?php endif; ?>
        <p class="photo-reminder"><strong>Photos received: <?= $photoCount ?></strong>
            <?php if ($photoCount > 0): ?><a href="#photos">View the photos</a><?php else: ?><span class="muted">The customer did not send any, so the condition is unchecked.</span><?php endif; ?></p>
        <form method="post" action="<?= e($post('offer')) ?>" class="offer-form" data-offer-form>
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="field">
                    <label for="offer_cash">Cash offer ($)</label>
                    <input type="text" inputmode="decimal" id="offer_cash" name="offer_cash" value="<?= e(Forms::val('offer_cash', number_format($defaults['cash'], 2, '.', ''))) ?>" required data-offer-cash>
                    <small class="hint">Paid in hand when the games are collected and inspected. Estimate: <?= e(money($r['estimate_cash'])) ?>.</small>
                </div>
                <div class="field">
                    <label for="offer_credit">Credit offer ($)</label>
                    <input type="text" inputmode="decimal" id="offer_credit" name="offer_credit" value="<?= e(Forms::val('offer_credit', number_format($defaults['credit'], 2, '.', ''))) ?>" required data-offer-credit>
                    <small class="hint">Added to the customer's wallet. Estimate: <?= e(money($r['estimate_credit'])) ?>.</small>
                </div>
            </div>
            <div class="alert alert-warn alert-inline" data-offer-warn hidden>Credit is lower than cash. Credit should normally be worth the same or more than cash. Check the amounts.</div>
            <div class="status-actions">
                <button class="btn btn-primary" type="submit"><?= $status === 'offered' ? 'Revise offer' : 'Send offer to customer' ?></button>
            </div>
        </form>
        <div class="status-actions secondary-actions">
            <?php if ($status === 'new'): ?>
                <form method="post" action="<?= e($post('contact')) ?>" class="inline-form"><?= csrf_field() ?><button class="btn" type="submit">Mark as contacted</button></form>
            <?php endif; ?>
            <form method="post" action="<?= e($post('decline')) ?>" class="inline-form" data-confirm="Decline request <?= e($r['code']) ?>? The customer will see it as declined.">
                <?= csrf_field() ?><button class="btn btn-danger" type="submit">Decline request</button>
            </form>
            <form method="post" action="<?= e($post('cancel')) ?>" class="inline-form push-right" data-confirm="Cancel request <?= e($r['code']) ?>?">
                <?= csrf_field() ?><button class="btn btn-ghost" type="submit">Cancel</button>
            </form>
        </div>

    <?php elseif ($status === 'accepted'): ?>
        <div class="callout">
            <p>The customer accepted the <strong><?= e($r['accepted_method'] ?? '-') ?></strong> offer<?= $r['accepted_at'] ? ' on ' . e(date('j M, H:i', strtotime($r['accepted_at']))) : '' ?>:
                <strong><?= e(money($r['accepted_method'] === 'credit' ? $r['offer_credit'] : $r['offer_cash'])) ?> <?= e($r['accepted_method'] ?? '') ?></strong>.</p>
            <p><strong><?= $r['collection'] === 'pickup' ? 'We collect from the customer' : 'Customer drops the games off' ?></strong><?= $r['pickup_note'] ? ': ' . e($r['pickup_note']) : '' ?></p>
        </div>
        <div class="status-actions">
            <form method="post" action="<?= e($post('collect')) ?>" class="inline-form" data-confirm="Confirm you have the games in hand?">
                <?= csrf_field() ?><button class="btn btn-primary btn-lg" type="submit">Mark collected &rarr;</button>
            </form>
            <form method="post" action="<?= e($post('cancel')) ?>" class="inline-form push-right" data-confirm="Cancel request <?= e($r['code']) ?>?">
                <?= csrf_field() ?><button class="btn btn-danger" type="submit">Cancel request</button>
            </form>
        </div>

    <?php elseif ($status === 'collected'): ?>
        <?php if ($isGuest): ?>
            <div class="alert alert-warn alert-inline">This request was sent without an account, so it cannot be paid as credit. Pay it in cash.</div>
        <?php endif; ?>
        <p class="muted">Inspect the games. The customer agreed <strong><?= e(money($offerFor($method))) ?> <?= e($method) ?></strong>. If you find problems, lower the amount; you cannot pay more than the agreed offer.</p>
        <form method="post" action="<?= e($post('complete')) ?>" class="complete-form" data-complete-form
              data-offer-cash="<?= e(number_format((float) $r['offer_cash'], 2, '.', '')) ?>" data-offer-credit="<?= e(number_format((float) $r['offer_credit'], 2, '.', '')) ?>"
              data-confirm="Complete this request? Credit is added to the wallet immediately and cannot be undone here.">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="field">
                    <label for="final_method">Pay the customer in</label>
                    <select id="final_method" name="final_method" data-final-method>
                        <option value="cash" <?= $fMethod === 'cash' ? 'selected' : '' ?>>Cash</option>
                        <option value="credit" <?= $fMethod === 'credit' ? 'selected' : '' ?> <?= $isGuest ? 'disabled' : '' ?>>Store credit (wallet)</option>
                    </select>
                </div>
                <div class="field">
                    <label for="final_amount">Final amount ($)</label>
                    <input type="text" inputmode="decimal" id="final_amount" name="final_amount" value="<?= e($fAmount) ?>" required data-final-amount>
                    <small class="hint" data-final-hint>Agreed offer for this method: <?= e(money($offerFor($fMethod))) ?>.</small>
                </div>
                <div class="field span-2">
                    <label for="inspection_note">Inspection note (optional)</label>
                    <input type="text" id="inspection_note" name="inspection_note" maxlength="1000" value="<?= e(Forms::val('inspection_note', '')) ?>" placeholder="e.g. one disc scratched, lowered by $3">
                </div>
            </div>
            <div class="status-actions">
                <button class="btn btn-primary btn-lg" type="submit">Complete and pay &rarr;</button>
                <span class="muted">Cash is only recorded here: hand it over yourself. Credit goes straight to the wallet, once.</span>
            </div>
        </form>
        <div class="status-actions secondary-actions">
            <form method="post" action="<?= e($post('cancel')) ?>" class="inline-form push-right" data-confirm="Cancel request <?= e($r['code']) ?>? Nothing has been paid yet.">
                <?= csrf_field() ?><button class="btn btn-danger" type="submit">Cancel request</button>
            </form>
        </div>

    <?php elseif ($status === 'completed'): ?>
        <?php if ($r['final_method'] === 'credit'): ?>
            <p><strong><?= e(money($r['final_amount'])) ?> credit</strong> was added to <?= $customer ? '<a href="' . e(url('/admin/customers/' . $customer['id'])) . '">' . e($customer['name']) . '\'s wallet</a>' : 'the customer\'s wallet' ?><?= $r['completed_at'] ? ' on ' . e(date('j M Y, H:i', strtotime($r['completed_at']))) : '' ?>.</p>
        <?php else: ?>
            <p><strong><?= e(money($r['final_amount'])) ?> cash</strong> was paid to the customer<?= $r['completed_at'] ? ' on ' . e(date('j M Y, H:i', strtotime($r['completed_at']))) : '' ?>.</p>
        <?php endif; ?>

    <?php else: ?>
        <p class="muted">Nothing more to do. It was <?= e(strtolower(Forms::label($status))) ?>; no money or credit was moved.</p>
    <?php endif; ?>

    </div>
</section>

<div class="cols-2">
    <section class="card">
        <div class="card-head"><h2>Customer</h2><?php if ($wa): ?><a class="btn btn-sm" href="<?= e($wa) ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?></div>
        <dl class="kv">
            <dt>Name</dt><dd><?= $customer ? '<a href="' . e(url('/admin/customers/' . $customer['id'])) . '"><strong>' . e($customer['name']) . '</strong></a>' : e($r['name']) . ' <span class="tag">guest, no account</span>' ?></dd>
            <dt>Phone</dt><dd><?= $wa ? '<a href="' . e($wa) . '" target="_blank" rel="noopener">' . e(Phone::pretty($phone) ?: $phone) . '</a>' : e($phone) ?></dd>
            <dt>Area</dt><dd><?= e($r['area'] ?? '-') ?></dd>
            <?php if ($customer): ?><dt>Wallet</dt><dd><?= e(money($customer['credit_balance'])) ?> credit</dd><?php endif; ?>
            <dt>Prefers</dt><dd><span class="pill pill-<?= e($r['preferred_method']) ?>"><?= e(ucfirst($r['preferred_method'])) ?></span> &middot; <?= $r['collection'] === 'pickup' ? 'wants pickup' : 'will drop off' ?></dd>
            <dt>Pickup note</dt><dd><?= $r['pickup_note'] ? e($r['pickup_note']) : '<span class="muted">-</span>' ?></dd>
        </dl>
    </section>

    <section class="card">
        <div class="card-head"><h2>Money</h2></div>
        <dl class="kv kv-totals">
            <dt>Instant estimate (cash)</dt><dd><?= $estimateShown ? e(money($r['estimate_cash'])) : '<span class="muted">-</span>' ?></dd>
            <dt>Instant estimate (credit)</dt><dd><?= $estimateShown ? e(money($r['estimate_credit'])) : '<span class="muted">-</span>' ?></dd>
            <?php if (!$estimateShown): ?><dt>Quoted total (old request)</dt><dd><?= e(money($r['offered_total'])) ?></dd><?php endif; ?>
            <dt>Our offer (cash)</dt><dd><?= $hasOffer ? '<strong>' . e(money($r['offer_cash'] ?? 0)) . '</strong>' : '<span class="muted">not yet</span>' ?></dd>
            <dt>Our offer (credit)</dt><dd><?= $hasOffer ? '<strong>' . e(money($r['offer_credit'] ?? 0)) . '</strong>' : '<span class="muted">not yet</span>' ?></dd>
            <dt>Customer chose</dt><dd><?= $r['accepted_method'] ? e(ucfirst($r['accepted_method'])) : '<span class="muted">-</span>' ?></dd>
            <?php if ($r['final_amount'] !== null): ?><dt>Final, paid as <?= e($r['final_method']) ?></dt><dd><strong class="big"><?= e(money($r['final_amount'])) ?></strong></dd><?php endif; ?>
        </dl>
    </section>
</div>

<section class="card">
    <div class="card-head"><h2>Games (<?= count($items) ?>)</h2></div>
    <?php if ($items): ?>
        <div class="table-wrap">
            <table class="data compact">
                <thead><tr><th>Game</th><th>Platform</th><th>Condition</th><th>Includes</th><th class="num">Cash value</th><th class="num">Credit value</th></tr></thead>
                <tbody>
                <?php foreach ($items as $it):
                    $offerVal = isset($it['offer']) && is_numeric($it['offer']) ? (float) $it['offer'] : null;
                    $cash   = isset($it['cash']) && is_numeric($it['cash']) ? (float) $it['cash'] : ($r['kind'] === 'sell' ? $offerVal : null);
                    $credit = isset($it['credit']) && is_numeric($it['credit']) ? (float) $it['credit'] : ($r['kind'] === 'trade_in' ? $offerVal : null);
                    $matched = !array_key_exists('matched', $it) || $it['matched'];
                    $inc = \App\Modules\Admin\RequestController::includes($it);
                    $lacks = $inc !== null && in_array(false, $inc, true);
                ?>
                    <tr>
                        <td><?= e($it['title'] ?? '-') ?><?= !$matched ? ' <span class="tag" title="Not in our price list: price it yourself">to be priced</span>' : '' ?></td>
                        <td><?= e($it['platform'] ?? '-') ?></td>
                        <td><?= e($it['condition'] ?? '-') ?></td>
                        <td>
                            <?php if ($inc === null): ?>
                                <span class="muted">Not stated</span>
                            <?php else: ?>
                                <span class="inc-list">
                                    <?php foreach ($incLabels as $k => $lbl): ?>
                                        <span class="inc <?= $inc[$k] === true ? 'inc-yes' : ($inc[$k] === false ? 'inc-no' : 'inc-unk') ?>"><?= e($lbl) ?> <?= $inc[$k] === true ? '&#10003;' : ($inc[$k] === false ? '&#10007;' : '?') ?></span>
                                    <?php endforeach; ?>
                                </span>
                                <?php if ($lacks): ?><br><small class="text-warn">Missing items usually lower the offer.</small><?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="num"><?= $cash !== null ? e(money($cash)) : '<span class="muted">-</span>' ?></td>
                        <td class="num"><?= $credit !== null ? e(money($credit)) : '<span class="muted">-</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td colspan="4">Instant estimate</td><td class="num"><strong><?= e(money($estimateShown ? $r['estimate_cash'] : ($r['kind'] === 'sell' ? $r['offered_total'] : 0))) ?></strong></td><td class="num"><strong><?= e(money($estimateShown ? $r['estimate_credit'] : ($r['kind'] === 'trade_in' ? $r['offered_total'] : 0))) ?></strong></td></tr></tfoot>
            </table>
        </div>
    <?php else: ?>
        <?php if (trim((string) $rawItems) === '[]'): ?>
            <div class="card-body"><p class="muted">No games are listed on this request.</p></div>
        <?php else: ?>
            <div class="card-body"><p class="muted">Items could not be read. Raw data:</p><pre class="raw"><?= e((string) $rawItems) ?></pre></div>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (!empty($r['wanted_items'])): ?><div class="card-body"><strong>Wants in exchange:</strong> <?= nl2br(e($r['wanted_items'])) ?></div><?php endif; ?>
</section>

<section class="card" id="photos">
    <div class="card-head"><h2>Photos from the customer (<?= $photoCount ?>)</h2></div>
    <div class="card-body">
        <?php if (!$photos): ?>
            <p class="muted">The customer did not send any photos with this request.</p>
        <?php else: ?>
            <div class="rv-photos rq-photos">
                <?php foreach ($photos as $i => $ph): $lbl = $photoLabels[$ph['kind']] ?? 'Photo'; ?>
                    <figure class="rv-photo">
                        <a href="<?= e(media($ph['path'])) ?>" target="_blank" rel="noopener" title="Open the full photo in a new tab"><img src="<?= e(media($ph['path'])) ?>" alt="<?= e($lbl) ?> <?= $i + 1 ?>" loading="lazy"></a>
                        <figcaption><strong><?= e($lbl === 'Photo' ? 'Photo ' . ($i + 1) : $lbl) ?></strong></figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="cols-2">
    <section class="card">
        <div class="card-head"><h2>History</h2></div>
        <div class="card-body">
            <ol class="timeline">
                <?php foreach ($timeline as [$label, $at]): ?>
                    <li class="<?= $at ? 'done' : '' ?>"><span class="dot"></span><span class="tl-label"><?= e($label) ?></span><small class="muted"><?= $at ? e(date('j M Y, H:i', strtotime($at))) : 'not yet' ?></small></li>
                <?php endforeach; ?>
                <?php if ($closed): ?><li class="done closed"><span class="dot"></span><span class="tl-label"><?= e(Forms::label($status)) ?></span><small class="muted">closed</small></li><?php endif; ?>
            </ol>
        </div>
    </section>

    <section class="card">
        <div class="card-head"><h2>Admin note</h2></div>
        <div class="card-body">
            <form method="post" action="<?= e(url('/admin/requests/buyback/' . $id)) ?>">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="admin_note" class="sr-only">Admin note</label>
                    <textarea id="admin_note" name="admin_note" rows="4" maxlength="5000" placeholder="Agreed pickup time, condition problems..."><?= e($r['admin_note'] ?? '') ?></textarea>
                </div>
                <button class="btn" type="submit">Save note</button>
            </form>
        </div>
    </section>
</div>
