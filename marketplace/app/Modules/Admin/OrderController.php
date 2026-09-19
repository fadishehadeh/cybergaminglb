<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Support\Wallet;

final class OrderController extends AdminController
{
    public const FLOW     = ['new', 'confirmed', 'picked_up', 'delivered'];
    public const STATUSES = ['new', 'confirmed', 'picked_up', 'delivered', 'cancelled'];

    /** Digital-only orders have nothing to pick up: new -> confirmed -> delivered. */
    public const FLOW_DIGITAL = ['new', 'confirmed', 'delivered'];

    /**
     * Forward moves (skipping is allowed, e.g. house stock needs no pickup) plus cancel, until delivered/cancelled.
     * Orders with digital lines can never skip "confirmed": that is the step where the OMT/Whish payment is checked,
     * and the code must not be released before it. Digital-only orders also skip "picked up".
     */
    public static function allowed(string $from, bool $hasDigital = false, bool $digitalOnly = false): array
    {
        $i = array_search($from, self::FLOW, true);
        if ($i === false || $from === 'delivered') {
            return [];
        }
        $next = array_slice(self::FLOW, $i + 1);
        if ($digitalOnly) {
            $next = array_values(array_diff($next, ['picked_up']));
        }
        if ($hasDigital && $from === 'new') {
            $next = ['confirmed'];
        }
        return [...$next, 'cancelled'];
    }

    /** Counts of digital / physical lines and the digital subtotal, for one order. */
    public static function lineMix(int $orderId): array
    {
        $row = db()->fetch(
            'SELECT COALESCE(SUM(is_digital = 1), 0) AS dl, COALESCE(SUM(is_digital = 0), 0) AS pl,
                    COALESCE(SUM(IF(is_digital = 1, unit_price * qty, 0)), 0) AS dsub
               FROM order_items WHERE order_id = ?',
            [$orderId]
        ) ?? [];
        return ['digital' => (int) ($row['dl'] ?? 0), 'physical' => (int) ($row['pl'] ?? 0), 'subtotal' => (float) ($row['dsub'] ?? 0)];
    }

    /** Amount the order comes to. Orders from before delivery fees have grand_total = 0, so fall back to total + fee. */
    public const GRAND_SQL = 'IF(o.grand_total > 0, o.grand_total, o.total + o.delivery_fee)';

    /** Cash the courier collects: what is left after credit. */
    public static function cashDue(array $order): float
    {
        $grand = (float) $order['grand_total'] > 0 ? (float) $order['grand_total'] : (float) $order['total'] + (float) $order['delivery_fee'];
        return max(0.0, round($grand - (float) $order['credit_used'], 2));
    }

