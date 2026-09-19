<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;
use App\Support\Wallet;

final class DashboardController extends AdminController
{
    public function index(Request $request): void
    {
        $monthStart = "DATE_FORMAT(CURDATE(), '%Y-%m-01')";

        $orderCount = static fn (string $extra): int => (int) db()->fetchValue(
            "SELECT COUNT(*) FROM orders WHERE status <> 'cancelled' AND $extra"
        );

        $house = db()->fetch(
            "SELECT COALESCE(SUM(oi.unit_price * oi.qty), 0) AS amount, COALESCE(SUM(oi.qty), 0) AS units
               FROM order_items oi JOIN orders o ON o.id = oi.order_id
              WHERE o.status = 'delivered' AND o.created_at >= $monthStart AND oi.seller_id IS NULL"
        ) ?? ['amount' => 0, 'units' => 0];

        $kpi = [
            'orders_today'  => $orderCount('DATE(created_at) = CURDATE()'),
            'orders_month'  => $orderCount("created_at >= $monthStart"),
            'revenue_month' => (float) db()->fetchValue(
                "SELECT COALESCE(SUM(total), 0) FROM orders WHERE status = 'delivered' AND created_at >= $monthStart"
            ),
            'commission_month' => (float) db()->fetchValue(
                "SELECT COALESCE(SUM((oi.unit_price - oi.seller_price) * oi.qty), 0)
                   FROM order_items oi JOIN orders o ON o.id = oi.order_id
                  WHERE o.status = 'delivered' AND o.created_at >= $monthStart AND oi.seller_id IS NOT NULL"
            ),
            'house_month'   => (float) $house['amount'],
            'house_units'   => (int) $house['units'],
            'pending_products' => (int) db()->fetchValue("SELECT COUNT(*) FROM products WHERE status = 'pending'"),
            'missing_photos'   => (int) db()->fetchValue('SELECT COUNT(*) FROM products p WHERE ' . ListingRules::missingSql('p')),
            'pending_sellers'  => (int) db()->fetchValue("SELECT COUNT(*) FROM sellers WHERE status = 'pending'"),
            'payouts_owed'  => (float) db()->fetchValue("SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE status = 'pending'"),
            'requests_new'  => (int) db()->fetchValue("SELECT COUNT(*) FROM buyback_requests WHERE status = 'new'")
                             + (int) db()->fetchValue("SELECT COUNT(*) FROM swap_requests WHERE status = 'new'"),
        ];

        $count = static fn (string $status): int => (int) db()->fetchValue('SELECT COUNT(*) FROM buyback_requests WHERE status = ?', [$status]);
        $kpi += [
            'credit_owed'      => Wallet::totalLiability(),
            'sell_to_offer'    => $count('new') + $count('contacted'),
            'sell_offered'     => $count('offered'),
            'sell_to_collect'  => $count('accepted'),
            'sell_to_inspect'  => $count('collected'),
            'delivery_fees_month' => (float) db()->fetchValue(
                "SELECT COALESCE(SUM(delivery_fee), 0) FROM orders WHERE status = 'delivered' AND created_at >= $monthStart"
            ),
        ];

        // Cash the courier collects. Digital lines are prepaid (OMT/Whish): a digital-only order collects nothing, and in a
        // mixed order only the physical part counts (wallet credit is applied to the digital part first).
        $cash = db()->fetch(
            "SELECT COALESCE(SUM(c.cash), 0) AS amount, COALESCE(SUM(c.cash > 0), 0) AS orders FROM (
                SELECT IF(x.dl = 0,
                          GREATEST(0, x.grand - x.credit_used),
                          IF(x.pl = 0, 0, GREATEST(0, x.grand - x.dsub - GREATEST(0, x.credit_used - x.dsub)))) AS cash
                  FROM (
                    SELECT IF(o.grand_total > 0, o.grand_total, o.total + o.delivery_fee) AS grand, o.credit_used,
                           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id AND is_digital = 1) AS dl,
                           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id AND is_digital = 0) AS pl,
                           (SELECT COALESCE(SUM(unit_price * qty), 0) FROM order_items WHERE order_id = o.id AND is_digital = 1) AS dsub
                      FROM orders o WHERE o.status IN ('confirmed', 'picked_up')
                  ) x
             ) c"
        ) ?? ['amount' => 0, 'orders' => 0];
        $kpi['cash_to_collect'] = (float) $cash['amount'];
        $kpi['cash_orders']     = (int) $cash['orders'];

        // Digital orders still to process: waiting for payment (new) or for the code (confirmed).
        $digitalOrders = db()->fetchAll(
            "SELECT o.id, o.code, o.buyer_name, o.status, o.created_at,
                    (SELECT COALESCE(SUM(unit_price * qty), 0) FROM order_items WHERE order_id = o.id AND is_digital = 1) AS digital_total
               FROM orders o
              WHERE o.status IN ('new', 'confirmed') AND EXISTS (SELECT 1 FROM order_items x WHERE x.order_id = o.id AND x.is_digital = 1)
              ORDER BY o.created_at ASC, o.id ASC LIMIT 6"
        );
        $kpi['digital_open'] = (int) db()->fetchValue(
            "SELECT COUNT(*) FROM orders o
              WHERE o.status IN ('new', 'confirmed') AND EXISTS (SELECT 1 FROM order_items x WHERE x.order_id = o.id AND x.is_digital = 1)"
        );
        $kpi['digital_new'] = (int) db()->fetchValue(
            "SELECT COUNT(*) FROM orders o
              WHERE o.status = 'new' AND EXISTS (SELECT 1 FROM order_items x WHERE x.order_id = o.id AND x.is_digital = 1)"
        );

        $newOrders = db()->fetchAll(
            "SELECT id, code, buyer_name, buyer_area, total, created_at FROM orders WHERE status = 'new' ORDER BY created_at ASC LIMIT 8"
        );
        $pendingProducts = db()->fetchAll(
            "SELECT p.id, p.title, p.seller_price, p.price, p.item_condition, p.is_digital, s.code AS seller_code, pl.name AS platform,
                    " . ListingRules::photoCountSql('p') . " AS photo_count
               FROM products p
               LEFT JOIN sellers s ON s.id = p.seller_id
               LEFT JOIN platforms pl ON pl.id = p.platform_id
              WHERE p.status = 'pending' ORDER BY p.created_at ASC LIMIT 8"
        );
        $pendingSellers = db()->fetchAll(
            "SELECT id, code, name, area, created_at FROM sellers WHERE status = 'pending' ORDER BY created_at ASC LIMIT 8"
        );
        $recentOrders = db()->fetchAll(
            "SELECT o.id, o.code, o.buyer_name, o.buyer_area, o.total, o.status, o.created_at,
                    (SELECT COALESCE(SUM(qty), 0) FROM order_items WHERE order_id = o.id) AS units,
                    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id AND is_digital = 1) AS digital_lines
               FROM orders o ORDER BY o.created_at DESC, o.id DESC LIMIT 10"
        );

        $this->view('dashboard', compact('kpi', 'newOrders', 'pendingProducts', 'pendingSellers', 'recentOrders', 'digitalOrders'));
    }
}
