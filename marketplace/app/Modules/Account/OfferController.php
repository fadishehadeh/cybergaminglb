<?php
declare(strict_types=1);

namespace App\Modules\Account;

use App\Core\Request;
use App\Modules\Storefront\Rules;
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
            'SELECT code, kind, status, reject_choice, items, offer_cash, offer_credit, estimate_cash, estimate_credit, offered_total, final_amount, final_method, revised_amount, created_at
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

        [$zone, $zoneMode] = $this->zoneOf($offer, $me);

        $this->page('account/offers/show', [
            'me'        => $me,
            'offer'     => $offer,
            'items'     => AccountUi::decodeItems($offer['items']),
            'wanted'    => AccountUi::decodeItems($offer['wanted_items']),
            'hub'       => (string) setting('hub_address', ''),
            'zone'      => $zone,
            'zoneMode'  => $zoneMode,
            'pickupFee' => Rules::pickupFee(),
            'minSell'   => Rules::minSell(),
            'returnFee' => Rules::returnFee(),
            'holdDays'  => Rules::holdDays(),
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
            $errors[] = 'Please choose how we get your games: you bring them to our hub, or a courier picks them up.';
        } elseif ($collection === 'pickup' && mb_strlen($note) < 5) {
            $errors[] = 'Please tell us the address (or a landmark) and a good time for the courier to pick up your games.';
        }
        // A courier shipment from a remote zone must be worth a minimum (the offer for the payout method chosen).
        [, $zoneMode] = $this->zoneOf($offer, $me);
        if (!$errors && $collection === 'pickup') {
            $amount = (float) ($method === 'credit' ? $offer['offer_credit'] : $offer['offer_cash']);
            $minError = Rules::minSellError($zoneMode, $collection, $amount);
            if ($minError !== null) {
                $errors[] = 'Shipments from your area must be worth at least ' . AccountUi::amount(Rules::minSell()) . ': choose the higher offer, or bring your games to our hub yourself.';
            }
        }
        if ($errors) {
            $this->invalid($path, $errors, ['method' => $method, 'collection' => $collection, 'pickup_note' => $note]);
        }

        $changed = db()->execute(
            "UPDATE buyback_requests
                SET status = 'accepted', accepted_method = ?, collection = ?, pickup_note = ?, pickup_fee = ?, accepted_at = NOW()
              WHERE id = ? AND user_id = ? AND status = 'offered'",
            [$method, $collection, $collection === 'pickup' ? $note : null, $collection === 'pickup' ? number_format(Rules::pickupFee(), 2, '.', '') : '0.00', (int) $offer['id'], (int) $me['id']]
        );
        if ($changed === 0) {
            $this->stale($path, null);
        }

        $this->app->session()->flash('success', 'Offer accepted. Thank you! ' . ($collection === 'pickup'
            ? ($zoneMode === 'remote'
                ? 'We will contact you on WhatsApp to arrange the courier pickup. We inspect your games when they reach our hub.'
                : 'We will contact you on WhatsApp to arrange the pickup. Our courier checks your games on the spot.')
            : 'Bring your games to our hub and we will finish the inspection with you.'));
        $this->redirect($path);
    }

    /**
     * The seller's decision about an item we could not accept: take the revised offer, get it sent back (return fee paid
     * in cash on delivery) or let us recycle it. One guarded UPDATE decides: it only matches an undecided 'rejected'
     * request of this customer, so a second click, a stale tab or a forged request changes nothing.
     */
    public function choose(Request $request, string $code): void
    {
        $me = $this->me();
        $offer = $this->find($code, (int) $me['id']);
        $path = '/account/offers/' . $offer['code'];
        $choice = $this->rawString($request, 'choice');

        if (!in_array($choice, ['new_offer', 'return', 'recycle'], true)) {
            $this->invalid($path, ['Please choose one of the options.']);
        }
        if ($choice === 'new_offer' && $offer['revised_amount'] === null) {
            $this->invalid($path, ['There is no revised offer for this item. Please choose to have it sent back or recycled.']);
        }

        $id = (int) $offer['id'];
        $uid = (int) $me['id'];
        $guard = "WHERE id = ? AND user_id = ? AND status = 'rejected' AND reject_choice IS NULL";
        if ($choice === 'new_offer') {
            $changed = db()->execute("UPDATE buyback_requests SET reject_choice = 'new_offer', reject_choice_at = NOW() $guard AND revised_amount IS NOT NULL", [$id, $uid]);
        } elseif ($choice === 'return') {
            $changed = db()->execute(
                "UPDATE buyback_requests SET reject_choice = 'return', reject_choice_at = NOW(), return_fee = ?, status = 'return_pending' $guard",
                [number_format(Rules::returnFee(), 2, '.', ''), $id, $uid]
            );
        } else {
            $changed = db()->execute("UPDATE buyback_requests SET reject_choice = 'recycle', reject_choice_at = NOW(), status = 'recycled' $guard", [$id, $uid]);
        }
        if ($changed === 0) {
            $this->stale($path, $offer['status']);
        }

        $this->app->session()->flash('success', match ($choice) {
            'new_offer' => 'Thank you. You accepted our revised offer of ' . AccountUi::amount($offer['revised_amount']) . ' and we will complete it shortly.',
            'return'    => 'Thank you. We will send your item back. Please pay the ' . AccountUi::amount(Rules::returnFee()) . ' return fee in cash to the courier when it arrives.',
            default     => 'Thank you. We will recycle the item for you, free of charge.',
        });
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
                    collection, pickup_note, zone, zone_mode, pickup_fee, offer_cash, offer_credit, offered_at, accepted_method, accepted_at, collected_at,
                    final_amount, final_method, completed_at, created_at,
                    inspection_result, reject_reason, revised_amount, reject_choice, reject_choice_at, return_fee, hold_until
               FROM buyback_requests WHERE code = ? AND user_id = ?',
            [$code, $userId]
        );
        if ($offer === null) {
            $this->notFound();
        }
        return $offer;
    }

    /**
     * Zone and mode of a request. Older requests (sent before delivery zones existed) fall back to the account's area;
     * an unknown zone has no mode, so no remote rules are applied to it.
     * @return array{0:string,1:string} zone name, 'local' | 'remote' | ''
     */
    private function zoneOf(array $offer, array $me): array
    {
        $zone = trim((string) ($offer['zone'] ?? ''));
        if ($zone === '') {
            $zone = trim((string) ($me['area'] ?? ''));
        }
        $mode = (string) ($offer['zone_mode'] ?? '');
        if (!in_array($mode, ['local', 'remote'], true)) {
            $mode = in_array($zone, $this->zoneNames(), true) ? Delivery::mode($zone) : '';
        }
        return [$mode === '' ? '' : $zone, $mode];
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
