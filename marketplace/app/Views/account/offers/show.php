<?php
use App\Modules\Account\AccountUi;
use App\Modules\Storefront\Ui;

/** @var array $me @var array $offer @var array $items @var array $wanted @var string $hub @var string $zone @var string $zoneMode @var float $pickupFee @var float $minSell @var float $returnFee @var int $holdDays @var string $waLink */
$status = (string) $offer['status'];
$isTrade = $offer['kind'] === 'trade_in';
$kindLabel = $isTrade ? 'Trade-in request' : 'Sell request';
$meta = ['title' => $kindLabel . ' ' . $offer['code'] . ' | CyberGaming Lebanon', 'description' => 'Your request and our offer.', 'noindex' => true];
$accountNav = 'offers';
require base_path('app/Views/account/_nav.php');

[$statusLabel, $statusMod] = AccountUi::offerStatus($status, $offer['reject_choice']);
$errors = flash('form_errors', []);
$errors = is_array($errors) ? $errors : [];
$actionBase = url('/account/offers/' . $offer['code']);
$hasCash   = $offer['offer_cash'] !== null && (float) $offer['offer_cash'] > 0;
$hasCredit = $offer['offer_credit'] !== null && (float) $offer['offer_credit'] > 0;
$estCash   = (float) $offer['estimate_cash'] > 0 ? (float) $offer['estimate_cash'] : (float) $offer['offered_total'];
$estCredit = (float) $offer['estimate_credit'];
$canCancel = in_array($status, ['new', 'contacted', 'offered', 'accepted'], true);
$bonus     = ($hasCash && $hasCredit) ? (float) $offer['offer_credit'] - (float) $offer['offer_cash'] : 0.0;
$defMethod = old('method', $hasCredit ? 'credit' : 'cash');
$defColl   = old('collection', 'dropoff');
$defNote   = old('pickup_note', (string) ($me['address'] ?? ''));
$remote    = $zoneMode === 'remote';
$storedFee = (float) $offer['pickup_fee'];
$acceptedAmount = $offer['accepted_method'] === 'credit' ? $offer['offer_credit'] : ($offer['accepted_method'] === 'cash' ? $offer['offer_cash'] : ($offer['offer_credit'] ?? $offer['offer_cash']));
$revised   = $offer['revised_amount'];
$decided   = $offer['reject_choice'] !== null;
$holdText  = $offer['hold_until'] ? AccountUi::date($offer['hold_until'] . ' 12:00:00') : '';
$stopFmt   = static fn (float $n): string => AccountUi::amount($n);
?>
<div class="container acc-page">
    <?= Ui::breadcrumbs([['My account', '/account'], ['Sell offers', '/account/offers'], [$offer['code'], null]]) ?>
    <div class="acc-title-row">
        <h1><?= e($kindLabel) ?> <span class="order-code"><?= e($offer['code']) ?></span></h1>
        <?= AccountUi::pill($statusLabel, $statusMod) ?>
    </div>
    <p class="lead-sm">Sent on <?= e(AccountUi::date($offer['created_at'], true)) ?>.</p>

    <?php if ($errors): ?>
        <div class="form-errors" role="alert" tabindex="-1" data-focus-first>
            <strong>Please fix the following:</strong>
            <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <div class="acc-grid acc-grid-order">
        <div>
            <?php if ($status === 'new' || $status === 'contacted'): ?>
                <section class="acc-card acc-status acc-status-info" aria-labelledby="st-h">
                    <h2 id="st-h">We're reviewing your games</h2>
                    <p>Our team is checking your list. We'll send you an offer soon, and it will appear right here (we'll also message you on WhatsApp). Nothing is charged and you don't have to accept.</p>
                </section>

            <?php elseif ($status === 'offered'): ?>
                <section class="acc-card acc-status acc-status-warn" aria-labelledby="st-h">
                    <h2 id="st-h">We made you an offer</h2>
                    <p>Choose how you'd like to be paid and how we get your games. The final amount is confirmed when we inspect the games with you.</p>
                </section>

                <?php if ($zone !== ''): ?>
                <section class="acc-card acc-zone-rules" aria-labelledby="zr-h">
                    <h2 id="zr-h">Your zone: <?= e($zone) ?> <span class="pay-pill"><?= $remote ? 'Remote' : 'Local' ?></span></h2>
                    <?php if ($remote): ?>
                        <p>Your area is served by a third-party courier, so your games travel to our hub and we inspect them when they arrive. You can also bring them to the hub yourself, for free.</p>
                        <ul>
                            <li>Courier pickup fee: <strong><?= e(AccountUi::amount($pickupFee)) ?></strong>, deducted from your payout (never charged separately).</li>
                            <?php if ($minSell > 0): ?><li>Shipments by courier must be worth at least <strong><?= e(AccountUi::amount($minSell)) ?></strong>.</li><?php endif; ?>
                            <li>If an item is not acceptable, <strong>you choose</strong>: a revised (lower) offer, get it sent back (<?= e(AccountUi::amount($returnFee)) ?> return fee, paid in cash to the courier on delivery), or let us recycle it for free. We hold it for <?= (int) $holdDays ?> days; after that we recycle it.</li>
                        </ul>
                    <?php else: ?>
                        <p>Our own courier can come to you and check the games on the spot. Or bring them to our hub yourself, for free.</p>
                        <ul>
                            <li>Courier pickup fee: <strong><?= e(AccountUi::amount($pickupFee)) ?></strong>, deducted from your payout (never charged separately).</li>
                            <li>If the courier declines an item on the spot, there is no return trip and no fee.</li>
                        </ul>
                    <?php endif; ?>
                </section>
                <?php endif; ?>

                <form class="acc-card acc-form" method="post" action="<?= e($actionBase . '/accept') ?>" data-once data-offer-form data-fee="<?= e(number_format($pickupFee, 2, '.', '')) ?>" data-cash="<?= e(number_format((float) $offer['offer_cash'], 2, '.', '')) ?>" data-credit="<?= e(number_format((float) $offer['offer_credit'], 2, '.', '')) ?>" data-min="<?= e(number_format($remote ? $minSell : 0.0, 2, '.', '')) ?>">
                    <?= csrf_field() ?>
                    <fieldset class="acc-choice-group">
                        <legend>How would you like to be paid?</legend>
                        <?php if ($hasCredit): ?>
                            <label class="acc-choice">
                                <input type="radio" name="method" value="credit"<?= $defMethod === 'credit' ? ' checked' : '' ?> required>
                                <span class="acc-choice-body">
                                    <strong><?= e(AccountUi::amount($offer['offer_credit'])) ?> wallet credit</strong>
                                    <?php if ($bonus > 0): ?><span class="acc-choice-badge">Best deal: +<?= e(AccountUi::amount($bonus)) ?> extra</span><?php endif; ?>
                                    <small>Added to your wallet once we've inspected your games. Spend it in the shop, it never expires.</small>
                                </span>
                            </label>
                        <?php endif; ?>
                        <?php if ($hasCash): ?>
                            <label class="acc-choice">
                                <input type="radio" name="method" value="cash"<?= $defMethod === 'cash' ? ' checked' : '' ?> required>
                                <span class="acc-choice-body">
                                    <strong><?= e(AccountUi::amount($offer['offer_cash'])) ?> cash</strong>
                                    <small>Paid in cash when we collect or receive your games.</small>
                                </span>
                            </label>
                        <?php endif; ?>
                    </fieldset>

                    <fieldset class="acc-choice-group">
                        <legend>How do we get your games?</legend>
                        <label class="acc-choice">
                            <input type="radio" name="collection" value="dropoff"<?= $defColl === 'dropoff' ? ' checked' : '' ?> required>
                            <span class="acc-choice-body">
                                <strong>I'll bring it to your hub (free)</strong>
                                <small><?= $hub !== '' ? e($hub) . '. ' : '' ?>We'll confirm a time on WhatsApp.</small>
                            </span>
                        </label>
                        <label class="acc-choice">
                            <input type="radio" name="collection" value="pickup"<?= $defColl === 'pickup' ? ' checked' : '' ?> required>
                            <span class="acc-choice-body">
                                <strong><?= $remote ? 'Ship it by courier' : ($zoneMode === 'local' ? 'Our courier picks it up and checks it on the spot' : 'A courier picks it up') ?></strong>
                                <small><?= $remote ? 'A third-party courier brings it to our hub and we inspect it on arrival.' : 'Our own courier comes to you and inspects the games on the spot.' ?> The <?= e(AccountUi::amount($pickupFee)) ?> pickup fee is deducted from your payout.</small>
                            </span>
                        </label>
                        <div class="form-row pickup-note" data-pickup-note>
                            <label for="o-note">Pickup address and best time</label>
                            <textarea id="o-note" name="pickup_note" rows="3" maxlength="255" placeholder="Street, building, floor, landmark, and when you're free"><?= e($defNote) ?></textarea>
                            <small>Only needed if a courier picks it up.</small>
                        </div>
                    </fieldset>

                    <p class="pay-note acc-net" data-offer-net aria-live="polite"><?= Ui::icon('wallet', 20) ?> <span data-offer-net-text>You'll receive the amount above<?= $defColl === 'pickup' ? ' minus the ' . e(AccountUi::amount($pickupFee)) . ' pickup fee' : '' ?>.</span></p>
                    <p class="form-errors" role="alert" data-offer-min hidden>Shipments from your area must be worth at least <?= e(AccountUi::amount($minSell)) ?>: choose the higher offer, or bring the games to our hub.</p>

                    <button class="btn btn-primary btn-lg btn-block" type="submit">Accept this offer</button>
                </form>

                <form class="acc-card acc-inline-form" method="post" action="<?= e($actionBase . '/decline') ?>" data-once>
                    <?= csrf_field() ?>
                    <p>Not interested? You can decline and keep your games.</p>
                    <button class="btn btn-ghost" type="submit">Decline the offer</button>
                </form>

            <?php elseif ($status === 'accepted'): ?>
                <section class="acc-card acc-status acc-status-ok" aria-labelledby="st-h">
                    <h2 id="st-h"><?= $offer['collection'] === 'pickup' ? ($remote ? 'A courier will pick up your games' : 'Our courier will pick up your games') : 'Bring your games to us' ?></h2>
                    <?php if ($offer['collection'] === 'pickup'): ?>
                        <p>We'll contact you on WhatsApp to agree a time. Please have your games ready and in their cases.
                            <?= $remote ? 'The courier takes them to our hub and we inspect them on arrival.' : 'Our courier checks them on the spot.' ?>
                            The <?= e(AccountUi::amount($storedFee > 0 ? $storedFee : $pickupFee)) ?> pickup fee is deducted from your payout.</p>
                        <?php if ($offer['pickup_note']): ?><p class="acc-quote">Collection details you gave us: <?= e($offer['pickup_note']) ?></p><?php endif; ?>
                    <?php else: ?>
                        <p>Drop your games off at our hub<?= $hub !== '' ? ': <strong>' . e($hub) . '</strong>' : '' ?>. Message us on WhatsApp first so we can confirm a time and have someone ready for you. Bring this request number: <strong><?= e($offer['code']) ?></strong>.</p>
                    <?php endif; ?>
                    <p>You chose <strong><?= e(AccountUi::amount($offer['accepted_method'] === 'credit' ? $offer['offer_credit'] : $offer['offer_cash'])) ?> <?= $offer['accepted_method'] === 'credit' ? 'wallet credit' : 'in cash' ?></strong>
                        (accepted <?= e(AccountUi::date($offer['accepted_at'], true)) ?>).</p>
                    <p><a class="btn btn-wa" href="<?= e($waLink) ?>" rel="noopener" target="_blank"><?= Ui::icon('whatsapp', 20) ?> Message us on WhatsApp</a></p>
                </section>

            <?php elseif ($status === 'collected'): ?>
                <section class="acc-card acc-status acc-status-info" aria-labelledby="st-h">
                    <h2 id="st-h">We've got your games</h2>
                    <p>We're inspecting them now<?= $offer['collected_at'] ? ' (received ' . e(AccountUi::date($offer['collected_at'])) . ')' : '' ?>. As soon as we're done, we'll pay you
                        <?= $offer['accepted_method'] === 'credit' ? 'in wallet credit' : ($offer['accepted_method'] === 'cash' ? 'in cash' : 'the agreed amount') ?>.</p>
                    <p><a class="btn btn-wa btn-sm" href="<?= e($waLink) ?>" rel="noopener" target="_blank"><?= Ui::icon('whatsapp', 18) ?> Message us</a></p>
                </section>

            <?php elseif ($status === 'completed'): ?>
                <section class="acc-card acc-status acc-status-done" aria-labelledby="st-h">
                    <h2 id="st-h">All done. Thank you!</h2>
                    <?php $grossFinal = (float) $offer['final_amount']; $netFinal = max(0.0, $grossFinal - $storedFee); ?>
                    <p class="acc-final"><strong><?= e(AccountUi::amount($netFinal)) ?></strong>
                        <span><?= $offer['final_method'] === 'credit' ? 'added to your wallet as credit' : 'paid to you in cash' ?><?= $offer['completed_at'] ? ' on ' . e(AccountUi::date($offer['completed_at'])) : '' ?></span></p>
                    <?php if ($storedFee > 0): ?>
                        <p class="muted">Agreed amount <?= e(AccountUi::amount($grossFinal)) ?> minus the <?= e(AccountUi::amount($storedFee)) ?> courier pickup fee.</p>
                    <?php endif; ?>
                    <p>
                        <a class="btn btn-primary" href="<?= e(url('/shop')) ?>"><?= Ui::icon('cart', 18) ?> Go shopping</a>
                        <?php if ($offer['final_method'] === 'credit'): ?><a class="btn btn-ghost" href="<?= e(url('/account/wallet')) ?>">See it in your wallet</a><?php endif; ?>
                    </p>
                </section>

            <?php elseif ($status === 'rejected'): ?>
                <section class="acc-card acc-status acc-status-warn" aria-labelledby="st-h">
                    <?php if ($decided): ?>
                        <h2 id="st-h">You chose the new offer</h2>
                        <p>You accepted our revised offer of <strong><?= e(AccountUi::amount($revised)) ?></strong><?= $offer['reject_choice_at'] ? ' on ' . e(AccountUi::date($offer['reject_choice_at'], true)) : '' ?>. We'll complete it and pay you <?= $offer['accepted_method'] === 'credit' ? 'in wallet credit' : ($offer['accepted_method'] === 'cash' ? 'in cash' : 'as agreed') ?><?= $storedFee > 0 ? ', minus the ' . e(AccountUi::amount($storedFee)) . ' pickup fee' : '' ?>.</p>
                    <?php else: ?>
                        <h2 id="st-h">We couldn't accept it: please choose</h2>
                        <p>We inspected your games and could not accept them at the offered price.</p>
                        <?php if (!empty($offer['reject_reason'])): ?><p class="acc-quote"><strong>What we found:</strong> <?= e($offer['reject_reason']) ?></p><?php endif; ?>
                    <?php endif; ?>
                    <dl class="acc-dl">
                        <?php if ($acceptedAmount !== null): ?><div><dt>Original offer</dt><dd><?= e(AccountUi::amount($acceptedAmount)) ?></dd></div><?php endif; ?>
                        <div><dt>Revised offer</dt><dd><?= $revised !== null ? e(AccountUi::amount($revised)) : 'None: we could not make one' ?></dd></div>
                        <?php if ($revised !== null && $storedFee > 0): ?><div><dt>You'd receive (after the <?= e(AccountUi::amount($storedFee)) ?> pickup fee)</dt><dd><?= e(AccountUi::amount(max(0.0, (float) $revised - $storedFee))) ?></dd></div><?php endif; ?>
                        <?php if (!$decided && $holdText !== ''): ?><div><dt>Please decide by</dt><dd><?= e($holdText) ?></dd></div><?php endif; ?>
                    </dl>
                </section>

                <?php if (!$decided): ?>
                <form class="acc-card acc-form" method="post" action="<?= e($actionBase . '/choose') ?>" data-once>
                    <?= csrf_field() ?>
                    <fieldset class="acc-choice-group">
                        <legend>What would you like us to do?</legend>
                        <?php if ($revised !== null): ?>
                            <label class="acc-choice">
                                <input type="radio" name="choice" value="new_offer" required>
                                <span class="acc-choice-body"><strong>Take the new offer: <?= e(AccountUi::amount($revised)) ?></strong>
                                    <small>We pay you <?= e(AccountUi::amount($revised)) ?><?= $storedFee > 0 ? ' minus the ' . e(AccountUi::amount($storedFee)) . ' pickup fee' : '' ?>, as <?= $offer['accepted_method'] === 'cash' ? 'cash' : 'wallet credit' ?>.</small></span>
                            </label>
                        <?php endif; ?>
                        <label class="acc-choice">
                            <input type="radio" name="choice" value="return" required>
                            <span class="acc-choice-body"><strong>Send it back to me</strong>
                                <small>You pay the <?= e(AccountUi::amount($returnFee)) ?> return fee (pickup and return trip) in cash to the courier when it is delivered to you.</small></span>
                        </label>
                        <label class="acc-choice">
                            <input type="radio" name="choice" value="recycle" required>
                            <span class="acc-choice-body"><strong>Recycle it, free</strong>
                                <small>Nothing to pay. We keep the item and recycle it. You receive nothing for it.</small></span>
                        </label>
                    </fieldset>
                    <p class="fine">You can choose once. <?= $holdText !== '' ? 'If we hear nothing by ' . e($holdText) . ', we recycle it.' : 'If we hear nothing within ' . (int) $holdDays . ' days, we recycle it.' ?></p>
                    <button class="btn btn-primary btn-lg btn-block" type="submit">Confirm my choice</button>
                </form>
                <?php endif; ?>

            <?php elseif ($status === 'return_pending'): ?>
                <section class="acc-card acc-status acc-status-warn" aria-labelledby="st-h">
                    <h2 id="st-h">Your item is being returned to you</h2>
                    <p>You chose to have it sent back. When the courier hands it to you, please pay the <strong><?= e(AccountUi::amount($offer['return_fee'] ?? $returnFee)) ?></strong> return fee in cash (it covers the pickup and the return trip).</p>
                    <?php if (!empty($offer['reject_reason'])): ?><p class="acc-quote">Why we couldn't accept it: <?= e($offer['reject_reason']) ?></p><?php endif; ?>
                    <p><a class="btn btn-wa btn-sm" href="<?= e($waLink) ?>" rel="noopener" target="_blank"><?= Ui::icon('whatsapp', 18) ?> Message us</a></p>
                </section>

            <?php elseif ($status === 'returned'): ?>
                <section class="acc-card acc-status acc-status-done" aria-labelledby="st-h">
                    <h2 id="st-h">Your item was returned</h2>
                    <p>It is back with you. Thank you for choosing us, and you are welcome to send a new request any time.</p>
                    <p><a class="btn btn-primary" href="<?= e(url('/sell')) ?>">Get a new quote</a></p>
                </section>

            <?php elseif ($status === 'recycled'): ?>
                <section class="acc-card acc-status acc-status-off" aria-labelledby="st-h">
                    <h2 id="st-h">We recycled your item</h2>
                    <p><?= $offer['reject_choice'] === 'recycle' ? 'As you chose, we recycled it for free.' : 'We did not hear back within ' . (int) $holdDays . ' days, so we recycled it as agreed.' ?> Nothing was charged.</p>
                    <p><a class="btn btn-primary" href="<?= e(url('/sell')) ?>">Get a new quote</a></p>
                </section>

            <?php else: ?>
                <section class="acc-card acc-status acc-status-off" aria-labelledby="st-h">
                    <h2 id="st-h"><?= $status === 'declined' ? 'You declined this offer' : 'This request was cancelled' ?></h2>
                    <p>No games changed hands and nothing was charged. Changed your mind? You can always send a new request.</p>
                    <p><a class="btn btn-primary" href="<?= e(url('/sell')) ?>">Get a new quote</a></p>
                </section>
            <?php endif; ?>

            <section class="acc-card" aria-labelledby="games-h">
                <h2 id="games-h">Your games</h2>
                <?php if (!$items): ?>
                    <p class="acc-empty">No games listed.</p>
                <?php else: ?>
                    <ul class="acc-list">
                        <?php foreach ($items as $it): ?>
                            <li>
                                <span class="acc-list-main"><?= e($it['title'] ?? 'Game') ?>
                                    <small><?= e(trim(($it['platform'] ?? '') . (isset($it['condition']) && $it['condition'] !== '' ? ' · ' . $it['condition'] : ''), ' ·')) ?></small></span>
                                <?php if (!empty($it['matched']) && isset($it['offer'])): ?><strong><?= e(AccountUi::amount($it['offer'])) ?></strong><?php else: ?><small class="acc-muted">priced by our team</small><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($isTrade && $wanted): ?>
                    <h3 class="summary-sub">You want in return</h3>
                    <ul class="acc-list">
                        <?php foreach ($wanted as $w): ?>
                            <li><span class="acc-list-main"><?= e($w['title'] ?? ($w['name'] ?? 'Item')) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>

        <aside class="acc-card acc-summary" aria-labelledby="val-h">
            <h2 id="val-h">Values</h2>
            <dl class="acc-dl">
                <div><dt>Our estimate</dt><dd><?php if ($estCash > 0 || $estCredit > 0): ?><?= $estCash > 0 ? e(AccountUi::amount($estCash)) . ' cash' : '' ?><?= $estCash > 0 && $estCredit > 0 ? '<br>' : '' ?><?= $estCredit > 0 ? e(AccountUi::amount($estCredit)) . ' credit' : '' ?><?php else: ?>To be confirmed<?php endif; ?></dd></div>
                <?php if ($hasCash || $hasCredit): ?>
                    <div class="acc-dl-total"><dt>Our offer</dt><dd><?= $hasCredit ? e(AccountUi::amount($offer['offer_credit'])) . ' credit' : '' ?><?= $hasCash && $hasCredit ? '<br>' : '' ?><?= $hasCash ? e(AccountUi::amount($offer['offer_cash'])) . ' cash' : '' ?></dd></div>
                <?php endif; ?>
                <?php if ($zone !== ''): ?><div><dt>Zone</dt><dd><?= e($zone) ?><br><small><?= $remote ? 'Remote' : 'Local' ?></small></dd></div><?php endif; ?>
                <?php if ($storedFee > 0): ?><div><dt>Pickup fee</dt><dd>&minus;<?= e(AccountUi::amount($storedFee)) ?><br><small>deducted from your payout</small></dd></div><?php endif; ?>
                <?php if ($offer['accepted_method'] !== null): ?>
                    <div><dt>You chose</dt><dd><?= $offer['accepted_method'] === 'credit' ? 'Wallet credit' : 'Cash' ?></dd></div>
                    <div><dt>Handover</dt><dd><?= $offer['collection'] === 'pickup' ? 'We collect' : 'You drop off' ?></dd></div>
                <?php endif; ?>
            </dl>
            <p class="fine">Estimates assume the condition you selected. The final amount is confirmed when we inspect your games.</p>

            <?php if ($canCancel && $status !== 'offered'): ?>
                <form class="acc-cancel" method="post" action="<?= e($actionBase . '/cancel') ?>" data-confirm="Cancel this request?">
                    <?= csrf_field() ?>
                    <button class="btn btn-ghost btn-sm btn-block" type="submit">Cancel this request</button>
                </form>
            <?php endif; ?>
        </aside>
    </div>
</div>
<script src="<?= e(asset('js/account.js')) ?>" defer></script>
