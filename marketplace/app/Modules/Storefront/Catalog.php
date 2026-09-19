<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

/**
 * Read-only catalogue queries for the public storefront.
 * Deliberately never touches the sellers table and never selects seller_id / seller_price:
 * seller identity must not be reachable from anything the storefront renders.
 */
final class Catalog
{
    /** Slug of the category that holds gift cards; it is hidden together with digital products while the master switch is off. */
    public const GIFT_SLUG = 'gift-cards';

    /** Public product columns (no seller data). */
    private const COLS = 'p.id, p.slug, p.title, p.description, p.item_condition, p.includes_box, p.includes_cover_art, p.includes_manual,
        p.edition, p.is_steelbook, p.year, p.genres,
        p.price, p.stock, p.image, p.status, p.created_at, p.updated_at, p.category_id, p.platform_id,
        p.is_digital, p.digital_kind, p.digital_region,
        pl.name AS platform_name, pl.slug AS platform_slug, c.name AS category_name, c.slug AS category_slug';

    private const FROM = 'FROM products p
        JOIN categories c ON c.id = p.category_id
        LEFT JOIN platforms pl ON pl.id = p.platform_id';

    private static ?array $platforms = null;
    private static ?array $categories = null;

    public const SORTS = [
        'newest'     => ['Newest first', 'p.created_at DESC, p.id DESC'],
        'price_asc'  => ['Price: low to high', 'p.price ASC, p.id DESC'],
        'price_desc' => ['Price: high to low', 'p.price DESC, p.id DESC'],
        'az'         => ['Name: A to Z', 'p.title ASC'],
    ];

    /**
     * THE digital-goods gate. Every storefront query that reads `products` (alias `p`) must append this fragment.
     * With the master switch off (default) digital products and everything in the gift-cards category do not exist
     * for the public site; with it on it adds nothing.
     */
    public static function gate(): string
    {
        if (digital_enabled()) {
            return '';
        }
        return " AND p.is_digital = 0 AND p.category_id NOT IN (SELECT gc.id FROM categories gc WHERE gc.slug = '" . self::GIFT_SLUG . "')";
    }

    /** Products a customer can buy right now (active, in stock, digital gate applied). Alias `p`. */
    public static function visible(): string
    {
        return "p.status = 'active' AND p.stock > 0" . self::gate();
    }

    /** Same as visible() but never digital: what trade-ins may ask for (credit can not be spent on digital goods). */
    public static function wantable(): string
    {
        return self::visible() . ' AND p.is_digital = 0';
    }

    /** Active platforms with the number of purchasable products. */
    public static function platforms(): array
    {
        return self::$platforms ??= db()->fetchAll(
            "SELECT pl.id, pl.slug, pl.name,
                    (SELECT COUNT(*) FROM products p WHERE p.platform_id = pl.id AND " . self::visible() . ") AS product_count
               FROM platforms pl WHERE pl.is_active = 1 ORDER BY pl.sort_order, pl.name"
        );
    }

    public static function categories(): array
    {
        return self::$categories ??= db()->fetchAll(
            "SELECT c.id, c.slug, c.name, c.seo_title, c.seo_description, c.intro_text,
                    (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND " . self::visible() . ") AS product_count
               FROM categories c WHERE c.is_active = 1" . (digital_enabled() ? '' : " AND c.slug <> '" . self::GIFT_SLUG . "'") . " ORDER BY c.sort_order, c.name"
        );
    }

    public static function platformBySlug(string $slug): ?array
    {
        foreach (self::platforms() as $p) {
            if ($p['slug'] === $slug) {
                return $p;
            }
        }
        return null;
    }

    public static function categoryBySlug(string $slug): ?array
    {
        foreach (self::categories() as $c) {
            if ($c['slug'] === $slug) {
                return $c;
            }
        }
        return null;
    }

