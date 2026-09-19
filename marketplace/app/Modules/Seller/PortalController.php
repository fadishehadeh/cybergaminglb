<?php
declare(strict_types=1);

namespace App\Modules\Seller;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Modules\Admin\Forms;

/**
 * Base class for every logged-in seller-portal controller.
 * Rule number one: every query is scoped to seller()['id'], and no query ever reads a buyer column.
 */
abstract class PortalController extends Controller
{
    private ?array $sellerRow = null;

    /** The logged-in seller's ACTIVE sellers row. Anyone else is logged out and sent to the login page. */
    protected function seller(): array
    {
        if ($this->sellerRow !== null) {
            return $this->sellerRow;
        }
        $userId = auth()->id();
        $row = $userId ? db()->fetch('SELECT * FROM sellers WHERE user_id = ?', [$userId]) : null;
        if ($row === null || $row['status'] !== 'active') {
            $suspended = $row !== null && $row['status'] === 'suspended';
            auth()->logout();
            $this->app->session()->flash('error', $suspended
                ? 'Your seller account is suspended. Please contact CyberGaming.'
                : 'Your seller account is not active yet.');
            Response::redirect('/seller/login');
        }
        return $this->sellerRow = $row;
    }

    protected function view(string $template, array $data = []): void
    {
        $this->render('seller/' . $template, $data + ['seller' => $this->seller()], 'seller');
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

    protected function invalid(string $path, array $errors, Request $request): never
    {
        $this->back($path, implode("\n", $errors), $request->all() + ['_form' => '1']);
    }
}
