<?php
declare(strict_types=1);

namespace App\Modules\Account;

use App\Core\Request;
use App\Modules\Storefront\Rules;

/** A customer's own orders. Every query is scoped by user_id; other people's codes are a plain 404. */
final class OrderController extends BaseController
{
    public function index(Request $request): void
    {
        $me = $this->me();
        $orders = db()->fetchAll(
            'SELECT o.code, o.status, o.total, o.delivery_fee, o.grand_total, o.credit_used, o.payment_status, o.created_at,
                    (SELECT COALESCE(SUM(qty), 0) FROM order_items i WHERE i.order_id = o.id) AS item_count
               FROM orders o WHERE o.user_id = ? ORDER BY o.id DESC LIMIT 100',
            [(int) $me['id']]
        );
        $this->page('account/orders/index', ['me' => $me, 'orders' => $orders]);
    }

    public function show(Request $request, string $code): void
    {
        $me = $this->me();
        $code = strtoupper(trim($code));
        if (!preg_match('/^[A-Z0-9-]{4,20}$/', $code)) {
            $this->notFound();
        }
        // Only buyer-side columns. Seller details (order_items.seller_id / seller_price) are never selected.
        $order = db()->fetch(
            'SELECT id, code, user_id, status, total, delivery_fee, grand_total, credit_used, buyer_area, zone, zone_mode, payment_status, buyer_address, buyer_note, created_at, updated_at
               FROM orders WHERE code = ? AND user_id = ?',
            [$code, (int) $me['id']]
        );
        if ($order === null) {
            $this->notFound();
        }
        $items = db()->fetchAll('SELECT title, qty, unit_price, is_digital FROM order_items WHERE order_id = ? ORDER BY id', [(int) $order['id']]);

        $subtotal = (float) $order['total'];
        $delivery = (float) $order['delivery_fee'];
        $grand    = (float) $order['grand_total'];
        if ($grand <= 0) {
            $grand = $subtotal + $delivery; // orders placed before the wallet existed
        }
        $credit = min((float) $order['credit_used'], $grand);
        // Digital lines are always prepaid; the physical part is prepaid too for a remote zone that needed prepayment.
        $digital = 0.0;
        $hasDigital = false;
        $hasPhysical = false;
        foreach ($items as $i) {
            if ((int) $i['is_digital'] === 1) {
                $digital += (float) $i['unit_price'] * (int) $i['qty'];
                $hasDigital = true;
            } else {
                $hasPhysical = true;
            }
        }
        $physPrepaid = Rules::physicalPrepaid($order, $hasDigital, $hasPhysical);
        $due = Rules::due($grand, $credit, round($digital, 2), $physPrepaid);
        $cancelled = $order['status'] === 'cancelled';
        $cashDue = $cancelled ? 0.0 : $due['cash'];
        $prepayDue = $cancelled ? 0.0 : $due['prepay'];

        $summary = implode(', ', array_map(static fn (array $i): string => $i['qty'] . 'x ' . $i['title'], $items));
        $waLink = wa_link('Hi CyberGaming, about my order ' . $order['code'] . ($summary !== '' ? ' (' . $summary . ')' : ''));

        $this->page('account/orders/show', [
            'me'       => $me,
            'order'    => $order,
            'items'    => $items,
            'subtotal' => $subtotal,
            'delivery' => $delivery,
            'grand'    => $grand,
            'credit'   => $credit,
            'cashDue'  => $cashDue,
            'prepay'   => $prepayDue,
            'physPrepaid' => $physPrepaid,
            'waLink'   => $waLink,
        ]);
    }
}
