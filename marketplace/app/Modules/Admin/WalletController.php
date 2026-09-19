<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;
use App\Support\Wallet;

/** Wallet audit: how much credit we owe, who holds it, recent movements and a ledger-vs-balance integrity check. */
final class WalletController extends AdminController
{
    public function index(Request $request): void
    {
        $liability = Wallet::totalLiability();
        $holders   = (int) db()->fetchValue("SELECT COUNT(*) FROM users WHERE role = 'customer' AND credit_balance > 0");
        $otherHeld = (float) db()->fetchValue("SELECT COALESCE(SUM(credit_balance), 0) FROM users WHERE role <> 'customer'");

        $top = db()->fetchAll(
            "SELECT id, name, phone, credit_balance FROM users
              WHERE role = 'customer' AND credit_balance > 0 ORDER BY credit_balance DESC, id LIMIT 10"
        );

        $month = "DATE_FORMAT(CURDATE(), '%Y-%m-01')";
        $flow  = db()->fetch(
            "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount END), 0) AS added,
                    COALESCE(SUM(CASE WHEN amount < 0 THEN -amount END), 0) AS spent
               FROM credit_ledger WHERE created_at >= $month"
        ) ?? ['added' => 0, 'spent' => 0];

        $total  = (int) db()->fetchValue('SELECT COUNT(*) FROM credit_ledger');
        $pager  = Pagination::fromRequest($request, $total, 25);
        $recent = db()->fetchAll(
            'SELECT l.id, l.user_id, l.amount, l.type, l.ref_type, l.ref_id, l.note, l.balance_after, l.created_at, u.name AS customer, a.name AS admin_name
               FROM credit_ledger l
               JOIN users u ON u.id = l.user_id
               LEFT JOIN users a ON a.id = l.created_by
              ORDER BY l.id DESC' . $pager->limitSql()
        );

        $problems = Wallet::audit();

        // Anonymity check: what the public side currently exposes.
        $anon = [
            'swaps'   => (int) db()->fetchValue("SELECT COUNT(*) FROM swap_requests WHERE is_public = 1 AND status = 'listed'"),
            'members' => (int) db()->fetchValue(
                "SELECT COUNT(*) FROM products p JOIN sellers s ON s.id = p.seller_id WHERE s.type = 'member' AND p.status = 'active'"
            ),
        ];

        $this->view('wallet/index', compact('liability', 'holders', 'otherHeld', 'top', 'flow', 'recent', 'pager', 'problems', 'anon'));
    }
}
