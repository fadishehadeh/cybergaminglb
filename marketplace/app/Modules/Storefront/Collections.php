<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

/**
 * Programmatic landing pages (/collections/{slug}) built from live catalogue data.
 * A collection is indexable and listed in the sitemap only while it holds at least MIN_INDEXABLE products.
 * All queries start from Catalog::visible() (through SeoCatalog::where()), so gated or disabled goods never appear.
 */
final class Collections
{
    public const MIN_INDEXABLE = 6;
    public const PER_PAGE = 24;

    private static ?array $counts = null;

    /**
     * slug => definition. cond is an SQL fragment over the aliases p, c, pl; params use plain names (prefixed when several are combined).
     * @return array<string,array<string,mixed>>
     */
    public static function defs(): array
    {
        static $defs = null;
        if ($defs !== null) {
            return $defs;
        }
        $game = "c.slug = 'games'";
        $ps4 = "pl.slug = 'ps4'";
        $genre = static fn (string $g): array => ['cond' => " AND FIND_IN_SET(:genre, REPLACE(p.genres, ', ', ',')) > 0", 'params' => ['genre' => $g]];
        $defs = [
            'ps4-games-under-10' => ['group' => 'price', 'title' => 'PS4 Games Under $10', 'h1' => 'PS4 games under $10 in Lebanon', 'what' => 'PS4 games', 'cap' => 10,
                'cond' => " AND $game AND $ps4 AND p.price < 10", 'params' => [], 'order' => 'price_asc', 'tag' => 'buying'],
            'ps4-games-under-15' => ['group' => 'price', 'title' => 'PS4 Games Under $15', 'h1' => 'PS4 games under $15 in Lebanon', 'what' => 'PS4 games', 'cap' => 15,
                'cond' => " AND $game AND $ps4 AND p.price < 15", 'params' => [], 'order' => 'price_asc', 'tag' => 'buying'],
            'ps4-games-under-20' => ['group' => 'price', 'title' => 'PS4 Games Under $20', 'h1' => 'PS4 games under $20 in Lebanon', 'what' => 'PS4 games', 'cap' => 20,
                'cond' => " AND $game AND $ps4 AND p.price < 20", 'params' => [], 'order' => 'price_asc', 'tag' => 'buying'],
            'ps4-action-games' => ['group' => 'genre', 'genre' => 'Action', 'title' => 'Used PS4 Action Games', 'h1' => 'Used PS4 action games in Lebanon', 'what' => 'PS4 action games',
                'order' => 'newest', 'tag' => 'buying', 'params' => ['genre' => 'Action'], 'cond' => " AND $game AND $ps4" . $genre('Action')['cond']],
            'ps4-rpg-games' => ['group' => 'genre', 'genre' => 'RPG', 'title' => 'Used PS4 RPG Games', 'h1' => 'Used PS4 RPG games in Lebanon', 'what' => 'PS4 RPG games',
                'order' => 'newest', 'tag' => 'buying', 'params' => ['genre' => 'RPG'], 'cond' => " AND $game AND $ps4" . $genre('RPG')['cond']],
            'ps4-horror-games' => ['group' => 'genre', 'genre' => 'Horror', 'title' => 'Used PS4 Horror Games', 'h1' => 'Used PS4 horror games in Lebanon', 'what' => 'PS4 horror games',
                'order' => 'newest', 'tag' => 'buying', 'params' => ['genre' => 'Horror'], 'cond' => " AND $game AND $ps4" . $genre('Horror')['cond']],
            'ps4-open-world-games' => ['group' => 'genre', 'genre' => 'Open World', 'title' => 'Used PS4 Open World Games', 'h1' => 'Used PS4 open world games in Lebanon', 'what' => 'PS4 open world games',
                'order' => 'newest', 'tag' => 'buying', 'params' => ['genre' => 'Open World'], 'cond' => " AND $game AND $ps4" . $genre('Open World')['cond']],
            'ps4-sports-games' => ['group' => 'genre', 'genre' => 'Sports', 'title' => 'Used PS4 Sports Games', 'h1' => 'Used PS4 sports games in Lebanon', 'what' => 'PS4 sports games',
                'order' => 'newest', 'tag' => 'buying', 'params' => ['genre' => 'Sports'], 'cond' => " AND $game AND $ps4" . $genre('Sports')['cond']],
            'ps4-shooter-games' => ['group' => 'genre', 'genre' => 'Shooter', 'title' => 'Used PS4 Shooter Games', 'h1' => 'Used PS4 shooter games in Lebanon', 'what' => 'PS4 shooter games',
                'order' => 'newest', 'tag' => 'buying', 'params' => ['genre' => 'Shooter'], 'cond' => " AND $game AND $ps4" . $genre('Shooter')['cond']],
            'ps4-steelbook-editions' => ['group' => 'edition', 'title' => 'PS4 Steelbook Editions', 'h1' => 'PS4 steelbook editions in Lebanon', 'what' => 'PS4 steelbook editions',
                'cond' => " AND $game AND $ps4 AND p.is_steelbook = 1", 'params' => [], 'order' => 'newest', 'tag' => 'collectors'],
            'new-arrivals' => ['group' => 'new', 'title' => 'New Arrivals: Latest Games and Gear', 'h1' => 'New arrivals: latest games and gaming gear', 'what' => 'new arrivals', 'limit' => 24,
                'cond' => ' AND p.is_digital = 0', 'params' => [], 'order' => 'newest', 'tag' => 'buying'],
            'gaming-keyboards' => ['group' => 'hardware', 'title' => 'Gaming Keyboards', 'h1' => 'Gaming keyboards in Lebanon', 'what' => 'gaming keyboards',
                'cond' => " AND c.slug = 'keyboards'", 'params' => [], 'order' => 'newest', 'tag' => 'peripherals'],
            'gaming-mice' => ['group' => 'hardware', 'title' => 'Gaming Mice', 'h1' => 'Gaming mice in Lebanon', 'what' => 'gaming mice',
                'cond' => " AND c.slug = 'mice'", 'params' => [], 'order' => 'newest', 'tag' => 'peripherals'],
            'mousepads' => ['group' => 'hardware', 'title' => 'Gaming Mousepads and Desk Mats', 'h1' => 'Gaming mousepads and desk mats in Lebanon', 'what' => 'gaming mousepads',
                'cond' => " AND c.slug = 'mousepads'", 'params' => [], 'order' => 'newest', 'tag' => 'peripherals'],
            'gaming-headsets' => ['group' => 'hardware', 'title' => 'Gaming Headsets', 'h1' => 'Gaming headsets in Lebanon', 'what' => 'gaming headsets',
                'cond' => " AND c.slug = 'headsets'", 'params' => [], 'order' => 'newest', 'tag' => 'peripherals'],
            'used-consoles' => ['group' => 'hardware', 'title' => 'Used Game Consoles', 'h1' => 'Used game consoles in Lebanon', 'what' => 'game consoles',
                'cond' => " AND c.slug = 'consoles'", 'params' => [], 'order' => 'newest', 'tag' => 'consoles'],
            'game-controllers' => ['group' => 'hardware', 'title' => 'Game Controllers', 'h1' => 'Game controllers in Lebanon', 'what' => 'game controllers',
                'cond' => " AND c.slug = 'controllers'", 'params' => [], 'order' => 'newest', 'tag' => 'consoles'],
        ];
        foreach ($defs as $slug => &$d) {
            $d['slug'] = $slug;
        }
        unset($d);
        return $defs;
    }

