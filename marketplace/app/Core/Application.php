<?php
declare(strict_types=1);

namespace App\Core;

final class Application
{
    private static ?self $instance = null;

    private string $basePath;
    private array $config;
    private Session $session;
    private Database $database;
    private Auth $auth;
    private Csrf $csrf;
    private Router $router;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
        self::$instance = $this;

        $this->config = [
            'app'      => require $this->basePath . '/config/app.php',
            'database' => require $this->basePath . '/config/database.php',
        ];

        date_default_timezone_set((string) $this->config('app.timezone', 'Asia/Beirut'));

        if ($this->config('app.debug')) {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
        }

        $this->session  = new Session();
        $this->database = new Database($this->config['database']);
        $this->auth     = new Auth($this->database, $this->session);
        $this->csrf     = new Csrf($this->session);
        $this->router   = new Router($this);
    }

    public static function getInstance(): self
    {
        return self::$instance ?? throw new \RuntimeException('Application not initialised');
    }

    public function basePath(string $path = ''): string
    {
        return $path === '' ? $this->basePath : $this->basePath . '/' . ltrim($path, '/\\');
    }

    public function config(string $key, mixed $default = null): mixed
    {
        $value = $this->config;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }

    public function router(): Router     { return $this->router; }
    public function session(): Session   { return $this->session; }
    public function database(): Database { return $this->database; }
    public function auth(): Auth         { return $this->auth; }
    public function csrf(): Csrf         { return $this->csrf; }

    public function run(): void
    {
        $request = Request::capture();
        $this->securityHeaders();

        if ($request->method() === 'POST' && !$this->csrf->validate((string) $request->input('_token'))) {
            Response::abort(403, 'Your session expired. Please go back, refresh the page and try again.');
        }

        try {
            $this->router->dispatch($request);
        } catch (\Throwable $e) {
            $this->logError($e);
            Response::abort(500, $this->config('app.debug') ? $e->getMessage() : 'Something went wrong. Please try again.');
        }
    }

    private function securityHeaders(): void
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
        if (str_starts_with((string) $this->config('app.url'), 'https://')) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    private function logError(\Throwable $e): void
    {
        $dir = $this->basePath('storage/logs');
        if (is_dir($dir) || @mkdir($dir, 0755, true)) {
            @file_put_contents($dir . '/error.log', sprintf("[%s] %s in %s:%d\n", date('c'), $e->getMessage(), $e->getFile(), $e->getLine()), FILE_APPEND);
        }
    }
}
