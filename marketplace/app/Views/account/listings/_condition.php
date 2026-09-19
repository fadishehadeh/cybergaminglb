<?php
/**
 * New vs Used + what is included. Shared by the member listing form and the store-seller product form.
 * @var string $px CSS prefix of the calling form ('ls' or 'sp')  @var array $p product row (or [])  @var bool $isEdit
 */
use App\Modules\Admin\Forms;
use App\Modules\Seller\ListingCondition;

$curCond = (string) ($p['item_condition'] ?? '');
$type    = (string) Forms::val('condition_type', $isEdit ? ($curCond === 'New' ? 'new' : 'used') : '');
$grade   = (string) Forms::val('used_grade', in_array($curCond, ListingCondition::GRADES, true) ? $curCond : '');
?>
<div class="<?= e($px) ?>-span-2 lc" data-cond>
    <fieldset class="lc-set">
        <legend>Is the game new or used? *</legend>
        <div class="lc-choices">
            <label class="lc-choice">
                <input type="radio" name="condition_type" value="new" data-cond-type <?= $type === 'new' ? 'checked' : '' ?>>
                <span><strong>New (sealed, unopened)</strong><small>Factory sealed, never played.</small></span>
            </label>
            <label class="lc-choice">
                <input type="radio" name="condition_type" value="used" data-cond-type <?= $type === 'used' ? 'checked' : '' ?>>
                <span><strong>Used</strong><small>Opened or played, even once.</small></span>
            </label>
        </div>
    </fieldset>

    <div class="lc-used" data-used-only>
        <div class="lc-grade">
            <label for="used_grade">How good is the used copy? *</label>
            <select id="used_grade" name="used_grade" data-cond-grade>
                <option value="">Choose...</option>
                <?php foreach (ListingCondition::GRADES as $g): ?>
                    <option value="<?= e($g) ?>" <?= $grade === $g ? 'selected' : '' ?>><?= e($g) ?></option>
                <?php endforeach; ?>
            </select>
            <ul class="lc-grade-help">
                <?php foreach (ListingCondition::GRADE_HELP as $g => $help): ?>
                    <li><strong><?= e($g) ?>:</strong> <?= e($help) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <fieldset class="lc-set lc-inc">
            <legend>What comes with it? *</legend>
            <?php foreach (ListingCondition::INCLUDES as $col => $label):
                $val = (string) Forms::val($col, isset($p[$col]) && $p[$col] !== null ? (string) (int) $p[$col] : '');
            ?>
                <div class="lc-inc-row">
                    <span class="lc-inc-label"><?= e($label) ?></span>
                    <span class="lc-yn">
                        <label><input type="radio" name="<?= e($col) ?>" value="1" <?= $val === '1' ? 'checked' : '' ?>> <span>Yes</span></label>
                        <label><input type="radio" name="<?= e($col) ?>" value="0" <?= $val === '0' ? 'checked' : '' ?>> <span>No</span></label>
                    </span>
                </div>
            <?php endforeach; ?>
            <small class="lc-note">Buyers see this on the listing, so please be honest. We check every used copy before delivery.</small>
        </fieldset>
    </div>
</div>
