<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;
use App\Support\Wallet;

final class PayoutController extends AdminController
{
    public function index(Request $request): void
    {
        $balances = db()->fetchAll(
            "SELECT s.id, s.code, s.name, s.type, s.phone, s.area, s.payout_method, s.user_id,
                    u.name AS user_name, u.role AS user_role, u.status AS user_status,
                    SUM(p.amount) AS balance, COUNT(*) AS line_count, MIN(p.created_at) AS oldest
               FROM payouts p
               JOIN sellers s ON s.id = p.seller_id
               LEFT JOIN users u ON u.id = s.user_id
              WHERE p.status = 'pending'
              GROUP BY s.id, s.code, s.name, s.type, s.phone, s.area, s.payout_method, s.user_id, u.name, u.role, u.status
              ORDER BY balance DESC"
        );
        foreach ($balances as &$b) {
            $b['credit_ok'] = self::canPayCredit($b['user_id'], $b['user_role']);
        }
        unset($b);

        $lines = [];
        foreach (db()->fetchAll(
            "SELECT p.seller_id, p.amount, oi.title, oi.qty, o.code AS order_code, o.id AS order_id
               FROM payouts p
               LEFT JOIN order_items oi ON oi.id = p.order_item_id
               LEFT JOIN orders o ON o.id = oi.order_id
              WHERE p.status = 'pending' ORDER BY p.created_at, p.id"
        ) as $row) {
            $lines[$row['seller_id']][] = $row;
        }

        $total = (int) db()->fetchValue(
            "SELECT COUNT(*) FROM (SELECT 1 FROM payouts WHERE status = 'paid' GROUP BY seller_id, paid_at, note, method) t"
        );
        $pager   = Pagination::fromRequest($request, $total, 25);
        $history = db()->fetchAll(
            "SELECT s.id AS seller_id, s.code, s.name, p.paid_at, p.note, p.method, SUM(p.amount) AS amount, COUNT(*) AS line_count
               FROM payouts p JOIN sellers s ON s.id = p.seller_id
              WHERE p.status = 'paid'
              GROUP BY s.id, s.code, s.name, p.paid_at, p.note, p.method
              ORDER BY p.paid_at DESC, s.code" . $pager->limitSql()
        );

        $owed      = array_sum(array_map(static fn ($b) => (float) $b['balance'], $balances));
        $paidTotal = (float) db()->fetchValue("SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE status = 'paid'");
        $paidCredit = (float) db()->fetchValue("SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE status = 'paid' AND method = 'credit'");

        $this->view('payouts/index', compact('balances', 'lines', 'history', 'pager', 'owed', 'paidTotal', 'paidCredit'));
    }

    /** Credit payouts need a linked account that can hold a wallet. */
    private static function canPayCredit(mixed $userId, mixed $role): bool
    {
        return $userId !== null && in_array($role, ['customer', 'seller'], true);
    }

    public function pay(Request $request, string $sellerId): void
    {
        $id     = $this->id($sellerId);
        $note   = $this->str($request, 'note');
        $method = $this->str($request, 'method');
        if (mb_strlen($note) > 255) {
            $this->back('/admin/payouts', 'The note is too long (max 255 characters).');
        }
        if (!in_array($method, ['cash', 'credit'], true)) {
            $this->back('/admin/payouts', 'Choose how the seller is paid: cash or credit.');
        }
        $expected = Forms::decimal($request->input('expected'));
        $adminId  = (int) auth()->id();

        $result = db()->transaction(function () use ($id, $note, $method, $expected, $adminId): array {
            $seller = db()->fetch(
                'SELECT s.id, s.code, s.user_id, u.role AS user_role FROM sellers s LEFT JOIN users u ON u.id = s.user_id WHERE s.id = ?',
                [$id]
            );
            if (!$seller) {
                return ['error' => 'Seller not found.'];
            }
            if ($method === 'credit' && !self::canPayCredit($seller['user_id'], $seller['user_role'])) {
                return ['error' => 'Seller ' . $seller['code'] . ' has no linked customer account, so their payout cannot be added as credit. Pay it in cash.'];
            }

            // Lock the pending rows so a double submit or a second admin cannot pay the same lines twice.
            $rows = db()->fetchAll(
                "SELECT id, amount FROM payouts WHERE seller_id = ? AND status = 'pending' FOR UPDATE",
                [$id]
            );
            $balance = round(array_sum(array_map(static fn ($p) => (float) $p['amount'], $rows)), 2);
            if ($balance <= 0) {
                return ['error' => 'Seller ' . $seller['code'] . ' has nothing pending.'];
            }
            if ($expected !== null && abs($expected - $balance) > 0.001) {
                return ['error' => 'The balance for ' . $seller['code'] . ' changed to ' . money($balance) . ' since you opened this page. Review it and try again.'];
            }

            $paidAt   = (string) db()->fetchValue('SELECT NOW()');
            $credited = 0.0;
            $newBalance = null;
            foreach ($rows as $p) {
                $changed = db()->execute(
                    "UPDATE payouts SET status = 'paid', method = ?, paid_at = ?, note = ? WHERE id = ? AND status = 'pending'",
                    [$method, $paidAt, $note !== '' ? $note : null, $p['id']]
                );
                if ($changed !== 1) {
                    continue;
                }
                $amount = round((float) $p['amount'], 2);
                if ($method === 'credit' && $amount > 0 && !Wallet::hasEntry('payout_credit', 'payout', (int) $p['id'])) {
                    $newBalance = Wallet::credit((int) $seller['user_id'], $amount, 'payout_credit', 'payout', (int) $p['id'], 'Sale payout', $adminId);
                    $credited  += $amount;
                }
            }

            return $method === 'credit'
                ? ['ok' => 'Added ' . money($credited) . ' credit to the wallet linked to ' . $seller['code'] . ' (balance ' . money((float) $newBalance) . '). Payout marked as paid.']
                : ['ok' => 'Marked ' . money($balance) . ' as paid in cash to ' . $seller['code'] . '.'];
        });

        isset($result['error']) ? $this->fail($result['error']) : $this->ok($result['ok']);
        $this->redirect('/admin/payouts');
    }
}
