<?php
use App\Modules\Account\AccountUi;
use App\Modules\Storefront\Ui;

/** @var array $me @var array $offer @var array $items @var array $wanted @var string $hub @var float $pickupFee @var string $waLink */
$status = (string) $offer['status'];
$isTrade = $offer['kind'] === 'trade_in';
$kindLabel = $isTrade ? 'Trade-in request' : 'Sell request';
$meta = ['title' => $kindLabel . ' ' . $offer['code'] . ' | CyberGaming Lebanon', 'description' => 'Your request and our offer.', 'noindex' => true];
$accountNav = 'offers';
require base_path('app/Views/account/_nav.php');

[$statusLabel, $statusMod] = AccountUi::offerStatus($status);
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

                <form class="acc-card acc-form" method="post" action="<?= e($actionBase . '/accept') ?>" data-once data-offer-form>
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
                                <strong>I'll drop them off at your hub</strong>
                                <small><?= $hub !== '' ? e($hub) . '. ' : '' ?>We'll confirm a time on WhatsApp.</small>
                            </span>
                        </label>
                        <label class="acc-choice">
                            <input type="radio" name="collection" value="pickup"<?= $defColl === 'pickup' ? ' checked' : '' ?> required>
                            <span class="acc-choice-body">
                                <strong>Please collect them from me</strong>
                                <small>Delivery to your area is usually <?= e(AccountUi::amount($pickupFee)) ?>. We'll confirm any collection fee with you on WhatsApp before we come.</small>
                            </span>
                        </label>
                        <div class="form-row pickup-note" data-pickup-note>
                            <label for="o-note">Collection address and best time</label>
                            <textarea id="o-note" name="pickup_note" rows="3" maxlength="255" placeholder="Street, building, floor, landmark, and when you're free"><?= e($defNote) ?></textarea>
                            <small>Only needed if we collect.</small>
                        </div>
                    </fieldset>

                    <button class="btn btn-primary btn-lg btn-block" type="submit">Accept this offer</button>
                </form>

                <form class="acc-card acc-inline-form" method="post" action="<?= e($actionBase . '/decline') ?>" data-once>
                    <?= csrf_field() ?>
                    <p>Not interested? You can decline and keep your games.</p>
                    <button class="btn btn-ghost" type="submit">Decline the offer</button>
                </form>

            <?php elseif ($status === 'accepted'): ?>
                <section class="acc-card acc-status acc-status-ok" aria-labelledby="st-h">
                    <h2 id="st-h"><?= $offer['collection'] === 'pickup' ? "We'll collect your games" : 'Bring your games to us' ?></h2>
                    <?php if ($offer['collection'] === 'pickup'): ?>
                        <p>We'll contact you on WhatsApp to agree a time. Please have your games ready and in their cases.</p>
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
                    <p class="acc-final"><strong><?= e(AccountUi::amount($offer['final_amount'])) ?></strong>
                        <span><?= $offer['final_method'] === 'credit' ? 'added to your wallet as credit' : 'paid to you in cash' ?><?= $offer['completed_at'] ? ' on ' . e(AccountUi::date($offer['completed_at'])) : '' ?></span></p>
                    <p>
                        <a class="btn btn-primary" href="<?= e(url('/shop')) ?>"><?= Ui::icon('cart', 18) ?> Go shopping</a>
                        <?php if ($offer['final_method'] === 'credit'): ?><a class="btn btn-ghost" href="<?= e(url('/account/wallet')) ?>">See it in your wallet</a><?php endif; ?>
                    </p>
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