    public static function get(string $slug): ?array
    {
        return self::defs()[$slug] ?? null;
    }

    private const FROM = 'FROM products p JOIN categories c ON c.id = p.category_id LEFT JOIN platforms pl ON pl.id = p.platform_id';

    /** @return array<string,int> slug => number of visible products in the collection (one query for all of them) */
    public static function counts(): array
    {
        if (self::$counts !== null) {
            return self::$counts;
        }
        $sums = [];
        $params = [];
        $i = 0;
        foreach (self::defs() as $slug => $d) {
            [$cond, $p] = self::prefixed($d, 'd' . $i . '_');
            $sums[] = "COALESCE(SUM(CASE WHEN 1=1$cond THEN 1 ELSE 0 END), 0) AS n$i";
            $params += $p;
            $i++;
        }
        $row = db()->fetch('SELECT ' . implode(', ', $sums) . ' ' . self::FROM . ' WHERE ' . SeoCatalog::where(), $params) ?: [];
        self::$counts = [];
        $i = 0;
        foreach (self::defs() as $slug => $d) {
            $n = (int) ($row["n$i"] ?? 0);
            self::$counts[$slug] = isset($d['limit']) ? min($n, (int) $d['limit']) : $n;
            $i++;
        }
        return self::$counts;
    }

