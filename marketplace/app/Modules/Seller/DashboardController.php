<?php
declare(strict_types=1);

namespace App\Modules\Seller;

use App\Core\Request;
use App\Support\Pricing;

final class DashboardController extends PortalController
{
    public function index(Request $request): void
    {
        $seller = $this->seller();
        $sid    = (int) $seller['id'];

        $count = static fn (string $status): int => (int) db()->fetchValue(
            'SELECT COUNT(*) FROM products WHERE seller_id = ? AND status = ?',
            [$sid, $status]
        );

        // Quantities on order lines, by order status. Only order_items + orders.status: no buyer columns.
        $lines = ['new' => 0, 'confirmed' => 0, 'picked_up' => 0, 'delivered' => 0];
        foreach (db()->fetchAll(
            'SELECT o.status, COALESCE(SUM(oi.qty), 0) AS units
               FROM order_items oi JOIN orders o ON o.id = oi.order_id
              WHERE oi.seller_id = ? GROUP BY o.status',
            [$sid]
        ) as $row) {
            if (isset($lines[$row['status']])) {
                $lines[$row['status']] = (int) $row['units'];
            }
        }

        $stats = [
            'active'    => $count('active'),
            'pending'   => $count('pending'),
            'hidden'    => $count('hidden'),
            'sold'      => $lines['confirmed'] + $lines['picked_up'] + $lines['delivered'],
            'handover'  => $lines['confirmed'],
            'pay_pending' => (float) db()->fetchValue("SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE seller_id = ? AND status = 'pending'", [$sid]),
            'pay_paid'    => (float) db()->fetchValue("SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE seller_id = ? AND status = 'paid'", [$sid]),
        ];

        $this->view('dashboard', [
            'stats'      => $stats,
            'commission' => Pricing::commissionPct($seller),
            'isOverride' => $seller['commission_pct'] !== null,
        ]);
    }
}
