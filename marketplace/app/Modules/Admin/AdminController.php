<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

/** Base class for every admin controller. */
abstract class AdminController extends Controller
{
    protected function view(string $template, array $data = []): void
    {
        $this->render('admin/' . $template, $data, 'admin');
    }

    protected function ok(string $message): void
    {
        $this->app->session()->flash('success', $message);
    }

    protected function fail(string $message): void
    {
        $this->app->session()->flash('error', $message);
    }

    protected function str(Request $request, string $key): string
    {
        return Forms::text($request->input($key));
    }

    /** Route id parameter -> positive int, otherwise 404. */
    protected function id(string $raw): int
    {
        if (!ctype_digit($raw) || (int) $raw < 1) {
            Response::abort(404);
        }
        return (int) $raw;
    }

    protected function returnTo(Request $request, string $default): string
    {
        return Forms::safeReturn($request->input('return'), $default);
    }

    /** Validation errors are flashed as one message with one error per line. */
    protected function invalid(string $path, array $errors, Request $request): never
    {
        $this->back($path, implode("\n", $errors), $request->all() + ['_form' => '1']);
    }
}
