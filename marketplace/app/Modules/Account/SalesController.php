<?php
declare(strict_types=1);

namespace App\Modules\Account;

use App\Core\Request;
use App\Modules\Admin\Pagination;
use App\Modules\Seller\ListingCondition;

/**
 * Items sold from my listings + my payouts.
 * Selects order_items + orders.code/status/created_at ONLY: buyer columns are never read, so they cannot leak.
 */
final class SalesController extends ListingBase
{
    /** Order status => [label in member language, css modifier]. */
    public const STATUS = [
        'new'       => ['Order received. We are confirming it', 'pending'],
        'confirmed' => ['Sold! Please bring the item to our hub, or we will contact you for pickup', 'action'],
        'picked_up' => ['Collected', 'info'],
        'delivered' => ['Delivered. Your payout is coming', 'good'],
        'cancelled' => ['Cancelled', 'bad'],
    ];

    public function index(Request $request): void
    {
        if ($this->memberRow() === null) {
            $this->page('account/sales/index', ['seller' => null, 'lines' => [], 'pager' => null]);
            return;
        }
        $seller = $this->activeMember();
        $sid    = (int) $seller['id'];

        $total = (int) db()->fetchValue('SELECT COUNT(*) FROM order_items WHERE seller_id = ?', [$sid]);
        $pager = Pagination::fromRequest($request, $total, 20);

        $lines = db()->fetchAll(
            'SELECT oi.id, oi.title, oi.qty, oi.seller_price, o.code, o.status, o.created_at, p.image, p.id AS product_id, p.item_condition, p.includes_box, p.includes_cover_art, p.includes_manual,
                    (SELECT pa.status FROM payouts pa WHERE pa.order_item_id = oi.id AND pa.seller_id = oi.seller_id ORDER BY pa.id LIMIT 1) AS payout_status
               FROM order_items oi
               JOIN orders o ON o.id = oi.order_id
               LEFT JOIN products p ON p.id = oi.product_id AND p.seller_id = oi.seller_id
              WHERE oi.seller_id = ?
              ORDER BY o.created_at DESC, oi.id DESC' . $pager->limitSql(),
            [$sid]
        );

        $kinds = ListingCondition::kindsFor(array_column($lines, 'product_id'));

        $pending = db()->fetchAll(
            "SELECT pa.id, pa.amount, pa.created_at, oi.title, oi.qty, o.code AS order_code
               FROM payouts pa
               LEFT JOIN order_items oi ON oi.id = pa.order_item_id
               LEFT JOIN orders o ON o.id = oi.order_id
              WHERE pa.seller_id = ? AND pa.status = 'pending'
              ORDER BY pa.created_at DESC, pa.id DESC",
            [$sid]
        );
        $paid = db()->fetchAll(
            "SELECT pa.id, pa.amount, pa.paid_at, pa.method, oi.title, oi.qty, o.code AS order_code
               FROM payouts pa
               LEFT JOIN order_items oi ON oi.id = pa.order_item_id
               LEFT JOIN orders o ON o.id = oi.order_id
              WHERE pa.seller_id = ? AND pa.status = 'paid'
              ORDER BY pa.paid_at DESC, pa.id DESC LIMIT 50",
            [$sid]
        );

        $balance   = array_sum(array_map(static fn (array $r): float => (float) $r['amount'], $pending));
        $paidTotal = (float) db()->fetchValue("SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE seller_id = ? AND status = 'paid'", [$sid]);
        // Sold but not delivered yet: money on its way, not a payout yet.
        $inProgress = (float) db()->fetchValue(
            "SELECT COALESCE(SUM(oi.seller_price * oi.qty), 0) FROM order_items oi JOIN orders o ON o.id = oi.order_id
              WHERE oi.seller_id = ? AND o.status IN ('new','confirmed','picked_up')",
            [$sid]
        );

        $this->page('account/sales/index', compact('seller', 'lines', 'pager', 'pending', 'paid', 'balance', 'paidTotal', 'inProgress', 'kinds'));
    }
}
