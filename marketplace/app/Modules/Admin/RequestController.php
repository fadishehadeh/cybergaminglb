<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Support\Wallet;

/**
 * Buy-back / trade-in requests run as an offer workflow:
 *   new -> (contacted) -> offered -> [customer accepts online] -> accepted -> collected -> completed
 * with declined / cancelled as exits. Swaps keep their simple status editor.
 */
final class RequestController extends AdminController
{
    public const BUYBACK_STATUSES = ['new', 'contacted', 'offered', 'accepted', 'collected', 'completed', 'declined', 'cancelled'];
    public const SWAP_STATUSES    = ['new', 'listed', 'matched', 'completed', 'cancelled'];

    /** Statuses where the admin has something to do (offered is waiting on the customer). */
    public const ACTION_STATUSES = ['collected', 'accepted', 'new', 'contacted'];

    /** from status => statuses the admin may move it to. 'offered' -> 'offered' is a revised offer. */
    public const TRANSITIONS = [
        'new'       => ['contacted', 'offered', 'declined', 'cancelled'],
        'contacted' => ['offered', 'declined', 'cancelled'],
        'offered'   => ['offered', 'declined', 'cancelled'],
        'accepted'  => ['collected', 'cancelled'],
        'collected' => ['completed', 'cancelled'],
    ];

