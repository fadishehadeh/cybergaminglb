<?php
declare(strict_types=1);

namespace App\Modules\Seller;

use App\Core\Request;
use App\Modules\Admin\Forms;
use App\Support\ProductPhotos;

/**
 * Shared rules for the member listing form and the store-seller product form:
 *  - NEW (sealed) vs USED (Like New / Good / Fair)
 *  - what a USED copy includes (box / cover art / manual)
 *  - the three required photos of a used copy (+ up to 3 extras), all handled through ProductPhotos.
 * Both controllers call these helpers so the two forms can never drift apart.
 */
final class ListingCondition
{
    public const GRADES = ['Like New', 'Good', 'Fair'];

    public const GRADE_HELP = [
        'Like New' => 'Looks unplayed, no marks at all.',
        'Good'     => 'Light wear, plays perfectly.',
        'Fair'     => 'Visible wear or scratches on the case, the disc plays.',
    ];

    /** column => label in the form */
    public const INCLUDES = [
        'includes_box'       => 'Original box / case',
        'includes_cover_art' => 'Cover art (sleeve / inlay)',
        'includes_manual'    => 'Manual / inserts',
    ];

    public const MAX_EXTRA = 3;

    public const PHOTO_HINTS = [
        'disc'        => 'Photo of the disc: show the label side clearly.',
        'box_outside' => 'Box from the outside: front and back if you can.',
        'box_inside'  => 'Inside the box: show the disc tray and inserts.',
    ];

    /**
     * Reads and validates the New/Used choice and the included items.
     *
     * @param string[] $errors appended to
     * @return array{type: string, used: bool, condition: string, includes: array<string, int|null>}
     */
    public static function read(Request $request, array &$errors): array
    {
        $type  = Forms::text($request->input('condition_type'));
        $grade = Forms::text($request->input('used_grade'));

        // Older forms / scripts that still post the single item_condition field keep working.
        $legacy = Forms::text($request->input('item_condition'));
        if ($type === '' && $legacy !== '') {
            $type  = $legacy === 'New' ? 'new' : 'used';
            $grade = $grade !== '' ? $grade : $legacy;
        }

        $out = ['type' => $type, 'used' => false, 'condition' => '', 'includes' => array_fill_keys(array_keys(self::INCLUDES), null)];

        if ($type === 'new') {
            $out['condition'] = 'New';
            foreach (self::INCLUDES as $col => $_) {
                $out['includes'][$col] = 1;
            }
            return $out;
        }
        if ($type !== 'used') {
            $out['type'] = '';
            $errors[] = 'Say whether the game is New (sealed) or Used.';
            return $out;
        }

        $out['used'] = true;
        if (!in_array($grade, self::GRADES, true)) {
            $errors[] = 'Choose the condition of your used game: Like New, Good or Fair.';
        } else {
            $out['condition'] = $grade;
        }
        foreach (self::INCLUDES as $col => $label) {
            $v = Forms::text($request->input($col));
            if ($v !== '1' && $v !== '0') {
                $errors[] = 'Tell us if the ' . mb_strtolower($label) . ' is included (Yes or No).';
            } else {
                $out['includes'][$col] = (int) $v;
            }
        }
        return $out;
    }

    /**
     * Validates and processes the photo uploads (never attaches anything yet).
     * Existing photos satisfy the "required" rule. Returns the staged files and the extra-photo ids to delete.
     * If there are ANY errors (here or already in $errors) every staged file is discarded.
     *
     * @param string[] $errors appended to
     * @return array{staged: array, deleteExtra: int[], hadFiles: bool}
     */
    public static function photos(Request $request, ?array $product, bool $required, array &$errors): array
    {
        $productId = $product !== null ? (int) $product['id'] : 0;
        $existing  = $productId > 0 ? ProductPhotos::existingKinds($productId) : [];

        $extraSent = 0;
        $multi = $_FILES['photo_extra'] ?? null;
        if (is_array($multi) && isset($multi['error']) && is_array($multi['error'])) {
            foreach ($multi['error'] as $code) {
                if ((int) $code !== UPLOAD_ERR_NO_FILE) {
                    $extraSent++;
                }
            }
        }

        $deleteExtra = [];
        $raw = $request->input('delete_extra');
        if ($productId > 0 && is_array($raw)) {
            foreach ($raw as $id) {
                $id = is_string($id) && ctype_digit($id) ? (int) $id : 0;
                if ($id > 0 && (int) db()->fetchValue("SELECT COUNT(*) FROM product_images WHERE id = ? AND product_id = ? AND kind = 'extra'", [$id, $productId]) === 1) {
                    $deleteExtra[$id] = $id;
                }
            }
        }
        $deleteExtra = array_values($deleteExtra);

        $r =ProductPhotos::stage($_FILES, $existing, $required);
        foreach ($r['errors'] as $err) {
            $errors[] = $err;
        }

        $stagedExtras = 0;
        foreach ($r['staged'] as $s) {
            $stagedExtras += $s['kind'] === 'extra' ? 1 : 0;
        }
        $haveExtras = $productId > 0
            ? (int) db()->fetchValue("SELECT COUNT(*) FROM product_images WHERE product_id = ? AND kind = 'extra'", [$productId]) - count($deleteExtra)
            : 0;
        if ($extraSent > self::MAX_EXTRA || $haveExtras + $stagedExtras > self::MAX_EXTRA) {
            $errors[] = 'You can add up to ' . self::MAX_EXTRA . ' extra photos in total. Remove one first, or choose fewer.';
        }

        if ($errors) { // any error (ours or from the rest of the form): nothing is saved, so drop what was processed
            $hadFiles = $r['staged'] !== [] || self::anyFileSent();
            ProductPhotos::discard($r['staged']);
            return ['staged' => [], 'deleteExtra' => [], 'hadFiles' => $hadFiles];
        }
        return ['staged' => $r['staged'], 'deleteExtra' => $deleteExtra, 'hadFiles' => $r['staged'] !== []];
    }

