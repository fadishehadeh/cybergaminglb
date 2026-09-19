<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Support\Pricing;

final class SellerController extends AdminController
{
    private const STATUSES = ['pending', 'active', 'suspended'];

    public function index(Request $request): void
    {
        $q      = Forms::text($request->query('q'));
        $status = Forms::text($request->query('status'));
        if (!in_array($status, self::STATUSES, true)) {
            $status = '';
        }
        $type = Forms::text($request->query('type'));
        if (!in_array($type, ['store', 'member'], true)) {
            $type = '';
        }
        $conds  = [];
        $params = [];
        if ($type !== '') {
            $conds[]  = 's.type = ?';
            $params[] = $type;
        }
        if ($q !== '') {
            $like    = '%' . addcslashes($q, '%_\\') . '%';
            $conds[] = '(s.code LIKE ? OR s.name LIKE ? OR s.phone LIKE ? OR s.area LIKE ? OR u.name LIKE ? OR u.email LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like);
        }
        if ($status !== '') {
            $conds[]  = 's.status = ?';
            $params[] = $status;
        }
        $where = $conds ? ' WHERE ' . implode(' AND ', $conds) : '';

        $counts = ['pending' => 0, 'active' => 0, 'suspended' => 0];
        foreach (db()->fetchAll('SELECT status, COUNT(*) AS n FROM sellers GROUP BY status') as $row) {
            $counts[$row['status']] = (int) $row['n'];
        }

        $typeCounts = ['store' => 0, 'member' => 0];
        foreach (db()->fetchAll('SELECT type, COUNT(*) AS n FROM sellers GROUP BY type') as $row) {
            $typeCounts[$row['type']] = (int) $row['n'];
        }

        $total = (int) db()->fetchValue('SELECT COUNT(*) FROM sellers s LEFT JOIN users u ON u.id = s.user_id' . $where, $params);
        $pager = Pagination::fromRequest($request, $total, 25);

        $sellers = db()->fetchAll(
            "SELECT s.*, u.name AS user_name, u.email AS user_email, u.role AS user_role,
                    (SELECT COUNT(*) FROM products p WHERE p.seller_id = s.id AND p.status = 'active') AS active_listings,
                    (SELECT COUNT(*) FROM products p WHERE p.seller_id = s.id AND p.status = 'pending') AS pending_listings,
                    (SELECT COALESCE(SUM(oi.seller_price * oi.qty), 0)
                       FROM order_items oi JOIN orders o ON o.id = oi.order_id
                      WHERE oi.seller_id = s.id AND o.status = 'delivered') AS sales_total,
                    (SELECT COALESCE(SUM(pa.amount), 0) FROM payouts pa WHERE pa.seller_id = s.id AND pa.status = 'pending') AS balance
               FROM sellers s LEFT JOIN users u ON u.id = s.user_id" . $where . ' ORDER BY s.created_at DESC, s.id DESC' . $pager->limitSql(),
            $params
        );

        $this->view('sellers/index', [
            'sellers' => $sellers,
            'pager'   => $pager,
            'q'       => $q,
            'status'  => $status,
            'counts'  => $counts,
            'default' => (float) setting('commission_pct', 15),
            'memberDefault' => (float) setting('member_commission_pct', 10),
            'type'    => $type,
            'typeCounts' => $typeCounts,
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('sellers/form', ['seller' => null, 'login' => null]);
    }

    public function show(Request $request, string $id): void
    {
        $seller = $this->find($this->id($id));

        $products = db()->fetchAll(
            'SELECT p.id, p.title, p.image, p.seller_price, p.price, p.stock, p.status, pl.name AS platform
               FROM products p LEFT JOIN platforms pl ON pl.id = p.platform_id
              WHERE p.seller_id = ? ORDER BY p.created_at DESC, p.id DESC',
            [$seller['id']]
        );
        $lines = db()->fetchAll(
            'SELECT oi.id, oi.title, oi.qty, oi.unit_price, oi.seller_price, o.id AS order_id, o.code, o.status, o.created_at,
                    (SELECT pa.status FROM payouts pa WHERE pa.order_item_id = oi.id ORDER BY pa.id LIMIT 1) AS payout_status
               FROM order_items oi JOIN orders o ON o.id = oi.order_id
              WHERE oi.seller_id = ? ORDER BY o.created_at DESC, oi.id DESC',
            [$seller['id']]
        );
        $payouts = db()->fetchAll(
            'SELECT pa.*, o.code AS order_code, o.id AS order_id, oi.title
               FROM payouts pa
               LEFT JOIN order_items oi ON oi.id = pa.order_item_id
               LEFT JOIN orders o ON o.id = oi.order_id
              WHERE pa.seller_id = ? ORDER BY pa.created_at DESC, pa.id DESC',
            [$seller['id']]
        );
        $stats = [
            'pending' => array_sum(array_map(static fn ($p) => $p['status'] === 'pending' ? (float) $p['amount'] : 0.0, $payouts)),
            'paid'    => array_sum(array_map(static fn ($p) => $p['status'] === 'paid' ? (float) $p['amount'] : 0.0, $payouts)),
            'sold'    => array_sum(array_map(static fn ($l) => $l['status'] === 'delivered' ? (float) $l['seller_price'] * (int) $l['qty'] : 0.0, $lines)),
        ];
        $login = $seller['user_id'] ? db()->fetch('SELECT id, name, email, status, last_login_at FROM users WHERE id = ?', [$seller['user_id']]) : null;

        $this->view('sellers/show', compact('seller', 'products', 'lines', 'payouts', 'stats', 'login'));
    }

    public function edit(Request $request, string $id): void
    {
        $seller = $this->find($this->id($id));
        $login  = $seller['user_id'] ? db()->fetch('SELECT id, name, email, status FROM users WHERE id = ?', [$seller['user_id']]) : null;
        $this->view('sellers/form', ['seller' => $seller, 'login' => $login]);
    }

    public function store(Request $request): void
    {
        $this->save($request, null);
    }

    public function update(Request $request, string $id): void
    {
        $this->save($request, $this->find($this->id($id)));
    }

    /** Approve an application (or re-activate a suspended seller): seller AND login become active. */
    public function approve(Request $request, string $id): void
    {
        $seller = $this->find($this->id($id));
        db()->transaction(function () use ($seller): void {
            db()->execute("UPDATE sellers SET status = 'active' WHERE id = ?", [$seller['id']]);
            if ($seller['user_id']) {
                db()->execute("UPDATE users SET status = 'active' WHERE id = ?", [$seller['user_id']]);
            }
        });
        $this->ok($seller['name'] . ' (' . $seller['code'] . ') is approved and can now sign in to the seller portal. Let them know on WhatsApp or email.');
        $this->redirect($this->returnTo($request, '/admin/sellers'));
    }

    /** Reject an application / suspend a seller: they can no longer sign in. Their listings are left as they are. */
    public function suspend(Request $request, string $id): void
    {
        $seller = $this->find($this->id($id));
        db()->transaction(function () use ($seller): void {
            db()->execute("UPDATE sellers SET status = 'suspended' WHERE id = ?", [$seller['id']]);
            if ($seller['user_id']) {
                db()->execute("UPDATE users SET status = 'disabled' WHERE id = ?", [$seller['user_id']]);
            }
        });
        $this->ok($seller['name'] . ' (' . $seller['code'] . ') was ' . ($seller['status'] === 'pending' ? 'rejected' : 'suspended') . '. They can no longer sign in.');
        $this->redirect($this->returnTo($request, '/admin/sellers'));
    }

    // ------------------------------------------------------------------------------------------------

    private function find(int $id): array
    {
        $seller = db()->fetch('SELECT * FROM sellers WHERE id = ?', [$id]);
        if ($seller === null) {
            Response::abort(404);
        }
        return $seller;
    }

    private function save(Request $request, ?array $seller): void
    {
        $isEdit = $seller !== null;
        $back   = $isEdit ? '/admin/sellers/' . $seller['id'] . '/edit' : '/admin/sellers/create';
        $errors = [];

        $name = $this->str($request, 'name');
        if ($name === '' || mb_strlen($name) > 150) {
            $errors[] = 'Seller name is required (max 150 characters).';
        }
        $phone = $this->str($request, 'phone');
        if (mb_strlen($phone) > 40 || ($phone !== '' && !preg_match('/^[0-9+\-\s()]{6,40}$/', $phone))) {
            $errors[] = 'Phone may contain only digits, spaces, + - ( ).';
        }
        $area = $this->str($request, 'area');
        if (mb_strlen($area) > 120) {
            $errors[] = 'Area is too long (max 120 characters).';
        }
        $payout = $this->str($request, 'payout_method');
        if (mb_strlen($payout) > 120) {
            $errors[] = 'Payout method is too long (max 120 characters).';
        }
        $notes = $this->str($request, 'notes');
        if (mb_strlen($notes) > 5000) {
            $errors[] = 'Notes are too long.';
        }

        $commission   = null;
        $commissionIn = $this->str($request, 'commission_pct');
        if ($commissionIn !== '') {
            $commission = Forms::decimal($commissionIn);
            if ($commission === null || $commission > 100) {
                $errors[] = 'Commission override must be between 0 and 100 (leave blank to use the default).';
                $commission = null;
            }
        }

        $status = $this->str($request, 'status');
        if (!in_array($status, self::STATUSES, true)) {
            $errors[] = 'Choose a status.';
        }

        // Optional login
        $existingUser = $isEdit && $seller['user_id']
            ? db()->fetch('SELECT * FROM users WHERE id = ?', [$seller['user_id']])
            : null;
        $email    = strtolower($this->str($request, 'login_email'));
        $rawPass  = $request->input('login_password', '');
        $password = is_string($rawPass) ? $rawPass : '';
        $loginName = $this->str($request, 'login_name') ?: $name;
        $wantLogin = $existingUser !== null || $email !== '' || $password !== '';

        if ($wantLogin) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
                $errors[] = 'Enter a valid login email address.';
            } else {
                $taken = db()->fetchValue('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?', [$email, $existingUser['id'] ?? 0]);
                if ($taken > 0) {
                    $errors[] = 'That login email is already used by another account.';
                }
            }
            if ($existingUser === null && $password === '') {
                $errors[] = 'Set a password (at least 8 characters) for the seller login.';
            }
            if ($password !== '' && mb_strlen($password) < 8) {
                $errors[] = 'The login password must be at least 8 characters.';
            }
            if (mb_strlen($loginName) > 120) {
                $errors[] = 'Login name is too long.';
            }
        }

        if ($errors) {
            $this->invalid($back, $errors, $request);
        }

        $repriced = 0;
        $sellerId = db()->transaction(function () use ($seller, $isEdit, $name, $phone, $area, $payout, $notes, $commission, $status, $wantLogin, $existingUser, $email, $password, $loginName, &$repriced): int {
            $userId = $existingUser['id'] ?? null;
            $userStatus = $status === 'active' ? 'active' : 'disabled';

            if ($wantLogin) {
                if ($existingUser === null) {
                    $userId = db()->insert(
                        "INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, 'seller', ?)",
                        [$loginName, $email, password_hash($password, PASSWORD_DEFAULT), $userStatus]
                    );
                } else {
                    db()->execute('UPDATE users SET name = ?, email = ?, status = ? WHERE id = ?', [$loginName, $email, $userStatus, $userId]);
                    if ($password !== '') {
                        db()->execute('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $userId]);
                    }
                }
            }

            if ($isEdit) {
                db()->execute(
                    'UPDATE sellers SET user_id = ?, name = ?, phone = ?, area = ?, commission_pct = ?, payout_method = ?, notes = ?, status = ? WHERE id = ?',
                    [$userId, $name, $phone ?: null, $area ?: null, $commission, $payout ?: null, $notes ?: null, $status, $seller['id']]
                );
                $sellerId = (int) $seller['id'];

                $oldOverride = $seller['commission_pct'] === null ? null : (float) $seller['commission_pct'];
                if ($oldOverride !== $commission) {
                    $pct = Pricing::commissionPct(['commission_pct' => $commission, 'type' => $seller['type'] ?? 'store']);
                    foreach (db()->fetchAll('SELECT id, seller_price FROM products WHERE seller_id = ?', [$sellerId]) as $p) {
                        $repriced += db()->execute(
                            'UPDATE products SET commission_pct = ?, price = ? WHERE id = ?',
                            [$pct, Pricing::buyerPrice((float) $p['seller_price'], $pct), $p['id']]
                        );
                    }
                }
            } else {
                $sellerId = db()->insert(
                    'INSERT INTO sellers (user_id, code, name, phone, area, commission_pct, payout_method, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$userId, $this->newCode(), $name, $phone ?: null, $area ?: null, $commission, $payout ?: null, $notes ?: null, $status]
                );
            }
            return $sellerId;
        });

        $msg = $isEdit ? 'Seller saved.' : 'Seller created.';
        if ($repriced > 0) {
            $msg .= ' ' . $repriced . ' of their listing prices were recalculated for the new commission.';
        }
        $this->ok($msg);
        $this->redirect('/admin/sellers/' . $sellerId);
    }

    /** Anonymous public code: S-1234, unique. */
    private function newCode(): string
    {
        for ($i = 0; $i < 50; $i++) {
            $code = 'S-' . random_int(1000, 9999);
            if ((int) db()->fetchValue('SELECT COUNT(*) FROM sellers WHERE code = ?', [$code]) === 0) {
                return $code;
            }
        }
        return 'S-' . random_int(100000, 999999);
    }
}