    /** @return array{0:string,1:array<string,mixed>} condition and params with unique names */
    private static function prefixed(array $d, string $prefix): array
    {
        $cond = (string) $d['cond'];
        $params = [];
        foreach ((array) ($d['params'] ?? []) as $name => $value) {
            $cond = str_replace(':' . $name, ':' . $prefix . $name, $cond);
            $params[$prefix . $name] = $value;
        }
        return [$cond, $params];
    }

    public static function isIndexable(int $count): bool
    {
        return $count >= self::MIN_INDEXABLE;
    }

    /** Collections that have enough products to be indexed, in definition order. @return array<string,array> slug => def + n */
    public static function active(): array
    {
        $out = [];
        foreach (self::counts() as $slug => $n) {
            if (self::isIndexable($n)) {
                $out[$slug] = self::defs()[$slug] + ['n' => $n];
            }
        }
        return $out;
    }

    /** @return array<string,array> active collections of one group */
    public static function activeIn(string $group): array
    {
        return array_filter(self::active(), static fn (array $d): bool => $d['group'] === $group);
    }

    /** Other active collections to link to: same group first. */
    public static function related(array $def, int $limit = 6): array
    {
        $rest = array_diff_key(self::active(), [$def['slug'] => true]);
        uasort($rest, static fn (array $a, array $b): int => ($b['group'] === $def['group']) <=> ($a['group'] === $def['group']));
        return array_slice($rest, 0, $limit, true);
    }

    /**
     * Products, aggregates and one page of full product rows (through Catalog::purchasable so the card partial gets its usual columns).
     * @return array{n:int,min:?float,max:?float,genres:array<string,int>,brands:array<string,int>,conditions:array<string,int>,steelbooks:int,updated:?string,items:array,pages:int,page:int}
     */
    public static function load(array $def, int $page = 1, int $per = self::PER_PAGE): array
    {
        $order = $def['order'] === 'price_asc' ? 'p.price ASC, p.id DESC' : 'p.created_at DESC, p.id DESC';
        $sql = 'SELECT p.id, p.price, p.genres, p.brand, p.item_condition, p.is_steelbook, p.updated_at ' . self::FROM
            . ' WHERE ' . SeoCatalog::where() . $def['cond'] . " ORDER BY $order" . (isset($def['limit']) ? ' LIMIT ' . (int) $def['limit'] : '');
        $rows = db()->fetchAll($sql, (array) ($def['params'] ?? []));
        $n = count($rows);
        $genres = [];
        $brands = [];
        $conditions = [];
        $steel = 0;
        $updated = null;
        foreach ($rows as $r) {
            foreach (Ui::genres((string) $r['genres']) as $g) {
                $genres[$g] = ($genres[$g] ?? 0) + 1;
            }
            $b = trim((string) $r['brand']);
            if ($b !== '') {
                $brands[$b] = ($brands[$b] ?? 0) + 1;
            }
            $conditions[(string) $r['item_condition']] = ($conditions[(string) $r['item_condition']] ?? 0) + 1;
            $steel += (int) $r['is_steelbook'];
            if ($updated === null || (string) $r['updated_at'] > $updated) {
                $updated = (string) $r['updated_at'];
            }
        }
        arsort($genres);
        arsort($brands);
        arsort($conditions);
        $prices = array_map('floatval', array_column($rows, 'price'));

        $pages = max(1, (int) ceil($n / $per));
        $page = max(1, $page);
        $items = [];
        if ($page <= $pages) {
            $ids = array_map('intval', array_column(array_slice($rows, ($page - 1) * $per, $per), 'id'));
            $full = Catalog::purchasable($ids);
            foreach ($ids as $id) {
                if (isset($full[$id])) {
                    $items[] = $full[$id];
                }
            }
        }
        return [
            'n' => $n, 'min' => $prices ? min($prices) : null, 'max' => $prices ? max($prices) : null,
            'genres' => $genres, 'brands' => $brands, 'conditions' => $conditions, 'steelbooks' => $steel, 'updated' => $updated,
            'items' => $items, 'pages' => $pages, 'page' => $page,
        ];
    }

