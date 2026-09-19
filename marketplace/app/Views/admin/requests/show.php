<?php
use App\Modules\Admin\Forms;
use App\Modules\Admin\RequestController as RC;
use App\Support\Phone;

$pageTitle = 'Request ' . $r['code'];
$nav = 'requests';
$siteName = (string) setting('site_name', 'CyberGaming Lebanon');
$status = $r['status'];
$phone = $customer['phone'] ?? $r['phone'];
$isGuest = $r['user_id'] === null;
$id = (int) $r['id'];
$post = static fn (string $action): string => url('/admin/requests/buyback/' . $id . '/' . $action);
$hasOffer = $r['offer_cash'] !== null || $r['offer_credit'] !== null;
$estimateShown = (float) $r['estimate_cash'] > 0 || (float) $r['estimate_credit'] > 0;

// Where the seller is and how the games reach us.
$mode = $r['zone_mode'];                                  // local | remote | null (older request)
$modeBadge = Forms::modeBadge($mode);
$localSpot = RC::isLocalSpot($r);
$collectionText = RC::collectionLabel($r);
$fee = (float) $r['pickup_fee'];
$net = static fn (float $gross): float => RC::net($gross, $fee);

// Complete-form defaults: the offer for the method the customer accepted (cash for guests).
$method = RC::method($r);
$offerFor = static fn (string $m): float => RC::offerFor($r, $m);
$fMethod = Forms::val('final_method', $method);
$fAmount = Forms::val('final_amount', number_format($offerFor($fMethod), 2, '.', ''));

// Rejected items: the customer's decision and the countdown.
$choice = $r['reject_choice'];
$overdue = RC::isOverdue($r);
$holdDate = $r['hold_until'];
$daysLeft = $holdDate ? (int) floor((strtotime($holdDate) - strtotime(date('Y-m-d'))) / 86400) : null;
$returnFee = $r['return_fee'] !== null ? (float) $r['return_fee'] : RC::returnFeeFor($r);
$revised = $r['revised_amount'] !== null ? (float) $r['revised_amount'] : null;
$choiceText = ['new_offer' => 'Accepts the revised offer', 'return' => 'Wants the games back', 'recycle' => 'Lets us recycle the games'];

$wa = Forms::waLink($phone, 'Hi ' . $r['name'] . ', this is ' . $siteName . ' about your sell request ' . $r['code'] . '.');
if ($status === 'rejected' && $choice === null) {
    $waDecision = Forms::waLink($phone, 'Hi ' . $r['name'] . ', this is ' . $siteName . ' about your sell request ' . $r['code'] . '. After checking your games we could not accept them as they are: ' . $r['reject_reason'] . '. You can (1) '
        . ($revised !== null ? 'accept a revised offer of ' . money($revised) : 'ask us for a revised offer') . ', (2) have them sent back to you (' . money($returnFee) . ' cash to the courier on delivery), or (3) let us recycle them. '
        . ($holdDate ? 'Please reply by ' . date('j M Y', strtotime($holdDate)) . ', otherwise we will recycle them.' : 'Please reply soon, otherwise we will recycle them.'));
} else {
    $waDecision = $wa;
}

