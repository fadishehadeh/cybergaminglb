<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;

/** Simple e-commerce dashboard: orders, revenue, profit and stock needing attention. No accounts, no sellers. */
final class DashboardController extends AdminController
{
    public function index(Request $request): void
    {
        $monthStart = "DATE_FORMAT(CURDATE(), '%Y-%m-01')";

        $orderCount = static fn (string $extra): int => (int) db()->fetchValue(
            "SELECT COUNT(*) FROM orders WHERE status <> 'cancelled' AND $extra"
        );

        // Profit on delivered orders, from the cost snapshotted at order time. NULL cost (never priced) counts as 0 profit
        // for that line, so the figure is always a safe lower bound rather than an overstatement.
        $delivered = db()->fetch(
            "SELECT COALESCE(SUM(oi.unit_price * oi.qty), 0) AS revenue,
                    COALESCE(SUM(oi.qty), 0) AS units,
                    COALESCE(SUM((oi.unit_price - COALESCE(oi.cost_price, oi.unit_price)) * oi.qty), 0) AS profit,
                    COALESCE(SUM(oi.cost_price IS NULL), 0) AS uncosted_lines
               FROM order_items oi JOIN orders o ON o.id = oi.order_id
              WHERE o.status = 'delivered' AND o.created_at >= $monthStart"
        ) ?? ['revenue' => 0, 'units' => 0, 'profit' => 0, 'uncosted_lines' => 0];

        $deliveredAll = db()->fetch(
            "SELECT COALESCE(SUM((oi.unit_price - COALESCE(oi.cost_price, oi.unit_price)) * oi.qty), 0) AS profit
               FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.status = 'delivered'"
        ) ?? ['profit' => 0];

        // Potential profit sitting in current stock (active, in-stock, priced items only).
        $stockProfit = db()->fetch(
            "SELECT COALESCE(SUM((price - cost_price) * stock), 0) AS profit,
                    COALESCE(SUM(price * stock), 0) AS value,
                    COALESCE(SUM(stock), 0) AS units,
                    SUM(cost_price IS NULL) AS uncosted
               FROM products WHERE status = 'active' AND stock > 0 AND is_digital = 0"
        ) ?? ['profit' => 0, 'value' => 0, 'units' => 0, 'uncosted' => 0];

        $kpi = [
            'orders_today'  => $orderCount('DATE(created_at) = CURDATE()'),
            'orders_month'  => $orderCount("created_at >= $monthStart"),
            'revenue_month' => (float) db()->fetchValue(
                "SELECT COALESCE(SUM(total), 0) FROM orders WHERE status = 'delivered' AND created_at >= $monthStart"
            ),
            'units_month'        => (int) $delivered['units'],
            'profit_month'       => (float) $delivered['profit'],
            'profit_uncosted'    => (int) $delivered['uncosted_lines'],
            'profit_all_time'    => (float) $deliveredAll['profit'],
            'stock_value'        => (float) $stockProfit['value'],
            'stock_profit'       => (float) $stockProfit['profit'],
            'stock_units'        => (int) $stockProfit['units'],
            'stock_uncosted'     => (int) $stockProfit['uncosted'],
            'pending_products'  => (int) db()->fetchValue("SELECT COUNT(*) FROM products WHERE status = 'pending'"),
            'missing_photos'    => (int) db()->fetchValue('SELECT COUNT(*) FROM products p WHERE ' . ListingRules::missingSql('p')),
            'out_of_stock'      => (int) db()->fetchValue("SELECT COUNT(*) FROM products WHERE status = 'sold' AND is_digital = 0"),
            'articles_draft'    => (int) db()->fetchValue("SELECT COUNT(*) FROM articles WHERE is_published = 0"),
            'orders_awaiting_payment' => (int) db()->fetchValue("SELECT COUNT(*) FROM orders WHERE payment_status = 'awaiting' AND status <> 'cancelled'"),
        ];

        // Cash the courier collects (local zones, or a remote buyer who already earned cash on delivery).
        $cash = db()->fetch(
            "SELECT COALESCE(SUM(GREATEST(0, (CASE WHEN o.grand_total > 0 THEN o.grand_total ELSE o.total + o.delivery_fee END) - o.credit_used)), 0) AS amount,
                    COUNT(*) AS orders
               FROM orders o WHERE o.status IN ('confirmed', 'picked_up') AND o.payment_status = 'not_required'"
        ) ?? ['amount' => 0, 'orders' => 0];
        $kpi['cash_to_collect'] = (float) $cash['amount'];
        $kpi['cash_orders']     = (int) $cash['orders'];

        $awaitingOrders = db()->fetchAll(
            "SELECT o.id, o.code, o.buyer_name, o.zone, o.zone_mode, o.status, o.created_at,
                    (CASE WHEN o.grand_total > 0 THEN o.grand_total ELSE o.total + o.delivery_fee END) - o.credit_used AS due
               FROM orders o WHERE o.payment_status = 'awaiting' AND o.status <> 'cancelled'
              ORDER BY o.created_at ASC, o.id ASC LIMIT 6"
        );
        $newOrders = db()->fetchAll(
            "SELECT id, code, buyer_name, buyer_area, total, created_at FROM orders WHERE status = 'new' ORDER BY created_at ASC LIMIT 8"
        );
        $pendingProducts = db()->fetchAll(
            "SELECT p.id, p.title, p.cost_price, p.price, p.item_condition, p.is_digital, p.category_id, pl.name AS platform,
                    " . ListingRules::photoCountSql('p') . " AS photo_count
               FROM products p LEFT JOIN platforms pl ON pl.id = p.platform_id
              WHERE p.status = 'pending' ORDER BY p.created_at ASC LIMIT 8"
        );
        $recentOrders = db()->fetchAll(
            "SELECT o.id, o.code, o.buyer_name, o.buyer_area, o.total, o.status, o.created_at,
                    (SELECT COALESCE(SUM(qty), 0) FROM order_items WHERE order_id = o.id) AS units
               FROM orders o ORDER BY o.created_at DESC, o.id DESC LIMIT 10"
        );
        $lowStock = db()->fetchAll(
            "SELECT id, title, stock, price FROM products
              WHERE status = 'active' AND is_digital = 0 AND stock BETWEEN 1 AND 2
              ORDER BY stock ASC, title ASC LIMIT 8"
        );

        $this->view('dashboard', compact('kpi', 'newOrders', 'pendingProducts', 'recentOrders', 'awaitingOrders', 'lowStock'));
    }
}
