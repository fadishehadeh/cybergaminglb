<?php
use App\Modules\Admin\Forms;

$pageTitle = 'Settings';
$nav = 'settings';
$v = static fn (string $k) => Forms::val($k, $values[$k] ?? '');
$waIsPlaceholder = preg_replace('/\D+/', '', $values['whatsapp_number']) === '961';
?>
<div class="page-head">
    <div>
        <h1>Settings</h1>
        <p class="muted">Money rules and shop details. Changes apply straight away.</p>
    </div>
</div>

<?php $digitalOn = digital_enabled(); ?>
<section class="card digital-card <?= $digitalOn ? 'is-on' : 'is-off' ?>" id="digital">
    <div class="card-head">
        <h2>Digital goods &mdash; gift cards &amp; Steam gifts</h2>
        <span class="digital-state <?= $digitalOn ? 'on' : 'off' ?>" role="status">Digital: <?= $digitalOn ? 'ON' : 'OFF' ?></span>
    </div>
    <div class="card-body">
        <p><strong>OFF:</strong> gift cards and Steam gifts are hidden everywhere on the website (shop, search, home, sitemap, direct links).
            <strong>ON:</strong> they appear.</p>
        <p class="muted">Digital items are house stock, paid in advance by OMT/Whish. The code is sent on WhatsApp only after you confirm the payment. You always see them here in the admin, whatever the switch says.</p>

        <div class="digital-stats">
            <div><strong><?= (int) $digital['total'] ?></strong><span>digital products</span></div>
            <div><strong><?= (int) $digital['active'] ?></strong><span>active</span></div>
            <div><strong><?= (int) $digital['drafts'] ?></strong><span>hidden / pending drafts</span></div>
        </div>

        <div class="actions">
            <?php if ($digitalOn): ?>
                <form method="post" action="<?= e(url('/admin/settings/digital')) ?>" class="inline-form">
                    <?= csrf_field() ?><input type="hidden" name="digital_enabled" value="0">
                    <button class="btn btn-danger btn-lg" type="submit">Turn digital goods OFF</button>
                </form>
            <?php else: ?>
                <form method="post" action="<?= e(url('/admin/settings/digital')) ?>" class="inline-form"
                      data-confirm="<?= e('Turn digital goods ON? ' . $digital['live'] . ' active digital product' . ($digital['live'] === 1 ? '' : 's') . ' will appear on the website (shop, search, home and sitemap). Make sure prices and stock are right, and that you can process prepaid orders on WhatsApp.') ?>">
                    <?= csrf_field() ?><input type="hidden" name="digital_enabled" value="1">
                    <button class="btn btn-primary btn-lg" type="submit">Turn digital goods ON</button>
                </form>
            <?php endif; ?>
            <a class="btn" href="<?= e(url('/admin/products?kind=digital')) ?>">View digital products</a>
        </div>

        <h3 class="sub-title">Starter catalogue</h3>
        <p class="hint">Adds about 14 gift cards and Steam gifts (PlayStation, Steam Wallet, Xbox, Nintendo eShop, PlayStation Plus) as <strong>hidden drafts</strong> with a placeholder price of face value + 5%. They stay hidden until you edit the prices and publish each one, and the switch above is ON. Pressing it again never creates duplicates.</p>
        <form method="post" action="<?= e(url('/admin/settings/digital/starter')) ?>" class="inline-form">
            <?= csrf_field() ?>
            <button class="btn" type="submit">Add starter gift cards (as drafts)</button>
        </form>
    </div>
</section>