$timeline = [
    ['Request sent', $r['created_at'], null],
    ['Offer made', $r['offered_at'], null],
    ['Customer accepted' . ($r['accepted_method'] ? ' (' . $r['accepted_method'] . ')' : ''), $r['accepted_at'], null],
    [$mode === 'remote' ? 'Shipment received at the hub' : ($localSpot ? 'Courier checked it on the spot' : 'Games collected'), $r['collected_at'], null],
];
if ($r['inspection_result'] === 'rejected' && in_array($status, ['rejected', 'return_pending', 'returned', 'recycled'], true)) {
    $timeline[] = ['Not accepted on inspection', null, 'Reason: ' . $r['reject_reason'], true];
    if ($choice !== null) {
        $timeline[] = ['Customer decided: ' . strtolower($choiceText[$choice] ?? $choice), $r['reject_choice_at'], null];
    }
}
$timeline[] = ['Completed' . ($r['final_method'] ? ' (paid ' . $r['final_method'] . ')' : ''), $r['completed_at'], null];
$closed = in_array($status, ['declined', 'cancelled', 'returned', 'recycled'], true);
$photoCount = count($photos);
$photoLabels = ['disc' => 'Disc', 'box_outside' => 'Box, outside', 'box_inside' => 'Box, inside', 'extra' => 'Photo'];
$incLabels = ['box' => 'Box', 'cover' => 'Cover art', 'manual' => 'Manual'];
?>
<div class="page-head">
    <div>
        <h1>Request <?= e($r['code']) ?> <?= Forms::pill($status) ?> <span class="tag"><?= e(Forms::label($r['kind'])) ?></span> <?= $modeBadge ?></h1>
        <p class="muted">Sent <?= e(date('j M Y, H:i', strtotime($r['created_at']))) ?><?= $r['zone'] ? ' &middot; ' . e($r['zone']) : '' ?> &middot; <?= e($collectionText) ?></p>
    </div>
    <div class="actions"><a class="btn btn-ghost" href="<?= e(url('/admin/requests?tab=buyback')) ?>">&larr; All requests</a></div>
</div>