    public function index(Request $request): void
    {
        $status = Forms::text($request->query('status'));
        $q      = Forms::text($request->query('q'));
        $kind   = Forms::text($request->query('kind'));
        if (!in_array($kind, ['physical', 'digital'], true)) {
            $kind = '';
        }
        $open = $request->query('open') === '1';

        $where  = [];
        $params = [];
        if (in_array($status, self::STATUSES, true)) {
            $where[]  = 'o.status = ?';
            $params[] = $status;
        } elseif ($open) {
            // "still to process": waiting for payment (new) or for the code / hand-over (confirmed)
            $where[] = "o.status IN ('new', 'confirmed')";
        }
        if ($kind === 'digital') {
            $where[] = 'EXISTS (SELECT 1 FROM order_items x WHERE x.order_id = o.id AND x.is_digital = 1)';
        } elseif ($kind === 'physical') {
            $where[] = 'NOT EXISTS (SELECT 1 FROM order_items x WHERE x.order_id = o.id AND x.is_digital = 1)';
        }
        if ($q !== '') {
            $like     = '%' . addcslashes($q, '%_\\') . '%';
            $where[]  = '(o.code LIKE ? OR o.buyer_name LIKE ? OR o.buyer_phone LIKE ?)';
            array_push($params, $like, $like, $like);
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = (int) db()->fetchValue('SELECT COUNT(*) FROM orders o' . $whereSql, $params);
        $pager = Pagination::fromRequest($request, $total, 25);

        $orders = db()->fetchAll(
            'SELECT o.id, o.code, o.user_id, o.buyer_name, o.buyer_phone, o.buyer_area, o.total, o.delivery_fee, o.credit_used, o.grand_total, o.status, o.created_at,
                    ' . self::GRAND_SQL . ' AS grand,
                    (SELECT COALESCE(SUM(qty), 0) FROM order_items WHERE order_id = o.id) AS units,
                    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id AND seller_id IS NOT NULL) AS seller_lines,
                    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id AND is_digital = 1) AS digital_lines,
                    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id AND is_digital = 0) AS physical_lines,
                    (SELECT COALESCE(SUM(unit_price * qty), 0) FROM order_items WHERE order_id = o.id AND is_digital = 1) AS digital_subtotal
               FROM orders o' . $whereSql . ' ORDER BY o.created_at DESC, o.id DESC' . $pager->limitSql(),
            $params
        );

        $counts = [];
        foreach (db()->fetchAll('SELECT status, COUNT(*) AS n FROM orders GROUP BY status') as $row) {
            $counts[$row['status']] = (int) $row['n'];
        }

        foreach ($orders as &$o) {
            $o['split'] = Digital::split($o, (float) $o['digital_subtotal'], (int) $o['digital_lines'], (int) $o['physical_lines']);
        }
        unset($o);

        $this->view('orders/index', compact('orders', 'pager', 'status', 'q', 'counts', 'kind', 'open'));
    }

    public function show(Request $request, string $id): void
    {
        $order = $this->find($this->id($id));

        $items = db()->fetchAll(
            'SELECT oi.*, s.code AS seller_code, s.name AS seller_name, s.phone AS seller_phone, s.area AS seller_area,
                    s.payout_method, p.slug AS product_slug, p.digital_kind, p.digital_region,
                    (SELECT pa.status FROM payouts pa WHERE pa.order_item_id = oi.id ORDER BY pa.id LIMIT 1) AS payout_status
               FROM order_items oi
               LEFT JOIN sellers s ON s.id = oi.seller_id
               LEFT JOIN products p ON p.id = oi.product_id
              WHERE oi.order_id = ? ORDER BY oi.id',
            [$order['id']]
        );

        $totals = ['buyer' => 0.0, 'seller' => 0.0, 'commission' => 0.0, 'house' => 0.0];
        foreach ($items as $it) {
            $line = (float) $it['unit_price'] * (int) $it['qty'];
            $totals['buyer'] += $line;
            if ($it['seller_id'] !== null) {
                $totals['seller']     += (float) $it['seller_price'] * (int) $it['qty'];
                $totals['commission'] += ($it['unit_price'] - $it['seller_price']) * (int) $it['qty'];
            } else {
                $totals['house'] += $line;
            }
        }

        $customer = $order['user_id']
            ? db()->fetch('SELECT id, name, email, credit_balance FROM users WHERE id = ?', [$order['user_id']])
            : null;
        $refunded = (float) $order['credit_used'] > 0 && Wallet::hasEntry('order_refund', 'order', (int) $order['id']);

        $mix   = self::lineMix((int) $order['id']);
        $split = Digital::split($order, $mix['subtotal'], $mix['digital'], $mix['physical']);
        $digitalLines = array_values(array_filter($items, static fn (array $it): bool => (int) $it['is_digital'] === 1));
        $messages = [];
        foreach ($digitalLines as $it) {
            $messages[(int) $it['id']] = Digital::codeMessage($order, $it);
        }

        $this->view('orders/show', [
            'order'    => $order,
            'customer' => $customer,
            'refunded' => $refunded,
            'cashDue'  => $split['cash'],
            'split'    => $split,
            'digitalLines' => $digitalLines,
            'messages' => $messages,
            'items'   => $items,
            'totals'  => $totals,
            'allowed' => self::allowed($order['status'], $mix['digital'] > 0, $split['kind'] === 'digital'),
        ]);
    }

    public function status(Request $request, string $id): void
    {
        $orderId = $this->id($id);
        $to      = $this->str($request, 'status');
        $note    = $request->input('admin_note');
        $back    = '/admin/orders/' . $orderId;

        $result = db()->transaction(function () use ($orderId, $to, $note): array {
            $order = db()->fetch('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$orderId]);
            if ($order === null) {
                return ['error' => 'Order not found.'];
            }
            $mix = self::lineMix($orderId);
            if (!in_array($to, self::allowed($order['status'], $mix['digital'] > 0, $mix['digital'] > 0 && $mix['physical'] === 0), true)) {
                $why = $mix['digital'] > 0 && $order['status'] === 'new' && $to !== 'cancelled'
                    ? ' Orders with digital items must be confirmed (payment received) first.'
                    : '';
                return ['error' => 'Order ' . $order['code'] . ' is ' . Forms::label($order['status']) . ' and cannot be moved to ' . Forms::label($to) . '.' . $why];
            }

            db()->execute('UPDATE orders SET status = ? WHERE id = ?', [$to, $orderId]);
            if (is_string($note)) {
                db()->execute('UPDATE orders SET admin_note = ? WHERE id = ?', [Forms::text($note) ?: null, $orderId]);
            }

            $items   = db()->fetchAll('SELECT id, product_id, seller_id, qty, seller_price, is_digital FROM order_items WHERE order_id = ?', [$orderId]);
            $message = 'Order ' . $order['code'] . ' is now ' . strtolower(Forms::label($to)) . '.';

            if ($to === 'delivered') {
                $created = 0;
                $amount  = 0.0;
                foreach ($items as $it) {
                    if ($it['seller_id'] === null) {
                        continue;
                    }
                    // Idempotent: one payout per order line, ever.
                    $exists = (int) db()->fetchValue('SELECT COUNT(*) FROM payouts WHERE order_item_id = ?', [$it['id']]);
                    if ($exists > 0) {
                        continue;
                    }
                    $line = round((float) $it['seller_price'] * (int) $it['qty'], 2);
                    db()->execute(
                        "INSERT INTO payouts (seller_id, order_item_id, amount, status) VALUES (?, ?, ?, 'pending')",
                        [$it['seller_id'], $it['id'], $line]
                    );
                    $created++;
                    $amount += $line;
                }
                if ($created > 0) {
                    $message .= ' ' . $created . ' seller payout' . ($created === 1 ? '' : 's') . ' queued (' . money($amount) . ' owed).';
                }
            }

            if ($to === 'cancelled') {
                $restored = 0;
                foreach ($items as $it) {
                    // Digital stock (codes) is managed by hand, so cancelling never puts it back automatically.
                    if ($it['product_id'] !== null && (int) $it['is_digital'] !== 1) {
                        $restored++;
                        db()->execute(
                            "UPDATE products SET stock = stock + ?, status = IF(status = 'sold', 'active', status) WHERE id = ?",
                            [(int) $it['qty'], $it['product_id']]
                        );
                    }
                }
                db()->execute(
                    "DELETE pa FROM payouts pa JOIN order_items oi ON oi.id = pa.order_item_id WHERE oi.order_id = ? AND pa.status = 'pending'",
                    [$orderId]
                );
                if ($restored > 0) {
                    $message .= ' Stock was restored.';
                }
                if ($mix['digital'] > 0) {
                    $message .= ' Digital stock is managed by hand and was not changed.';
                }

                // Give back credit the customer paid with, exactly once (same transaction as the cancel).
                $credit = (float) $order['credit_used'];
                if ($credit > 0 && $order['user_id'] !== null && !Wallet::hasEntry('order_refund', 'order', $orderId)) {
                    $balance = Wallet::credit((int) $order['user_id'], $credit, 'order_refund', 'order', $orderId, 'Refund order ' . $order['code'], (int) auth()->id());
                    $message .= ' ' . money($credit) . ' credit was refunded to the customer\'s wallet (balance ' . money($balance) . ').';
                }
            }

            return ['ok' => $message];
        });

        if (isset($result['error'])) {
            $this->back($back, $result['error']);
        }
        $this->ok($result['ok']);
        $this->redirect($back);
    }

    public function note(Request $request, string $id): void
    {
        $order = $this->find($this->id($id));
        $note  = $this->str($request, 'admin_note');
        if (mb_strlen($note) > 5000) {
            $this->back('/admin/orders/' . $order['id'], 'The note is too long.');
        }
        db()->execute('UPDATE orders SET admin_note = ? WHERE id = ?', [$note !== '' ? $note : null, $order['id']]);
        $this->ok('Note saved.');
        $this->redirect('/admin/orders/' . $order['id']);
    }

    private function find(int $id): array
    {
        $order = db()->fetch('SELECT * FROM orders WHERE id = ?', [$id]);
        if ($order === null) {
            Response::abort(404);
        }
        return $order;
    }
}
