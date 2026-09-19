<?php
declare(strict_types=1);

namespace App\Support;

/** Key/value settings stored in the `settings` table (commission %, buy-back %, swap fee, WhatsApp number, ...). */
final class Settings
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            try {
                foreach (db()->fetchAll('SELECT `key`, `value` FROM settings') as $row) {
                    self::$cache[$row['key']] = $row['value'];
                }
            } catch (\Throwable) {
                // settings table not created yet (fresh install)
            }
        }
        return self::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        return array_key_exists($key, $all) && $all[$key] !== '' ? $all[$key] : $default;
    }

    public static function set(string $key, string $value): void
    {
        db()->execute(
            'INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
            [$key, $value]
        );
        self::$cache = null;
    }
}