<!-- Action panel -->
<section class="card action-panel status-<?= e(str_replace('_', '-', $status)) ?>">
    <div class="card-head"><h2>
        <?php if (in_array($status, ['new', 'contacted'], true)): ?>Next: make an offer
        <?php elseif ($status === 'offered'): ?>Waiting for the customer to accept
        <?php elseif ($status === 'accepted'): ?><?= $localSpot ? 'Next: the courier checks the games on the spot' : ($mode === 'remote' ? 'Next: wait for the shipment, then mark it received' : 'Next: collect the games') ?>
        <?php elseif ($status === 'collected'): ?>Next: inspect the games and <?= $r['inspection_result'] === 'passed' ? 'pay' : 'pay or reject' ?>
        <?php elseif ($status === 'rejected'): ?><?= $choice === 'new_offer' ? 'Customer accepted the revised offer: complete it' : ($overdue ? 'No answer and the deadline passed: recycle it' : 'Not accepted: waiting for the customer\'s decision') ?>
        <?php elseif ($status === 'return_pending'): ?>Next: send the games back
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
        <?php if ($fee > 0): ?><p class="muted">A <?= e(money($fee)) ?> pickup fee will be deducted from whatever you finally pay this customer.</p><?php endif; ?>
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
            <?php $accGross = $offerFor($r['accepted_method'] ?: $method); ?>
            <p>The customer accepted the <strong><?= e($r['accepted_method'] ?? '-') ?></strong> offer<?= $r['accepted_at'] ? ' on ' . e(date('j M, H:i', strtotime($r['accepted_at']))) : '' ?>:
                <strong><?= e(money($accGross)) ?> <?= e($r['accepted_method'] ?? '') ?></strong><?= $fee > 0 ? ', minus the ' . e(money($fee)) . ' pickup fee = <strong>' . e(money($net($accGross))) . ' payout</strong>' : '' ?>.</p>
            <p><strong><?= e($collectionText) ?></strong><?= $r['pickup_note'] ? ': ' . e($r['pickup_note']) : '' ?></p>
        </div>
        <?php if ($localSpot): ?>
            <p class="muted">Our own courier picks the games up and checks them at the customer's door. If they are fine, accept them and pay below. If not, decline: nothing is charged and there is no return trip.</p>
            <div class="status-actions">
                <form method="post" action="<?= e($post('spot-accept')) ?>" class="inline-form" data-confirm="Confirm the courier checked the games on the spot and is taking them?">
                    <?= csrf_field() ?><button class="btn btn-primary btn-lg" type="submit">Courier checked it on the spot &mdash; ACCEPT &rarr;</button>
                </form>
            </div>
            <form method="post" action="<?= e($post('spot-decline')) ?>" class="spot-decline-form" data-confirm="Courier declined the games on the spot? The request is cancelled, no fee is charged and nothing is returned.">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="spot_reason">Courier declined it on the spot: why? (required)</label>
                    <input type="text" id="spot_reason" name="reject_reason" maxlength="255" required value="<?= e(Forms::val('reject_reason', '')) ?>" placeholder="e.g. discs badly scratched, game does not match the photos">
                </div>
                <button class="btn btn-danger" type="submit">Courier declined it on the spot</button>
            </form>
            <div class="status-actions secondary-actions">
                <form method="post" action="<?= e($post('cancel')) ?>" class="inline-form push-right" data-confirm="Cancel request <?= e($r['code']) ?>?">
                    <?= csrf_field() ?><button class="btn btn-ghost" type="submit">Cancel request</button>
                </form>
            </div>
        <?php else: ?>
            <div class="status-actions">
                <form method="post" action="<?= e($post('collect')) ?>" class="inline-form" data-confirm="<?= $mode === 'remote' ? 'Confirm the shipment has arrived at the hub?' : 'Confirm you have the games in hand?' ?>">
                    <?= csrf_field() ?><button class="btn btn-primary btn-lg" type="submit"><?= $mode === 'remote' ? 'Mark received at the hub' : 'Mark collected' ?> &rarr;</button>
                </form>
                <form method="post" action="<?= e($post('cancel')) ?>" class="inline-form push-right" data-confirm="Cancel request <?= e($r['code']) ?>?">
                    <?= csrf_field() ?><button class="btn btn-danger" type="submit">Cancel request</button>
                </form>
            </div>
        <?php endif; ?>

    <?php elseif ($status === 'collected'): ?>
        <?php if ($isGuest): ?>
            <div class="alert alert-warn alert-inline">This request was sent without an account, so it cannot be paid as credit. Pay it in cash.</div>
        <?php endif; ?>
        <?php if ($r['inspection_result'] === 'passed'): ?>
            <div class="callout"><p><strong>The courier already checked the games on the spot and accepted them.</strong> Pay the customer below.</p></div>
        <?php else: ?>
            <div class="inspect-cols">
            <div>
            <h3 class="sub-title">Inspection passed: pay the customer</h3>
        <?php endif; ?>
        <p class="muted">The customer agreed <strong><?= e(money($offerFor($method))) ?> <?= e($method) ?></strong>. If you find small problems, lower the amount; you cannot pay more than the agreed offer.
            <?php if ($fee > 0): ?><br><strong>How the amount works:</strong> the final amount is the gross agreed value. The <?= e(money($fee)) ?> pickup fee is deducted from it when you pay, so the customer receives final amount minus <?= e(money($fee)) ?>.<?php endif; ?></p>
        <form method="post" action="<?= e($post('complete')) ?>" class="complete-form" data-complete-form data-pickup-fee="<?= e(number_format($fee, 2, '.', '')) ?>"
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
                    <label for="final_amount">Final amount<?= $fee > 0 ? ' (gross, before the pickup fee)' : '' ?> ($)</label>
                    <input type="text" inputmode="decimal" id="final_amount" name="final_amount" value="<?= e($fAmount) ?>" required data-final-amount>
                    <small class="hint" data-final-hint>Agreed offer for this method: <?= e(money($offerFor($fMethod))) ?>.</small>
                    <?php if ($fee > 0): ?><small class="hint net-hint">Customer receives <strong data-net-out><?= e(money($net((float) $fAmount))) ?></strong> (final amount &minus; <?= e(money($fee)) ?> pickup fee).</small><?php endif; ?>
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
        <?php if ($r['inspection_result'] !== 'passed'): ?>
            </div>
            <div>
            <h3 class="sub-title">Inspection failed: reject the games</h3>
            <p class="muted">Nothing is paid. Give the reason and, if you want, a lower revised offer. The customer then chooses: take the revised offer, get the games back (<?= e(money(RC::returnFeeFor($r))) ?> cash to the courier), or let us recycle them. No answer in <?= (int) RC::holdDays() ?> days and we recycle them.</p>
            <form method="post" action="<?= e($post('inspect-reject')) ?>" class="reject-form" data-confirm="Reject these games? The customer will be asked to decide what happens next. Nothing is paid.">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="reject_reason">Why are they not acceptable? (required)</label>
                    <input type="text" id="reject_reason" name="reject_reason" maxlength="255" required value="<?= e(Forms::val('reject_reason', '')) ?>" placeholder="e.g. disc unreadable, fake case, wrong game">
                </div>
                <div class="field">
                    <label for="revised_amount">Revised offer ($, optional)</label>
                    <input type="text" inputmode="decimal" id="revised_amount" name="revised_amount" value="<?= e(Forms::val('revised_amount', '')) ?>" placeholder="lower than <?= e(money($offerFor($method))) ?>">
                    <small class="hint">Above $0 and below the agreed <?= e(money($offerFor($method))) ?>. Leave empty to offer no alternative price.</small>
                </div>
                <button class="btn btn-danger" type="submit">Reject and ask the customer</button>
            </form>
            </div>
            </div>
        <?php endif; ?>
        <div class="status-actions secondary-actions">
            <form method="post" action="<?= e($post('cancel')) ?>" class="inline-form push-right" data-confirm="Cancel request <?= e($r['code']) ?>? Nothing has been paid yet.">
                <?= csrf_field() ?><button class="btn btn-danger" type="submit">Cancel request</button>
            </form>
        </div>

    <?php elseif ($status === 'rejected'): ?>
        <div class="callout callout-warn">
            <p><strong>Why it was not accepted:</strong> <?= e($r['reject_reason'] ?? '-') ?></p>
            <p><strong>Revised offer:</strong> <?= $revised !== null ? e(money($revised)) . ($fee > 0 ? ' minus the ' . e(money($fee)) . ' pickup fee = ' . e(money($net($revised))) . ' payout' : '') : 'none was offered' ?></p>
            <p><strong>Customer's decision:</strong> <?= $choice ? e($choiceText[$choice] ?? $choice) . ($r['reject_choice_at'] ? ' on ' . e(date('j M, H:i', strtotime($r['reject_choice_at']))) : '') : 'not yet' ?></p>
            <p><strong>Deadline:</strong>
                <?php if ($holdDate): ?>
                    <?= e(date('j M Y', strtotime($holdDate))) ?>
                    <?php if ($choice !== null): ?><?php elseif ($overdue): ?><span class="text-warn">&mdash; passed <?= (int) abs((int) $daysLeft) ?> day<?= abs((int) $daysLeft) === 1 ? '' : 's' ?> ago</span>
                    <?php elseif ($daysLeft === 0): ?>&mdash; <strong>last day</strong>
                    <?php else: ?>&mdash; <?= (int) $daysLeft ?> day<?= $daysLeft === 1 ? '' : 's' ?> left<?php endif; ?>
                <?php else: ?><span class="muted">not set</span><?php endif; ?>
            </p>
        </div>

        <?php if ($choice === 'new_offer' && $revised !== null): ?>
            <?php $revMethod = $isGuest ? 'cash' : $method; ?>
            <p>The customer accepted <strong><?= e(money($revised)) ?></strong><?= $fee > 0 ? ' (minus the ' . e(money($fee)) . ' pickup fee)' : '' ?>. Pay <strong><?= e(money($net($revised))) ?></strong> as <strong><?= e($revMethod === 'credit' ? 'store credit' : 'cash') ?></strong>, the method they accepted originally.</p>
            <div class="status-actions">
                <form method="post" action="<?= e($post('complete-revised')) ?>" class="inline-form" data-confirm="Complete with the revised offer? <?= $revMethod === 'credit' ? 'Credit is added to the wallet immediately and cannot be undone here.' : 'Hand over the cash yourself.' ?>">
                    <?= csrf_field() ?><button class="btn btn-primary btn-lg" type="submit">Complete with revised offer &rarr;</button>
                </form>
            </div>
        <?php else: ?>
            <?php if (!$overdue && $choice === null): ?>
                <p class="muted">Nothing to do until the customer answers on the website. You can message them on WhatsApp, and record their answer below if they reply there.</p>
            <?php endif; ?>
            <div class="status-actions">
                <?php if ($waDecision): ?><a class="btn btn-primary" href="<?= e($waDecision) ?>" target="_blank" rel="noopener">WhatsApp the customer</a><?php endif; ?>
                <?php if ($overdue): ?>
                    <form method="post" action="<?= e($post('recycle')) ?>" class="inline-form" data-confirm="Recycle these games now? The customer did not answer by the deadline: we keep them and pay nothing.">
                        <?= csrf_field() ?><button class="btn btn-danger btn-lg" type="submit">Recycle now</button>
                    </form>
                <?php endif; ?>
            </div>
            <?php if ($choice === null): ?>
                <h3 class="sub-title">Record the customer's answer (they replied on WhatsApp, or have no account)</h3>
                <div class="status-actions">
                    <?php if ($revised !== null): ?>
                        <form method="post" action="<?= e($post('decision')) ?>" class="inline-form" data-confirm="Record that the customer accepts the revised offer of <?= e(money($revised)) ?>?">
                            <?= csrf_field() ?><input type="hidden" name="choice" value="new_offer"><button class="btn" type="submit">Accepts the revised offer (<?= e(money($revised)) ?>)</button>
                        </form>
                    <?php endif; ?>
                    <form method="post" action="<?= e($post('decision')) ?>" class="inline-form" data-confirm="Record that the customer wants the games sent back? They pay <?= e(money($returnFee)) ?> cash to the courier on delivery.">
                        <?= csrf_field() ?><input type="hidden" name="choice" value="return"><button class="btn" type="submit">Wants them back (<?= e(money($returnFee)) ?> on delivery)</button>
                    </form>
                    <form method="post" action="<?= e($post('decision')) ?>" class="inline-form" data-confirm="Record that the customer lets us recycle the games? Nothing is paid.">
                        <?= csrf_field() ?><input type="hidden" name="choice" value="recycle"><button class="btn" type="submit">Lets us recycle them</button>
                    </form>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    <?php elseif ($status === 'return_pending'): ?>
        <div class="callout callout-warn">
            <p><strong>The customer wants the games back.</strong> Send them with the courier and collect <strong><?= e(money($returnFee)) ?></strong> cash on delivery (pickup + return trip).</p>
            <p><strong>Why it was not accepted:</strong> <?= e($r['reject_reason'] ?? '-') ?></p>
        </div>
        <div class="status-actions">
            <?php if ($wa): ?><a class="btn" href="<?= e($wa) ?>" target="_blank" rel="noopener">WhatsApp the customer</a><?php endif; ?>
            <form method="post" action="<?= e($post('returned')) ?>" class="inline-form" data-confirm="Mark as returned? Do this once the courier has the games. Collect <?= e(money($returnFee)) ?> cash on delivery.">
                <?= csrf_field() ?><button class="btn btn-primary btn-lg" type="submit">Mark returned &rarr;</button>
            </form>
        </div>

    <?php elseif ($status === 'returned'): ?>
        <p>The games were sent back to the customer, who pays <?= e(money($returnFee)) ?> cash to the courier on delivery. No money or credit was moved by us. See the admin note for the date.</p>

    <?php elseif ($status === 'recycled'): ?>
        <p>We kept the games and paid nothing: <?= $choice === 'recycle' ? 'the customer chose to let us recycle them' : 'the customer did not answer by the deadline' ?>. This is final.</p>
        <?php if ($r['reject_reason']): ?><p class="muted">Why it was not accepted: <?= e($r['reject_reason']) ?></p><?php endif; ?>

    <?php elseif ($status === 'completed'): ?>
        <?php $gross = (float) $r['final_amount']; $paid = $net($gross); ?>
        <?php if ($r['final_method'] === 'credit'): ?>
            <p><strong><?= e(money($paid)) ?> credit</strong> was added to <?= $customer ? '<a href="' . e(url('/admin/customers/' . $customer['id'])) . '">' . e($customer['name']) . '\'s wallet</a>' : 'the customer\'s wallet' ?><?= $r['completed_at'] ? ' on ' . e(date('j M Y, H:i', strtotime($r['completed_at']))) : '' ?>.</p>
        <?php else: ?>
            <p><strong><?= e(money($paid)) ?> cash</strong> was paid to the customer<?= $r['completed_at'] ? ' on ' . e(date('j M Y, H:i', strtotime($r['completed_at']))) : '' ?>.</p>
        <?php endif; ?>
        <?php if ($fee > 0): ?><p class="muted">Final amount <?= e(money($gross)) ?> minus <?= e(money($fee)) ?> pickup fee = <?= e(money($paid)) ?> paid.</p><?php endif; ?>

    <?php else: ?>
        <p class="muted">Nothing more to do. It was <?= e(strtolower(Forms::label($status))) ?>; no money or credit was moved.<?= $status === 'cancelled' && $r['inspection_result'] === 'rejected' && $r['reject_reason'] ? ' The courier declined the games on the spot: ' . e($r['reject_reason']) . '. No fee was charged and nothing is returned.' : '' ?></p>
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
            <dt>Zone</dt><dd><?= $r['zone'] ? e($r['zone']) . ' ' . $modeBadge : '<span class="muted">not recorded (older request)</span>' ?></dd>
            <?php if ($customer): ?><dt>Wallet</dt><dd><?= e(money($customer['credit_balance'])) ?> credit</dd><?php endif; ?>
            <dt>Prefers</dt><dd><span class="pill pill-<?= e($r['preferred_method']) ?>"><?= e(ucfirst($r['preferred_method'])) ?></span></dd>
            <dt>Collection</dt><dd><?= e($collectionText) ?></dd>
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
            <dt>Pickup fee (deducted)</dt><dd><?php if ($status === 'cancelled' && $r['inspection_result'] === 'rejected'): ?><span class="muted">none: declined on the spot, no fee</span><?php elseif ($fee > 0): ?>&minus;<?= e(money($fee)) ?><?php else: ?><span class="muted">none</span><?= $r['collection'] === 'dropoff' ? ' <small class="muted">(brings it to the hub)</small>' : '' ?><?php endif; ?></dd>
            <?php if ($revised !== null): ?><dt>Revised offer (gross)</dt><dd><?= e(money($revised)) ?></dd><?php endif; ?>
            <?php if ($r['final_amount'] !== null): ?>
                <dt>Final agreed (gross)</dt><dd><?= e(money($r['final_amount'])) ?></dd>
                <dt>Net paid as <?= e($r['final_method']) ?></dt><dd><strong class="big"><?= e(money($net((float) $r['final_amount']))) ?></strong></dd>
            <?php elseif ($hasOffer && $r['accepted_method'] && !in_array($status, ['declined', 'cancelled', 'recycled', 'returned', 'rejected', 'return_pending'], true)): ?>
                <dt>Net payout if paid at the offer</dt><dd><strong><?= e(money($net($offerFor($r['accepted_method'])))) ?></strong> <small class="muted"><?= e($r['accepted_method']) ?></small></dd>
            <?php endif; ?>
            <?php if ($r['return_fee'] !== null && in_array($status, ['return_pending', 'returned'], true)): ?><dt>Return fee (cash on delivery)</dt><dd><?= e(money($r['return_fee'])) ?></dd><?php endif; ?>
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
                    $inc = RC::includes($it);
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
                <?php foreach ($timeline as $row): [$label, $at, $note] = $row; $forceDone = !empty($row[3]); ?>
                    <li class="<?= ($at || $forceDone) ? 'done' : '' ?>"><span class="dot"></span><span class="tl-label"><?= e($label) ?></span><small class="muted"><?= $note !== null ? e($note) : ($at ? e(date('j M Y, H:i', strtotime($at))) : 'not yet') ?></small></li>
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
