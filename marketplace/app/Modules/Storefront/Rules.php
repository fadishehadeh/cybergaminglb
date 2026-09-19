<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Support\Delivery;

/**
 * The local vs remote delivery rules in one place: numbers come from settings, wording is shared by the checkout,
 * the sell / trade flow, the account pages and the information pages so they never disagree.
 *
 *  LOCAL zones  : our own courier, flat fee, can inspect on the spot, cash on delivery.
 *  REMOTE zones : third-party courier that cannot inspect: buyers prepay (OMT / Whish) until they have
 *                 remote_cod_after_orders delivered orders; sellers ship to our hub and a pickup fee is deducted.
 */
final class Rules
{
    public static function prepayOn(): bool
    {
        return (string) setting('remote_prepay_required', '1') === '1';
    }

    /** Delivered orders after which a customer may pay cash on delivery outside the local area (0 = never). */
    public static function codAfter(): int
    {
        return max(0, (int) setting('remote_cod_after_orders', 3));
    }

    public static function holdDays(): int
    {
        return max(1, (int) setting('reject_hold_days', 14));
    }

    public static function minSell(): float
    {
        return Delivery::remoteMinSell();
    }

    public static function pickupFee(): float
    {
        return Delivery::pickupFee();
    }

    public static function returnFee(): float
    {
        return Delivery::returnFee();
    }

    /** @return string[] */
    public static function names(string $mode): array
    {
        $out = [];
        foreach (Shipping::zones() as $z) {
            if ($z['mode'] === $mode) {
                $out[] = $z['name'];
            }
        }
        return $out;
    }

    /** "$5", "$5 to $6" for the delivery fees of the zones of one mode. */
    public static function feeRange(string $mode): string
    {
        $fees = [];
        foreach (Shipping::zones() as $z) {
            if ($z['mode'] === $mode) {
                $fees[] = (float) $z['fee'];
            }
        }
        if (!$fees) {
            return '';
        }
        $lo = min($fees);
        $hi = max($fees);
        return $lo === $hi ? money($lo) : money($lo) . ' to ' . money($hi);
    }

    /** "Beirut, Baabda, Dbayeh and Metn coast" (a bracketed detail is dropped to keep the line short). */
    public static function nameList(array $names): string
    {
        return implode(', ', array_map(static fn (string $n): string => trim((string) preg_replace('/\s*\([^)]*\)/', '', $n)), $names));
    }

    /** One-line delivery summary for the cart, built from the live zones and settings. */
    public static function cartLine(): string
    {
        $local = self::names('local');
        $remote = self::names('remote');
        $parts = [];
        if ($local) {
            $parts[] = 'Delivery from ' . self::feeRange('local') . ' in ' . self::nameList($local) . ' (our own courier, cash on delivery)';
        }
        if ($remote) {
            $fee = self::feeRange('remote');
            $tail = self::prepayOn()
                ? 'with prepayment (OMT or Whish)' . (self::codAfter() > 0 ? ', or cash on delivery after ' . self::codAfter() . ' delivered orders' : '')
                : 'with cash on delivery';
            $parts[] = ($local ? $fee . ' elsewhere ' : 'Delivery ' . $fee . ' ') . $tail;
        }
        return implode('; ', $parts) . '.';
    }

    /** Type label for the zone tables. */
    public static function typeLabel(string $mode): string
    {
        if ($mode === 'local') {
            return 'Local: our courier, cash on delivery';
        }
        return self::prepayOn() ? 'Remote: prepay by OMT or Whish' : 'Remote: third-party courier';
    }

    /**
     * How an order's total splits into payments. $digital is always prepaid; the physical remainder is prepaid
     * only for a remote order that needs prepayment, otherwise it is cash on delivery.
     * @return array{cash:float,prepay:float,physical:float}
     */
    public static function due(float $grand, float $credit, float $digital, bool $physicalPrepaid): array
    {
        $physical = max(0.0, round($grand - $credit - $digital, 2));
        return [
            'cash'     => $physicalPrepaid ? 0.0 : $physical,
            'prepay'   => round($digital + ($physicalPrepaid ? $physical : 0.0), 2),
            'physical' => $physical,
        ];
    }

    /**
     * Was the physical part of this stored order prepaid? Stored orders keep zone_mode + payment_status: a remote
     * order that is awaiting/received payment without digital lines is prepaid. With digital lines payment_status
     * is set for them alone, so the physical rule is re-evaluated for that account.
     */
    public static function physicalPrepaid(array $order, bool $hasDigital, bool $hasPhysical): bool
    {
        if (!$hasPhysical || ($order['zone_mode'] ?? null) !== 'remote') {
            return false;
        }
        if (!$hasDigital) {
            return ($order['payment_status'] ?? 'not_required') !== 'not_required';
        }
        return Delivery::requiresPrepay((string) ($order['zone'] ?? $order['buyer_area'] ?? ''), ($order['user_id'] ?? null) !== null ? (int) $order['user_id'] : null);
    }

    /** What a seller receives: the estimate minus the pickup fee (never below zero). */
    public static function net(float $estimate, float $pickupFee): float
    {
        return max(0.0, round($estimate - $pickupFee, 2));
    }

    /** Shipments from a remote zone by courier must be worth this much; returns an error text or null. */
    public static function minSellError(string $zoneMode, string $collection, float $estimate): ?string
    {
        $min = self::minSell();
        if ($zoneMode === 'remote' && $collection === 'pickup' && $min > 0 && $estimate + 0.004 < $min) {
            return 'Shipments from your area must be worth at least ' . money($min) . ': add more games, or bring them to our hub.';
        }
        return null;
    }

    /**
     * FAQ entries about how sold / traded games reach us and what happens to a rejected item. Shared by the /sell and
     * /trade pages so the numbers (pickup fee, minimum, return fee, hold days) always come from the settings.
     * @return array<int,array{0:string,1:string}>
     */
    public static function sellFaqs(): array
    {
        $fee = money(self::pickupFee());
        $ret = money(self::returnFee());
        $min = self::minSell();
        $days = self::holdDays();
        $local = self::nameList(self::names('local'));
        $faqs = [
            ['Where do I hand my games over?', 'You have two options. Bring them to our hub yourself: free, in every area. Or have them picked up. In local areas (' . $local . ') our own courier comes to you and checks the games on the spot. Elsewhere, a third-party courier ships them to our hub and we inspect them on arrival. You choose when you send your request.'],
            ['What does a courier pickup cost?', 'A ' . $fee . ' pickup fee, deducted from your payout: you never pay it separately, and the site shows what you will receive before you send your request. Bringing the games to our hub is free. If our courier declines an item on the spot in a local area, there is no return trip and no fee.'],
        ];
        if ($min > 0) {
            $faqs[] = ['Is there a minimum if I live outside the local area?', 'Yes. A courier shipment from a remote area must be worth at least ' . money($min) . ' (the estimate for the payout you choose). Add more games, or bring them to our hub yourself: there is no minimum for that.'];
        }
        $faqs[] = ['What happens if you cannot accept a game after inspecting it?', 'You choose. Take a revised (lower) offer if we can make one, or get the game sent back and pay the ' . $ret . ' return fee (the pickup plus the return trip) in cash to the courier on delivery, or let us recycle it for free. Nothing is decided without you.'];
        $faqs[] = ['How long do you wait for my answer about a rejected game?', 'We hold it for ' . $days . ' days. If we hear nothing within that time, we recycle it.'];
        return $faqs;
    }
}
