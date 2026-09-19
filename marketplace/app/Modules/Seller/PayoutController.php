<?php
declare(strict_types=1);

namespace App\Modules\Seller;

use App\Core\Request;
use App\Modules\Admin\Pagination;

final class PayoutController extends PortalController
{
    public function index(Request $request): void
    {
        $seller = $this->seller();
        $sid    = (int) $seller['id'];

        $pending = db()->fetchAll(
            "SELECT pa.id, pa.amount, pa.created_at, oi.title, oi.qty, o.code AS order_code
               FROM payouts pa
               LEFT JOIN order_items oi ON oi.id = pa.order_item_id
               LEFT JOIN orders o ON o.id = oi.order_id
              WHERE pa.seller_id = ? AND pa.status = 'pending'
              ORDER BY pa.created_at DESC, pa.id DESC",
            [$sid]
        );

        $total = (int) db()->fetchValue("SELECT COUNT(*) FROM payouts WHERE seller_id = ? AND status = 'paid'", [$sid]);
        $pager = Pagination::fromRequest($request, $total, 20);
        $paid  = db()->fetchAll(
            "SELECT pa.id, pa.amount, pa.paid_at, pa.note, oi.title, oi.qty, o.code AS order_code
               FROM payouts pa
               LEFT JOIN order_items oi ON oi.id = pa.order_item_id
               LEFT JOIN orders o ON o.id = oi.order_id
              WHERE pa.seller_id = ? AND pa.status = 'paid'
              ORDER BY pa.paid_at DESC, pa.id DESC" . $pager->limitSql(),
            [$sid]
        );

        $balance = array_sum(array_map(static fn (array $r): float => (float) $r['amount'], $pending));
        $paidTotal = (float) db()->fetchValue("SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE seller_id = ? AND status = 'paid'", [$sid]);

        $this->view('payouts', compact('pending', 'paid', 'pager', 'balance', 'paidTotal'));
    }
}
