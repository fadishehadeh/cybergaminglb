<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

/** Session cart: product_id => qty. Prices/stock are always re-read from the database. */
final class Cart
{
    private const KEY = 'cart';
    public const MAX_LINE_QTY = 10;

    /** @return array<int,int> */
    public static function raw(): array
    {
        $cart = app()->session()->get(self::KEY, []);
        return is_array($cart) ? $cart : [];
    }

    /** Items in the cart badge: only lines that can still be bought (a hidden category / platform / digital line does not count). */
    public static function count(): int
    {
        $cart = self::raw();
        if (!$cart) {
            return 0;
        }
        $products = Catalog::purchasable(array_keys($cart));
        $n = 0;
        foreach ($cart as $id => $qty) {
            if (isset($products[(int) $id])) {
                $n += max(1, min((int) $qty, (int) $products[(int) $id]['stock'], self::MAX_LINE_QTY));
            }
        }
        return $n;
    }

    private static function save(array $cart): void
    {
        app()->session()->put(self::KEY, $cart);
    }

    public static function clear(): void
    {
        app()->session()->remove(self::KEY);
    }

    public static function remove(int $id): void
    {
        $cart = self::raw();
        unset($cart[$id]);
        self::save($cart);
    }

    /** Sets a line quantity (capped at stock); returns the qty stored, 0 if the product cannot be bought. */
    public static function set(int $id, int $qty): int
    {
        $products = Catalog::purchasable([$id]);
        $p = $products[$id] ?? null;
        $cart = self::raw();
        if ($p === null || $qty < 1) {
            unset($cart[$id]);
            self::save($cart);
            return 0;
        }
        $qty = min($qty, (int) $p['stock'], self::MAX_LINE_QTY);
        $cart[$id] = $qty;
        self::save($cart);
        return $qty;
    }

    /** Adds to the line; returns the resulting qty or 0 if the product is not purchasable. */
    public static function add(int $id, int $qty): int
    {
        return self::set($id, (self::raw()[$id] ?? 0) + max(1, $qty));
    }

    /**
     * Cart lines joined with live product data. Lines whose product sold out / was hidden are dropped and
     * quantities are clamped to current stock. Digital lines are dropped silently while the master switch is off
     * (Catalog::purchasable() applies the digital gate). `physical` / `digital` split the subtotal.
     * @return array{lines:array,total:float,physical:float,digital:float,has_physical:bool,has_digital:bool,count:int,changed:bool}
     */
    public static function lines(): array
    {
        $cart = self::raw();
        $products = Catalog::purchasable(array_keys($cart));
        $lines = [];
        $total = 0.0;
        $physical = 0.0;
        $digital = 0.0;
        $nPhysical = 0;
        $nDigital = 0;
        $count = 0;
        $clean = [];
        foreach ($cart as $id => $qty) {
            $p = $products[(int) $id] ?? null;
            if ($p === null) {
                continue;
            }
            $qty = max(1, min((int) $qty, (int) $p['stock'], self::MAX_LINE_QTY));
            $clean[(int) $id] = $qty;
            $line = (float) $p['price'] * $qty;
            $lines[] = ['product' => $p, 'qty' => $qty, 'line_total' => (float) $line];
            $total += $line;
            if (Digital::is($p)) {
                $digital += $line;
                $nDigital++;
            } else {
                $physical += $line;
                $nPhysical++;
            }
            $count += $qty;
        }
        $changed = $clean !== array_map('intval', $cart);
        if ($changed) {
            self::save($clean);
        }
        return [
            'lines'        => $lines,
            'total'        => round($total, 2),
            'physical'     => round($physical, 2),
            'digital'      => round($digital, 2),
            'has_physical' => $nPhysical > 0,
            'has_digital'  => $nDigital > 0,
            'count'        => $count,
            'changed'      => $changed,
        ];
    }
}
