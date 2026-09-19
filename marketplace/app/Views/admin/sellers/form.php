<?php
use App\Modules\Admin\Forms;

$isEdit    = $seller !== null;
$pageTitle = $isEdit ? 'Edit seller ' . $seller['code'] : 'Add seller';
$nav       = 'sellers';
$s         = $seller ?? [];
$action    = $isEdit ? '/admin/sellers/' . $seller['id'] . '/edit' : '/admin/sellers/create';
$f         = static fn (string $key, mixed $default = '') => Forms::val($key, $s[$key] ?? $default);
$default   = (float) setting('commission_pct', 15);
?>
<div class="page-head">
    <div>
        <h1><?= e($pageTitle) ?></h1>
        <p class="muted">These details are private and only visible inside the admin panel.</p>
    </div>
    <div class="actions"><a class="btn btn-ghost" href="<?= e(url($isEdit ? '/admin/sellers/' . $seller['id'] : '/admin/sellers')) ?>">&larr; Back</a></div>
</div>

<form method="post" action="<?= e(url($action)) ?>" autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="_form" value="1">
    <div class="cols-form">
        <section class="card">
            <div class="card-head"><h2>Seller</h2><?php if ($isEdit): ?><span class="tag">Public code <?= e($seller['code']) ?></span><?php endif; ?></div>
            <div class="card-body form-grid">
                <div class="field span-2">
                    <label for="name">Name / store *</label>
                    <input type="text" id="name" name="name" value="<?= e($f('name')) ?>" maxlength="150" required>
                </div>
                <div class="field">
                    <label for="phone">Phone / WhatsApp</label>
                    <input type="text" id="phone" name="phone" value="<?= e($f('phone')) ?>" maxlength="40" placeholder="03 123 456">
                </div>
                <div class="field">
                    <label for="area">Area</label>
                    <input type="text" id="area" name="area" value="<?= e($f('area')) ?>" maxlength="120" placeholder="Jounieh">
                </div>
                <div class="field">
                    <label for="commission_pct">Commission override (%)</label>
                    <input type="text" inputmode="decimal" id="commission_pct" name="commission_pct" value="<?= e($f('commission_pct')) ?>" placeholder="Default (<?= e(Forms::pct($default)) ?>)">
                    <small class="hint">Leave blank to use the global default (<?= e(Forms::pct($default)) ?>). Saving a change re-prices this seller's listings.</small>
                </div>
                <div class="field">
                    <label for="payout_method">Payout method</label>
                    <input type="text" id="payout_method" name="payout_method" value="<?= e($f('payout_method')) ?>" maxlength="120" placeholder="OMT, Whish, cash at pickup...">
                </div>
                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status"><?= Forms::options(['active' => 'Active', 'pending' => 'Pending', 'suspended' => 'Suspended'], $f('status', 'active')) ?></select>
                </div>
                <div class="field span-2">
                    <label for="notes">Private notes</label>
                    <textarea id="notes" name="notes" rows="4"><?= e($f('notes')) ?></textarea>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-head"><h2>Portal login <span class="muted">(optional)</span></h2></div>
            <div class="card-body form-grid one">
                <p class="hint">Lets this seller sign in to the seller portal to see their own listings. Their user role is "seller".</p>
                <div class="field">
                    <label for="login_name">Login name</label>
                    <input type="text" id="login_name" name="login_name" value="<?= e(Forms::val('login_name', $login['name'] ?? '')) ?>" maxlength="120" placeholder="Same as seller name">
                </div>
                <div class="field">
                    <label for="login_email">Login email</label>
                    <input type="email" id="login_email" name="login_email" value="<?= e(Forms::val('login_email', $login['email'] ?? '')) ?>" maxlength="190" autocomplete="off">
                </div>
                <div class="field">
                    <label for="login_password"><?= $login ? 'New password' : 'Password' ?></label>
                    <input type="password" id="login_password" name="login_password" autocomplete="new-password" minlength="8" placeholder="<?= $login ? 'Leave blank to keep current' : 'At least 8 characters' ?>">
                </div>
                <?php if ($login): ?><small class="hint">Login is <?= e($login['status']) ?>. Suspending the seller disables the login.</small><?php endif; ?>
            </div>
        </section>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-lg"><?= $isEdit ? 'Save seller' : 'Create seller' ?></button>
        <a class="btn btn-ghost btn-lg" href="<?= e(url($isEdit ? '/admin/sellers/' . $seller['id'] : '/admin/sellers')) ?>">Cancel</a>
    </div>
</form>
