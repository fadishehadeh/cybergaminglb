<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Support\Delivery;

/**
 * Read-only queries for the machine-readable surfaces (sitemap, feeds, llms.txt) and the delivery-zone pages.
 * Every product query goes through Catalog::visible() / Catalog::gate(); nothing here ever selects seller data or serial numbers.
 */
final class SeoCatalog
{
    private const FROM = 'FROM products p JOIN categories c ON c.id = p.category_id LEFT JOIN platforms pl ON pl.id = p.platform_id';

    /** Same visibility rule for every SEO surface, plus "the category and platform are switched on". Aliases p, c, pl. */
    public static function where(): string
    {
        return Catalog::visible() . ' AND c.is_active = 1 AND (pl.id IS NULL OR pl.is_active = 1)';
    }

    /** @param int[] $ids @return array<int,string[]> product id => photo paths in display order */
    public static function photos(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $rows = db()->fetchAll(
            "SELECT product_id, path FROM product_images WHERE product_id IN ($in)
              ORDER BY product_id, FIELD(kind,'disc','box_outside','box_inside','unit_front','unit_back','box_accessories','powered_on','extra'), sort_order, id",
            $ids
        );
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['product_id']][] = (string) $r['path'];
        }
        return $out;
    }

    /** Public product pages for the sitemap (active and sold, digital gate applied) with their photos. */
    public static function sitemapProducts(): array
    {
        $rows = db()->fetchAll(
            "SELECT p.id, p.slug, p.title, p.status, p.updated_at, p.image "
            . self::FROM . " WHERE p.status IN ('active','sold')" . Catalog::gate() . ' AND c.is_active = 1 AND (pl.id IS NULL OR pl.is_active = 1) ORDER BY p.id'
        );
        $photos = self::photos(array_column($rows, 'id'));
        foreach ($rows as &$r) {
            $r['photos'] = $photos[(int) $r['id']] ?? [];
        }
        unset($r);
        return $rows;
    }

    /** Everything a product feed needs. Deliberately no seller columns and no serial_number. */
    public static function feedProducts(): array
    {
        $rows = db()->fetchAll(
            'SELECT p.id, p.slug, p.title, p.description, p.item_condition, p.is_steelbook, p.edition, p.year, p.genres, p.price, p.stock,
                    p.image, p.updated_at, p.is_digital, p.brand, p.model, p.specs, p.included_items, p.warranty_months,
                    p.includes_box, p.includes_cover_art, p.includes_manual,
                    pl.name AS platform_name, pl.slug AS platform_slug, c.name AS category_name, c.slug AS category_slug, c.kind AS category_kind '
            . self::FROM . ' WHERE ' . self::where() . ' ORDER BY p.id'
        );
        $photos = self::photos(array_column($rows, 'id'));
        foreach ($rows as &$r) {
            $r['photos'] = $photos[(int) $r['id']] ?? [];
        }
        unset($r);
        return $rows;
    }

    /** Newest update among products people can see (sitemap / feed Last-Modified). */
    public static function lastUpdated(): ?string
    {
        $v = db()->fetchValue("SELECT MAX(p.updated_at) " . self::FROM . " WHERE p.status IN ('active','sold')" . Catalog::gate());
        return $v !== null ? (string) $v : null;
    }

    /** @return array<string,string> category slug => newest updated_at among its visible products */
    public static function categoryLastmods(): array
    {
        $out = [];
        foreach (db()->fetchAll('SELECT c.slug AS k, MAX(p.updated_at) AS t ' . self::FROM . ' WHERE ' . self::where() . ' GROUP BY c.slug') as $r) {
            $out[(string) $r['k']] = (string) $r['t'];
        }
        return $out;
    }

    /** @return array<string,string> platform slug => newest updated_at */
    public static function platformLastmods(): array
    {
        $out = [];
        foreach (db()->fetchAll('SELECT pl.slug AS k, MAX(p.updated_at) AS t ' . self::FROM . ' WHERE ' . self::where() . ' AND pl.id IS NOT NULL GROUP BY pl.slug') as $r) {
            $out[(string) $r['k']] = (string) $r['t'];
        }
        return $out;
    }

    /** Inventory summary per category and per platform (llms-full.txt, JSON feed). */
    public static function inventory(): array
    {
        $cats = db()->fetchAll(
            'SELECT c.slug, c.name, c.kind, COUNT(*) AS n, MIN(p.price) AS lo, MAX(p.price) AS hi ' . self::FROM
            . ' WHERE ' . self::where() . ' GROUP BY c.id, c.slug, c.name, c.kind, c.sort_order ORDER BY c.sort_order, c.name'
        );
        $plats = db()->fetchAll(
            'SELECT pl.slug, pl.name, COUNT(*) AS n, MIN(p.price) AS lo, MAX(p.price) AS hi ' . self::FROM
            . ' WHERE ' . self::where() . ' AND pl.id IS NOT NULL GROUP BY pl.id, pl.slug, pl.name, pl.sort_order ORDER BY pl.sort_order, pl.name'
        );
        return ['categories' => $cats, 'platforms' => $plats];
    }

    // ---------------------------------------------------------------- delivery zones

    /** URL slug of a zone: "Mount Lebanon (mountain areas)" becomes "mount-lebanon". */
    public static function zoneSlug(string $name): string
    {
        $clean = trim((string) preg_replace('/\s*\([^)]*\)/', '', $name));
        return slugify($clean !== '' ? $clean : $name);
    }

    /** @return array<int,array{id:int,name:string,fee:float,mode:string,slug:string,short:string,detail:string}> active zones in display order, slugs unique */
    public static function zones(): array
    {
        static $zones = null;
        if ($zones !== null) {
            return $zones;
        }
        $zones = [];
        $seen = [];
        foreach (Delivery::zones() as $z) {
            $name = (string) $z['name'];
            $slug = self::zoneSlug($name);
            if (isset($seen[$slug])) {
                $slug .= '-' . (int) $z['id'];
            }
            $seen[$slug] = true;
            preg_match('/\(([^)]*)\)/', $name, $m);
            $zones[] = [
                'id'     => (int) $z['id'],
                'name'   => $name,
                'fee'    => (float) $z['fee'],
                'mode'   => ($z['mode'] ?? '') === 'local' ? 'local' : 'remote',
                'slug'   => $slug,
                'short'  => trim((string) preg_replace('/\s*\([^)]*\)/', '', $name)),
                'detail' => trim((string) ($m[1] ?? '')),
            ];
        }
        return $zones;
    }

    public static function zoneBySlug(string $slug): ?array
    {
        foreach (self::zones() as $z) {
            if ($z['slug'] === $slug) {
                return $z;
            }
        }
        return null;
    }

    /** Cheapest delivery fee across active zones (what schema.org shippingRate and the feeds advertise). */
    public static function cheapestFee(): float
    {
        $fees = array_column(self::zones(), 'fee');
        return $fees ? (float) min($fees) : Delivery::defaultFee();
    }

    public static function viewMtime(string $relative): int
    {
        $f = base_path('app/Views/' . $relative);
        return is_file($f) ? (int) filemtime($f) : 0;
    }
}
