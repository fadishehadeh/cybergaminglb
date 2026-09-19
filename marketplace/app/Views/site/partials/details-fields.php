<?php
/** Contact block shared by sell, trade and swap. @var array $details name/phone/area/note @var array $areas @var string $p id prefix */
$p = $p ?? 'd';
$withNote = $withNote ?? true;
$areaLabel = $areaLabel ?? 'Your area';
$areaHelp = $areaHelp ?? '';
?>
<div class="form-row">
    <label for="<?= e($p) ?>-name">Your name <abbr title="required">*</abbr></label>
    <input id="<?= e($p) ?>-name" name="name" type="text" required minlength="2" maxlength="120" autocomplete="name" value="<?= e($details['name'] ?? '') ?>">
</div>
<div class="form-row">
    <label for="<?= e($p) ?>-phone">Phone / WhatsApp number <abbr title="required">*</abbr></label>
    <input id="<?= e($p) ?>-phone" name="phone" type="tel" required maxlength="40" inputmode="tel" autocomplete="tel" placeholder="+961 70 123 456" value="<?= e($details['phone'] ?? '') ?>">
    <small>Only we see this. We never share it with other customers.</small>
</div>
<div class="form-row">
    <label for="<?= e($p) ?>-area"><?= e($areaLabel) ?> <abbr title="required">*</abbr></label>
    <select id="<?= e($p) ?>-area" name="area" required>
        <option value="">Choose your area…</option>
        <?php foreach ($areas as $a): ?><option value="<?= e($a) ?>"<?= ($details['area'] ?? '') === $a ? ' selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?>
    </select>
    <?php if ($areaHelp !== ''): ?><small><?= e($areaHelp) ?></small><?php endif; ?>
</div>
<?php if ($withNote): ?>
<div class="form-row">
    <label for="<?= e($p) ?>-note">Note <span class="opt">(optional)</span></label>
    <textarea id="<?= e($p) ?>-note" name="note" rows="3" maxlength="500" placeholder="Anything we should know: extras in the box, best time to reach you, etc."><?= e($details['note'] ?? '') ?></textarea>
</div>
<?php endif; ?>
<div class="hp" aria-hidden="true"><label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