    /** True if the browser sent at least one photo file (used to tell the user to pick them again after an error). */
    public static function anyFileSent(): bool
    {
        foreach (['photo_disc', 'photo_box_outside', 'photo_box_inside'] as $k) {
            if (isset($_FILES[$k]['error']) && (int) $_FILES[$k]['error'] !== UPLOAD_ERR_NO_FILE) {
                return true;
            }
        }
        $m = $_FILES['photo_extra']['error'] ?? null;
        if (is_array($m)) {
            foreach ($m as $c) {
                if ((int) $c !== UPLOAD_ERR_NO_FILE) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Saves the staged photos, removes the ticked extras and keeps the storefront picture (products.image) sensible:
     * a listing without a main image uses the "box outside" photo. Call inside the product transaction.
     *
     * @return ?string the final products.image value
     */
    public static function commit(int $productId, array $staged, array $deleteExtra, ?string $image): ?string
    {
        $oldBox = db()->fetchValue("SELECT path FROM product_images WHERE product_id = ? AND kind = 'box_outside' LIMIT 1", [$productId]);
        $oldBox = $oldBox !== null && $oldBox !== false ? (string) $oldBox : null;

        ProductPhotos::attach($productId, $staged);

        foreach ($deleteExtra as $imageId) {
            $path = db()->fetchValue("SELECT path FROM product_images WHERE id = ? AND product_id = ? AND kind = 'extra'", [$imageId, $productId]);
            db()->execute("DELETE FROM product_images WHERE id = ? AND product_id = ? AND kind = 'extra'", [$imageId, $productId]);
            if ($path) {
                Forms::deleteUpload((string) $path);
            }
        }

        $box = db()->fetchValue("SELECT path FROM product_images WHERE product_id = ? AND kind = 'box_outside' LIMIT 1", [$productId]);
        $box = $box ? (string) $box : null;
        $final = $image;
        if ($box !== null && ($image === null || $image === '' || ($oldBox !== null && $image === $oldBox && $box !== $oldBox))) {
            $final = $box;
        }
        if ($final !== $image) {
            db()->execute('UPDATE products SET image = ? WHERE id = ?', [$final, $productId]);
        }
        return $final;
    }

    /** Deletes an uploaded main image file unless a listing photo still uses that same file. */
    public static function dropMainImage(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }
        if ((int) db()->fetchValue('SELECT COUNT(*) FROM product_images WHERE path = ?', [$path]) > 0) {
            return;
        }
        if ((int) db()->fetchValue('SELECT COUNT(*) FROM products WHERE image = ?', [$path]) > 0) {
            return;
        }
        Forms::deleteUpload($path);
    }

    /** Did the buyer-visible condition data change? Compares the stored row with the newly submitted values. */
    public static function includesChanged(array $product, array $includes): bool
    {
        foreach ($includes as $col => $value) {
            $old = $product[$col] === null ? null : (int) $product[$col];
            if ($old !== $value) {
                return true;
            }
        }
        return false;
    }

    /** product id => kinds present, for the photo indicator in list rows. @return array<int, string[]> */
    public static function kindsFor(array $productIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if (!$ids) {
            return [];
        }
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $map = [];
        foreach (db()->fetchAll("SELECT product_id, kind FROM product_images WHERE product_id IN ($ph)", $ids) as $r) {
            $map[(int) $r['product_id']][] = $r['kind'];
        }
        return $map;
    }
}
