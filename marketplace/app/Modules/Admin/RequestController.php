<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Support\Delivery;
use App\Support\Wallet;

/**
 * Buy-back / trade-in requests run as an offer workflow:
 *   new -> (contacted) -> offered -> [customer accepts online] -> accepted -> collected -> completed
 * with declined / cancelled as exits. Swaps keep their simple status editor.
 *
 * Local vs remote: a LOCAL seller who lets our courier pick up has the games checked on the spot (accept -> collected,
 * or decline -> cancelled with no fee and no return trip). Everything else is inspected at our hub:
 *   collected -> PASS   -> completed (final amount minus the pickup fee, as cash or credit)
 *   collected -> REJECT -> rejected -> the customer chooses: revised offer (stays 'rejected', then completed),
 *                          return ('return_pending' -> 'returned') or recycle ('recycled'); no answer by hold_until -> recycled.
 *
 * MONEY RULE: final_amount is always the GROSS agreed amount (before the pickup fee). What the customer actually gets
 * ("net") is max(0, final_amount - pickup_fee), where pickup_fee is stored on the request. Net is what is paid in cash
 * or added to the wallet; it is never stored separately, it is always derived with self::net().
 */
final class RequestController extends AdminController
{
    public const BUYBACK_STATUSES = ['new', 'contacted', 'offered', 'accepted', 'collected', 'completed', 'rejected', 'return_pending', 'returned', 'recycled', 'declined', 'cancelled'];
    public const SWAP_STATUSES    = ['new', 'listed', 'matched', 'completed', 'cancelled'];

    /** from status => statuses the admin may move it to. 'offered' -> 'offered' is a revised offer. */
    public const TRANSITIONS = [
        'new'            => ['contacted', 'offered', 'declined', 'cancelled'],
        'contacted'      => ['offered', 'declined', 'cancelled'],
        'offered'        => ['offered', 'declined', 'cancelled'],
        'accepted'       => ['collected', 'cancelled'],
        'collected'      => ['completed', 'rejected', 'cancelled'],
        'rejected'       => ['completed', 'return_pending', 'recycled'],
        'return_pending' => ['returned'],
    ];

    /** SQL condition (alias $a) for "the admin has something to do". Overdue = past hold_until with no customer decision. */
    public static function actionSql(string $a = 'r'): string
    {
        return "($a.status IN ('new','contacted','accepted','collected','return_pending')"
            . " OR ($a.status = 'rejected' AND ($a.reject_choice = 'new_offer' OR ($a.reject_choice IS NULL AND $a.hold_until < CURDATE()))))";
    }

    /** Rejected, no decision yet, deadline not passed / passed. */
    public const UNDECIDED_SQL = "r.status = 'rejected' AND r.reject_choice IS NULL AND (r.hold_until IS NULL OR r.hold_until >= CURDATE())";
    public const OVERDUE_SQL   = "r.status = 'rejected' AND r.reject_choice IS NULL AND r.hold_until < CURDATE()";

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
        $extra = [
            'action'    => (int) db()->fetchValue('SELECT COUNT(*) FROM buyback_requests r WHERE ' . self::actionSql('r')),
            'undecided' => (int) db()->fetchValue('SELECT COUNT(*) FROM buyback_requests r WHERE ' . self::UNDECIDED_SQL),
            'overdue'   => (int) db()->fetchValue('SELECT COUNT(*) FROM buyback_requests r WHERE ' . self::OVERDUE_SQL),
        ];
        $counts = [
            'buyback' => $extra['action'],
            'swaps'   => (int) db()->fetchValue("SELECT COUNT(*) FROM swap_requests WHERE status = 'new'"),
        ];