    /** "$6 to $14", or "$12" when every item costs the same. */
    private static function range(?float $min, ?float $max): string
    {
        if ($min === null || $max === null) {
            return '';
        }
        return $min === $max ? money($min) : money($min) . ' to ' . money($max);
    }

    /** Data-driven intro, 60 to 100 words, built only from the live numbers. */
    public static function intro(array $def, array $s): string
    {
        $n = $s['n'];
        $what = (string) $def['what'];
        $fee = money(SeoCatalog::cheapestFee());
        if ($n === 0) {
            return "There are no $what in stock right now. New items arrive often, so check back soon, or browse everything else in the shop. "
                . "We deliver across Lebanon from $fee, with cash on delivery in local areas.";
        }
        $range = self::range($s['min'], $s['max']);
        if ($def['group'] === 'new') {
            $lead = "These are the $n newest items at CyberGaming Lebanon, priced from $range.";
        } elseif ($def['group'] === 'price') {
            $lead = "$n $what " . ($n === 1 ? 'is' : 'are') . ' in stock at CyberGaming Lebanon for less than ' . money($def['cap']) . ", priced from $range.";
        } else {
            $lead = "$n $what " . ($n === 1 ? 'is' : 'are') . " in stock at CyberGaming Lebanon, priced from $range.";
        }

        $conds = $s['conditions'];
        if (count($conds) === 1) {
            $c = (string) array_key_first($conds);
            $one = $def['group'] === 'hardware' ? 'item' : 'copy';
            $many = $def['group'] === 'hardware' ? 'items' : 'copies';
            $condText = $c === 'New'
                ? ($n === 1 ? 'It is new.' : 'All are new.')
                : ($n === 1 ? "It is a used $one in $c condition." : "All are used $many in $c condition.");
        } else {
            $parts = [];
            foreach ($conds as $c => $k) {
                $parts[] = "$k $c";
            }
            $last = array_pop($parts);
            $condText = 'Conditions: ' . ($parts ? implode(', ', $parts) . ' and ' : '') . $last . '.';
        }

        $extra = '';
        if ($def['group'] === 'hardware' && $s['brands']) {
            $top = array_slice($s['brands'], 0, 3, true);
            $extra = 'Brands in stock: ' . implode(', ', array_map(static fn (string $b, int $k): string => "$b ($k)", array_keys($top), $top)) . '. ';
        } elseif ($s['genres'] && $def['group'] !== 'hardware') {
            $own = (string) ($def['genre'] ?? '');
            $top = array_slice(array_diff_key($s['genres'], $own !== '' ? [$own => 1] : []), 0, 3, true);
            if ($top) {
                $extra = ($own !== '' ? 'Many also carry the ' : 'The most common genres are ') . implode(', ', array_map(static fn (string $g, int $k): string => "$g ($k)", array_keys($top), $top))
                    . ($own !== '' ? ' tags.' : '.') . ' ';
            }
            if ($s['steelbooks'] > 0 && $def['group'] !== 'edition') {
                $extra .= $s['steelbooks'] . ($s['steelbooks'] === 1 ? ' is a collectable steelbook edition. ' : ' are collectable steelbook editions. ');
            }
        }
        $text = trim("$lead $condText {$extra}Every item is inspected before sale and prices are in US dollars. We deliver across Lebanon from $fee, with cash on delivery in local areas.");
        if (count(preg_split('/\s+/', $text) ?: []) < 60) {
            $text .= ' Store credit from selling us games works at checkout.';
        }
        return $text;
    }

    /** Meta description (70 to 160 characters). */
    public static function description(array $def, array $s): string
    {
        $what = (string) $def['what'];
        if ($s['n'] === 0) {
            return Seo::clip("No $what in stock right now at CyberGaming Lebanon. Browse the shop for inspected used and new games and gaming gear with delivery across Lebanon.");
        }
        $range = self::range($s['min'], $s['max']);
        $lead = $def['group'] === 'price' ? $s['n'] . " $what under " . money($def['cap']) . ' in Lebanon' : $s['n'] . " $what in Lebanon";
        return Seo::clip("$lead, $range. Inspected before sale, delivery across Lebanon, pay cash on delivery, OMT or Whish.");
    }
}
