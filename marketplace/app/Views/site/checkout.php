<?php
use App\Modules\Storefront\Digital;
use App\Modules\Storefront\Shipping;
use App\Modules\Storefront\Ui;

/**
 * @var array $cart @var bool $needsDelivery @var array $zones @var ?array $user @var float $balance @var bool $useCredit
 * @var array $values @var array $totals @var float $freeOver
 */
$isCustomer = $user !== null;
$hasDigital = !empty($cart['has_digital']);
// credit only pays for physical items + delivery, so a digital-only cart has no credit option
$hasCredit = $isCustomer && $balance > 0 && $needsDelivery;
$first = $isCustomer ? explode(' ', trim((string) $user['name']))[0] : '';
$cashText = static fn (?float $cash): string => $cash === null ? 'Choose your area' : ($cash <= 0 ? '$0 (paid with credit)' : money($cash));
?>
<div class="container page-head">
    <?= Ui::breadcrumbs([['Home', '/'], ['Cart', '/cart'], ['Checkout', null]]) ?>
    <h1>Checkout</h1>
    <?php if (!$needsDelivery): ?>
        <p class="lead-sm"><?= $isCustomer ? 'Hi ' . e($first) . ', ' : '' ?>this order is digital only: no delivery address needed. Tell us where to reach you on WhatsApp and we send your code after your payment is confirmed.</p>
    <?php elseif ($isCustomer): ?>
        <p class="lead-sm">Hi <?= e($first) ?>, we filled in your details from your account. Check them, pick your delivery area and place your order.</p>
    <?php else: ?>
        <p class="lead-sm">No account needed. Tell us where to reach you and we will confirm everything on WhatsApp.</p>
    <?php endif; ?>
</div>

