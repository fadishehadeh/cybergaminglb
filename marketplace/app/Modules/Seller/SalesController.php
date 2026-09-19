<?php
declare(strict_types=1);

namespace App\Modules\Seller;

use App\Core\Request;
use App\Modules\Admin\Pagination;

/** Sold items. Selects order_items + orders.code/status/created_at ONLY: buyers stay anonymous. */
final class SalesController extends PortalController
{
    /** Order status -> [label in seller language, pill class]. */
    public const STATUS = [
        'new'       => ['Order received, we are confirming it', 'pending'],
        'confirmed' => ['Sold. Please hand the item to our courier / hub', 'action'],
        'picked_up' => ['Collected', 'info'],
        'delivered' => ['Delivered. Payout scheduled', 'good'],
        'cancelled' => ['Cancelled. The item is back on sale', 'bad'],
    ];

    public function index(Request $request): void
    {
        $seller = $this->seller();
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

        $this->view('sales', ['lines' => $lines, 'pager' => $pager, 'kinds' => $kinds]);
    }
}
