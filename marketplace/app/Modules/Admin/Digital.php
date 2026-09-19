<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Support\Pricing;

/**
 * Digital goods (gift cards, Steam game gifts, game keys) in the admin panel: labels and choices for the product
 * form, the one-click starter catalogue, and the money split of orders that contain digital lines.
 *
 * Digital goods are HOUSE inventory only and are PREPAID (OMT / Whish): the code is released on WhatsApp only after
 * the payment is confirmed. They are never part of "cash to collect" and never go on a courier/pickup list.
 */
final class Digital
{
    public const KINDS = [
        'gift_card'  => 'Gift card',
        'steam_gift' => 'Steam game gift',
        'game_key'   => 'Game key',
    ];

    /** Region choices in the product form. "Other" reveals a free-text field. */
    public const REGIONS = ['Global', 'US', 'UAE', 'KSA', 'EU', 'Turkey'];

    public const DEFAULT_STOCK = 999;
    public const CATEGORY_SLUG = 'gift-cards';

    /** [title, kind, region, face value in $, platform slug or null] */
    private const STARTER = [
        ['PlayStation Store gift card $10 (US)', 'gift_card', 'US', 10, null],
        ['PlayStation Store gift card $25 (US)', 'gift_card', 'US', 25, null],
        ['PlayStation Store gift card $50 (US)', 'gift_card', 'US', 50, null],
        ['Steam Wallet gift card $10 (Global)', 'gift_card', 'Global', 10, 'pc'],
        ['Steam Wallet gift card $20 (Global)', 'gift_card', 'Global', 20, 'pc'],
        ['Steam Wallet gift card $50 (Global)', 'gift_card', 'Global', 50, 'pc'],
        ['Xbox gift card $10 (US)', 'gift_card', 'US', 10, null],
        ['Xbox gift card $25 (US)', 'gift_card', 'US', 25, null],
        ['Xbox gift card $50 (US)', 'gift_card', 'US', 50, null],
        ['Nintendo eShop gift card $10 (US)', 'gift_card', 'US', 10, 'switch'],
        ['Nintendo eShop gift card $25 (US)', 'gift_card', 'US', 25, 'switch'],
        ['PlayStation Plus 3 months (US)', 'gift_card', 'US', 25, null],
        ['Steam gift: Elden Ring (region check first)', 'steam_gift', 'Global', 60, 'pc'],
        ['Steam gift: Cyberpunk 2077 (region check first)', 'steam_gift', 'Global', 30, 'pc'],
    ];

    public static function kindLabel(?string $kind): string
    {
        return self::KINDS[(string) $kind] ?? '';
    }

    /** Slugs of the starter catalogue (used for idempotency and by tests/cleanup). */
    public static function starterSlugs(): array
    {
        return array_map(static fn (array $row): string => slugify($row[0]), self::STARTER);
    }

    /**
     * Region as the form needs it: [select value, free text]. A stored region outside the list becomes "Other" + text.
     * @return array{0:string,1:string}
     */
    public static function regionChoice(?string $stored): array
    {
        $stored = (string) $stored;
        if ($stored === '') {
            return ['', ''];
        }
        return in_array($stored, self::REGIONS, true) ? [$stored, ''] : ['Other', $stored];
    }

