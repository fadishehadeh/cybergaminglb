<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function __construct(private Application $app)
    {
    }

    /** @param array $middlewares list of middleware class names */
    public function get(string $path, mixed $handler, array $middlewares = []): void
    {
        $this->add('GET', $path, $handler, $middlewares);
        $this->add('HEAD', $path, $handler, $middlewares);
    }

    public function post(string $path, mixed $handler, array $middlewares = []): void
    {
        $this->add('POST', $path, $handler, $middlewares);
    }

    private function add(string $method, string $path, mixed $handler, array $middlewares): void
    {
        $path = '/' . trim($path, '/');
        $pattern = preg_quote($path, '#');
        $pattern = preg_replace_callback(
            '#\\\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\\\}#',
            static fn (array $m): string => '(?P<' . $m[1] . '>[^/]+)',
            $pattern
        );
        $this->routes[] = compact('method', 'path', 'handler', 'middlewares') + ['pattern' => "#^{$pattern}$#"];
    }

    public function dispatch(Request $request): void
    {
        $path = $request->path();
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method() || !preg_match($route['pattern'], $path, $m)) {
                continue;
            }
            $params = array_map('rawurldecode', array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY));

            $core = function (Request $req) use ($route, $params) {
                $h = $route['handler'];
                if (is_array($h)) {
                    return (new $h[0]($this->app))->{$h[1]}($req, ...array_values($params));
                }
                return $h($req, ...array_values($params));
            };

            $pipeline = array_reduce(
                array_reverse($route['middlewares']),
                static fn (callable $next, string $mw) => static fn (Request $req) => (new $mw())->handle($req, $next),
                $core
            );
            $pipeline($request);
            return;
        }

        Response::abort(404);
    }
}
