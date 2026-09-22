<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Placeholders for guide/article text so numbers never go stale: write {{local_fee}} and it renders the current setting.
 * Unknown tokens are left untouched.
 */
final class Tokens
{
    /** @return array<string, array{0: string, 1: string}> token => [description, example output] */
    public static function help(): array
    {
        return [
            'site_name'      => ['Store name', (string) setting('site_name', 'CyberGaming Lebanon')],
            'local_fee'      => ['Delivery fee in local zones', money(self::localFee())],
            'remote_fee'     => ['Delivery fee in remote zones', money(self::remoteFee())],
            'pickup_fee'     => ['Courier pickup fee deducted from a seller payout', money(Delivery::pickupFee())],
            'return_fee'     => ['Fee to send back a rejected item', money(Delivery::returnFee())],
            'min_sell'       => ['Minimum shipment value from remote zones', money(Delivery::remoteMinSell())],
            'buyback_pct'    => ['Cash buy-back percentage', (string) setting('buyback_pct', 45) . '%'],
            'tradein_pct'    => ['Store-credit percentage', (string) setting('tradein_pct', 50) . '%'],
            'swap_fee'       => ['Swap fee per side', money((float) setting('swap_fee', 3))],
            'member_commission' => ['Commission on member sales', (string) setting('member_commission_pct', 10) . '%'],
            'cod_after'      => ['Delivered orders before cash on delivery outside the local area', (string) setting('remote_cod_after_orders', 3)],
            'hold_days'      => ['Days we hold a rejected item before recycling', (string) setting('reject_hold_days', 14)],
            'local_zones'    => ['Names of the local delivery zones', self::zoneNames('local')],
            'remote_zones'   => ['Names of the remote delivery zones', self::zoneNames('remote')],
            'product_count'  => ['Number of items in stock', (string) self::productCount()],
        ];
    }

    public static function replace(string $text): string
    {
        $map = [];
        foreach (self::help() as $token => [, $value]) {
            $map['{{' . $token . '}}'] = $value;
        }
        return strtr($text, $map);
    }

    private static function localFee(): float
    {
        $fee = db()->fetchValue("SELECT MIN(fee) FROM delivery_zones WHERE is_active = 1 AND mode = 'local'");
        return $fee === null ? Delivery::defaultFee() : (float) $fee;
    }

    private static function remoteFee(): float
    {
        $fee = db()->fetchValue("SELECT MIN(fee) FROM delivery_zones WHERE is_active = 1 AND mode = 'remote'");
        return $fee === null ? Delivery::defaultFee() : (float) $fee;
    }

    private static function zoneNames(string $mode): string
    {
        $names = array_column(db()->fetchAll('SELECT name FROM delivery_zones WHERE is_active = 1 AND mode = ? ORDER BY sort_order, name', [$mode]), 'name');
        return implode(', ', $names);
    }

    private static function productCount(): int
    {
        return (int) db()->fetchValue('SELECT COUNT(*) FROM products p WHERE ' . \App\Modules\Storefront\Catalog::visible());
    }
}