    public static function canMove(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    // ------------------------------------------------------------------ list

    public function index(Request $request): void
    {
        $tab    = $request->query('tab') === 'swaps' ? 'swaps' : 'buyback';
        $status = Forms::text($request->query('status'));
        $q      = Forms::text($request->query('q'));

        $buybackCounts = array_fill_keys(self::BUYBACK_STATUSES, 0);
        foreach (db()->fetchAll('SELECT status, COUNT(*) AS n FROM buyback_requests GROUP BY status') as $row) {
            $buybackCounts[$row['status']] = (int) $row['n'];
        }
        $counts = [
            'buyback' => $buybackCounts['new'] + $buybackCounts['accepted'] + $buybackCounts['collected'],
            'swaps'   => (int) db()->fetchValue("SELECT COUNT(*) FROM swap_requests WHERE status = 'new'"),
        ];

        $statuses = $tab === 'swaps' ? self::SWAP_STATUSES : self::BUYBACK_STATUSES;
        $table    = $tab === 'swaps' ? 'swap_requests' : 'buyback_requests';
        $where    = [];
        $params   = [];
        if ($tab === 'buyback' && $status === 'action') {
            $where[] = "r.status IN ('new','contacted','accepted','collected')";
        } elseif ($tab === 'buyback' && $status === 'toprice') {
            $where[] = "r.status IN ('new','contacted')";
        } elseif (in_array($status, $statuses, true)) {
            $where[]  = 'r.status = ?';
            $params[] = $status;
        } else {
            $status = '';
        }
        if ($q !== '') {
            $like    = '%' . addcslashes($q, '%_\\') . '%';
            $where[] = '(r.code LIKE ? OR r.name LIKE ? OR r.phone LIKE ?)';
            array_push($params, $like, $like, $like);
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = (int) db()->fetchValue("SELECT COUNT(*) FROM $table r" . $whereSql, $params);
        $pager = Pagination::fromRequest($request, $total, 25);

        if ($tab === 'swaps') {
            $rows = db()->fetchAll(
                'SELECT r.*, pl.name AS platform FROM swap_requests r LEFT JOIN platforms pl ON pl.id = r.platform_id'
                . $whereSql . ' ORDER BY r.created_at DESC, r.id DESC' . $pager->limitSql(),
                $params
            );
        } else {
            // Things that need the admin first (oldest waiting first), then waiting-on-customer, then finished.
            $rows = db()->fetchAll(
                "SELECT r.*, u.name AS account_name
                   FROM buyback_requests r LEFT JOIN users u ON u.id = r.user_id" . $whereSql
                . " ORDER BY FIELD(r.status, 'collected','accepted','new','contacted','offered','completed','declined','cancelled'),
                            CASE WHEN r.status IN ('collected','accepted','new','contacted') THEN r.created_at END ASC,
                            r.created_at DESC, r.id DESC" . $pager->limitSql(),
                $params
            );
            foreach ($rows as &$r) {
                $r['items_list'] = self::items((string) $r['items']);
            }
            unset($r);
        }

        $this->view('requests/index', compact('tab', 'status', 'q', 'statuses', 'rows', 'pager', 'counts', 'buybackCounts'));
    }

    // ---------------------------------------------------------------- detail

    public function showBuyback(Request $request, string $id): void
    {
        $r = $this->find($this->id($id));
        $customer = $r['user_id']
            ? db()->fetch('SELECT id, name, email, phone, area, credit_balance, status FROM users WHERE id = ?', [$r['user_id']])
            : null;

        $items = self::items((string) $r['items']);
        $rawItems = $items ? null : (string) $r['items'];

        $defaults = self::offerDefaults($r);

        $this->view('requests/show', [
            'r'        => $r,
            'customer' => $customer,
            'items'    => $items,
            'photos'   => self::photos($r['photos'] ?? null),
            'rawItems' => $rawItems,
            'defaults' => $defaults,
            'allowed'  => self::TRANSITIONS[$r['status']] ?? [],
        ]);
    }

    // --------------------------------------------------------------- actions

    /** Save only the admin note (the status is changed through the workflow actions). */
    public function updateBuyback(Request $request, string $id): void
    {
        $r    = $this->find($this->id($id));
        $note = $this->str($request, 'admin_note');
        if (mb_strlen($note) > 5000) {
            $this->back($this->detail($r), 'The note is too long.');
        }
        db()->execute('UPDATE buyback_requests SET admin_note = ? WHERE id = ?', [$note !== '' ? $note : null, $r['id']]);
        $this->ok('Note saved.');
        $this->redirect($this->detail($r));
    }

    public function contact(Request $request, string $id): void
    {
        $r = $this->find($this->id($id));
        $this->move($r, ['new'], 'contacted', '', [], 'Request ' . $r['code'] . ' marked as contacted.');
    }

    public function offer(Request $request, string $id): void
    {
        $r    = $this->find($this->id($id));
        $back = $this->detail($r);
        if (!self::canMove($r['status'], 'offered')) {
            $this->back($back, 'Request ' . $r['code'] . ' is ' . strtolower(Forms::label($r['status'])) . '; an offer can only be made or revised before the customer accepts.');
        }

        $cash   = Forms::decimal($request->input('offer_cash'));
        $credit = Forms::decimal($request->input('offer_credit'));
        $errors = [];
        if ($cash === null || $credit === null) {
            $errors[] = 'Cash and credit offers must be amounts of 0 or more, e.g. 25 or 24.50.';
        } else {
            if ($cash > 100000 || $credit > 100000) {
                $errors[] = 'Offers are limited to $100,000.';
            }
            if ($cash <= 0 && $credit <= 0) {
                $errors[] = 'Offer something: at least one of cash or credit must be above $0. To refuse the request use "Decline request".';
            }
        }
        if ($errors) {
            $this->back($back, implode("\n", $errors), ['offer_cash' => $request->input('offer_cash'), 'offer_credit' => $request->input('offer_credit'), '_form' => '1']);
        }

        $changed = db()->execute(
            "UPDATE buyback_requests SET status = 'offered', offer_cash = ?, offer_credit = ?, offered_at = NOW(),
                    accepted_method = NULL, accepted_at = NULL
              WHERE id = ? AND status IN ('new','contacted','offered')",
            [$cash, $credit, $r['id']]
        );
        if ($changed === 0) {
            $this->back($back, 'The request changed while you were editing it. Reload and try again.');
        }

        $msg = ($r['status'] === 'offered' ? 'Offer revised' : 'Offer sent') . ' for ' . $r['code'] . ': ' . money($cash) . ' cash or ' . money($credit) . ' credit. The customer can now accept it online.';
        if ($credit < $cash) {
            $msg .= ' Note: the credit amount is lower than the cash amount, which is unusual (credit is normally worth more).';
        }
        $this->ok($msg);
        $this->redirect($back);
    }

    public function decline(Request $request, string $id): void
    {
        $r = $this->find($this->id($id));
        $this->move($r, ['new', 'contacted', 'offered'], 'declined', '', [], 'Request ' . $r['code'] . ' declined.');
    }

    public function collect(Request $request, string $id): void
    {
        $r = $this->find($this->id($id));
        $this->move($r, ['accepted'], 'collected', ', collected_at = NOW()', [], 'Request ' . $r['code'] . ' marked as collected. Inspect the games, then complete it to pay the customer.');
    }

    public function cancel(Request $request, string $id): void
    {
        $r = $this->find($this->id($id));
        $this->move($r, ['new', 'contacted', 'offered', 'accepted', 'collected'], 'cancelled', '', [], 'Request ' . $r['code'] . ' cancelled. Nothing was paid or credited.');
    }

    /** Inspection done: pay the final amount as cash (just recorded) or credit (wallet), once, in one transaction. */
    public function complete(Request $request, string $id): void
    {
        $r    = $this->find($this->id($id));
        $back = $this->detail($r);
        $adminId = (int) auth()->id();

        $method = $this->str($request, 'final_method');
        $amount = Forms::decimal($request->input('final_amount'));
        $note   = $this->str($request, 'inspection_note');
        $old    = ['final_amount' => $request->input('final_amount'), 'final_method' => $method, 'inspection_note' => $note, '_form' => '1'];

        if (!in_array($method, ['cash', 'credit'], true)) {
            $this->back($back, 'Choose how the customer is paid: cash or credit.', $old);
        }
        if ($amount === null || $amount <= 0 || $amount > 100000) {
            $this->back($back, 'Enter the final amount to pay (above $0, max $100,000).', $old);
        }
        if (mb_strlen($note) > 1000) {
            $this->back($back, 'The inspection note is too long (max 1000 characters).', $old);
        }

        $result = db()->transaction(function () use ($r, $method, $amount, $note, $adminId): array {
            $row = db()->fetch('SELECT * FROM buyback_requests WHERE id = ? FOR UPDATE', [$r['id']]);
            if ($row === null) {
                return ['error' => 'Request not found.'];
            }
            // Idempotency guard 1: only a collected request can be completed (a second submit finds it completed).
            if ($row['status'] !== 'collected') {
                return ['error' => $row['status'] === 'completed'
                    ? 'Request ' . $row['code'] . ' was already completed, so nothing was paid or credited a second time.'
                    : 'Request ' . $row['code'] . ' is ' . strtolower(Forms::label($row['status'])) . ' and cannot be completed' . (in_array($row['status'], ['new', 'contacted', 'offered', 'accepted'], true) ? ' yet: collect the games first.' : '.') . ' Nothing was changed.'];
            }
            $offer = (float) ($method === 'credit' ? $row['offer_credit'] : $row['offer_cash']);
            if ($amount > $offer + 0.001) {
                return ['error' => 'The final amount (' . money($amount) . ') is above the agreed ' . $method . ' offer (' . money($offer) . '). Inspection can only lower it, so pay up to the offer.'];
            }

            $creditNow = false;
            $extra     = '';
            if ($method === 'credit') {
                if ($row['user_id'] === null) {
                    return ['error' => 'This request has no customer account (it was sent as a guest), so it cannot be paid as credit. Pay it in cash instead.'];
                }
                if (db()->fetchValue('SELECT COUNT(*) FROM users WHERE id = ?', [$row['user_id']]) < 1) {
                    return ['error' => 'The customer account no longer exists. Pay in cash instead.'];
                }
                // Idempotency guard 2: never a second wallet entry for the same request.
                if (Wallet::hasEntry('offer_credit', 'buyback', (int) $row['id'])) {
                    $extra = ' Credit for this request had already been added earlier, so no second entry was made.';
                } else {
                    $creditNow = true;
                }
            }

            $adminNote = $row['admin_note'];
            if ($note !== '') {
                $adminNote = trim((string) $adminNote . "\n" . 'Inspection ' . date('j M Y') . ': ' . $note);
            }

            $changed = db()->execute(
                "UPDATE buyback_requests
                    SET status = 'completed', completed_at = NOW(), final_amount = ?, final_method = ?, admin_note = ?
                  WHERE id = ? AND status = 'collected'",
                [$amount, $method, $adminNote !== '' ? $adminNote : null, $row['id']]
            );
            if ($changed !== 1) {
                return ['error' => 'The request changed while you were completing it. Nothing was paid.'];
            }

            if ($creditNow) {
                $balance = Wallet::credit((int) $row['user_id'], $amount, 'offer_credit', 'buyback', (int) $row['id'], 'Sell request ' . $row['code'], $adminId);
                return ['ok' => 'Request ' . $row['code'] . ' completed. ' . money($amount) . ' credit was added to the customer\'s wallet (new balance ' . money($balance) . ').'];
            }
            if ($method === 'cash') {
                return ['ok' => 'Request ' . $row['code'] . ' completed. Pay the customer ' . money($amount) . ' in cash now.'];
            }
            return ['ok' => 'Request ' . $row['code'] . ' completed.' . $extra];
        });

        isset($result['error']) ? $this->fail($result['error']) : $this->ok($result['ok']);
        $this->redirect($back);
    }

    // --------------------------------------------------------------- swaps

    public function updateSwap(Request $request, string $id): void
    {
        $id     = $this->id($id);
        $status = $this->str($request, 'status');
        $back   = $this->returnTo($request, '/admin/requests?tab=swaps');

        $row = db()->fetch('SELECT * FROM swap_requests WHERE id = ?', [$id]);
        if (!$row) {
            Response::abort(404);
        }
        if (!in_array($status, self::SWAP_STATUSES, true)) {
            $this->back($back, 'Choose a valid status.');
        }

        // Fee is per side. Keep the stored fee unless a new one is typed; when the swap completes with no fee, record the standard one.
        $fee   = $row['fee'] !== null ? (float) $row['fee'] : null;
        $feeIn = $this->str($request, 'fee');
        if ($feeIn !== '') {
            $fee = Forms::decimal($feeIn);
            if ($fee === null) {
                $this->back($back, 'Fee must be a valid amount.');
            }
        }
        if ($status === 'completed' && $fee === null) {
            $fee = (float) setting('swap_fee', 3);
        }
        // The public board follows the status: only 'listed' swaps are public.
        db()->execute(
            'UPDATE swap_requests SET status = ?, fee = ?, is_public = ? WHERE id = ?',
            [$status, $fee, $status === 'listed' ? 1 : 0, $id]
        );

        $this->ok('Request ' . $row['code'] . ' updated to ' . strtolower(Forms::label($status)) . '.');
        $this->redirect($back);
    }

    /** Put a swap on the public board: is_public = 1 and status 'listed'. */
    public function publishSwap(Request $request, string $id): void
    {
        $id   = $this->id($id);
        $row  = $this->findSwap($id);
        $back = $this->returnTo($request, '/admin/requests?tab=swaps');
        $changed = db()->execute(
            "UPDATE swap_requests SET is_public = 1, status = 'listed' WHERE id = ? AND status IN ('new', 'listed')",
            [$id]
        );
        $changed
            ? $this->ok('Swap ' . $row['code'] . ' is now on the public swap board.')
            : $this->fail('Swap ' . $row['code'] . ' is ' . strtolower(Forms::label($row['status'])) . ' and cannot be published.');
        $this->redirect($back);
    }

    /** Take a swap off the board: is_public = 0 and status back to 'new'. */
    public function unpublishSwap(Request $request, string $id): void
    {
        $id   = $this->id($id);
        $row  = $this->findSwap($id);
        $back = $this->returnTo($request, '/admin/requests?tab=swaps');
        db()->execute("UPDATE swap_requests SET is_public = 0, status = IF(status = 'listed', 'new', status) WHERE id = ?", [$id]);
        $this->ok('Swap ' . $row['code'] . ' was removed from the public swap board.');
        $this->redirect($back);
    }

    // --------------------------------------------------------------- helpers

    /** Decoded items JSON as a list of rows with normalised keys; [] when unreadable. */
    public static function items(string $json): array
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        return array_values(array_filter($decoded, 'is_array'));
    }

    /**
     * What the customer says comes with an item: ['box' => ?bool, 'cover' => ?bool, 'manual' => ?bool] (null = not stated),
     * or null when the item has no "includes" data at all (older requests).
     */
    public static function includes(array $item): ?array
    {
        $inc = $item['includes'] ?? null;
        if (!is_array($inc)) {
            return null;
        }
        $flag = static function (mixed $v): ?bool {
            if ($v === true || $v === 1 || $v === '1' || $v === 'yes' || $v === 'true') {
                return true;
            }
            if ($v === false || $v === 0 || $v === '0' || $v === 'no' || $v === 'false') {
                return false;
            }
            return null;
        };
        return ['box' => $flag($inc['box'] ?? null), 'cover' => $flag($inc['cover'] ?? null), 'manual' => $flag($inc['manual'] ?? null)];
    }

    /**
     * Photos the customer sent: [{kind, path}] with only safe relative paths under uploads/ (anything else is dropped).
     * @return array<int, array{kind: string, path: string}>
     */
    public static function photos(?string $json): array
    {
        $decoded = $json !== null && $json !== '' ? json_decode($json, true) : null;
        if (!is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $row) {
            $path = is_array($row) ? ($row['path'] ?? null) : (is_string($row) ? $row : null);
            if (!is_string($path) || !preg_match('#^[A-Za-z0-9_][A-Za-z0-9_./-]*\.(jpe?g|png|webp)$#i', $path) || str_contains($path, '..')) {
                continue;
            }
            $kind = is_array($row) && is_string($row['kind'] ?? null) ? $row['kind'] : 'extra';
            $out[] = ['kind' => $kind, 'path' => $path];
        }
        return $out;
    }

    /** Prefill values for the offer form: current offer, else the estimates, else the old single "offered_total". */
    public static function offerDefaults(array $r): array
    {
        if ($r['offer_cash'] !== null || $r['offer_credit'] !== null) {
            return ['cash' => (float) ($r['offer_cash'] ?? 0), 'credit' => (float) ($r['offer_credit'] ?? 0)];
        }
        $cash   = (float) $r['estimate_cash'];
        $credit = (float) $r['estimate_credit'];
        if ($cash <= 0 && $credit <= 0) {
            // Requests created before offers existed only have offered_total (cash for a sale, credit for a trade-in).
            $total  = (float) $r['offered_total'];
            $cash   = $r['kind'] === 'trade_in' ? 0.0 : $total;
            $credit = $total;
        }
        return ['cash' => $cash, 'credit' => $credit];
    }

    private function detail(array $r): string
    {
        return '/admin/requests/buyback/' . $r['id'];
    }

    /** Guarded status change: only from $from statuses; a repeated submit changes nothing. */
    private function move(array $r, array $from, string $to, string $extraSet, array $params, string $message): void
    {
        $back = $this->detail($r);
        $in   = "'" . implode("','", $from) . "'";
        $changed = db()->execute(
            "UPDATE buyback_requests SET status = ?$extraSet WHERE id = ? AND status IN ($in)",
            [$to, ...$params, $r['id']]
        );
        if ($changed === 0) {
            $current = (string) db()->fetchValue('SELECT status FROM buyback_requests WHERE id = ?', [$r['id']]);
            $this->back($back, 'Request ' . $r['code'] . ' is ' . strtolower(Forms::label($current)) . ' and cannot be moved to ' . strtolower(Forms::label($to)) . '. Nothing was changed.');
        }
        $this->ok($message);
        $this->redirect($back);
    }

    private function find(int $id): array
    {
        $row = db()->fetch('SELECT * FROM buyback_requests WHERE id = ?', [$id]);
        if (!$row) {
            Response::abort(404);
        }
        return $row;
    }

    private function findSwap(int $id): array
    {
        $row = db()->fetch('SELECT id, code, status FROM swap_requests WHERE id = ?', [$id]);
        if (!$row) {
            Response::abort(404);
        }
        return $row;
    }
}
