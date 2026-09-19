<?php
declare(strict_types=1);

namespace App\Support;

/** Delivery fee = the zone's fee (admin-editable table delivery_zones), or the default fee; free above an optional threshold. */
final class Delivery
{
    /** @return array<int, array{id:int,name:string,fee:string}> active zones in display order */
    public static function zones(): array
    {
        try {
            return db()->fetchAll('SELECT id, name, fee FROM delivery_zones WHERE is_active = 1 ORDER BY sort_order, name');
        } catch (\Throwable) {
            return [];
        }
    }

    public static function defaultFee(): float
    {
        return (float) setting('delivery_fee', 4);
    }

    public static function freeOver(): float
    {
        return (float) setting('free_delivery_over', 0);
    }

    public static function feeForZone(string $zone): float
    {
        $fee = db()->fetchValue('SELECT fee FROM delivery_zones WHERE name = ? AND is_active = 1', [$zone]);
        return $fee === null ? self::defaultFee() : (float) $fee;
    }

    /** Fee the customer pays for an order of $subtotal delivered to $zone. Server-authoritative: never trust a posted fee. */
    public static function fee(string $zone, float $subtotal): float
    {
        $free = self::freeOver();
        if ($free > 0 && $subtotal >= $free) {
            return 0.0;
        }
        return round(self::feeForZone($zone), 2);
    }
}
