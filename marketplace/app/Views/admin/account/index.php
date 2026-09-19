<?php
$pageTitle = 'My account';
$nav = 'account';
?>
<div class="page-head">
    <div>
        <h1>My account</h1>
        <p class="muted"><?= e($me['name'] ?? '') ?> &middot; <?= e($me['email'] ?? '') ?><?= !empty($me['last_login_at']) ? ' &middot; last sign-in ' . e(date('j M Y, H:i', strtotime($me['last_login_at']))) : '' ?></p>
    </div>
</div>

<section class="card narrow-card">
    <div class="card-head"><h2>Change password</h2></div>
    <form method="post" action="<?= e(url('/admin/account')) ?>" class="card-body form-grid one" autocomplete="off">
        <?= csrf_field() ?>
        <div class="field">
            <label for="current_password">Current password</label>
            <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
        </div>
        <div class="field">
            <label for="new_password">New password</label>
            <input type="password" id="new_password" name="new_password" required minlength="10" autocomplete="new-password">
            <small class="hint">At least 10 characters. A few random words make a strong password.</small>
        </div>
        <div class="field">
            <label for="confirm_password">Confirm new password</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="10" autocomplete="new-password">
        </div>
        <div><button type="submit" class="btn btn-primary">Change password</button></div>
    </form>
</section>