    public static function counts(): array
    {
        $row = db()->fetch(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(status = 'active'), 0) AS active,
                    COALESCE(SUM(status = 'active' AND stock > 0), 0) AS live,
                    COALESCE(SUM(status IN ('hidden', 'pending')), 0) AS drafts
               FROM products WHERE is_digital = 1"
        ) ?? [];
        return [
            'total'  => (int) ($row['total'] ?? 0),
            'active' => (int) ($row['active'] ?? 0),
            'live'   => (int) ($row['live'] ?? 0),
            'drafts' => (int) ($row['drafts'] ?? 0),
        ];
    }

    /**
     * Adds the starter catalogue as HIDDEN drafts (house stock, category Gift Cards, stock 999). Existing slugs are skipped,
     * so pressing the button twice creates nothing new. Prices are face value + 5%, rounded up to $0.50: placeholders to edit.
     * @return array{created:int, skipped:int}|null null when the Gift Cards category does not exist
     */
    public static function createStarter(): ?array
    {
        $category = db()->fetchValue('SELECT id FROM categories WHERE slug = ?', [self::CATEGORY_SLUG]);
        if (!$category) {
            return null;
        }
        $created = 0;
        $skipped = 0;
        foreach (self::STARTER as [$title, $kind, $region, $face, $platformSlug]) {
            $slug = slugify($title);
            if ((int) db()->fetchValue('SELECT COUNT(*) FROM products WHERE slug = ?', [$slug]) > 0) {
                $skipped++;
                continue;
            }
            $platformId = $platformSlug !== null ? db()->fetchValue('SELECT id FROM platforms WHERE slug = ?', [$platformSlug]) : null;
            $price = round(Pricing::buyerPrice((float) $face, 5.0), 2);
            $desc  = $kind === 'steam_gift'
                ? 'Steam game gift for a Steam account in a supported region. Check that the region matches yours before ordering. The link/code is sent on WhatsApp after payment is confirmed. All digital sales are final once delivered.'
                : 'Digital code sent on WhatsApp after payment is confirmed. Region: ' . $region . '. All digital sales are final once delivered.';
            db()->execute(
                "INSERT INTO products (seller_id, category_id, platform_id, slug, title, description, item_condition, edition, is_steelbook,
                        is_digital, digital_kind, digital_region, year, genres, seller_price, commission_pct, price, stock, image, status)
                 VALUES (NULL, ?, ?, ?, ?, ?, 'New', 'Standard', 0, 1, ?, ?, NULL, NULL, ?, 0, ?, ?, NULL, 'hidden')",
                [(int) $category, $platformId ?: null, $slug, $title, $desc, $kind, $region, $price, $price, self::DEFAULT_STOCK]
            );
            $created++;
        }
        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Where the money of an order is collected.
     *  - physical only : cash on delivery = total - credit
     *  - digital only  : prepaid via OMT/Whish, nothing to collect at the door
     *  - mixed         : digital lines are prepaid; wallet credit settles the digital part first, cash covers the rest
     *
     * $order needs total, delivery_fee, credit_used and grand_total (0 on old orders); payment_status and zone_mode refine it:
     * an order with payment_status awaiting/received is prepaid, so a physical order collects nothing at the door, and a
     * REMOTE mixed order is prepaid as a whole (a local mixed order still collects cash for the physical part).
     * @return array{kind:string, grand:float, digital:float, prepaid:float, cash:float, all_prepaid:bool}
     */
    public static function split(array $order, float $digitalSubtotal, int $digitalLines, int $physicalLines): array
    {
        $grandTotal = (float) ($order['grand_total'] ?? 0);
        $grand  = $grandTotal > 0 ? $grandTotal : (float) $order['total'] + (float) $order['delivery_fee'];
        $credit = (float) $order['credit_used'];
        // Prepaid orders (payment_status awaiting/received): the customer pays by OMT/Whish, so nothing is collected at the door.
        $prepaid = ($order['payment_status'] ?? 'not_required') !== 'not_required';
        $remote  = ($order['zone_mode'] ?? null) === 'remote';

        if ($digitalLines === 0) {
            return ['kind' => 'physical', 'grand' => $grand, 'digital' => 0.0, 'prepaid' => $prepaid ? max(0.0, round($grand - $credit, 2)) : 0.0,
                    'cash' => $prepaid ? 0.0 : max(0.0, round($grand - $credit, 2)), 'all_prepaid' => $prepaid];
        }
        if ($physicalLines === 0) {
            return ['kind' => 'digital', 'grand' => $grand, 'digital' => $grand, 'prepaid' => max(0.0, round($grand - $credit, 2)), 'cash' => 0.0, 'all_prepaid' => true];
        }
        if ($prepaid && $remote) {
            // Remote mixed order: the courier cannot take cash, so the whole order is prepaid.
            return ['kind' => 'mixed', 'grand' => $grand, 'digital' => $digitalSubtotal, 'prepaid' => max(0.0, round($grand - $credit, 2)), 'cash' => 0.0, 'all_prepaid' => true];
        }
        $creditLeft = max(0.0, $credit - $digitalSubtotal);
        return [
            'kind'    => 'mixed',
            'grand'   => $grand,
            'digital' => $digitalSubtotal,
            'prepaid' => max(0.0, round($digitalSubtotal - $credit, 2)),
            'cash'    => max(0.0, round($grand - $digitalSubtotal - $creditLeft, 2)),
            'all_prepaid' => false,
        ];
    }

    /** Ready-to-send WhatsApp text for one digital line. The owner pastes the code where it says [PASTE CODE]. */
    public static function codeMessage(array $order, array $item): string
    {
        $region = trim((string) ($item['digital_region'] ?? ''));
        return 'Hi ' . $order['buyer_name'] . ', your order ' . $order['code'] . ' is confirmed. Here is your ' . $item['title']
            . ' code: [PASTE CODE]. Region: ' . ($region !== '' ? $region : 'as listed')
            . '. All digital sales are final once delivered.';
    }
}
