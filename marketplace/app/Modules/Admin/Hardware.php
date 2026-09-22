<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;
use App\Modules\Seller\ListingCondition;
use App\Support\ProductPhotos;

/**
 * Admin rules for HARDWARE listings (categories whose kind = hardware: keyboards, mice, headsets, consoles...):
 * brand/model, specs, what is in the box, warranty, the private serial number, New/Used + grade (no box/cover/manual questions)
 * and the unit photos (front, back, box + accessories, optional powered-on, up to 3 extras).
 */
final class Hardware
{
    /** Brands offered in the brand field's suggestion list (brands already used by products are added to it). */
    public const BRANDS = [
        'Logitech', 'Razer', 'HyperX', 'SteelSeries', 'Corsair', 'Redragon', 'Keychron', 'Glorious', 'Ducky', 'ASUS ROG', 'MSI',
        'Cooler Master', 'Fantech', 'A4Tech', 'Sony', 'Nintendo', 'Microsoft', 'Valve', 'Anker',
    ];

    /** PC peripherals: the platform field defaults to PC for these categories. */
    public const PERIPHERAL_SLUGS = ['keyboards', 'mice', 'mousepads', 'headsets'];

    /** How the photo slots read in the admin form (the stored kinds stay unit_front / unit_back / box_accessories). */
    public const CAPTIONS = [
        'unit_front'      => 'Product, front / top',
        'unit_back'       => 'Product, back or underside',
        'box_accessories' => 'Box and accessories',
        'powered_on'      => 'Powered on / lit up',
    ];
    public const HINTS = [
        'unit_front'      => 'The whole product from the front or top, on a plain background.',
        'unit_back'       => 'The back or underside: ports, feet, label, wear marks.',
        'box_accessories' => 'The box (if any) and everything that comes with it.',
        'powered_on'      => 'Optional: plugged in and working (lighting, screen, indicator).',
    ];

    public const MAX_SPEC_LINES = 30;
    public const MAX_SPEC_CHARS = 160;
    public const MAX_INCLUDED_LINES = 20;
    public const MAX_INCLUDED_CHARS = 120;
    public const MAX_WARRANTY = 36;

    private const EXAMPLES = [
        'keyboards' => ['Switch type: Red linear', 'Layout: US ANSI', 'Connectivity: USB wired', 'Backlight: RGB'],
        'mice'      => ['DPI: 16000', 'Sensor: optical', 'Buttons: 6', 'Connectivity: wireless 2.4 GHz'],
        'mousepads' => ['Size: 900x400x4 mm', 'Surface: cloth', 'Base: rubber', 'Stitched edges: yes'],
        'headsets'  => ['Connectivity: USB and 3.5 mm', 'Microphone: detachable', 'Drivers: 50 mm', 'Colour: Black'],
        '_default'  => ['Storage: 1 TB', 'Colour: Black', 'Controllers included: 1'],
    ];

    /** @return string[] example spec lines for a category slug */
    public static function specExample(string $slug): array
    {
        return self::EXAMPLES[$slug] ?? self::EXAMPLES['_default'];
    }

    /** @return string[] suggestion list: the defaults, then brands already used, no duplicates (case-insensitive) */
    public static function brands(): array
    {
        $seen = [];
        $out  = [];
        $used = array_column(db()->fetchAll("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand <> '' ORDER BY brand"), 'brand');
        foreach ([...self::BRANDS, ...$used] as $b) {
            $key = mb_strtolower((string) $b);
            if ($b !== '' && !isset($seen[$key])) {
                $seen[$key] = true;
                $out[] = (string) $b;
            }
        }
        return $out;
    }

    /**
     * New (sealed) or Used + grade. No box/cover/manual questions: those stay NULL.
     * Fields: hw_condition_type (new|used), hw_used_grade.
     * @param string[] $errors appended to
     * @return array{type: string, used: bool, condition: string}
     */
    public static function readCondition(Request $request, array &$errors): array
    {
        $type  = Forms::text($request->input('hw_condition_type'));
        $grade = Forms::text($request->input('hw_used_grade'));
        if ($type === 'new') {
            return ['type' => 'new', 'used' => false, 'condition' => 'New'];
        }
        if ($type !== 'used') {
            $errors[] = 'Say whether the item is New (sealed) or Used.';
            return ['type' => '', 'used' => false, 'condition' => ''];
        }
        if (!in_array($grade, ListingCondition::GRADES, true)) {
            $errors[] = 'Choose the grade of the used item: Like New, Good or Fair.';
            $grade = '';
        }
        return ['type' => 'used', 'used' => true, 'condition' => $grade];
    }

