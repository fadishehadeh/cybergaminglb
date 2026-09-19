<?php
declare(strict_types=1);

namespace App\Core;

/** Route middleware: new SomeMiddleware()->handle($request, $next) — call $next($request) to continue. */
interface Middleware
{
    public function handle(Request $request, callable $next): mixed;
}
