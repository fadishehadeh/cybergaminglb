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
        <div class="card-head"><h2>Local vs remote delivery</h2><small class="muted">How buyer payment works outside your own courier's area.</small></div>
        <div class="card-body">
            <p class="hint"><strong>Local</strong> zones: your own courier delivers for the zone fee and can take cash on delivery. <strong>Remote</strong> zones: a third-party courier, so buyers prepay (OMT/Whish) unless they qualify for cash on delivery below. Set each zone's mode in <a href="#zones">Delivery zones</a>.</p>
            <div class="form-grid" data-remote-rules>
                <div class="field">
                    <label for="remote_prepay_required">Buyers outside the local area must pay first</label>
                    <select id="remote_prepay_required" name="remote_prepay_required">
                        <option value="1" <?= $v('remote_prepay_required') === '1' ? 'selected' : '' ?>>On: pay by OMT/Whish before we ship</option>
                        <option value="0" <?= $v('remote_prepay_required') === '0' ? 'selected' : '' ?>>Off: cash on delivery everywhere</option>
                    </select>
                    <small class="hint">On: a remote order waits for you to press "Mark payment received" and cannot be picked up or delivered before that.</small>
                </div>
                <div class="field">
                    <label for="remote_cod_after_orders">Cash on delivery after N delivered orders</label>
                    <input type="text" inputmode="numeric" id="remote_cod_after_orders" name="remote_cod_after_orders" value="<?= e($v('remote_cod_after_orders')) ?>" required>
                    <small class="hint">A trusted customer who already has this many delivered orders may pay cash on delivery even outside the local area. <strong>0 = never cash on delivery outside.</strong> Example: with 3, a buyer in the Bekaa pays first for their first three orders and may pay cash from the fourth.</small>
                </div>
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
                <small class="hint">Your pickup/return point, if you have one.</small>
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
