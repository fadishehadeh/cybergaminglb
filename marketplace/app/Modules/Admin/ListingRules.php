<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;
use App\Modules\Seller\ListingCondition;
use App\Support\ProductPhotos;

/**
 * Admin-side rules for New vs Used, the included items and the three required photos.
 *
 * A listing needs the photos when it is a physical (non-digital) item that is not New. New (sealed) and digital items
 * are exempt. The member/store forms use App\Modules\Seller\ListingCondition; this class reuses its constants and
 * helpers and only adds what the admin needs on top (the "Not stated" state of legacy stock, SQL fragments, badges).
 */
final class ListingRules
{
    public const REQUIRED = ['disc', 'box_outside', 'box_inside'];

    /** Required photo kinds per listing type: game = disc + box; hardware = unit front/back + box and accessories. */
    public static function requiredKinds(string $type): array
    {
        return array_keys(ProductPhotos::labels($type));
    }

    private static function kindSql(string $alias): string
    {
        return "(SELECT ck.kind FROM categories ck WHERE ck.id = {$alias}.category_id)";
    }

    /** SQL: (subquery) number of distinct required photo kinds a product has (game kinds, or hardware kinds in a hardware category). */
    public static function photoCountSql(string $alias = 'p'): string
    {
        $k = self::kindSql($alias);
        return "(SELECT COUNT(DISTINCT pi.kind) FROM product_images pi WHERE pi.product_id = {$alias}.id AND ("
            . "(pi.kind IN ('disc','box_outside','box_inside') AND COALESCE({$k}, 'game') <> 'hardware')"
            . " OR (pi.kind IN ('unit_front','unit_back','box_accessories') AND {$k} = 'hardware')))";
    }

    /** SQL predicate: a used, physical listing (game or hardware) with fewer than three required photos. */
    public static function missingSql(string $alias = 'p'): string
    {
        return "({$alias}.is_digital = 0 AND {$alias}.item_condition <> 'New' AND COALESCE(" . self::kindSql($alias) . ", 'game') <> 'digital' AND " . self::photoCountSql($alias) . ' < 3)';
    }

    /** Listing type of a product row: digital | hardware | game (needs category_id; defaults to game). */
    public static function typeOf(array $product): string
    {
        static $cache = [];
        if ((int) ($product['is_digital'] ?? 0) === 1) {
            return 'digital';
        }
        $cat = (int) ($product['category_id'] ?? 0);
        if ($cat < 1) {
            return 'game';
        }
        return $cache[$cat] ??= ProductPhotos::typeForCategory($cat);
    }

    public static function isNew(array $product): bool
    {
        return (string) ($product['item_condition'] ?? '') === 'New';
    }

    /** Does this product row have to carry the three photos? (used game or used hardware; New and digital are exempt) */
    public static function needsPhotos(array $product): bool
    {
        return self::typeOf($product) !== 'digital' && (string) ($product['item_condition'] ?? 'New') !== 'New';
    }

    /** @return string[] required kinds the product still lacks (always [] for New / digital items) */
    public static function missing(array $product): array
    {
        if (!self::needsPhotos($product)) {
            return [];
        }
        return ProductPhotos::missingKinds((int) $product['id'], self::typeOf($product));
    }

    /** "disc, box outside and box inside" style list for messages. */
    public static function kindList(array $kinds): string
    {
        $labels = array_map(
            static fn (string $k): string => str_replace([', ', ' / '], ' ', mb_strtolower(ProductPhotos::LABELS[$k] ?? Hardware::CAPTIONS[$k] ?? $k)),
            array_values($kinds)
        );
        $last = array_pop($labels);
        return $labels ? implode(', ', $labels) . ' and ' . $last : (string) $last;
    }

    /** product id => number of distinct required kinds present */
    public static function counts(array $productIds): array
    {
        $out = [];
        foreach (ListingCondition::kindsFor($productIds) as $id => $kinds) {
            $out[$id] = count(array_intersect(array_unique($kinds), self::REQUIRED));
        }
        return $out;
    }

    /** "New" / "Used · Good" tag (HTML). Digital items have no physical condition, so no tag. */
    public static function conditionTag(array $p): string
    {
        if ((int) ($p['is_digital'] ?? 0) === 1) {
            return '';
        }
        $c = (string) ($p['item_condition'] ?? '');
        if ($c === 'New') {
            return '<span class="tag tag-new" title="Sealed, unopened">New</span>';
        }
        return '<span class="tag tag-used">Used &middot; ' . e($c) . '</span>';
    }

    /** "Photos 2/3" tag (HTML) for a physical product. */
    public static function photoTag(array $p, int $count): string
    {
        if ((int) ($p['is_digital'] ?? 0) === 1) {
            return '';
        }
        $used = self::needsPhotos($p);
        $hw   = self::typeOf($p) === 'hardware';
        $cls  = $count >= 3 ? 'tag-photos-ok' : ($used ? 'tag-photos-low' : 'tag-photos-opt');
        $tip  = $used
            ? ($count >= 3 ? 'All three required photos are there' : ($hw ? 'A used item needs product front, product back and box and accessories photos' : 'A used game needs disc, box outside and box inside photos'))
            : ($hw ? 'Photos are optional for new sealed items' : 'Photos are optional for new sealed games');
        return '<span class="tag ' . $cls . '" title="' . e($tip) . '">Photos ' . $count . '/3</span>';
    }

    /**
     * Reads the New / Used choice and the three "included" answers of the admin form.
     * New => everything included. Used => a grade and an answer for each item; the ONLY exception is legacy stock whose
     * stored value is NULL ("Not stated"): it may stay unanswered.
     *
     * @param string[] $errors appended to
     * @return array{type: string, used: bool, condition: string, includes: array<string, int|null>}
     */
    public static function read(Request $request, ?array $product, array &$errors): array
    {
        $type  = Forms::text($request->input('condition_type'));
        $grade = Forms::text($request->input('used_grade'));
        $out   = ['type' => $type, 'used' => false, 'condition' => '', 'includes' => array_fill_keys(array_keys(ListingCondition::INCLUDES), null)];

        if ($type === 'new') {
            $out['condition'] = 'New';
            foreach (array_keys(ListingCondition::INCLUDES) as $col) {
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
        if (in_array($grade, ListingCondition::GRADES, true)) {
            $out['condition'] = $grade;
        } else {
            $errors[] = 'Choose the grade of the used game: Like New, Good or Fair.';
        }
        foreach (ListingCondition::INCLUDES as $col => $label) {
            $v = Forms::text($request->input($col));
            if ($v === '1' || $v === '0') {
                $out['includes'][$col] = (int) $v;
            } elseif ($product !== null && $product[$col] === null) {
                $out['includes'][$col] = null; // legacy stock: "Not stated" stays as it was
            } else {
                $errors[] = 'Say whether the ' . mb_strtolower($label) . ' is included (Yes or No).';
            }
        }
        return $out;
    }

    /** "Yes" / "No" / "Not stated" for a stored includes_* value (HTML-safe text). */
    public static function includeText(mixed $value): string
    {
        return $value === null || $value === '' ? 'Not stated' : ((int) $value === 1 ? 'Yes' : 'No');
    }

    /** CSS modifier for a stored includes_* value. */
    public static function includeClass(mixed $value): string
    {
        return $value === null || $value === '' ? 'inc-unk' : ((int) $value === 1 ? 'inc-yes' : 'inc-no');
    }
}
