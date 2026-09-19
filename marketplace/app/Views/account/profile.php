<?php
use App\Support\Alias;
use App\Support\Phone;

/** @var array $me @var string[] $zones @var array|null $seller */
$meta = ['title' => 'My profile | CyberGaming Lebanon', 'description' => 'Your CyberGaming account details.', 'noindex' => true];
$accountNav = 'profile';
require base_path('app/Views/account/_nav.php');
$errors = flash('form_errors', []);
$errors = is_array($errors) ? $errors : [];
$curArea = (string) old('area', (string) ($me['area'] ?? ''));
$alias = Alias::ensure((int) auth()->id());
?>
<div class="container acc-page">
    <h1>My profile</h1>

    <?php if ($errors): ?>
        <div class="form-errors" role="alert" tabindex="-1" data-focus-first>
            <strong>Please fix the following:</strong>
            <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <div class="acc-grid">
        <form class="acc-card acc-form" method="post" action="<?= e(url('/account/profile')) ?>" autocomplete="on" data-once>
            <?= csrf_field() ?>
            <h2>Your details</h2>
            <div class="form-row">
                <label for="p-email">Email</label>
                <input id="p-email" type="email" value="<?= e($me['email']) ?>" readonly aria-describedby="p-email-h">
                <small id="p-email-h">To change your email, <a href="<?= e(wa_link('Hi CyberGaming, I would like to change the email on my account (' . $me['email'] . ').')) ?>" rel="noopener" target="_blank">contact us</a>.</small>
            </div>
            <div class="form-row">
                <label for="p-name">Full name</label>
                <input id="p-name" name="name" type="text" required minlength="2" maxlength="120" autocomplete="name" value="<?= e(old('name', $me['name'])) ?>">
            </div>
            <div class="form-row">
                <label for="p-phone">Phone / WhatsApp</label>
                <input id="p-phone" name="phone" type="tel" required maxlength="40" inputmode="tel" autocomplete="tel" value="<?= e(old('phone', Phone::pretty((string) $me['phone']))) ?>">
            </div>
            <div class="form-row">
                <label for="p-area">Area</label>
                <select id="p-area" name="area" required>
                    <option value="">Choose your area</option>
                    <?php foreach ($zones as $z): ?>
                        <option value="<?= e($z) ?>"<?= $curArea === $z ? ' selected' : '' ?>><?= e($z) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label for="p-address">Address <span class="opt">(optional)</span></label>
                <input id="p-address" name="address" type="text" maxlength="255" autocomplete="street-address" value="<?= e(old('address', (string) ($me['address'] ?? ''))) ?>">
            </div>
            <button class="btn btn-primary" type="submit">Save changes</button>
        </form>

        <div>
            <section class="acc-card acc-hint-card" aria-labelledby="alias-h">
                <h2 id="alias-h">Your anonymous ID</h2>
                <p>Your anonymous ID: <strong><?= e($alias) ?></strong>. This is the only name other members would ever see &mdash; we never show your real name, phone or email to anyone.</p>
            </section>
            <form class="acc-card acc-form" id="password" method="post" action="<?= e(url('/account/profile/password')) ?>" autocomplete="off" data-once>
                <?= csrf_field() ?>
                <h2>Change password</h2>
                <div class="form-row">
                    <label for="pw-cur">Current password</label>
                    <input id="pw-cur" name="current_password" type="password" required autocomplete="current-password">
                </div>
                <div class="form-row">
                    <label for="pw-new">New password</label>
                    <input id="pw-new" name="new_password" type="password" required minlength="8" autocomplete="new-password" aria-describedby="pw-new-h">
                    <small id="pw-new-h">At least 8 characters.</small>
                </div>
                <div class="form-row">
                    <label for="pw-conf">Confirm new password</label>
                    <input id="pw-conf" name="confirm_password" type="password" required minlength="8" autocomplete="new-password">
                </div>
                <button class="btn btn-dark" type="submit">Change password</button>
            </form>

            <?php if ($seller): ?>
                <section class="acc-card acc-hint-card">
                    <h2>Member selling</h2>
                    <?php if ($seller['status'] === 'active'): ?>
                        <p>You can list games for other members. Buyers never see your name or number.</p>
                        <a class="btn btn-ghost btn-sm" href="<?= e(url('/account/listings')) ?>">Manage my listings</a>
                    <?php elseif ($seller['status'] === 'pending'): ?>
                        <p>Your member seller profile is waiting for approval. We'll message you when it's ready. <a href="<?= e(url('/account/listings')) ?>">See my listings page</a>.</p>
                    <?php else: ?>
                        <p>Your member seller profile is currently paused. Please <a href="<?= e(wa_link('Hi CyberGaming, about my member seller profile.')) ?>" rel="noopener" target="_blank">contact us</a>.</p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="<?= e(asset('js/account.js')) ?>" defer></script>
