<?php
use App\Modules\Admin\Forms;

/** @var array $seller @var string $email @var float $commission @var bool $isOverride */
$pageTitle = 'Account';
$nav = 'account';
$f = static fn (string $key, mixed $default = '') => Forms::val($key, $default);
?>
<div class="sp-head">
    <div>
        <h1>Account</h1>
        <p class="sp-muted">Seller code <strong><?= e($seller['code']) ?></strong> &middot; signed in as <?= e($email) ?></p>
    </div>
</div>

<div class="sp-cols">
    <form class="sp-card sp-form" method="post" action="<?= e(url('/seller/account')) ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="_form" value="1">
        <h2>Your details</h2>
        <p class="sp-muted">Only CyberGaming sees these. They are never shown to buyers.</p>
        <div class="sp-fields sp-fields-one">
            <div class="sp-field">
                <label for="name">Store or seller name *</label>
                <input type="text" id="name" name="name" value="<?= e($f('name', $seller['name'])) ?>" maxlength="150" required>
            </div>
            <div class="sp-field">
                <label for="phone">Phone / WhatsApp *</label>
                <input type="tel" id="phone" name="phone" value="<?= e($f('phone', $seller['phone'] ?? '')) ?>" maxlength="40" required inputmode="tel">
            </div>
            <div class="sp-field">
                <label for="area">Area</label>
                <input type="text" id="area" name="area" value="<?= e($f('area', $seller['area'] ?? '')) ?>" maxlength="120">
            </div>
            <div class="sp-field">
                <label for="payout_method">Payout method</label>
                <input type="text" id="payout_method" name="payout_method" value="<?= e($f('payout_method', $seller['payout_method'] ?? '')) ?>" maxlength="120" placeholder="Cash, OMT, Whish...">
                <small>How you would like to be paid. We confirm the details with you.</small>
            </div>
        </div>
        <div class="sp-actions"><button type="submit" class="sp-btn sp-btn-primary">Save details</button></div>
    </form>

    <div class="sp-stack">
        <section class="sp-card">
            <h2>Commission</h2>
            <p class="sp-big"><?= e(Forms::pct($commission)) ?></p>
            <p class="sp-muted"><?= $isOverride ? 'Your agreed rate.' : 'Our standard rate.' ?> It is added on top of your price, so you always receive what you asked for. To discuss it, contact CyberGaming.</p>
        </section>

        <form class="sp-card sp-form" id="password" method="post" action="<?= e(url('/seller/account/password')) ?>" novalidate>
            <?= csrf_field() ?>
            <h2>Change password</h2>
            <div class="sp-fields sp-fields-one">
                <div class="sp-field">
                    <label for="current_password">Current password</label>
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                </div>
                <div class="sp-field">
                    <label for="new_password">New password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="10" autocomplete="new-password">
                    <small>At least 10 characters.</small>
                </div>
                <div class="sp-field">
                    <label for="confirm_password">Repeat new password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="10" autocomplete="new-password">
                </div>
            </div>
            <div class="sp-actions"><button type="submit" class="sp-btn">Change password</button></div>
        </form>
    </div>
</div>
