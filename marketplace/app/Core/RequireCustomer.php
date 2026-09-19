<?php
declare(strict_types=1);

namespace App\Core;

final class RequireCustomer implements Middleware
{
    public function handle(Request $request, callable $next): mixed
    {
        if (!auth()->hasRole('customer')) {
            $query = $request->method() === 'GET' ? '?next=' . rawurlencode($request->path()) : '';
            Response::redirect('/account/login' . $query);
        }
        return $next($request);
    }
}
