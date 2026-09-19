<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Money rules. All percentages come from the admin panel (settings table) or a per-seller override.
 *
 *  buyer price  = seller price + commission, rounded UP to the next $0.50   (house stock: no commission)
 *  buy-back     = resale price x buyback_pct / 100                          (what we pay a customer for a used item)
 *  trade credit = resale price x tradein_pct / 100                          (store credit for a trade-in)
 */
final class Pricing
{
    public static function commissionPct(?array $seller = null): float
    {
        if ($seller && ($seller["type"] ?? "store") === "member" && ($seller["commission_pct"] === null || $seller["commission_pct"] === "")) {
            return (float) setting("member_commission_pct", 10);
        }
        if ($seller && $seller['commission_pct'] !== null && $seller['commission_pct'] !== '') {
            return (float) $seller['commission_pct'];
        }
        return (float) setting('commission_pct', 15);
    }

    public static function buyerPrice(float $sellerPrice, float $commissionPct): float
    {
        // work in cents-of-a-percent integers so 50 * 1.10 is exactly 55, not 55.000000000001
        return ceil(round($sellerPrice * (100 + $commissionPct), 2) / 50) / 2;
    }

    /** Condition multiplier (percent) for what we pay/credit: settings buyback_factor_{new|like_new|good|fair}. */
    public static function conditionFactor(string $condition = 'Good'): float
    {
        $key = 'buyback_factor_' . strtolower(str_replace(' ', '_', $condition));
        return (float) setting($key, 90);
    }

    public static function buybackOffer(float $resalePrice, string $condition = 'Good'): float
    {
        $pct = (float) setting('buyback_pct', 45) * self::conditionFactor($condition) / 100;
        return floor(round($resalePrice * $pct, 2) / 50) / 2;
    }

    public static function tradeCredit(float $resalePrice, string $condition = 'Good'): float
    {
        $pct = (float) setting('tradein_pct', 50) * self::conditionFactor($condition) / 100;
        return floor(round($resalePrice * $pct, 2) / 50) / 2;
    }

    /** Recompute the buyer-facing price of every product from the current commission settings. */
    public static function recalculateAll(): int
    {
        $count = 0;
        $rows = db()->fetchAll(
            'SELECT p.id, p.seller_price, s.commission_pct AS seller_pct, s.type AS seller_type, p.seller_id
               FROM products p LEFT JOIN sellers s ON s.id = p.seller_id'
        );
        foreach ($rows as $r) {
            $pct = $r['seller_id'] === null
                ? 0.0
                : self::commissionPct(['commission_pct' => $r['seller_pct'], 'type' => $r['seller_type']]);
            $count += db()->execute(
                'UPDATE products SET commission_pct = ?, price = ? WHERE id = ?',
                [$pct, self::buyerPrice((float) $r['seller_price'], $pct), $r['id']]
            );
        }
        return $count;
    }
}