        $statuses = $tab === 'swaps' ? self::SWAP_STATUSES : self::BUYBACK_STATUSES;
        $table    = $tab === 'swaps' ? 'swap_requests' : 'buyback_requests';
        $where    = [];
        $params   = [];
        if ($tab === 'buyback' && $status === 'action') {
            $where[] = self::actionSql('r');
        } elseif ($tab === 'buyback' && $status === 'undecided') {
            $where[] = self::UNDECIDED_SQL;
        } elseif ($tab === 'buyback' && $status === 'overdue') {
            $where[] = self::OVERDUE_SQL;
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
                . " ORDER BY " . self::actionSql('r') . " DESC,
                            FIELD(r.status, 'collected','return_pending','rejected','accepted','new','contacted','offered','completed','returned','recycled','declined','cancelled'),
                            CASE WHEN " . self::actionSql('r') . " THEN r.created_at END ASC,
                            r.created_at DESC, r.id DESC" . $pager->limitSql(),
                $params
            );
            foreach ($rows as &$r) {
                $r['items_list'] = self::items((string) $r['items']);
            }
            unset($r);
        }

        $this->view('requests/index', compact('tab', 'status', 'q', 'statuses', 'rows', 'pager', 'counts', 'buybackCounts', 'extra'));
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
        $msg = $r['zone_mode'] === 'remote' || $r['collection'] !== 'pickup'
            ? 'Request ' . $r['code'] . ' marked as received at the hub. Inspect the games: pass them to pay the customer, or reject them.'
            : 'Request ' . $r['code'] . ' marked as collected. Inspect the games, then complete it to pay the customer.';
        $this->move($r, ['accepted'], 'collected', ', collected_at = NOW()', [], $msg);
    }

    public function cancel(Request $request, string $id): void
    {
        $r = $this->find($this->id($id));
        $this->move($r, ['new', 'contacted', 'offered', 'accepted', 'collected'], 'cancelled', '', [], 'Request ' . $r['code'] . ' cancelled. Nothing was paid or credited.');
    }

    /** Local courier pickup: the courier checked the games on the spot and took them. Goes straight to 'collected', inspection passed. */
    public function spotAccept(Request $request, string $id): void
    {
        $r    = $this->find($this->id($id));
        $back = $this->detail($r);
        $changed = db()->execute(
            "UPDATE buyback_requests SET status = 'collected', collected_at = NOW(), inspection_result = 'passed'
              WHERE id = ? AND status = 'accepted' AND zone_mode = 'local' AND collection = 'pickup'",
            [$r['id']]
        );
        if ($changed === 0) {
            $this->back($back, $this->cannot($r, 'be accepted on the spot') . ' (this only applies to accepted requests in a local zone with courier pickup).');
        }
        $this->ok('Request ' . $r['code'] . ': the courier checked and accepted the games on the spot. Complete it below to pay the customer.');
        $this->redirect($back);
    }

    /** Local courier pickup: the courier declined the games on the spot. No fee, no return trip. */
    public function spotDecline(Request $request, string $id): void
    {
        $r      = $this->find($this->id($id));
        $back   = $this->detail($r);
        $reason = $this->str($request, 'reject_reason');
        if ($reason === '' || mb_strlen($reason) > 255) {
            $this->back($back, 'Say why the courier declined the games (required, max 255 characters).', ['reject_reason' => $reason, '_form' => '1']);
        }
        $changed = db()->execute(
            "UPDATE buyback_requests SET status = 'cancelled', inspection_result = 'rejected', reject_reason = ?
              WHERE id = ? AND status = 'accepted' AND zone_mode = 'local' AND collection = 'pickup'",
            [$reason, $r['id']]
        );
        if ($changed === 0) {
            $this->back($back, $this->cannot($r, 'be declined on the spot') . ' (this only applies to accepted requests in a local zone with courier pickup).');
        }
        $this->ok('Request ' . $r['code'] . ' cancelled: the courier declined the games on the spot. No fee is charged and nothing is returned.');
        $this->redirect($back);
    }

    /** Hub inspection failed: keep the items, record why and an optional lower offer, and start the customer's decision period. */
    public function inspectReject(Request $request, string $id): void
    {
        $r      = $this->find($this->id($id));
        $back   = $this->detail($r);
        $reason = $this->str($request, 'reject_reason');
        $revIn  = $this->str($request, 'revised_amount');
        $old    = ['reject_reason' => $reason, 'revised_amount' => $revIn, '_form' => '1'];

        if ($reason === '' || mb_strlen($reason) > 255) {
            $this->back($back, 'Say why the games are not acceptable (required, max 255 characters).', $old);
        }
        $revised = null;
        if ($revIn !== '') {
            $revised = Forms::decimal($revIn);
            $offer   = self::offerFor($r, self::method($r));
            if ($revised === null || $revised <= 0) {
                $this->back($back, 'The revised offer must be an amount above $0, or leave it empty to offer no alternative price.', $old);
            }
            if ($revised >= $offer) {
                $this->back($back, 'The revised offer (' . money($revised) . ') must be lower than the agreed offer (' . money($offer) . ').', $old);
            }
        }

        $changed = db()->execute(
            "UPDATE buyback_requests
                SET status = 'rejected', inspection_result = 'rejected', reject_reason = ?, revised_amount = ?,
                    reject_choice = NULL, reject_choice_at = NULL, hold_until = DATE_ADD(CURDATE(), INTERVAL ? DAY)
              WHERE id = ? AND status = 'collected' AND inspection_result <> 'passed'",
            [$reason, $revised, self::holdDays(), $r['id']]
        );
        if ($changed === 0) {
            $this->back($back, $this->cannot($r, 'be rejected') . ' (only collected games that have not passed inspection can be rejected).');
        }
        $this->ok('Request ' . $r['code'] . ' rejected on inspection. The customer now decides: ' . ($revised !== null ? 'revised offer of ' . money($revised) . ', ' : '') . 'return or recycle. No answer in ' . self::holdDays() . ' days and it is recycled.');
        $this->redirect($back);
    }

    /** Record the customer's decision by hand (they answered on WhatsApp, or the request came from a guest). */
    public function decision(Request $request, string $id): void
    {
        $r      = $this->find($this->id($id));
        $back   = $this->detail($r);
        $choice = $this->str($request, 'choice');
        if (!in_array($choice, ['new_offer', 'return', 'recycle'], true)) {
            $this->back($back, 'Choose what the customer decided.');
        }
        $guard = "id = ? AND status = 'rejected' AND reject_choice IS NULL";
        if ($choice === 'new_offer') {
            $changed = db()->execute("UPDATE buyback_requests SET reject_choice = 'new_offer', reject_choice_at = NOW() WHERE $guard AND revised_amount > 0", [$r['id']]);
        } elseif ($choice === 'return') {
            $changed = db()->execute("UPDATE buyback_requests SET status = 'return_pending', reject_choice = 'return', reject_choice_at = NOW(), return_fee = ? WHERE $guard", [self::returnFeeFor($r), $r['id']]);
        } else {
            $changed = db()->execute("UPDATE buyback_requests SET status = 'recycled', reject_choice = 'recycle', reject_choice_at = NOW() WHERE $guard", [$r['id']]);
        }
        if ($changed === 0) {
            $this->back($back, $this->cannot($r, 'take that decision') . ' (a decision can only be recorded once' . ($choice === 'new_offer' ? ', and a revised offer needs a revised amount' : '') . ').');
        }
        $this->ok([
            'new_offer' => 'Recorded: the customer accepts the revised offer of ' . money($r['revised_amount']) . '. Complete the request below to pay them.',
            'return'    => 'Recorded: the customer wants the games back. Send them and collect ' . money(self::returnFeeFor($r)) . ' cash on delivery.',
            'recycle'   => 'Recorded: the customer lets us recycle the games. Nothing is paid.',
        ][$choice]);
        $this->redirect($back);
    }

    /** The rejected games were sent back. Recorded in the admin note (no extra column). */
    public function markReturned(Request $request, string $id): void
    {
        $r    = $this->find($this->id($id));
        $back = $this->detail($r);
        $fee  = $r['return_fee'] !== null ? (float) $r['return_fee'] : self::returnFeeFor($r);
        $line = 'Returned on ' . date('j M Y') . '; collect ' . money($fee) . ' cash on delivery';
        $changed = db()->execute(
            "UPDATE buyback_requests SET status = 'returned', admin_note = IF(admin_note IS NULL OR admin_note = '', ?, CONCAT(admin_note, CHAR(10), ?))
              WHERE id = ? AND status = 'return_pending'",
            [$line . '.', $line . '.', $r['id']]
        );
        if ($changed === 0) {
            $this->back($back, $this->cannot($r, 'be marked as returned') . '.');
        }
        $this->ok('Request ' . $r['code'] . ' marked as returned. ' . $line . '.');
        $this->redirect($back);
    }

    /** Undecided rejected item whose deadline has passed: we keep it, no payment. */
    public function recycle(Request $request, string $id): void
    {
        $r    = $this->find($this->id($id));
        $back = $this->detail($r);
        $recycled = 'Recycled on ' . date('j M Y') . ': no answer from the customer by the deadline.';
        $changed = db()->execute(
            "UPDATE buyback_requests SET status = 'recycled', admin_note = IF(admin_note IS NULL OR admin_note = '', ?, CONCAT(admin_note, CHAR(10), ?))
              WHERE id = ? AND status = 'rejected' AND reject_choice IS NULL AND hold_until < CURDATE()",
            [$recycled, $recycled, $r['id']]
        );
        if ($changed === 0) {
            $this->back($back, $this->cannot($r, 'be recycled') . ' (only undecided rejected items past their deadline can be recycled).');
        }
        $this->ok('Request ' . $r['code'] . ' recycled: the customer did not answer in time. Nothing is paid.');
        $this->redirect($back);
    }

    /** Inspection PASSED: pay the final amount (gross) minus the pickup fee, as cash (just recorded) or credit (wallet), once. */
    public function complete(Request $request, string $id): void
    {
        $r    = $this->find($this->id($id));
        $back = $this->detail($r);

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

        $result = $this->payOut((int) $r['id'], false, $amount, $method, $note);
        isset($result['error']) ? $this->fail($result['error']) : $this->ok($result['ok']);
        $this->redirect($back);
    }

    /** The customer accepted the revised (lower) offer: pay revised_amount (gross) minus the pickup fee, with the method they accepted. */
    public function completeRevised(Request $request, string $id): void
    {
        $r    = $this->find($this->id($id));
        $back = $this->detail($r);
        $result = $this->payOut((int) $r['id'], true, null, null, '');
        isset($result['error']) ? $this->fail($result['error']) : $this->ok($result['ok']);
        $this->redirect($back);
    }

    /**
     * The one place money is paid out for a sell request. One transaction, guarded three ways so a double submit pays once:
     * row lock + status guard (collected / rejected+new_offer), guarded UPDATE, and Wallet::hasEntry before the credit.
     * @return array{ok?:string, error?:string}
     */
    private function payOut(int $requestId, bool $revised, ?float $amountIn, ?string $methodIn, string $note): array
    {
        $adminId = (int) auth()->id();

        return db()->transaction(function () use ($requestId, $revised, $amountIn, $methodIn, $note, $adminId): array {
            $row = db()->fetch('SELECT * FROM buyback_requests WHERE id = ? FOR UPDATE', [$requestId]);
            if ($row === null) {
                return ['error' => 'Request not found.'];
            }
            $guest = $row['user_id'] === null;

            if ($revised) {
                if ($row['status'] !== 'rejected' || $row['reject_choice'] !== 'new_offer' || (float) $row['revised_amount'] <= 0) {
                    return ['error' => $row['status'] === 'completed'
                        ? 'Request ' . $row['code'] . ' was already completed, so nothing was paid or credited a second time.'
                        : 'Request ' . $row['code'] . ' is ' . strtolower(Forms::label($row['status'])) . ' and cannot be completed with a revised offer' . ($row['status'] === 'rejected' ? ': the customer has not accepted one yet.' : '.') . ' Nothing was changed.'];
                }
                $gross  = (float) $row['revised_amount'];
                $method = $guest ? 'cash' : self::method($row);
            } else {
                // Idempotency guard 1: only a collected request can be completed (a second submit finds it completed).
                if ($row['status'] !== 'collected') {
                    return ['error' => $row['status'] === 'completed'
                        ? 'Request ' . $row['code'] . ' was already completed, so nothing was paid or credited a second time.'
                        : 'Request ' . $row['code'] . ' is ' . strtolower(Forms::label($row['status'])) . ' and cannot be completed' . (in_array($row['status'], ['new', 'contacted', 'offered', 'accepted'], true) ? ' yet: collect the games first.' : '.') . ' Nothing was changed.'];
                }
                $gross  = (float) $amountIn;
                $method = (string) $methodIn;
                $offer  = self::offerFor($row, $method);
                if ($gross > $offer + 0.001) {
                    return ['error' => 'The final amount (' . money($gross) . ') is above the agreed ' . $method . ' offer (' . money($offer) . '). Inspection can only lower it, so pay up to the offer.'];
                }
            }

            $fee = (float) $row['pickup_fee'];
            $net = self::net($gross, $fee);

            $creditNow = false;
            if ($method === 'credit') {
                if ($guest) {
                    return ['error' => 'This request has no customer account (it was sent as a guest), so it cannot be paid as credit. Pay it in cash instead.'];
                }
                if ((int) db()->fetchValue('SELECT COUNT(*) FROM users WHERE id = ?', [$row['user_id']]) < 1) {
                    return ['error' => 'The customer account no longer exists. Pay in cash instead.'];
                }
                // Idempotency guard 3: never a second wallet entry for the same request.
                $creditNow = $net > 0 && !Wallet::hasEntry('offer_credit', 'buyback', (int) $row['id']);
            }

            $adminNote = $row['admin_note'];
            if ($note !== '') {
                $adminNote = trim((string) $adminNote . "\n" . 'Inspection ' . date('j M Y') . ': ' . $note);
            }

            // final_amount = GROSS agreed amount; the customer gets $net = max(0, gross - pickup_fee).
            $changed = db()->execute(
                $revised
                    ? "UPDATE buyback_requests SET status = 'completed', completed_at = NOW(), final_amount = ?, final_method = ?, admin_note = ?
                        WHERE id = ? AND status = 'rejected' AND reject_choice = 'new_offer'"
                    : "UPDATE buyback_requests SET status = 'completed', completed_at = NOW(), final_amount = ?, final_method = ?, admin_note = ?, inspection_result = 'passed'
                        WHERE id = ? AND status = 'collected'",
                [$gross, $method, $adminNote !== '' ? $adminNote : null, $row['id']]
            );
            if ($changed !== 1) {
                return ['error' => 'The request changed while you were completing it. Nothing was paid.'];
            }

            $maths = $fee > 0 ? ' (' . money($gross) . ' minus ' . money($fee) . ' pickup fee)' : '';
            $head  = 'Request ' . $row['code'] . ($revised ? ' completed with the revised offer. ' : ' completed. ');
            if ($net <= 0) {
                return ['ok' => $head . 'The pickup fee (' . money($fee) . ') is as much as the amount (' . money($gross) . '), so there is nothing to pay out.'];
            }
            if ($creditNow) {
                $balance = Wallet::credit((int) $row['user_id'], $net, 'offer_credit', 'buyback', (int) $row['id'], 'Sell request ' . $row['code'] . ($fee > 0 ? ' (minus pickup fee ' . money($fee) . ')' : ''), $adminId);
                return ['ok' => $head . money($net) . ' credit' . $maths . ' was added to the customer\'s wallet (new balance ' . money($balance) . ').'];
            }
            if ($method === 'cash') {
                return ['ok' => $head . 'Pay the customer ' . money($net) . ' in cash now' . $maths . '.'];
            }
            return ['ok' => $head . 'Credit for this request had already been added earlier, so no second entry was made.'];
        });
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

    /** What the customer receives: the gross agreed amount minus the pickup fee, never below 0. */
    public static function net(float $gross, float $pickupFee): float
    {
        return max(0.0, round($gross - $pickupFee, 2));
    }

    /** Payout method for a request: what the customer accepted, else what they preferred; guests can only be paid in cash. */
    public static function method(array $r): string
    {
        if ($r['user_id'] === null) {
            return 'cash';
        }
        return $r['accepted_method'] ?: ($r['preferred_method'] ?: 'credit');
    }

    public static function offerFor(array $r, string $method): float
    {
        return (float) ($method === 'credit' ? $r['offer_credit'] : $r['offer_cash']);
    }

    public static function holdDays(): int
    {
        return max(1, (int) setting('reject_hold_days', 14));
    }

    /** Return trip fee: charged when a courier sends it back (remote sellers, or a courier pickup); nothing when a local seller collects at the hub. */
    public static function returnFeeFor(array $r): float
    {
        return ($r['zone_mode'] === 'remote' || $r['collection'] === 'pickup') ? Delivery::returnFee() : 0.0;
    }

    /** Local seller whose games our own courier checks on the spot. */
    public static function isLocalSpot(array $r): bool
    {
        return $r['zone_mode'] === 'local' && $r['collection'] === 'pickup';
    }

    /** How the games reach us, in plain words. */
    public static function collectionLabel(array $r): string
    {
        if ($r['collection'] !== 'pickup') {
            return 'Brings it to our hub';
        }
        return match ($r['zone_mode']) {
            'local'  => 'Our courier picks it up and checks it on the spot',
            'remote' => 'Shipped by a third-party courier to our hub',
            default  => 'We collect from the customer',
        };
    }

    /** Rejected, undecided and past the deadline. */
    public static function isOverdue(array $r): bool
    {
        return $r['status'] === 'rejected' && $r['reject_choice'] === null && $r['hold_until'] !== null && $r['hold_until'] < date('Y-m-d');
    }

    /** [what to do next, is it the admin's turn]. */
    public static function nextStep(array $r): array
    {
        switch ($r['status']) {
            case 'new':
            case 'contacted':
                return ['Make an offer', true];
            case 'offered':
                return ['Waiting for the customer', false];
            case 'accepted':
                return [self::isLocalSpot($r) ? 'Courier checks it on the spot' : ($r['zone_mode'] === 'remote' ? 'Receive the shipment' : 'Collect the games'), true];
            case 'collected':
                return [$r['inspection_result'] === 'passed' ? 'Pay the customer' : 'Inspect and pay', true];
            case 'rejected':
                if ($r['reject_choice'] === 'new_offer') {
                    return ['Complete with the revised offer', true];
                }
                if (self::isOverdue($r)) {
                    return ['Deadline passed: recycle', true];
                }
                return ['Customer to decide' . ($r['hold_until'] ? ' by ' . date('j M', strtotime($r['hold_until'])) : ''), false];
            case 'return_pending':
                return ['Send the games back', true];
        }
        return ['', false];
    }

    /** "Request X is <status> and cannot <action>." */
    private function cannot(array $r, string $action): string
    {
        $current = (string) db()->fetchValue('SELECT status FROM buyback_requests WHERE id = ?', [$r['id']]);
        return 'Request ' . $r['code'] . ' is ' . strtolower(Forms::label($current)) . ' and cannot ' . $action . '. Nothing was changed';
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
