<?php
use App\Modules\Storefront\Cart;
use App\Modules\Storefront\Digital;
use App\Modules\Storefront\Ui;

/** @var array $cart @var array $suggest @var float $minFee @var float $freeOver @var ?float $balance */
$hasDigital = !empty($cart['has_digital']);
$hasPhysical = !empty($cart['has_physical']);
$physical = (float) ($cart['physical'] ?? $cart['total']);
?>
<div class="container page-head">
    <?= Ui::breadcrumbs([['Home', '/'], ['Cart', null]]) ?>
    <h1>Your cart</h1>
</div>

<div class="container">
<?php if (!$cart['lines']): ?>
    <div class="empty">
        <?= Ui::icon('cart', 44) ?>
        <h2>Your cart is empty</h2>
        <p>Find something you like and it will show up here.</p>
        <p><a class="btn btn-primary" href="<?= e(url('/shop')) ?>">Start shopping</a></p>
    </div>
    <?php if ($suggest): ?>
        <section class="section" aria-labelledby="sg-h">
            <div class="section-head"><h2 id="sg-h">Latest arrivals</h2></div>
            <div class="product-grid"><?php foreach ($suggest as $p): ?><?= Ui::partial('product-card', ['p' => $p, 'level' => 'h3']) ?><?php endforeach; ?></div>
        </section>
    <?php endif; ?>
<?php else: ?>
    <div class="cart-layout">
        <ul class="cart-lines">
            <?php foreach ($cart['lines'] as ['product' => $p, 'qty' => $qty, 'line_total' => $lt]):
                $max = max(1, min((int) $p['stock'], Cart::MAX_LINE_QTY));
                $dig = Digital::is($p); ?>
                <li class="cart-line">
                    <a class="cart-thumb" href="<?= e(url('/product/' . $p['slug'])) ?>"><?= Ui::cover($p, $p['title'] . ($dig ? '' : ' cover'), 90, 120, 'loading="lazy"') ?></a>
                    <div class="cart-info">
                        <h2><a href="<?= e(url('/product/' . $p['slug'])) ?>"><?= e($p['title']) ?></a></h2>
                        <?php if ($dig): ?>
                            <p class="card-meta"><span class="tag tag-digital">Digital</span> <?= e(implode(' · ', array_filter([Digital::kindLabel($p['digital_kind'] ?? null), Digital::region($p) !== '' ? 'Region ' . Digital::region($p) : '']))) ?> &middot; <?= e(money($p['price'])) ?> each</p>
                            <p class="card-meta">Code sent on WhatsApp after you pay by OMT / Whish.</p>
                        <?php else: ?>
                            <p class="card-meta"><?= e(implode(' · ', array_filter([Ui::shortPlatform($p['platform_slug'], $p['platform_name']), $p['item_condition']]))) ?> &middot; <?= e(money($p['price'])) ?> each</p>
                        <?php endif; ?>
                        <div class="cart-actions">
                            <form method="post" action="<?= e(url('/cart/update')) ?>" class="qty-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                                <?php if ($max > 1): ?>
                                    <label for="q<?= (int) $p['id'] ?>" class="sr-only">Quantity for <?= e($p['title']) ?></label>
                                    <select id="q<?= (int) $p['id'] ?>" name="qty" data-autosubmit><?php for ($i = 1; $i <= $max; $i++): ?><option value="<?= $i ?>"<?= $i === $qty ? ' selected' : '' ?>><?= $i ?></option><?php endfor; ?></select>
                                    <button class="btn btn-ghost btn-sm" type="submit">Update</button>
                                <?php else: ?>
                                    <span class="card-meta"><?= $dig ? 'Qty 1' : 'Qty 1 (last copy)' ?></span>
                                <?php endif; ?>
                            </form>
                            <form method="post" action="<?= e(url('/cart/remove')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                                <button class="btn-link" type="submit">Remove</button>
                            </form>
                        </div>
                    </div>
                    <p class="cart-line-total"><?= e(money($lt)) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>

        <aside class="summary" aria-label="Order summary">
            <h2>Summary</h2>
            <dl>
                <div><dt>Items</dt><dd><?= (int) $cart['count'] ?></dd></div>
                <div><dt>Delivery</dt><dd><?= $hasPhysical ? 'from ' . e(money($minFee)) : 'None (digital)' ?></dd></div>
                <div class="grand"><dt>Subtotal</dt><dd><?= e(money($cart['total'])) ?></dd></div>
            </dl>
            <?php if ($hasPhysical): ?>
                <p class="fine delivery-note"><?= Ui::icon('truck', 16) ?> Delivery from <?= e(money($minFee)) ?> &mdash; exact fee at checkout, by area<?= $hasDigital ? ' (charged on the physical items only)' : '' ?>.
                    <?php if ($freeOver > 0): ?>
                        <br><strong>Free delivery over <?= e(money($freeOver)) ?></strong><?php if ($physical < $freeOver): ?> &mdash; add <?= e(money(round($freeOver - $physical, 2))) ?> more<?= $hasDigital ? ' in physical items' : '' ?> to get it.<?php else: ?> &mdash; you qualify!<?php endif; ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
            <?php if ($hasDigital): ?>
                <p class="fine digital-note"><?= Ui::icon('gift', 16) ?> <span><strong>Gift cards &amp; digital codes are paid up front.</strong> Pay by OMT or Whish before we send your code on WhatsApp &mdash; no cash on delivery, and all digital sales are final once the code is delivered.<?= $hasPhysical ? ' Physical items in the same order are still paid on delivery.' : '' ?></span></p>
            <?php endif; ?>
            <?php if ($balance !== null && $balance > 0): ?>
                <?php if ($hasPhysical): ?>
                    <p class="fine credit-note"><?= Ui::icon('wallet', 16) ?> You have <strong><?= e(money($balance)) ?></strong> store credit to use at checkout<?= $hasDigital ? ' (credit can\'t be used on gift cards, only on your physical items and delivery)' : '' ?>.</p>
                <?php else: ?>
                    <p class="fine credit-note"><?= Ui::icon('wallet', 16) ?> Store credit can't be used on gift cards.</p>
                <?php endif; ?>
            <?php endif; ?>
            <a class="btn btn-primary btn-lg btn-block" href="<?= e(url('/checkout')) ?>">Checkout</a>
            <p class="fine"><?= $hasDigital && !$hasPhysical ? 'Nothing is charged on the site: we send you the OMT / Whish payment details on WhatsApp.' : 'Pay with store credit and/or cash on delivery. No card details, nothing is charged on the site.' ?></p>
            <form method="post" action="<?= e(url('/cart/clear')) ?>">
                <?= csrf_field() ?>
                <button class="btn-link" type="submit">Empty cart</button>
            </form>
        </aside>
    </div>
<?php endif; ?>
</div>
