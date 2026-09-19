<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Support\Delivery;

/** Storefront-side view of the delivery zones (admin-editable table) with a safe fallback to the built-in area list. */
final class Shipping
{
    /** @return array<int, array{name:string,fee:float,mode:string}> */
    public static function zones(): array
    {
        $out = [];
        foreach (Delivery::zones() as $z) {
            $out[] = ['name' => (string) $z['name'], 'fee' => (float) $z['fee'], 'mode' => ($z['mode'] ?? '') === 'local' ? 'local' : 'remote'];
        }
        if ($out) {
            return $out;
        }
        $fee = Delivery::defaultFee();
        return array_map(static fn (string $a): array => ['name' => $a, 'fee' => $fee, 'mode' => $a === 'Beirut' ? 'local' : 'remote'], CheckoutController::AREAS);
    }

    /** @return string[] */
    public static function names(): array
    {
        return array_column(self::zones(), 'name');
    }

    /** 'local' or 'remote' for a zone name ('' when the name is not a delivery zone). */
    public static function mode(string $zone): string
    {
        foreach (self::zones() as $z) {
            if ($z['name'] === $zone) {
                return $z['mode'];
            }
        }
        return '';
    }

    public static function minFee(): float
    {
        $fees = array_column(self::zones(), 'fee');
        return $fees ? (float) min($fees) : Delivery::defaultFee();
    }

    public static function freeOver(): float
    {
        return Delivery::freeOver();
    }

    /** Server-authoritative fee (0 above the free-delivery threshold). */
    public static function fee(string $area, float $subtotal): float
    {
        return Delivery::fee($area, $subtotal);
    }

    /** "Beirut — $5 delivery · Local" (or "free delivery" once the subtotal reaches the free-delivery threshold). */
    public static function label(array $zone, float $subtotal = 0.0, ?bool $prepay = null): string
    {
        $free = self::freeOver();
        $kind = ($zone['mode'] ?? 'remote') === 'local' ? 'Local' : (($prepay ?? Rules::prepayOn()) ? 'Prepay' : 'Remote');
        if ($free > 0 && $subtotal >= $free) {
            return $zone['name'] . ' — free delivery · ' . $kind;
        }
        return $zone['name'] . ' — ' . money($zone['fee']) . ' delivery · ' . $kind;
    }
}