<form method="post" action="<?= e(url('/admin/settings')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="_form" value="1">

    <section class="card">
        <div class="card-head"><h2>Commission &amp; pricing</h2></div>
        <div class="card-body form-grid">
            <div class="field">
                <label for="commission_pct">Default commission (%)</label>
                <input type="text" inputmode="decimal" id="commission_pct" name="commission_pct" value="<?= e($v('commission_pct')) ?>" required data-example="commission">
                <small class="hint">Added on top of the seller's price. Example: a $10 seller price with <span data-ex-pct>15</span>% commission is listed at <strong data-ex-out>$12</strong> (rounded up to $0.50). Sellers can override this on their own page. Changing it re-prices every seller listing.</small>
            </div>
            <div class="field">
                <label for="member_commission_pct">Member commission (%)</label>
                <input type="text" inputmode="decimal" id="member_commission_pct" name="member_commission_pct" value="<?= e($v('member_commission_pct')) ?>" required data-example="member-commission">
                <small class="hint">Commission on member-to-member sales (customers who list games for other members). Example: a $10 member price with <span data-ex-mpct>10</span>% commission is listed at <strong data-ex-mout>$11</strong>. A member with their own override keeps it. Changing it re-prices every member listing.</small>
            </div>
            <div class="field">
                <label for="buyback_pct">Buy-back (%)</label>
                <input type="text" inputmode="decimal" id="buyback_pct" name="buyback_pct" value="<?= e($v('buyback_pct')) ?>" required>
                <small class="hint">What we pay a customer for a used item, as a share of its resale price. Example: a $20 game at 45% is bought for $9.</small>
            </div>
            <div class="field">
                <label for="tradein_pct">Trade-in credit (%)</label>
                <input type="text" inputmode="decimal" id="tradein_pct" name="tradein_pct" value="<?= e($v('tradein_pct')) ?>" required>
                <small class="hint">Store credit for a trade-in, as a share of resale price. Example: a $20 game at 50% gives $10 credit.</small>
            </div>
            <div class="field">
                <label for="swap_fee">Swap fee ($)</label>
                <input type="text" inputmode="decimal" id="swap_fee" name="swap_fee" value="<?= e($v('swap_fee')) ?>" required>
                <small class="hint">Flat fee charged for running a customer-to-customer swap through the hub.</small>
            </div>
        </div>
    </section>

    <section class="card" id="delivery">
        <div class="card-head"><h2>Delivery fees</h2></div>
        <div class="card-body form-grid">
            <div class="field">
                <label for="delivery_fee">Default delivery fee ($)</label>
                <input type="text" inputmode="decimal" id="delivery_fee" name="delivery_fee" value="<?= e($v('delivery_fee')) ?>" required>
                <small class="hint">Used when the customer's area is not one of the zones below (or the zone is switched off). Example: with a $4 default, a delivery to an unlisted area adds $4 to the order.</small>
            </div>
            <div class="field">
                <label for="free_delivery_over">Free delivery over ($)</label>
                <input type="text" inputmode="decimal" id="free_delivery_over" name="free_delivery_over" value="<?= e($v('free_delivery_over')) ?>" required data-example="free-delivery">
                <small class="hint">Orders with an items subtotal at or above this get free delivery. <strong>0 turns it off.</strong> Example: <span data-ex-free>set to 0: every order pays its zone fee</span>.</small>
            </div>
        </div>
    </section>
    <section class="card" id="remote-rules">
        <div class="card-head"><h2>Local vs remote delivery</h2><small class="muted">How orders and sell requests work outside your own courier's area.</small></div>
        <div class="card-body">
            <p class="hint"><strong>Local</strong> zones: your own courier delivers for the zone fee, can inspect on the spot and can take cash. <strong>Remote</strong> zones: a third-party courier who cannot inspect, so buyers prepay (OMT/Whish) and sellers ship to your hub, where you inspect. Set each zone's mode in <a href="#zones">Delivery zones</a>.</p>
            <div class="form-grid" data-remote-rules>
                <div class="field">
                    <label for="remote_prepay_required">Buyers outside the local area must pay first</label>
                    <select id="remote_prepay_required" name="remote_prepay_required">
                        <option value="1" <?= $v('remote_prepay_required') === '1' ? 'selected' : '' ?>>On: pay by OMT/Whish before we ship</option>
                        <option value="0" <?= $v('remote_prepay_required') === '0' ? 'selected' : '' ?>>Off: cash on delivery everywhere</option>
                    </select>
                    <small class="hint">On: a remote order waits for you to press "Mark payment received" and cannot be picked up or delivered before that. We inspect, photograph and seal the items at our hub.</small>
                </div>
                <div class="field">
                    <label for="remote_cod_after_orders">Cash on delivery after N delivered orders</label>
                    <input type="text" inputmode="numeric" id="remote_cod_after_orders" name="remote_cod_after_orders" value="<?= e($v('remote_cod_after_orders')) ?>" required>
                    <small class="hint">A trusted customer who already has this many delivered orders may pay cash on delivery even outside the local area. <strong>0 = never cash on delivery outside.</strong> Example: with 3, a buyer in the Bekaa pays first for their first three orders and may pay cash from the fourth.</small>
                </div>
                <div class="field">
                    <label for="remote_min_sell_value">Minimum shipment value from outside ($)</label>
                    <input type="text" inputmode="decimal" id="remote_min_sell_value" name="remote_min_sell_value" value="<?= e($v('remote_min_sell_value')) ?>" required>
                    <small class="hint">A seller outside the local area must send games worth at least this (our estimate), so the courier trip is worth it. <strong>0 = no minimum.</strong> Example: at $25, a $20 shipment is refused and a $30 one is accepted.</small>
                </div>
                <div class="field">
                    <label for="pickup_fee">Pickup fee ($)</label>
                    <input type="text" inputmode="decimal" id="pickup_fee" name="pickup_fee" value="<?= e($v('pickup_fee')) ?>" required data-rr-pickup>
                    <small class="hint">Charged to a seller when a courier collects the games (our courier locally, the third-party courier outside). It is deducted from their payout; bringing the games to the hub is free. Example: a seller outside the area ships $40 of games and you offer $18: with a <span data-ex-pickup><?= e(money((float) $values['pickup_fee'])) ?></span> pickup fee they receive <strong data-ex-net><?= e(money(max(0, 18 - (float) $values['pickup_fee']))) ?></strong>.</small>
                </div>
                <div class="field">
                    <label for="return_fee">Return fee ($)</label>
                    <input type="text" inputmode="decimal" id="return_fee" name="return_fee" value="<?= e($v('return_fee')) ?>" required data-rr-return>
                    <small class="hint">When an item fails inspection and the seller asks for it back, they pay this in cash to the courier on the return delivery (pickup + return trip). Example: with <span data-ex-return><?= e(money((float) $values['return_fee'])) ?></span>, the courier collects that amount when handing the games back. Nothing is charged for a revised offer or for recycling.</small>
                </div>
                <div class="field">
                    <label for="reject_hold_days">Days to wait for a decision</label>
                    <input type="text" inputmode="numeric" id="reject_hold_days" name="reject_hold_days" value="<?= e($v('reject_hold_days')) ?>" required>
                    <small class="hint">After an item fails inspection the seller can accept a lower offer, get it back, or let us recycle it. With no answer by this many days, it is recycled (we keep it, no payment). Example: rejected today with 14 days, it is recycled once the 14th day has passed unless they reply.</small>
                </div>
            </div>
            <div class="example-box">
                <h3>How a sell request from outside the local area plays out</h3>
                <ol class="plain-steps">
                    <li>The seller ships $40 of games to your hub. You inspect them and they are fine, so you pay the $18 offer: <strong>they receive $18 &minus; <?= e(money((float) $values['pickup_fee'])) ?> pickup fee = <?= e(money(max(0, 18 - (float) $values['pickup_fee']))) ?></strong>, in cash or credit.</li>
                    <li>They arrive damaged: you reject them and may propose a lower revised offer. The seller chooses: take the revised offer, get the games back (<?= e(money((float) $values['return_fee'])) ?> cash to the courier), or let you recycle them.</li>
                    <li>Local sellers can bring the games to your hub for free, or let your own courier check them on the spot for the pickup fee. If the courier declines them on the spot there is no return trip and no fee.</li>
                </ol>
            </div>
        </div>
    </section>
    <section class="card" id="buyback-factors">
        <div class="card-head"><h2>Buy-back condition factors</h2></div>
        <div class="card-body">
            <p class="hint">What we pay for a used item depends on its condition. Each factor is a <strong>percentage (0 to 100) of the standard buy-back and trade-in %</strong> above. Example: with buy-back at 45% and "Good" at 90%, a Good item is bought at 40.5% of its resale price.</p>
            <div class="form-grid">
                <?php foreach (\App\Modules\Admin\SettingsController::FACTORS as $key => [$label]): ?>
                    <div class="field">
                        <label for="<?= e($key) ?>"><?= e($label) ?> condition (%)</label>
                        <input type="text" inputmode="decimal" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($v($key)) ?>" required data-factor="<?= e($label) ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="example-box" data-buyback-example data-price="<?= e((string) $example['price']) ?>">
                <h3>Worked example: a $<?= e((string) (int) $example['price']) ?> game (resale price)</h3>
                <table>
                    <thead><tr><th>Condition</th><th class="num">Factor</th><th class="num">We pay (cash)</th><th class="num">Store credit</th></tr></thead>
                    <tbody>
                    <?php foreach ($example['rows'] as $row): ?>
                        <tr class="<?= $row['label'] === 'Good' ? 'hl' : '' ?>" data-ex-row="<?= e($row['label']) ?>">
                            <td><?= e($row['label']) ?></td>
                            <td class="num" data-ex-factor><?= e(Forms::pct($row['factor'])) ?></td>
                            <td class="num" data-ex-cash><?= e(money($row['cash'])) ?></td>
                            <td class="num" data-ex-credit><?= e(money($row['credit'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="muted" data-ex-sentence>A $<?= e((string) (int) $example['price']) ?> game in Good condition: we pay <strong data-ex-good-cash><?= e(money(\App\Support\Pricing::buybackOffer($example['price'], 'Good'))) ?></strong>, or credit <strong data-ex-good-credit><?= e(money(\App\Support\Pricing::tradeCredit($example['price'], 'Good'))) ?></strong>. Amounts are rounded down to the next $0.50. The table above shows the saved settings and updates as you type.</p>
            </div>
        </div>
    </section>

    <section class="card" id="whatsapp">
        <div class="card-head"><h2>Shop details</h2></div>
        <div class="card-body form-grid">
            <div class="field">
                <label for="whatsapp_number">WhatsApp number</label>
                <input type="text" inputmode="numeric" id="whatsapp_number" name="whatsapp_number" value="<?= e($v('whatsapp_number')) ?>" placeholder="96170123456">
                <small class="hint <?= $waIsPlaceholder ? 'text-warn' : '' ?>">Digits only, with country code, no + or spaces (e.g. 96170123456). Used for every "chat on WhatsApp" button.<?= $waIsPlaceholder ? ' It is still the placeholder 961.' : '' ?></small>
            </div>
            <div class="field">
                <label for="contact_email">Contact email</label>
                <input type="email" id="contact_email" name="contact_email" value="<?= e($v('contact_email')) ?>" maxlength="190">
            </div>
            <div class="field">
                <label for="site_name">Site name</label>
                <input type="text" id="site_name" name="site_name" value="<?= e($v('site_name')) ?>" maxlength="100" required>
            </div>
            <div class="field">
                <label for="instagram_url">Instagram URL</label>
                <input type="url" id="instagram_url" name="instagram_url" value="<?= e($v('instagram_url')) ?>" maxlength="255" placeholder="https://www.instagram.com/...">
            </div>
            <div class="field span-2">
                <label for="tagline">Tagline</label>
                <input type="text" id="tagline" name="tagline" value="<?= e($v('tagline')) ?>" maxlength="200">
            </div>
            <div class="field span-2">
                <label for="hub_address">Hub address</label>
                <input type="text" id="hub_address" name="hub_address" value="<?= e($v('hub_address')) ?>" maxlength="255">
                <small class="hint">Where customers drop off and collect items for swaps and buy-backs.</small>
            </div>
        </div>
    </section>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-lg">Save settings</button>
    </div>
</form>

<section class="card" id="zones">
    <div class="card-head"><h2>Delivery zones</h2><small class="muted">Each zone has its own fee and mode. Customers pick their zone when they order.</small></div>
    <div class="card-body">
        <p class="hint"><strong>Local = your own courier can inspect and take cash; Remote = third-party courier, prepay + hub inspection.</strong> Example: with Beirut at $5, a $30 order to Beirut costs $35 in total. Deactivating a zone hides it from customers; it is never deleted. Lower sort numbers are listed first.</p>
        <?php if ($zones): ?>
            <ul class="zone-summary" aria-label="Zones at a glance">
                <?php foreach ($zones as $z): ?>
                    <li class="<?= (int) $z['is_active'] ? '' : 'zone-off' ?>"><?= Forms::modeBadge($z['mode']) ?> <?= e($z['name']) ?> <strong><?= e(money($z['fee'])) ?></strong><?= (int) $z['is_active'] ? '' : ' <small class="muted">(hidden)</small>' ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if (!$zones): ?>
            <p class="empty-inline">No zones yet. Every delivery uses the default fee. Add your first zone below.</p>
        <?php else: ?>
            <div class="zone-head" aria-hidden="true"><span>Zone</span><span>Fee ($)</span><span>Mode</span><span>Sort</span><span>Active</span><span></span></div>
            <?php foreach ($zones as $z): ?>
                <form method="post" action="<?= e(url('/admin/delivery/zones/' . $z['id'])) ?>" class="zone-row<?= (int) $z['is_active'] ? '' : ' zone-off' ?>">
                    <?= csrf_field() ?>
                    <input type="text" name="name" value="<?= e($z['name']) ?>" maxlength="80" required aria-label="Zone name">
                    <input type="text" inputmode="decimal" name="fee" value="<?= e($z['fee']) ?>" required aria-label="Fee for <?= e($z['name']) ?>">
                    <select name="mode" aria-label="Mode for <?= e($z['name']) ?>" required><?= Forms::options(\App\Modules\Admin\DeliveryController::MODES, $z['mode']) ?></select>
                    <input type="text" inputmode="numeric" name="sort_order" value="<?= (int) $z['sort_order'] ?>" aria-label="Sort order">
                    <label class="check"><input type="checkbox" name="is_active" value="1" <?= (int) $z['is_active'] ? 'checked' : '' ?>> <span class="zone-active-label">Active</span></label>
                    <button class="btn btn-sm" type="submit">Save</button>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>

        <h3 class="sub-title">Add a zone</h3>
        <form method="post" action="<?= e(url('/admin/delivery/zones')) ?>" class="zone-row zone-new">
            <?= csrf_field() ?>
            <input type="text" name="name" value="<?= e(Forms::val('new_zone_name', '')) ?>" maxlength="80" placeholder="Zone name" required aria-label="New zone name">
            <input type="text" inputmode="decimal" name="fee" value="<?= e(Forms::val('new_zone_fee', '')) ?>" placeholder="Fee" required aria-label="New zone fee">
            <select name="mode" aria-label="New zone mode" required><option value="">Mode...</option><?= Forms::options(\App\Modules\Admin\DeliveryController::MODES, Forms::val('new_zone_mode', '')) ?></select>
            <input type="text" inputmode="numeric" name="sort_order" value="<?= e(Forms::val('new_zone_sort', '')) ?>" placeholder="Sort" aria-label="New zone sort order">
            <label class="check"><input type="checkbox" name="is_active" value="1" checked> <span class="zone-active-label">Active</span></label>
            <button class="btn btn-primary btn-sm" type="submit">Add zone</button>
        </form>
    </div>
</section>
<section class="card">
    <div class="card-head"><h2>Recalculate prices</h2></div>
    <div class="card-body">
        <p>Re-applies the current commission rules to every seller listing (using each seller's override if they have one). House stock is unaffected apart from $0.50 rounding. Orders already placed keep the prices they were sold at.</p>
        <form method="post" action="<?= e(url('/admin/settings/recalculate')) ?>" data-confirm="Recalculate the buyer price of all products from the current commission settings?">
            <?= csrf_field() ?>
            <button type="submit" class="btn">Recalculate all prices</button>
        </form>
    </div>
</section>