<div class="container cart-layout">
    <form class="checkout-form card-box" method="post" action="<?= e(url('/checkout')) ?>" novalidate
          data-once data-checkout data-subtotal="<?= e(number_format((float) $totals['subtotal'], 2, '.', '')) ?>" data-physical="<?= e(number_format((float) $totals['physical'], 2, '.', '')) ?>" data-free-over="<?= e(number_format($freeOver, 2, '.', '')) ?>" data-balance="<?= e(number_format($balance, 2, '.', '')) ?>">
        <?= csrf_field() ?>
        <h2>Your details</h2>
        <div class="form-row">
            <label for="c-name">Full name <abbr title="required">*</abbr></label>
            <input id="c-name" name="name" type="text" required minlength="2" maxlength="120" autocomplete="name" value="<?= e($values['name']) ?>">
        </div>
        <div class="form-row">
            <label for="c-phone">Phone / WhatsApp number <abbr title="required">*</abbr></label>
            <input id="c-phone" name="phone" type="tel" required maxlength="40" inputmode="tel" autocomplete="tel" placeholder="+961 70 123 456" value="<?= e($values['phone']) ?>">
            <small><?= $needsDelivery ? 'We will message you on this number to confirm your order.' : 'We send your code and the payment details on this WhatsApp number.' ?></small>
        </div>
        <?php if ($needsDelivery): ?>
        <div class="form-row">
            <label for="c-area">Delivery area <abbr title="required">*</abbr></label>
            <select id="c-area" name="area" required data-area>
                <option value="" data-fee="">Choose your area…</option>
                <?php foreach ($zones as $z): ?>
                    <option value="<?= e($z['name']) ?>" data-fee="<?= e(number_format($z['fee'], 2, '.', '')) ?>"<?= $values['area'] === $z['name'] ? ' selected' : '' ?>><?= e(Shipping::label($z, (float) $totals['physical'])) ?></option>
                <?php endforeach; ?>
            </select>
            <small>The delivery fee depends on your area<?= $freeOver > 0 ? ' and is free for orders of ' . e(money($freeOver)) . ' or more' : '' ?><?= $hasDigital ? '. It applies to your physical items only' : '' ?>.</small>
        </div>
        <div class="form-row">
            <label for="c-address">Address details</label>
            <input id="c-address" name="address" type="text" maxlength="255" autocomplete="street-address" placeholder="Town, street, building, floor" value="<?= e($values['address']) ?>">
        </div>
        <?php endif; ?>
        <div class="form-row">
            <label for="c-note">Note (optional)</label>
            <textarea id="c-note" name="note" rows="3" maxlength="1000" placeholder="<?= $needsDelivery ? 'Preferred delivery time, pickup instead of delivery, etc.' : 'Anything we should know, e.g. your Steam profile name for a Steam gift.' ?>"><?= e($values['note']) ?></textarea>
        </div>
        <div class="hp" aria-hidden="true"><label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        <?php if ($hasCredit): ?>
            <div class="credit-box">
                <input type="hidden" name="credit_seen" value="<?= e(number_format($balance, 2, '.', '')) ?>">
                <label class="check-row" for="c-credit">
                    <input id="c-credit" type="checkbox" name="use_credit" value="1" data-use-credit<?= $useCredit ? ' checked' : '' ?>>
                    <span><strong>Pay with my credit</strong> <small>You have <?= e(money($balance)) ?> store credit. Whatever it does not cover is paid in cash on delivery.<?= $hasDigital ? ' <strong>Credit can\'t be used on gift cards</strong>: it only pays for your physical items and delivery.' : '' ?></small></span>
                </label>
            </div>
        <?php elseif ($isCustomer && !$needsDelivery): ?>
            <p class="pay-note"><?= Ui::icon('wallet', 20) ?> <span>Store credit can't be used on gift cards and digital items, so there is no credit option for this order<?= $balance > 0 ? ' (your ' . e(money($balance)) . ' credit stays in your wallet)' : '' ?>.</span></p>
        <?php elseif ($isCustomer): ?>
            <p class="pay-note"><?= Ui::icon('wallet', 20) ?> <span>You have no store credit yet. <a href="<?= e(url('/sell')) ?>">Sell us your games</a> to earn some.</span></p>
        <?php endif; ?>

        <?php if ($hasDigital): ?>
            <p class="pay-note pay-note-digital"><?= Ui::icon('gift', 20) ?> <span><strong>Digital items are prepaid.</strong> Pay <?= e(money($totals['digital'])) ?> by <?= e(Digital::PAYMENT_NOTE) ?> All digital sales are final once the code is delivered.<?= $needsDelivery ? ' Your physical items are still paid on delivery.' : '' ?></span></p>
        <?php endif; ?>
        <?php if ($needsDelivery): ?>
            <p class="pay-note"><?= Ui::icon('wallet', 20) ?> <span><strong>Pay cash on delivery<?= $hasCredit ? ', after any credit' : '' ?>, or OMT / Whish<?= $hasDigital ? ' (for the physical part)' : '' ?>.</strong> We confirm everything on WhatsApp before we ship, and nothing is charged online.</span></p>
        <?php else: ?>
            <p class="pay-note"><?= Ui::icon('chat', 20) ?> <span>Nothing is charged on the site. After you place the order we message you on WhatsApp with the OMT / Whish details, and send your code once we confirm the payment.</span></p>
        <?php endif; ?>
        <button class="btn btn-primary btn-lg btn-block" type="submit">Place order</button>
    </form>

    <div class="side-stack">
    <aside class="summary" aria-label="Order summary">
        <h2>Your order</h2>
        <ul class="mini-lines">
            <?php foreach ($cart['lines'] as ['product' => $p, 'qty' => $qty, 'line_total' => $lt]): ?>
                <li>
                    <?= Ui::cover($p, '', 48, 64, 'loading="lazy"') ?>
                    <span><?= e($p['title']) ?><?= Digital::is($p) ? ' <span class="tag tag-digital">Digital</span>' : '' ?><small><?= $qty ?> &times; <?= e(money($p['price'])) ?></small></span>
                    <strong><?= e(money($lt)) ?></strong>
                </li>
            <?php endforeach; ?>
        </ul>
        <dl class="totals" data-totals>
            <div><dt>Subtotal</dt><dd><?= e(money($totals['subtotal'])) ?></dd></div>
            <div><dt>Delivery</dt><dd data-out="fee"><?= !$needsDelivery ? 'None (digital)' : ($totals['fee'] === null ? 'Choose your area' : ($totals['fee'] <= 0 ? 'Free' : e(money($totals['fee'])))) ?></dd></div>
            <?php if ($hasCredit): ?>
                <div class="credit-row"<?= $totals['credit'] > 0 ? '' : ' hidden' ?> data-row="credit"><dt>Credit used</dt><dd>&minus;<span data-out="credit"><?= e(money($totals['credit'])) ?></span></dd></div>
            <?php endif; ?>
            <div class="grand"><dt>Total</dt><dd data-out="grand"><?= $totals['grand'] === null ? e(money($totals['subtotal'])) . ' + delivery' : e(money($totals['grand'])) ?></dd></div>
            <?php if ($hasDigital): ?>
                <div class="prepaid-due"><dt>Prepaid (OMT / Whish)</dt><dd><?= e(money($totals['prepaid'])) ?></dd></div>
            <?php endif; ?>
            <?php if ($needsDelivery): ?>
                <div class="cash-due"><dt>Cash due on delivery</dt><dd data-out="cash"><?= e($cashText($totals['cash'])) ?></dd></div>
            <?php endif; ?>
        </dl>
        <p class="fine"><a href="<?= e(url('/cart')) ?>">Edit cart</a></p>
    </aside>

    <?php if (!$isCustomer): ?>
        <aside class="card-box account-promo" aria-labelledby="promo-h">
            <h2 id="promo-h"><?= Ui::icon('wallet', 22) ?> Have games to sell?</h2>
            <p>Create an account to earn credit. Sell us games you no longer play and get store credit worth more than cash, then spend it right here at checkout.</p>
            <p class="promo-actions">
                <a class="btn btn-outline btn-sm" href="<?= e(url('/account/login?next=/checkout')) ?>">Sign in</a>
                <a class="btn btn-primary btn-sm" href="<?= e(url('/account/register?next=/checkout')) ?>">Create an account</a>
                <a class="btn-link" href="<?= e(url('/credit')) ?>">How store credit works</a>
            </p>
        </aside>
    <?php endif; ?>
    </div>
</div>