    /** Distinct genres among purchasable products in the given scope, sorted. */
    public static function genres(?int $platformId = null, ?int $categoryId = null): array
    {
        [$where, $params] = self::scope($platformId, $categoryId);
        $rows = db()->fetchAll("SELECT DISTINCT p.genres FROM products p WHERE $where AND p.genres IS NOT NULL AND p.genres <> ''", $params);
        $out = [];
        foreach ($rows as $r) {
            foreach (Ui::genres((string) $r['genres']) as $g) {
                $out[$g] = $g;
            }
        }
        natcasesort($out);
        return array_values($out);
    }

    /**
     * @param array{q?:string,genre?:string,edition?:string,cond?:string,sort?:string} $f
     * @return array{items:array,total:int,pages:int,page:int}
     */
    public static function listing(?int $platformId, ?int $categoryId, array $f, int $page, int $per = 24): array
    {
        [$where, $params] = self::scope($platformId, $categoryId);

        $q = trim((string) ($f['q'] ?? ''));
        if ($q !== '') {
            foreach (array_slice(preg_split('/\s+/u', $q) ?: [], 0, 5) as $i => $word) {
                $where .= " AND p.title LIKE :q$i";
                $params["q$i"] = '%' . addcslashes($word, '%_\\') . '%';
            }
        }
        if (($f['genre'] ?? '') !== '') {
            $where .= " AND FIND_IN_SET(:genre, REPLACE(p.genres, ', ', ',')) > 0";
            $params['genre'] = $f['genre'];
        }
        $cond = (string) ($f['cond'] ?? '');
        if ($cond === 'new') {
            $where .= " AND p.item_condition = 'New'";
        } elseif ($cond === 'used') {
            $where .= " AND p.item_condition IN ('Like New','Good','Fair')";
        }
        if (($f['edition'] ?? '') === 'steelbook') {
            $where .= ' AND p.is_steelbook = 1';
        }

        $total = (int) db()->fetchValue("SELECT COUNT(*) FROM products p WHERE $where", $params);
        $pages = max(1, (int) ceil($total / $per));
        $page = max(1, $page);
        if ($page > $pages) {
            return ['items' => [], 'total' => $total, 'pages' => $pages, 'page' => $page];
        }

        $order = (self::SORTS[$f['sort'] ?? 'newest'] ?? self::SORTS['newest'])[1];
        $offset = ($page - 1) * $per;
        $items = db()->fetchAll(
            'SELECT ' . self::COLS . ' ' . self::FROM . " WHERE $where ORDER BY $order LIMIT " . (int) $per . ' OFFSET ' . (int) $offset,
            $params
        );
        return ['items' => $items, 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    /** Count + cheapest price for a scope (used for SEO copy). */
    public static function summary(?int $platformId, ?int $categoryId): array
    {
        [$where, $params] = self::scope($platformId, $categoryId);
        return db()->fetch("SELECT COUNT(*) AS n, MIN(p.price) AS min_price FROM products p WHERE $where", $params) ?? ['n' => 0, 'min_price' => null];
    }

    /** Newest physical products (home "latest arrivals", cart suggestions). Digital goods have their own home section. */
    public static function latest(int $limit = 8): array
    {
        return db()->fetchAll('SELECT ' . self::COLS . ' ' . self::FROM . ' WHERE ' . self::visible() . ' AND p.is_digital = 0 ORDER BY p.created_at DESC, p.id DESC LIMIT ' . (int) $limit);
    }

    /** Newest digital products (home page section). Empty unless the master switch is on. */
    public static function giftCards(int $limit = 4): array
    {
        if (!digital_enabled()) {
            return [];
        }
        return db()->fetchAll('SELECT ' . self::COLS . ' ' . self::FROM . ' WHERE ' . self::visible() . ' AND p.is_digital = 1 ORDER BY p.created_at DESC, p.id DESC LIMIT ' . (int) $limit);
    }

    public static function steelbooks(int $limit = 4): array
    {
        return db()->fetchAll('SELECT ' . self::COLS . ' ' . self::FROM . ' WHERE ' . self::visible() . ' AND p.is_steelbook = 1 ORDER BY p.price DESC, p.id DESC LIMIT ' . (int) $limit);
    }

    /** Numbers shown on the home page, straight from the database (physical, inspected stock only). */
    public static function stats(): array
    {
        $row = db()->fetch(
            "SELECT COUNT(*) AS items, COALESCE(SUM(p.is_steelbook), 0) AS steelbooks, MIN(p.price) AS min_price,
                    COUNT(DISTINCT p.platform_id) AS platforms
               FROM products p WHERE " . self::visible() . ' AND p.is_digital = 0'
        ) ?? [];
        return [
            'items'      => (int) ($row['items'] ?? 0),
            'steelbooks' => (int) ($row['steelbooks'] ?? 0),
            'platforms'  => (int) ($row['platforms'] ?? 0),
            'min_price'  => $row['min_price'] !== null ? (float) $row['min_price'] : null,
        ];
    }

    /** Any product that is public: active or sold (pending/hidden return null so the page 404s). */
    public static function product(string $slug): ?array
    {
        return db()->fetch(
            'SELECT ' . self::COLS . ' ' . self::FROM . " WHERE p.slug = :slug AND p.status IN ('active','sold')" . self::gate(),
            ['slug' => $slug]
        );
    }

    public static function extraImages(int $productId): array
    {
        return array_column(db()->fetchAll('SELECT path FROM product_images WHERE product_id = :id ORDER BY sort_order, id', ['id' => $productId]), 'path');
    }

    public static function related(array $product, int $limit = 4): array
    {
        $pid = (int) $product['id'];
        $params = ['cat' => (int) $product['category_id'], 'id' => $pid];
        $platform = '';
        if ($product['platform_id'] !== null) {
            $platform = ' AND p.platform_id = :plat';
            $params['plat'] = (int) $product['platform_id'];
        }
        // deterministic pseudo-shuffle so related items differ per product but stay stable between requests
        return db()->fetchAll(
            'SELECT ' . self::COLS . ' ' . self::FROM . ' WHERE ' . self::visible() . " AND p.category_id = :cat AND p.id <> :id$platform
              ORDER BY MOD(p.id * 7919 + $pid, 101), p.id LIMIT " . (int) $limit,
            $params
        );
    }

    /** @param int[] $ids */
    public static function purchasable(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $rows = db()->fetchAll('SELECT ' . self::COLS . ' ' . self::FROM . " WHERE p.id IN ($in) AND " . self::visible(), $ids);
        return array_column($rows, null, 'id');
    }

    /** Everything the sitemap needs. */
    public static function sitemapProducts(): array
    {
        return db()->fetchAll("SELECT p.slug, p.status, p.updated_at FROM products p WHERE p.status IN ('active','sold')" . self::gate() . ' ORDER BY p.id');
    }

    public static function sitemapCombos(): array
    {
        return db()->fetchAll(
            "SELECT pl.slug AS platform, c.slug AS category, MAX(p.updated_at) AS updated_at
               FROM products p JOIN platforms pl ON pl.id = p.platform_id JOIN categories c ON c.id = p.category_id
              WHERE " . self::visible() . " AND pl.is_active = 1 AND c.is_active = 1
              GROUP BY pl.slug, c.slug"
        );
    }

    /** @return array{0:string,1:array} */
    private static function scope(?int $platformId, ?int $categoryId): array
    {
        $where = self::visible();
        $params = [];
        if ($platformId !== null) {
            $where .= ' AND p.platform_id = :platform';
            $params['platform'] = $platformId;
        }
        if ($categoryId !== null) {
            $where .= ' AND p.category_id = :category';
            $params['category'] = $categoryId;
        }
        return [$where, $params];
    }
}
