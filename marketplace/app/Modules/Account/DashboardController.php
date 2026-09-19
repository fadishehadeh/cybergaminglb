<?php
declare(strict_types=1);

namespace App\Modules\Account;

use App\Core\Request;
use App\Support\Wallet;

final class DashboardController extends BaseController
{
    public function index(Request $request): void
    {
        $me = $this->me();
        $id = (int) $me['id'];

        $offers = db()->fetchAll(
            "SELECT code, kind, status, offer_cash, offer_credit, created_at FROM buyback_requests
              WHERE user_id = ? AND status = 'offered' ORDER BY offered_at DESC, id DESC",
            [$id]
        );
        // rejected items wait for the customer's decision (new offer / return / recycle)
        $choices = db()->fetchAll(
            "SELECT code, kind, revised_amount, hold_until FROM buyback_requests
              WHERE user_id = ? AND status = 'rejected' AND reject_choice IS NULL ORDER BY id DESC",
            [$id]
        );
        $openOffers = (int) db()->fetchValue(
            "SELECT COUNT(*) FROM buyback_requests WHERE user_id = ? AND status IN ('new','contacted','accepted','collected','rejected','return_pending')",
            [$id]
        );
        $ledger = AccountUi::describeLedger(Wallet::history($id, 5), $id);
        $orders = db()->fetchAll(
            'SELECT code, status, total, delivery_fee, grand_total, credit_used, payment_status, created_at FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 3',
            [$id]
        );

        $missing = [];
        if (trim((string) ($me['phone'] ?? '')) === '') {
            $missing[] = 'your phone number';
        }
        if (trim((string) ($me['area'] ?? '')) === '') {
            $missing[] = 'your area';
        }
        if (trim((string) ($me['address'] ?? '')) === '') {
            $missing[] = 'your delivery address';
        }

        $this->page('account/dashboard', [
            'me'         => $me,
            'balance'    => Wallet::balance($id),
            'offers'     => $offers,
            'choices'    => $choices,
            'openOffers' => $openOffers,
            'ledger'     => $ledger,
            'orders'     => $orders,
            'missing'    => $missing,
        ]);
    }
}
