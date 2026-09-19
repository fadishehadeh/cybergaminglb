<?php
declare(strict_types=1);

namespace App\Modules\Account;

use App\Core\Request;
use App\Support\Wallet;

final class WalletController extends BaseController
{
    private const PER_PAGE = 20;

    public function index(Request $request): void
    {
        $me = $this->me();
        $id = (int) $me['id'];

        $total = (int) db()->fetchValue('SELECT COUNT(*) FROM credit_ledger WHERE user_id = ?', [$id]);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = (int) $request->query('page', 1);
        $page  = min(max(1, $page), $pages);

        $rows = AccountUi::describeLedger(Wallet::history($id, self::PER_PAGE, ($page - 1) * self::PER_PAGE), $id);

        $this->page('account/wallet', [
            'me'      => $me,
            'balance' => Wallet::balance($id),
            'rows'    => $rows,
            'total'   => $total,
            'page'    => $page,
            'pages'   => $pages,
        ]);
    }
}
