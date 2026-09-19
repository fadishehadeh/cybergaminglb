<?php
declare(strict_types=1);

namespace App\Modules\Account;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Modules\Admin\Forms;
use App\Support\Pricing;

/**
 * Shared plumbing for the member-selling pages (listings + sales).
 * Rule number one: every query is scoped to the logged-in customer's own sellers row; another member's id is a 404.
 */
abstract class ListingBase extends Controller
{
    /** The customer's sellers row, or null when they have not accepted the member terms yet. */
    protected function memberRow(): ?array
    {
        $userId = auth()->id();
        if ($userId === null) {
            Response::redirect('/account/login');
        }
        return db()->fetch('SELECT * FROM sellers WHERE user_id = ?', [$userId]);
    }

    /**
     * The ACTIVE member row. Not a member yet -> the terms page; suspended -> a stop page. Both end the request.
     * Used by every handler except the "become a member" screens.
     */
    protected function activeMember(): array
    {
        $row = $this->memberRow();
        if ($row === null) {
            Response::redirect('/account/listings/new');
        }
        if ($row['status'] !== 'active') {
            $this->page('account/listings/suspended', ['seller' => $row]);
            exit;
        }
        return $row;
    }

    /** Render inside the storefront layout (private page: never cached). */
    protected function page(string $template, array $data = []): void
    {
        header('Cache-Control: no-store');
        $this->render($template, $data + ['user' => auth()->user()], 'site');
    }

    protected function ok(string $message): void
    {
        $this->app->session()->flash('success', $message);
    }

    protected function str(Request $request, string $key): string
    {
        return Forms::text($request->input($key));
    }

    /** Route id parameter -> positive int, otherwise 404. */
    protected function id(string $raw): int
    {
        if (!ctype_digit($raw) || (int) $raw < 1 || strlen($raw) > 9) {
            Response::abort(404);
        }
        return (int) $raw;
    }

    protected function commission(array $sellerRow): float
    {
        return Pricing::commissionPct($sellerRow);
    }

    /** "10%" / "12.5%". */
    public static function pct(float $pct): string
    {
        return rtrim(rtrim(number_format($pct, 2, '.', ''), '0'), '.') . '%';
    }
}
