<?php
declare(strict_types=1);

namespace App\Modules\Admin;

/** Video games (category kind "game": PS4/PS5/etc) behind the admin's games master switch. */
final class Games
{
    /** @return array{total:int,active:int,live:int} */
    public static function counts(): array
    {
        $row = db()->fetch(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(p.status = 'active'), 0) AS active,
                    COALESCE(SUM(p.status = 'active' AND p.stock > 0), 0) AS live
               FROM products p JOIN categories c ON c.id = p.category_id WHERE c.kind = 'game'"
        ) ?? [];
        return [
            'total'  => (int) ($row['total'] ?? 0),
            'active' => (int) ($row['active'] ?? 0),
            'live'   => (int) ($row['live'] ?? 0),
        ];
    }
}
