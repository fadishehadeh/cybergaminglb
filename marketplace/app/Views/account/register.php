<?php
use App\Modules\Storefront\Ui;

/** @var string[] $zones @var string $next */
$meta = [
    'title'       => 'Create your account | Earn credit for your games | CyberGaming Lebanon',
    'description' => 'Join CyberGaming Lebanon: sell your used games for cash or store credit, check out faster and track every order. Free account, no fees.',
    'canonical'   => url('/account/register'),
];
$errors = flash('form_errors', []);
$errors = is_array($errors) ? $errors : [];
$loginHref = url('/account/login') . ($next !== '' ? '?next=' . rawurlencode($next) : '');
?>
<link rel="stylesheet" href="<?= e(asset('css/account.css')) ?>">
<div class="container page-head">
    <?= Ui::breadcrumbs([['Home', '/'], ['Create account', null]]) ?>
    <h1>Create your CyberGaming account</h1>
    <p class="lead-sm">Turn the games you finished into credit for the next one. Free to join, no monthly fees.</p>
</div>

<div class="container acc-auth">
    <section class="acc-card acc-benefits" aria-labelledby="benefits-h">
        <h2 id="benefits-h">Why create an account?</h2>
        <ul class="acc-checks">
            <li><?= Ui::icon('wallet', 22) ?><div><strong>Earn credit for your games</strong><span>Sell us games and take store credit (1 credit = $1) or cash. Credit never expires.</span></div></li>
            <li><?= Ui::icon('cart', 22) ?><div><strong>Checkout faster</strong><span>Your details are ready, and you can pay with credit and top up the rest in cash on delivery.</span></div></li>
            <li><?= Ui::icon('box', 22) ?><div><strong>Track every order and offer</strong><span>See where your order is and accept or decline our offers in one place.</span></div></li>
            <li><?= Ui::icon('lock', 22) ?><div><strong>Private by design</strong><span>Buyers and sellers never see each other's name or number.</span></div></li>
        </ul>
        <p class="fine">Curious how credit works? Read about <a href="<?= e(url('/credit')) ?>">CyberGaming credit</a>. Already a member? <a href="<?= e($loginHref) ?>">Sign in</a>.</p>
    </section>

    <form class="acc-card acc-form" method="post" action="<?= e(url('/account/register')) ?>" autocomplete="on" data-once>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <h2>Your details</h2>
        <?php if ($errors): ?>
            <div class="form-errors" role="alert" tabindex="-1" data-focus-first>
                <strong>Please fix the following:</strong>
                <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <div class="hp" aria-hidden="true">
            <label for="r-website">Leave this field empty</label>
            <input id="r-website" type="text" name="website" tabindex="-1" autocomplete="off" value="">
        </div>

        <div class="form-row">
            <label for="r-name">Full name <abbr title="required">*</abbr></label>
            <input id="r-name" name="name" type="text" required minlength="2" maxlength="120" autocomplete="name" value="<?= e(old('name')) ?>">
        </div>
        <div class="form-row">
            <label for="r-phone">Phone / WhatsApp number <abbr title="required">*</abbr></label>
            <input id="r-phone" name="phone" type="tel" required maxlength="40" inputmode="tel" autocomplete="tel" placeholder="e.g. 70 123 456" value="<?= e(old('phone')) ?>" aria-describedby="r-phone-h">
            <small id="r-phone-h">We confirm orders and offers on WhatsApp. You can also sign in with this number.</small>
        </div>
        <div class="form-row">
            <label for="r-email">Email <abbr title="required">*</abbr></label>
            <input id="r-email" name="email" type="email" required maxlength="190" autocomplete="email" value="<?= e(old('email')) ?>">
        </div>
        <div class="form-grid">
            <div class="form-row">
                <label for="r-pass">Password <abbr title="required">*</abbr></label>
                <input id="r-pass" name="password" type="password" required minlength="8" autocomplete="new-password" aria-describedby="r-pass-h">
                <small id="r-pass-h">At least 8 characters.</small>
            </div>
            <div class="form-row">
                <label for="r-pass2">Confirm password <abbr title="required">*</abbr></label>
                <input id="r-pass2" name="password_confirm" type="password" required minlength="8" autocomplete="new-password">
            </div>
        </div>
        <div class="form-row">
            <label for="r-area">Area <abbr title="required">*</abbr></label>
            <select id="r-area" name="area" required autocomplete="address-level1">
                <option value="">Choose your area</option>
                <?php foreach ($zones as $z): ?>
                    <option value="<?= e($z) ?>"<?= old('area') === $z ? ' selected' : '' ?>><?= e($z) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="r-address">Address <span class="opt">(optional)</span></label>
            <input id="r-address" name="address" type="text" maxlength="255" autocomplete="street-address" placeholder="Street, building, floor" value="<?= e(old('address')) ?>">
        </div>
        <div class="form-row form-check">
            <label class="check" for="r-terms">
                <input id="r-terms" name="terms" type="checkbox" value="1" required<?= old('terms') === '1' ? ' checked' : '' ?>>
                <span>I agree to the <a href="<?= e(url('/delivery-and-payment')) ?>" target="_blank" rel="noopener">delivery &amp; payment terms</a> and confirm that the games I sell are mine to sell.</span>
            </label>
        </div>

        <button class="btn btn-primary btn-lg btn-block" type="submit">Create my account</button>
        <p class="fine">Already have an account? <a href="<?= e($loginHref) ?>">Sign in</a>. Guests can still buy with cash on delivery, no account needed.</p>
    </form>
</div>
<script src="<?= e(asset('js/account.js')) ?>" defer></script>
