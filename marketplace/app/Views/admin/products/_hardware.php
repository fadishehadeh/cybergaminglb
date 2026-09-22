<?php
/**
 * Hardware-only fields of the admin product form (shown when the selected category's kind is "hardware").
 * @var array $p product row (or [])  @var bool $isEdit  @var string $mode  @var string[] $brands  @var string $specExample newline-joined example lines
 */
use App\Modules\Admin\Forms;
use App\Modules\Admin\Hardware;
use App\Modules\Seller\ListingCondition;

$curCond = (string) ($p['item_condition'] ?? '');
$hwType  = (string) Forms::val('hw_condition_type', $isEdit ? ($curCond === 'New' ? 'new' : 'used') : '');
$hwGrade = (string) Forms::val('hw_used_grade', in_array($curCond, ListingCondition::GRADES, true) ? $curCond : '');
$hidden  = $mode === 'hardware' ? '' : 'hidden';
?>
<div class="span-2 hw-block" data-kind="hardware" <?= $hidden ?>>
    <div class="form-grid">
        <div class="field">
            <label for="brand">Brand *</label>
            <input type="text" id="brand" name="brand" value="<?= e(Forms::val('brand', $p['brand'] ?? '')) ?>" maxlength="60" list="brand-list" autocomplete="off" placeholder="Logitech">
            <datalist id="brand-list">
                <?php foreach ($brands as $b): ?><option value="<?= e($b) ?>"></option><?php endforeach; ?>
            </datalist>
            <small class="hint">Pick from the list or type a new brand: it is added to the list once a product uses it.</small>
        </div>
        <div class="field">
            <label for="model">Model</label>
            <input type="text" id="model" name="model" value="<?= e(Forms::val('model', $p['model'] ?? '')) ?>" maxlength="120" placeholder="G502 X Plus">
        </div>

        <div class="cond field span-2">
            <fieldset class="cond-set">
                <legend>Is the item new or used? *</legend>
                <div class="cond-choices">
                    <label class="cond-choice">
                        <input type="radio" name="hw_condition_type" value="new" data-cond-type="hardware" <?= $hwType === 'new' ? 'checked' : '' ?>>
                        <span><strong>New (sealed)</strong><small>Unopened. Photos are optional.</small></span>
                    </label>
                    <label class="cond-choice">
                        <input type="radio" name="hw_condition_type" value="used" data-cond-type="hardware" <?= $hwType === 'used' ? 'checked' : '' ?>>
                        <span><strong>Used</strong><small>Needs a grade and three photos before it can go live.</small></span>
                    </label>
                </div>
            </fieldset>
            <div class="cond-used" data-used-only <?= $hwType === 'used' ? '' : 'hidden' ?>>
                <div class="field cond-grade">
                    <label for="hw_used_grade">Grade of the used item *</label>
                    <select id="hw_used_grade" name="hw_used_grade">
                        <option value="">Choose...</option>
                        <?php foreach (ListingCondition::GRADES as $g): ?>
                            <option value="<?= e($g) ?>" <?= $hwGrade === $g ? 'selected' : '' ?>><?= e($g) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="hint"><?php foreach (['Like New' => 'Looks unused, no marks at all.', 'Good' => 'Light wear, works perfectly.', 'Fair' => 'Visible wear or scuffs, works perfectly.'] as $g => $help): ?><strong><?= e($g) ?>:</strong> <?= e($help) ?> <?php endforeach; ?></small>
                </div>
            </div>
        </div>

        <div class="field">
            <label for="warranty_months">Warranty (months)</label>
            <input type="number" id="warranty_months" name="warranty_months" value="<?= e(Forms::val('warranty_months', isset($p['warranty_months']) ? (string) $p['warranty_months'] : '')) ?>" min="0" max="<?= (int) Hardware::MAX_WARRANTY ?>" placeholder="e.g. 3">
            <small class="hint">0 to <?= (int) Hardware::MAX_WARRANTY ?>. Leave empty if you do not state one.</small>
        </div>
        <div class="field">
            <label for="serial_number">Serial number <span class="tag tag-private">Only you can see this</span></label>
            <input type="text" id="serial_number" name="serial_number" value="<?= e(Forms::val('serial_number', $p['serial_number'] ?? '')) ?>" maxlength="80" autocomplete="off">
            <small class="hint">Private: for your own records and warranty claims. It is never shown on the website, in feeds or in search.</small>
        </div>

        <div class="field span-2">
            <label for="specs">Specs <small class="muted counter" data-lines-for="specs" data-max="<?= (int) Hardware::MAX_SPEC_LINES ?>"></small></label>
            <textarea id="specs" name="specs" rows="6" placeholder="<?= e($specExample) ?>" data-spec-field><?= e(Forms::val('specs', $p['specs'] ?? '')) ?></textarea>
            <small class="hint">One <strong>Label: value</strong> per line, up to <?= (int) Hardware::MAX_SPEC_LINES ?> lines of <?= (int) Hardware::MAX_SPEC_CHARS ?> characters. Shown as a spec table.
                Example for this category: <span class="spec-example" data-spec-example><?= e(str_replace("\n", ' / ', $specExample)) ?></span></small>
        </div>
        <div class="field span-2">
            <label for="included_items">What is in the box <small class="muted counter" data-lines-for="included_items" data-max="<?= (int) Hardware::MAX_INCLUDED_LINES ?>"></small></label>
            <textarea id="included_items" name="included_items" rows="4" placeholder="USB receiver&#10;Charging cable&#10;Spare keycaps"><?= e(Forms::val('included_items', $p['included_items'] ?? '')) ?></textarea>
            <small class="hint">One item per line, up to <?= (int) Hardware::MAX_INCLUDED_LINES ?> lines.</small>
        </div>
    </div>
</div>