    /**
     * Textarea with one entry per line. Returns the cleaned text (LF, blank lines dropped) or null when empty.
     * With $labelled every line must look like "Label: value".
     * @param string[] $errors appended to
     */
    public static function lines(string $raw, string $what, int $maxLines, int $maxChars, bool $labelled, array &$errors): ?string
    {
        $out = [];
        foreach (preg_split('/\R/u', $raw) ?: [] as $line) {
            $line = trim((string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $line));
            if ($line !== '') {
                $out[] = $line;
            }
        }
        if (count($out) > $maxLines) {
            $errors[] = ucfirst($what) . ': at most ' . $maxLines . ' lines (you have ' . count($out) . ').';
        }
        foreach ($out as $n => $line) {
            if (mb_strlen($line) > $maxChars) {
                $errors[] = ucfirst($what) . ', line ' . ($n + 1) . ' is over ' . $maxChars . ' characters.';
                break;
            }
        }
        if ($labelled) {
            foreach ($out as $n => $line) {
                $pos = mb_strpos($line, ':');
                if ($pos === false || $pos < 1 || trim(mb_substr($line, $pos + 1)) === '' || $pos > 40) {
                    $errors[] = ucfirst($what) . ', line ' . ($n + 1) . ' must look like "Label: value" (label up to 40 characters).';
                    break;
                }
            }
        }
        return $out ? implode("\n", $out) : null;
    }

    /**
     * Validates and processes the photo uploads (nothing is attached yet). Same contract as ListingCondition::photos().
     * @param string[] $errors appended to
     * @return array{staged: array, deleteExtra: int[], hadFiles: bool}
     */
    public static function photos(Request $request, ?array $product, array &$errors): array
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

        $r = ProductPhotos::stage($_FILES, $existing, false, 'hardware');
        foreach ($r['errors'] as $err) {
            $errors[] = strtr($err, [
                'Unit, front' => self::CAPTIONS['unit_front'], 'Unit, back and ports' => self::CAPTIONS['unit_back'],
                'Box and accessories' => self::CAPTIONS['box_accessories'], 'Powered on' => self::CAPTIONS['powered_on'],
            ]);
        }

        $stagedExtras = 0;
        foreach ($r['staged'] as $s) {
            $stagedExtras += $s['kind'] === 'extra' ? 1 : 0;
        }
        $haveExtras = $productId > 0
            ? (int) db()->fetchValue("SELECT COUNT(*) FROM product_images WHERE product_id = ? AND kind = 'extra'", [$productId]) - count($deleteExtra)
            : 0;
        if ($extraSent > ListingCondition::MAX_EXTRA || $haveExtras + $stagedExtras > ListingCondition::MAX_EXTRA) {
            $errors[] = 'You can add up to ' . ListingCondition::MAX_EXTRA . ' extra photos in total. Remove one first, or choose fewer.';
        }

        if ($errors) {
            $hadFiles = $r['staged'] !== [] || self::anyFileSent();
            ProductPhotos::discard($r['staged']);
            return ['staged' => [], 'deleteExtra' => [], 'hadFiles' => $hadFiles];
        }
        return ['staged' => $r['staged'], 'deleteExtra' => $deleteExtra, 'hadFiles' => $r['staged'] !== []];
    }

    public static function anyFileSent(): bool
    {
        foreach (['photo_unit_front', 'photo_unit_back', 'photo_box_accessories', 'photo_powered_on'] as $k) {
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
     * Saves the staged photos, removes the ticked extras and keeps products.image sensible: an item without a main image
     * uses its front photo. Call inside the product transaction.
     */
    public static function commit(int $productId, array $staged, array $deleteExtra, ?string $image): ?string
    {
        $front = static function () use ($productId): ?string {
            $p = db()->fetchValue("SELECT path FROM product_images WHERE product_id = ? AND kind = 'unit_front' LIMIT 1", [$productId]);
            return $p ? (string) $p : null;
        };
        $oldFront = $front();

        ProductPhotos::attach($productId, $staged);

        foreach ($deleteExtra as $imageId) {
            $path = db()->fetchValue("SELECT path FROM product_images WHERE id = ? AND product_id = ? AND kind = 'extra'", [$imageId, $productId]);
            db()->execute("DELETE FROM product_images WHERE id = ? AND product_id = ? AND kind = 'extra'", [$imageId, $productId]);
            if ($path) {
                Forms::deleteUpload((string) $path);
            }
        }

        $newFront = $front();
        $final = $image;
        if ($newFront !== null && ($image === null || $image === '' || ($oldFront !== null && $image === $oldFront && $newFront !== $oldFront))) {
            $final = $newFront;
        }
        if ($final !== $image) {
            db()->execute('UPDATE products SET image = ? WHERE id = ?', [$final, $productId]);
        }
        return $final;
    }
}
