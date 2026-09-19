<?php
declare(strict_types=1);

namespace App\Modules\Account;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Support\Delivery;

/** Shared behaviour for the customer account pages: private, never cached, never indexed. */
abstract class BaseController extends Controller
{
    protected function page(string $template, array $data = []): void
    {
        $this->noStore();
        $this->render($template, $data + ['nav' => 'account'], 'site');
    }

    protected function noStore(): void
    {
        header('Cache-Control: no-store');
    }

    /** The signed-in customer (RequireCustomer guarantees one; redirect defensively). */
    protected function me(): array
    {
        $user = auth()->user();
        if ($user === null || $user['role'] !== 'customer') {
            $this->redirect('/account/login');
        }
        return $user;
    }

    protected function str(Request $r, string $key, int $max): string
    {
        return AccountUi::clean($r->input($key, ''), $max);
    }

    protected function rawString(Request $r, string $key): string
    {
        $v = $r->input($key, '');
        return is_string($v) ? $v : '';
    }

    /** @return string[] active delivery zone names */
    protected function zoneNames(): array
    {
        return array_map(static fn (array $z): string => (string) $z['name'], Delivery::zones());
    }

    /** Flash a list of validation errors (rendered by the form view) and go back with the old input. */
    protected function invalid(string $path, array $errors, array $input = []): void
    {
        $this->app->session()->flash('form_errors', array_values($errors));
        $this->back($path, null, $input);
    }

    protected function notFound(): void
    {
        Response::abort(404);
    }
}
