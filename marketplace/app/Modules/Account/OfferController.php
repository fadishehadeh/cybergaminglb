<?php
declare(strict_types=1);

namespace App\Modules\Account;

use App\Core\Request;
use App\Support\Delivery;

/**
 * A customer's sell / trade-in requests and our offers. Every read AND every state change is scoped by
 * user_id, and every transition is a single guarded UPDATE (... AND status = 'expected'), so a double click,
 * a stale tab or a forged request can never move a request twice or from the wrong state.
 */
final class OfferController extends BaseController
{
    public function index(Request $request): void
    {
        $me = $this->me();
        $offers = db()->fetchAll(
            'SELECT code, kind, status, items, offer_cash, offer_credit, estimate_cash, estimate_credit, offered_total, final_amount, final_method, created_at
               FROM buyback_requests WHERE user_id = ? ORDER BY id DESC LIMIT 100',
            [(int) $me['id']]
        );
        foreach ($offers as &$o) {
            $o['item_count'] = count(AccountUi::decodeItems($o['items']));
        }
        unset($o);
        $this->page('account/offers/index', ['me' => $me, 'offers' => $offers]);
    }

    public function show(Request $request, string $code): void
    {
        $me = $this->me();
        $offer = $this->find($code, (int) $me['id']);

        $zone = trim((string) ($me['area'] ?? ''));
        $pickupFee = $zone !== '' ? Delivery::feeForZone($zone) : Delivery::defaultFee();

        $this->page('account/offers/show', [
            'me'        => $me,
            'offer'     => $offer,
            'items'     => AccountUi::decodeItems($offer['items']),
            'wanted'    => AccountUi::decodeItems($offer['wanted_items']),
            'hub'       => (string) setting('hub_address', ''),
            'pickupFee' => $pickupFee,
            'waLink'    => wa_link($this->waMessage($offer)),
        ]);
    }

    public function accept(Request $request, string $code): void
    {
        $me = $this->me();
        $offer = $this->find($code, (int) $me['id']);
        $path = '/account/offers/' . $offer['code'];

        if ($offer['status'] !== 'offered') {
            $this->stale($path, $offer['status']);
        }

        $method     = $this->rawString($request, 'method');
        $collection = $this->rawString($request, 'collection');
        $note       = $this->str($request, 'pickup_note', 255);

        $errors = [];
        $hasCash   = $offer['offer_cash'] !== null && (float) $offer['offer_cash'] > 0;
        $hasCredit = $offer['offer_credit'] !== null && (float) $offer['offer_credit'] > 0;
        if (!in_array($method, ['cash', 'credit'], true) || ($method === 'cash' && !$hasCash) || ($method === 'credit' && !$hasCredit)) {
            $errors[] = 'Please choose how you would like to be paid: cash or wallet credit.';
        }
        if (!in_array($collection, ['dropoff', 'pickup'], true)) {
            $errors[] = 'Please choose how we get your games: you drop them off, or we collect them.';
        } elseif ($collection === 'pickup' && mb_strlen($note) < 5) {
            $errors[] = 'Please tell us the address (or a landmark) and a good time for us to collect your games.';
        }
        if ($errors) {
            $this->invalid($path, $errors, ['method' => $method, 'collection' => $collection, 'pickup_note' => $note]);
        }

        $changed = db()->execute(
            "UPDATE buyback_requests
                SET status = 'accepted', accepted_method = ?, collection = ?, pickup_note = ?, accepted_at = NOW()
              WHERE id = ? AND user_id = ? AND status = 'offered'",
            [$method, $collection, $collection === 'pickup' ? $note : null, (int) $offer['id'], (int) $me['id']]
        );
        if ($changed === 0) {
            $this->stale($path, null);
        }

        $this->app->session()->flash('success', 'Offer accepted. Thank you! ' . ($collection === 'pickup'
            ? 'We will contact you on WhatsApp to arrange the collection.'
            : 'Bring your games to our hub and we will finish the inspection with you.'));
        $this->redirect($path);
    }

    public function decline(Request $request, string $code): void
    {
        $me = $this->me();
        $offer = $this->find($code, (int) $me['id']);
        $path = '/account/offers/' . $offer['code'];

        $changed = db()->execute(
            "UPDATE buyback_requests SET status = 'declined' WHERE id = ? AND user_id = ? AND status = 'offered'",
            [(int) $offer['id'], (int) $me['id']]
        );
        if ($changed === 0) {
            $this->stale($path, $offer['status']);
        }
        $this->app->session()->flash('success', 'You declined the offer. No problem, your games stay with you.');
        $this->redirect($path);
    }

    public function cancel(Request $request, string $code): void
    {
        $me = $this->me();
        $offer = $this->find($code, (int) $me['id']);
        $path = '/account/offers/' . $offer['code'];

        $changed = db()->execute(
            "UPDATE buyback_requests SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('new','contacted','offered','accepted')",
            [(int) $offer['id'], (int) $me['id']]
        );
        if ($changed === 0) {
            $this->stale($path, $offer['status']);
        }
        $this->app->session()->flash('success', 'Your request ' . $offer['code'] . ' was cancelled.');
        $this->redirect($path);
    }

    // ---------- helpers ----------

    /** The request when it belongs to this customer; otherwise a 404 (never reveals that a code exists). */
    private function find(string $code, int $userId): array
    {
        $code = strtoupper(trim($code));
        if (!preg_match('/^[A-Z0-9-]{4,20}$/', $code)) {
            $this->notFound();
        }
        $offer = db()->fetch(
            'SELECT id, code, kind, status, items, wanted_items, offered_total, estimate_cash, estimate_credit, preferred_method,
                    collection, pickup_note, offer_cash, offer_credit, offered_at, accepted_method, accepted_at, collected_at,
                    final_amount, final_method, completed_at, created_at
               FROM buyback_requests WHERE code = ? AND user_id = ?',
            [$code, $userId]
        );
        if ($offer === null) {
            $this->notFound();
        }
        return $offer;
    }

    /** The request is no longer in the state this action needs (already done, or changed by us meanwhile). */
    private function stale(string $path, ?string $status): void
    {
        $this->app->session()->flash('error', 'This request has already been updated, so nothing was changed. Here is where it stands now.');
        $this->redirect($path);
    }

    private function waMessage(array $offer): string
    {
        $msg = 'Hi CyberGaming, about my ' . ($offer['kind'] === 'trade_in' ? 'trade-in' : 'sell') . ' request ' . $offer['code'];
        if ($offer['status'] === 'accepted' && $offer['accepted_method'] !== null) {
            $amount = $offer['accepted_method'] === 'credit' ? $offer['offer_credit'] : $offer['offer_cash'];
            $msg .= ' (I accepted your offer: ' . AccountUi::amount($amount) . ' as ' . ($offer['accepted_method'] === 'credit' ? 'wallet credit' : 'cash') . ')';
        }
        return $msg;
    }
}
