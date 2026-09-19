<?php
declare(strict_types=1);

namespace App\Core;

final class RequireAdmin implements Middleware
{
    public function handle(Request $request, callable $next): mixed
    {
        if (!auth()->hasRole('admin')) {
            Response::redirect('/admin/login');
        }
        return $next($request);
    }
}
