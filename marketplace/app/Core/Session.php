<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    public function __construct()
    {
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        session_name((string) env('SESSION_NAME', 'cybergaming_session'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => str_starts_with((string) env('APP_URL', ''), 'https://'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        // Flash data lives for exactly one further request.
        foreach ($_SESSION['_flash'] ?? [] as $key => $item) {
            if ($item['read'] ?? false) {
                unset($_SESSION['_flash'][$key]);
            }
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = ['value' => $value, 'read' => false];
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        if (!isset($_SESSION['_flash'][$key])) {
            return $default;
        }
        $_SESSION['_flash'][$key]['read'] = true;
        return $_SESSION['_flash'][$key]['value'];
    }
}
