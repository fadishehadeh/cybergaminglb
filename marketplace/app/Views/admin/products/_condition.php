<?php
/**
 * New vs Used + grade + what is included, for the admin product form (same pattern as the member listing form).
 * Legacy stock whose "included" values are NULL shows a preselected "Not stated" choice that may stay as it is.
 * @var array $p product row (or [])  @var bool $isEdit
 */
use App\Modules\Admin\Forms;
use App\Modules\Seller\ListingCondition;

$curCond = (string) ($p['item_condition'] ?? '');
$type    = (string) Forms::val('condition_type', $isEdit ? ($curCond === 'New' ? 'new' : 'used') : '');
$grade   = (string) Forms::val('used_grade', in_array($curCond, ListingCondition::GRADES, true) ? $curCond : '');
?>
<div class="cond field span-2" data-cond data-kind="game" <?= ($mode ?? 'game') === 'game' ? '' : 'hidden' ?>>
    <fieldset class="cond-set">
        <legend>Is the game new or used? *</legend>
        <div class="cond-choices">
            <label class="cond-choice">
                <input type="radio" name="condition_type" value="new" data-cond-type="game" <?= $type === 'new' ? 'checked' : '' ?>>
                <span><strong>New (sealed)</strong><small>Unopened. Everything is included; photos are optional.</small></span>
            </label>
            <label class="cond-choice">
                <input type="radio" name="condition_type" value="used" data-cond-type="game" <?= $type === 'used' ? 'checked' : '' ?>>
                <span><strong>Used</strong><small>Needs a grade, the included items and three photos.</small></span>
            </label>
        </div>
    </fieldset>

    <div class="cond-used" data-used-only <?= $type === 'used' ? '' : 'hidden' ?>>
        <div class="field cond-grade">
            <label for="used_grade">Grade of the used copy *</label>
            <select id="used_grade" name="used_grade">
                <option value="">Choose...</option>
                <?php foreach (ListingCondition::GRADES as $g): ?>
                    <option value="<?= e($g) ?>" <?= $grade === $g ? 'selected' : '' ?>><?= e($g) ?></option>
                <?php endforeach; ?>
            </select>
            <small class="hint"><?php foreach (ListingCondition::GRADE_HELP as $g => $help): ?><strong><?= e($g) ?>:</strong> <?= e($help) ?> <?php endforeach; ?></small>
        </div>

        <fieldset class="cond-set cond-inc">
            <legend>What comes with it? *</legend>
            <?php foreach (ListingCondition::INCLUDES as $col => $label):
                $stored = isset($p[$col]) && $p[$col] !== null ? (string) (int) $p[$col] : '';
                $val    = (string) Forms::val($col, $stored);
                $legacy = $isEdit && array_key_exists($col, $p) && $p[$col] === null; // stored NULL = not stated
            ?>
                <div class="cond-inc-row">
                    <span class="cond-inc-label"><?= e($label) ?></span>
                    <span class="cond-yn">
                        <label><input type="radio" name="<?= e($col) ?>" value="1" <?= $val === '1' ? 'checked' : '' ?>> <span>Yes</span></label>
                        <label><input type="radio" name="<?= e($col) ?>" value="0" <?= $val === '0' ? 'checked' : '' ?>> <span>No</span></label>
                        <?php if ($legacy): ?>
                            <label class="cond-unk"><input type="radio" name="<?= e($col) ?>" value="" <?= $val === '' ? 'checked' : '' ?>> <span>Not stated</span></label>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endforeach; ?>
            <small class="hint">Shown to buyers. Old stock created before this was recorded can stay "Not stated" until you check it.</small>
        </fieldset>
    </div>
</div>
