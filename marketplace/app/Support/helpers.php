<?php
declare(strict_types=1);

use App\Core\Application;

(static function (): void {
    $envPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
    if (!is_file($envPath)) {
        return;
    }
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value, " \t\n\r\"'");
    }
})();

function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

function app(): Application
{
    return Application::getInstance();
}

function config(string $key, mixed $default = null): mixed
{
    return app()->config($key, $default);
}

function db(): \App\Core\Database
{
    return app()->database();
}

function auth(): \App\Core\Auth
{
    return app()->auth();
}

function request(): \App\Core\Request
{
    static $request = null;
    return $request ??= \App\Core\Request::capture();
}

function base_path(string $path = ''): string
{
    return app()->basePath($path);
}

function url(string $path = ''): string
{
    $base = rtrim((string) config('app.url'), '/');
    $path = ltrim($path, '/');
    return $path === '' ? $base : "$base/$path";
}

function asset(string $path): string
{
    $file = PUBLIC_PATH . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? filemtime($file) : 1;
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

function media(?string $path): string
{
    if ($path === null || $path === '') {
        return asset('img/placeholder.svg');
    }
    return url('uploads/' . ltrim($path, '/'));
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    return app()->csrf()->token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function flash(string $key, mixed $default = null): mixed
{
    return app()->session()->getFlash($key, $default);
}

function old(string $key, mixed $default = ''): mixed
{
    static $old = null;
    $old ??= app()->session()->getFlash('old_input', []);
    return $old[$key] ?? $default;
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'item';
}

/** Settings live in the `settings` table so the owner can change them from the admin panel. */
function setting(string $key, mixed $default = null): mixed
{
    return \App\Support\Settings::get($key, $default);
}

function money(float|int|string|null $amount): string
{
    $amount = (float) $amount;
    return '$' . (floor($amount) == $amount ? number_format($amount, 0) : number_format($amount, 2));
}

/** wa.me link to the store's WhatsApp number, with a pre-filled message. */
function wa_link(string $message = ''): string
{
    $number = preg_replace('/\D+/', '', (string) setting('whatsapp_number', '961'));
    return 'https://wa.me/' . $number . ($message !== '' ? '?text=' . rawurlencode($message) : '');
}

function redirect(string $path): never
{
    \App\Core\Response::redirect($path);
}

/** Master switch (admin > Settings): gift cards and other digital goods are hidden everywhere until this is on. */
function digital_enabled(): bool
{
    return (string) setting('digital_enabled', '0') === '1';
}
