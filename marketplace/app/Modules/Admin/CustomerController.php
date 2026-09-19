<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Support\Alias;
use App\Support\InsufficientCreditException;
use App\Support\Wallet;

/** Customer accounts: profile, wallet ledger, manual credit adjustments, suspend and password reset. */
final class CustomerController extends AdminController
{
    private const SORTS = [
        'joined'  => 'u.created_at DESC, u.id DESC',
        'oldest'  => 'u.created_at ASC, u.id ASC',
        'balance' => 'u.credit_balance DESC, u.id DESC',
        'name'    => 'u.name ASC, u.id ASC',
    ];

    /** Adjustments above this amount need the "I'm sure" tick-box. */
    public const LARGE_ADJUSTMENT = 200.0;

    public function index(Request $request): void
    {
        $q      = Forms::text($request->query('q'));
        $sort   = Forms::text($request->query('sort'));
        $status = Forms::text($request->query('status'));
        if (!isset(self::SORTS[$sort])) {
            $sort = 'joined';
        }
        if (!in_array($status, ['active', 'disabled'], true)) {
            $status = '';
        }

        $where  = ["u.role = 'customer'"];
        $params = [];
        if ($q !== '') {
            $like    = '%' . addcslashes($q, '%_\\') . '%';
            $digits  = preg_replace('/\D+/', '', $q) ?? '';
            $cond    = 'u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.alias LIKE ?';
            array_push($params, $like, $like, $like, $like);
            if ($digits !== '' && $digits !== $q) {
                $cond .= ' OR u.phone LIKE ?';
                $params[] = '%' . $digits . '%';
            }
            $where[] = '(' . $cond . ')';
        }
        if ($status !== '') {
            $where[]  = 'u.status = ?';
            $params[] = $status;
        }
        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $total = (int) db()->fetchValue('SELECT COUNT(*) FROM users u' . $whereSql, $params);
        $pager = Pagination::fromRequest($request, $total, 25);

        $customers = db()->fetchAll(
            'SELECT u.id, u.name, u.alias, u.email, u.phone, u.area, u.status, u.credit_balance, u.created_at, u.last_login_at,
                    (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS orders_count,
                    (SELECT COUNT(*) FROM buyback_requests b WHERE b.user_id = u.id) AS requests_count
               FROM users u' . $whereSql . ' ORDER BY ' . self::SORTS[$sort] . $pager->limitSql(),
            $params
        );

        // Every account has a public alias; create it on first sight for accounts that do not have one yet.
        foreach ($customers as &$c) {
            if ((string) ($c['alias'] ?? '') === '') {
                $c['alias'] = Alias::ensure((int) $c['id']);
            }
        }
        unset($c);

        $summary = db()->fetch(
            "SELECT COUNT(*) AS n, COALESCE(SUM(credit_balance), 0) AS credit FROM users WHERE role = 'customer'"
        ) ?? ['n' => 0, 'credit' => 0];

        $this->view('customers/index', compact('customers', 'pager', 'q', 'sort', 'status', 'summary'));
    }

    public function show(Request $request, string $id): void
    {
        $customer = $this->find($this->id($id));
        $uid      = (int) $customer['id'];
        if ((string) ($customer['alias'] ?? '') === '') {
            $customer['alias'] = Alias::ensure($uid);
        }

        $ledgerTotal = (int) db()->fetchValue('SELECT COUNT(*) FROM credit_ledger WHERE user_id = ?', [$uid]);
        $pager       = Pagination::fromRequest($request, $ledgerTotal, 15);
        $ledger      = db()->fetchAll(
            'SELECT l.id, l.amount, l.type, l.ref_type, l.ref_id, l.note, l.balance_after, l.created_at, l.created_by, a.name AS admin_name
               FROM credit_ledger l LEFT JOIN users a ON a.id = l.created_by
              WHERE l.user_id = ? ORDER BY l.id DESC' . $pager->limitSql(),
            [$uid]
        );

        $orders = db()->fetchAll(
            'SELECT id, code, status, total, delivery_fee, credit_used, created_at,
                    IF(grand_total > 0, grand_total, total + delivery_fee) AS grand
               FROM orders WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 20',
            [$uid]
        );
        $ordersCount = (int) db()->fetchValue('SELECT COUNT(*) FROM orders WHERE user_id = ?', [$uid]);

        $requests = db()->fetchAll(
            'SELECT id, code, kind, status, offered_total, offer_cash, offer_credit, accepted_method, final_amount, final_method, created_at
               FROM buyback_requests WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 20',
            [$uid]
        );

        $seller = db()->fetch('SELECT id, code, type, status FROM sellers WHERE user_id = ? ORDER BY id LIMIT 1', [$uid]);
        $listings = ['total' => 0, 'active' => 0, 'pending' => 0];
        if ($seller) {
            $row = db()->fetch(
                "SELECT COUNT(*) AS total, COALESCE(SUM(status = 'active'), 0) AS active, COALESCE(SUM(status = 'pending'), 0) AS pending
                   FROM products WHERE seller_id = ?",
                [$seller['id']]
            );
            $listings = array_map('intval', $row ?? $listings);
        }

        // The temporary password is flashed once by password() and never stored in clear text.
        $tempPassword = flash('temp_password');

        $this->view('customers/show', compact('customer', 'ledger', 'pager', 'ledgerTotal', 'orders', 'ordersCount', 'requests', 'seller', 'listings', 'tempPassword'));
    }

    public function adjust(Request $request, string $id): void
    {
        $customer = $this->find($this->id($id));
        $back     = '/admin/customers/' . $customer['id'] . '#wallet';
        $raw      = str_replace(',', '.', $this->str($request, 'amount'));
        $reason   = $this->str($request, 'reason');

        $errors = [];
        if (!preg_match('/^[+-]?\d{1,6}(\.\d{1,2})?$/', $raw)) {
            $errors[] = 'Enter the amount as a number such as 15 or -5.50 (minus removes credit).';
            $amount = 0.0;
        } else {
            $amount = round((float) $raw, 2);
            if ($amount == 0.0) {
                $errors[] = 'The amount cannot be zero.';
            }
        }
        if ($reason === '' || mb_strlen($reason) > 200) {
            $errors[] = 'A reason is required (max 200 characters). It is stored in the customer\'s ledger.';
        }
        if (abs($amount) > self::LARGE_ADJUSTMENT && !$request->input('sure')) {
            $errors[] = 'Adjustments over ' . money(self::LARGE_ADJUSTMENT) . ' need the "I\'m sure" box ticked.';
        }
        if ($errors) {
            $this->back($back, implode("\n", $errors), ['amount' => $raw, 'reason' => $reason, '_form' => '1']);
        }

        try {
            $balance = Wallet::adjust((int) $customer['id'], $amount, $reason, (int) auth()->id());
        } catch (InsufficientCreditException) {
            $this->back(
                $back,
                'Not enough credit: ' . $customer['name'] . ' only has ' . money($customer['credit_balance']) . ', so ' . money(abs($amount)) . ' cannot be removed.',
                ['amount' => $raw, 'reason' => $reason, '_form' => '1']
            );
        } catch (\InvalidArgumentException $e) {
            $this->back($back, $e->getMessage(), ['amount' => $raw, 'reason' => $reason, '_form' => '1']);
        }

        $this->ok(($amount > 0 ? 'Added ' : 'Removed ') . money(abs($amount)) . ($amount > 0 ? ' to ' : ' from ') . $customer['name'] . '\'s wallet. New balance: ' . money($balance) . '.');
        $this->redirect($back);
    }

    public function status(Request $request, string $id): void
    {
        $customer = $this->find($this->id($id));
        $to = $this->str($request, 'to') === 'disabled' ? 'disabled' : 'active';
        db()->execute("UPDATE users SET status = ? WHERE id = ? AND role IN ('customer','seller')", [$to, $customer['id']]);
        $this->ok($customer['name'] . ($to === 'disabled'
            ? ' is suspended and can no longer sign in. Their wallet balance is kept.'
            : ' is active again and can sign in.'));
        $this->redirect($this->returnTo($request, '/admin/customers/' . $customer['id']));
    }

    public function password(Request $request, string $id): void
    {
        $customer = $this->find($this->id($id));
        $back     = '/admin/customers/' . $customer['id'] . '#security';
        $raw      = $request->input('new_password');
        $password = is_string($raw) ? $raw : '';

        if (mb_strlen($password) < 10) {
            $this->back($back, 'The temporary password must be at least 10 characters.');
        }
        if (mb_strlen($password) > 200) {
            $this->back($back, 'The temporary password is too long.');
        }

        db()->execute('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $customer['id']]);
        // Shown once on the next page load, then gone.
        $this->app->session()->flash('temp_password', $password);
        $this->ok('Password reset for ' . $customer['name'] . '. Give them the temporary password below (shown once) and ask them to change it after signing in.');
        $this->redirect($back);
    }

    private function find(int $id): array
    {
        $row = db()->fetch(
            "SELECT id, name, alias, email, phone, area, address, role, status, credit_balance, last_login_at, created_at
               FROM users WHERE id = ? AND role IN ('customer','seller')",
            [$id]
        );
        if ($row === null) {
            Response::abort(404);
        }
        return $row;
    }
}
