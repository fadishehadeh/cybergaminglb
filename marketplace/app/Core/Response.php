<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $path, int $status = 302): never
    {
        $target = preg_match('#^https?://#', $path) ? $path : url($path);
        header('Location: ' . $target, true, $status);
        exit;
    }

    public static function text(string $body, string $contentType = 'text/plain; charset=utf-8', int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: ' . $contentType);
        echo $body;
        exit;
    }

    public static function json(mixed $data, int $status = 200): never
    {
        self::text((string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'application/json; charset=utf-8', $status);
    }

    public static function abort(int $status, string $message = ''): never
    {
        http_response_code($status);
        $titles = [403 => 'Forbidden', 404 => 'Page not found', 419 => 'Session expired', 429 => 'Too many attempts', 500 => 'Server error'];
        $title = $titles[$status] ?? "Error $status";

        $template = is_file(base_path("app/Views/errors/$status.php")) ? "errors/$status" : 'errors/error';
        if (is_file(base_path("app/Views/$template.php")) && is_file(base_path('app/Views/layouts/site.php'))) {
            try {
                View::render($template, compact('status', 'title', 'message'), 'site');
                exit;
            } catch (\Throwable) {
                // fall through to the plain page below
            }
        }
        echo '<!DOCTYPE html><meta charset="utf-8"><title>' . e($title) . '</title><body style="font-family:sans-serif;max-width:480px;margin:80px auto"><h1>' . e($title) . '</h1><p>' . e($message) . '</p></body>';
        exit;
    }
}
