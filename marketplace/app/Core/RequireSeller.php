<?php
declare(strict_types=1);

namespace App\Core;

final class RequireSeller implements Middleware
{
    public function handle(Request $request, callable $next): mixed
    {
        if (!auth()->hasRole('seller')) {
            Response::redirect('/seller/login');
        }
        return $next($request);
    }
}
