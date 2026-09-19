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
            return db()->fetchAll('SELECT id, name, fee, mode FROM delivery_zones WHERE is_active = 1 ORDER BY sort_order, name');
        } catch (\Throwable) {
            return [];
        }
    }

    /** 'local' (our own courier, can inspect, cash on delivery) or 'remote' (third-party courier). Unknown zones are remote. */
    public static function mode(string $zone): string
    {
        $mode = db()->fetchValue('SELECT mode FROM delivery_zones WHERE name = ?', [$zone]);
        return $mode === 'local' ? 'local' : 'remote';
    }

    public static function isLocal(string $zone): bool
    {
        return self::mode($zone) === 'local';
    }

    /** Number of delivered orders a customer already has (used to allow cash on delivery outside the local area). */
    public static function deliveredOrders(?int $userId): int
    {
        return $userId ? (int) db()->fetchValue("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'delivered'", [$userId]) : 0;
    }

    /**
     * Must this buyer pay BEFORE we ship? True for remote zones when the switch is on, unless the customer has enough
     * delivered orders (remote_cod_after_orders; 0 means cash on delivery is never allowed outside the local area).
     */
    public static function requiresPrepay(string $zone, ?int $userId): bool
    {
        if ((string) setting('remote_prepay_required', '1') !== '1' || self::isLocal($zone)) {
            return false;
        }
        $after = (int) setting('remote_cod_after_orders', 3);
        return !($after > 0 && self::deliveredOrders($userId) >= $after);
    }

    /** Minimum estimated value of a sell shipment from a remote zone (0 = no minimum). */
    public static function remoteMinSell(): float
    {
        return (float) setting('remote_min_sell_value', 25);
    }

    /** Courier pickup fee charged to a seller, deducted from their payout. */
    public static function pickupFee(): float
    {
        return (float) setting('pickup_fee', 5);
    }

    /** Pickup + return trip, paid in cash by the seller when a rejected item is sent back. */
    public static function returnFee(): float
    {
        return (float) setting('return_fee', 10);
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
