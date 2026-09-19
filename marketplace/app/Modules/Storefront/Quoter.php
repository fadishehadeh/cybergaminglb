<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Core\Request;
use App\Support\Pricing;

/**
 * Shared engine for the buy-back (/sell) and trade-in (/trade) flows.
 *
 * Nothing in here trusts a client-submitted price: the only things read from a form are the platform,
 * the title the customer typed and the condition. Every figure is recomputed from the catalogue and the
 * admin-editable settings (Pricing::buybackOffer / Pricing::tradeCredit).
 */
final class Quoter
{
    public const CONDITIONS = ['New', 'Like New', 'Good', 'Fair'];
    public const MAX_GIVE = 8;
    public const MAX_WANT = 6;
    public const MAX_TITLE = 120;
    public const MAX_NOTE = 500;
    public const PER_HOUR = 5;
    public const MAX_PHOTOS = 6;
    /** What a customer can say comes with a game they give us (form field => JSON key). */
    public const INCLUDES = ['game_box' => 'box', 'game_cover' => 'cover', 'game_manual' => 'manual'];
    private const SUFFIX_RE = '/\s*[\(\[]\s*steel\s?book(?:\s+edition)?\s*[\)\]]\s*$/i';

    // ---------- reading form input ----------

    /** Remove control characters, collapse whitespace, trim and cap the length. */
    public static function clean(mixed $v, int $max = 255): string
    {
        if (!is_string($v)) {
            return '';
        }
        $v = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $v) ?? '';
        $v = preg_replace('/\s+/u', ' ', $v) ?? '';
        return mb_substr(trim($v), 0, $max);
    }

    /** Multi-line free text (notes). */
    public static function cleanNote(mixed $v, int $max = self::MAX_NOTE): string
    {
        if (!is_string($v)) {
            return '';
        }
        $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v) ?? '';
        return mb_substr(trim($v), 0, $max);
    }

    /** The title without a trailing "(Steelbook Edition)" so a standard copy can be priced from it. */
    public static function baseTitle(string $title): string
    {
        return trim(preg_replace(self::SUFFIX_RE, '', $title) ?? $title);
    }

    /** @return array<string,array> active platforms keyed by slug */
    public static function platformsBySlug(): array
    {
        $out = [];
        foreach (Catalog::platforms() as $p) {
            $out[$p['slug']] = $p;
        }
        return $out;
    }

    /** Conditions with the percentage of the standard offer each one earns (from the admin settings). */
    public static function conditionFactors(): array
    {
        $out = [];
        foreach (self::CONDITIONS as $c) {
            $out[$c] = (float) Pricing::conditionFactor($c);
        }
        return $out;
    }

    /**
     * Read the "games you give" rows (game_platform[], game_title[], game_condition[] plus the checkboxes
     * game_box[i], game_cover[i], game_manual[i], keyed by the row number).
     * Empty rows are ignored. Returns the cleaned rows and a list of human-readable errors.
     *
     * @return array{0:array<int,array{platform:string,title:string,condition:string,box:bool,cover:bool,manual:bool}>,1:string[]}
     */
    public static function readGames(Request $r): array
    {
        $platforms = self::platformsBySlug();
        $plat = self::arr($r->input('game_platform'));
        $titles = self::arr($r->input('game_title'));
        $conds = self::arr($r->input('game_condition'));
        $flags = [];
        foreach (self::INCLUDES as $field => $key) {
            $flags[$key] = self::flags($r->input($field));
        }

        $rows = [];
        $errors = [];
        $count = min(self::MAX_GIVE, max(count($plat), count($titles), count($conds)));
        for ($i = 0; $i < $count; $i++) {
            $rawTitle = $titles[$i] ?? '';
            $title = self::clean($rawTitle, 400);
            if ($title === '') {
                continue;
            }
            $n = $i + 1;
            if (mb_strlen($title) > self::MAX_TITLE) {
                $errors[] = "Game $n: the title is too long (max " . self::MAX_TITLE . ' characters).';
                continue;
            }
            $slug = is_string($plat[$i] ?? null) ? $plat[$i] : '';
            if (!isset($platforms[$slug])) {
                $errors[] = "Game $n: please choose a platform.";
                continue;
            }
            $cond = is_string($conds[$i] ?? null) ? $conds[$i] : '';
            if (!in_array($cond, self::CONDITIONS, true)) {
                $errors[] = "Game $n: please choose a condition.";
                continue;
            }
            $rows[] = [
                'platform'  => $slug,
                'title'     => $title,
                'condition' => $cond,
                'box'       => isset($flags['box'][$i]),
                'cover'     => isset($flags['cover'][$i]),
                'manual'    => isset($flags['manual'][$i]),
            ];
        }
        if (!$rows && !$errors) {
            $errors[] = 'Please add at least one game (platform, title and condition).';
        }
        return [$rows, $errors];
    }

    /**
     * Read the "what you want" rows (want_title[], want_slug[]).
     *
     * @return array{0:array<int,array{title:string,slug:string}>,1:string[]}
     */
    public static function readWanted(Request $r): array
    {
        $titles = self::arr($r->input('want_title'));
        $slugs = self::arr($r->input('want_slug'));
        $rows = [];
        $errors = [];
        $count = min(self::MAX_WANT, max(count($titles), count($slugs)));
        for ($i = 0; $i < $count; $i++) {
            $title = self::clean($titles[$i] ?? '', 400);
            if ($title === '') {
                continue;
            }
            if (mb_strlen($title) > 190) {
                $errors[] = 'Wanted item ' . ($i + 1) . ': the title is too long.';
                continue;
            }
            $slug = is_string($slugs[$i] ?? null) ? mb_substr($slugs[$i], 0, 190) : '';
            $rows[] = ['title' => $title, 'slug' => $slug];
        }
        return [$rows, $errors];
    }

    private static function arr(mixed $v): array
    {
        return is_array($v) ? array_values($v) : [];
    }

    /** Checked checkboxes posted as name[rowNumber]=1: the set of row numbers that are ticked. @return array<int,true> */
    private static function flags(mixed $v): array
    {
        $out = [];
        if (is_array($v)) {
            foreach ($v as $k => $val) {
                if ((is_int($k) || (is_string($k) && ctype_digit($k))) && $val === '1') {
                    $out[(int) $k] = true;
                }
            }
        }
        return $out;
    }

    /** The three include flags of a row / line / stored item as clean booleans (missing = false). @return array{box:bool,cover:bool,manual:bool} */
    public static function includesOf(array $row): array
    {
        $inc = isset($row['includes']) && is_array($row['includes']) ? $row['includes'] : $row;
        return ['box' => !empty($inc['box']), 'cover' => !empty($inc['cover']), 'manual' => !empty($inc['manual'])];
    }

    /** "box, cover art and manual" / "box only" / "disc only": what a customer says comes with the game. */
    public static function includesText(array $row): string
    {
        $inc = self::includesOf($row);
        $parts = [];
        if ($inc['box']) { $parts[] = 'box'; }
        if ($inc['cover']) { $parts[] = 'cover art'; }
        if ($inc['manual']) { $parts[] = 'manual'; }
        if (!$parts) {
            return 'disc only';
        }
        if (count($parts) === 1) {
            return $parts[0] . ' only';
        }
        $last = array_pop($parts);
        return implode(', ', $parts) . ' and ' . $last;
    }

    // ---------- catalogue matching ----------

    /** Distinct standard titles we can price (any status except pending/hidden), for the <datalist>. */
    public static function referenceTitles(): array
    {
        $rows = db()->fetchAll("SELECT DISTINCT p.title FROM products p WHERE p.status NOT IN ('pending','hidden') AND p.is_digital = 0 AND p.category_id IN (SELECT id FROM categories WHERE slug = 'games')" . Catalog::gate() . ' ORDER BY p.title');
        $out = [];
        foreach ($rows as $r) {
            $out[self::baseTitle((string) $r['title'])] = true;
        }
        $titles = array_keys($out);
        natcasesort($titles);
        return array_values($titles);
    }

    /** Titles of products that can be bought right now, for the "what you want" <datalist>. */
    public static function wantableTitles(): array
    {
        $rows = db()->fetchAll('SELECT DISTINCT p.title FROM products p WHERE ' . Catalog::wantable() . ' ORDER BY p.title');
        return array_column($rows, 'title');
    }

    /**
     * Find the catalogue product that prices a game the customer owns: exact (case-insensitive) title on the
     * same platform first, then a LIKE match on the cleaned title. The standard edition is preferred over a
     * steelbook; pending and hidden products are never used as a reference.
     */
    public static function findReference(int $platformId, string $title): ?array
    {
        $base = self::baseTitle($title);
        if (mb_strlen($base) < 2) {
            return null;
        }
        $cols = 'p.id, p.title, p.price, p.is_steelbook, p.slug';
        // digital goods are never a buy-back reference (and stay invisible while the master switch is off)
        $where = "p.platform_id = :plat AND p.status NOT IN ('pending','hidden') AND p.is_digital = 0" . Catalog::gate();
        $order = 'p.is_steelbook ASC, (p.edition = \'Standard\') DESC, (p.status = \'active\') DESC, p.id ASC';

        $row = db()->fetch(
            "SELECT $cols FROM products p WHERE $where AND p.title IN (:t1, :t2, :t3) ORDER BY $order LIMIT 1",
            ['plat' => $platformId, 't1' => $title, 't2' => $base, 't3' => $base . ' (Steelbook Edition)']
        );
        if ($row) {
            return $row;
        }
        if (mb_strlen($base) < 4) {
            return null;
        }
        return db()->fetch(
            "SELECT $cols FROM products p WHERE $where AND p.title LIKE :like ORDER BY p.is_steelbook ASC, CHAR_LENGTH(p.title) ASC, p.id ASC LIMIT 1",
            ['plat' => $platformId, 'like' => '%' . addcslashes($base, '%_\\') . '%']
        );
    }

    /**
     * Find a product the customer can buy (active + in stock). A slug is honoured only when the typed title
     * still matches that product, otherwise an exact title match, then the best LIKE match.
     */
    public static function findWanted(string $title, string $slug = ''): ?array
    {
        $cols = 'p.id, p.slug, p.title, p.price, pl.name AS platform_name, pl.slug AS platform_slug';
        $from = 'FROM products p LEFT JOIN platforms pl ON pl.id = p.platform_id WHERE ' . Catalog::wantable();

        if ($slug !== '') {
            $p = db()->fetch("SELECT $cols $from AND p.slug = :slug", ['slug' => $slug]);
            if ($p && (mb_strtolower($p['title']) === mb_strtolower($title) || mb_strtolower(self::baseTitle($p['title'])) === mb_strtolower(self::baseTitle($title)))) {
                return $p;
            }
        }
        $base = self::baseTitle($title);
        $row = db()->fetch(
            "SELECT $cols $from AND p.title IN (:t1, :t2, :t3) ORDER BY p.price ASC, p.id ASC LIMIT 1",
            ['t1' => $title, 't2' => $base, 't3' => $base . ' (Steelbook Edition)']
        );
        if ($row) {
            return $row;
        }
        if (mb_strlen($base) < 4) {
            return null;
        }
        return db()->fetch(
            "SELECT $cols $from AND p.title LIKE :like ORDER BY CHAR_LENGTH(p.title) ASC, p.price ASC, p.id ASC LIMIT 1",
            ['like' => '%' . addcslashes($base, '%_\\') . '%']
        );
    }

    // ---------- quotes ----------

    /**
     * Price every "game you give" row in BOTH currencies: cash (buy-back offer) and store credit (trade credit).
     * $mode ('sell' or 'trade') only decides which one is exposed as 'offer' / 'total' for the page headline.
     *
     * @param array<int,array{platform:string,title:string,condition:string,box?:bool,cover?:bool,manual?:bool}> $rows
     * @return array{lines:array,total:float,total_cash:float,total_credit:float,matched:int}
     */
    public static function priceGames(array $rows, string $mode): array
    {
        $platforms = self::platformsBySlug();
        $lines = [];
        $totalCash = 0.0;
        $totalCredit = 0.0;
        $matched = 0;
        foreach ($rows as $row) {
            $platform = $platforms[$row['platform']] ?? null;
            if ($platform === null) {
                continue;
            }
            $ref = self::findReference((int) $platform['id'], $row['title']);
            $cash = 0.0;
            $credit = 0.0;
            if ($ref) {
                $resale = (float) $ref['price'];
                $cash = Pricing::buybackOffer($resale, $row['condition']);
                $credit = Pricing::tradeCredit($resale, $row['condition']);
            }
            $isMatched = $ref !== null && ($cash > 0 || $credit > 0);
            if ($isMatched) {
                $totalCash += $cash;
                $totalCredit += $credit;
                $matched++;
            }
            $lines[] = [
                'title'         => $row['title'],
                'platform'      => (string) $platform['name'],
                'platform_slug' => (string) $platform['slug'],
                'condition'     => $row['condition'],
                'cash'          => $isMatched ? $cash : 0.0,
                'credit'        => $isMatched ? $credit : 0.0,
                'offer'         => $isMatched ? ($mode === 'trade' ? $credit : $cash) : 0.0,
                'matched'       => $isMatched,
                'matched_title' => $isMatched ? self::baseTitle((string) $ref['title']) : '',
                'includes'      => self::includesOf($row),
            ];
        }
        return [
            'lines'        => $lines,
            'total'        => $mode === 'trade' ? $totalCredit : $totalCash,
            'total_cash'   => $totalCash,
            'total_credit' => $totalCredit,
            'matched'      => $matched,
        ];
    }

    /**
     * @param array<int,array{title:string,slug:string}> $rows
     * @return array{lines:array,total:float,matched:int}
     */
    public static function priceWanted(array $rows): array
    {
        $lines = [];
        $total = 0.0;
        $matched = 0;
        $seen = [];
        foreach ($rows as $row) {
            $p = self::findWanted($row['title'], $row['slug']);
            if ($p && isset($seen[(int) $p['id']])) {
                continue; // the same copy twice: we only have one to give
            }
            if ($p) {
                $seen[(int) $p['id']] = true;
                $total += (float) $p['price'];
                $matched++;
            }
            $lines[] = [
                'title'    => $p ? (string) $p['title'] : $row['title'],
                'typed'    => $row['title'],
                'platform' => $p ? (string) ($p['platform_name'] ?? '') : '',
                'slug'     => $p ? (string) $p['slug'] : '',
                'price'    => $p ? (float) $p['price'] : 0.0,
                'matched'  => $p !== null,
            ];
        }
        return ['lines' => $lines, 'total' => $total, 'matched' => $matched];
    }

    /** The lines as stored in buyback_requests.items ('offer' = the value for the customer's preferred method). */
    public static function itemsForStorage(array $lines, string $method = 'credit'): array
    {
        return array_map(static function (array $l) use ($method): array {
            $inc = self::includesOf($l);
            return [
                'title'         => $l['title'],
                'platform'      => $l['platform'],
                'condition'     => $l['condition'],
                'cash'          => (float) $l['cash'],
                'credit'        => (float) $l['credit'],
                'offer'         => (float) ($method === 'cash' ? $l['cash'] : $l['credit']),
                'matched'       => (bool) $l['matched'],
                'matched_title' => $l['matched_title'],
                // what the customer says comes with the game: nested for readers of `includes`, flat for the simple ones
                'includes'      => $inc,
                'box'           => $inc['box'],
                'cover'         => $inc['cover'],
                'manual'        => $inc['manual'],
            ];
        }, $lines);
    }

    // ---------- details + saving ----------

    /**
     * Read the options a signed-in customer picks when sending a request. Name, phone and area come from the
     * account, never from the form.
     *
     * @return array{0:array{method:string,collection:string,pickup_note:string,note:string},1:string[]}
     */
    public static function readOptions(Request $r): array
    {
        $errors = [];
        $method = $r->input('preferred_method', 'credit');
        $method = is_string($method) ? $method : '';
        if (!in_array($method, ['cash', 'credit'], true)) {
            $errors[] = 'Please choose how you would like to be paid: cash or store credit.';
            $method = 'credit';
        }
        $collection = $r->input('collection', 'dropoff');
        $collection = is_string($collection) ? $collection : '';
        if (!in_array($collection, ['dropoff', 'pickup'], true)) {
            $errors[] = 'Please choose how we get your games: you drop them off, or we collect them.';
            $collection = 'dropoff';
        }
        $d = [
            'method'      => $method,
            'collection'  => $collection,
            'pickup_note' => $collection === 'pickup' ? self::clean($r->input('pickup_note'), 255) : '',
            'note'        => self::cleanNote($r->input('note')),
        ];
        return [$d, $errors];
    }

    /** Same rules as the checkout form (still used by the swap board). */
    /** @param string[]|null $areas allowed area names (default: the built-in list) */
    public static function validateContact(string $name, string $phone, string $area, ?array $areas = null): array
    {
        $errors = [];
        if (mb_strlen($name) < 2) {
            $errors[] = 'Please enter your name.';
        }
        $digits = strlen((string) preg_replace('/\D+/', '', $phone));
        if (!preg_match('/^[0-9+()\-\s]+$/', $phone) || $digits < 7 || $digits > 15) {
            $errors[] = 'Please enter a valid phone / WhatsApp number (7 to 15 digits).';
        }
        if (!in_array($area, $areas ?? CheckoutController::AREAS, true)) {
            $errors[] = 'Please choose your area.';
        }
        return $errors;
    }

    /** Unique code such as BB-7K2M9Q (no look-alike characters). */
    public static function uniqueCode(string $prefix, string $table): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        for ($try = 0; $try < 20; $try++) {
            $code = $prefix;
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            if (db()->fetchValue("SELECT 1 FROM $table WHERE code = :c", ['c' => $code]) === null) {
                return $code;
            }
        }
        throw new \RuntimeException('Could not generate a request code');
    }

    /**
     * Saves the request for a signed-in customer. Estimates are the sums over the matched rows.
     * @param array<int,array{kind:string,path:string}> $photos already-processed photos (ProductPhotos::stageLoose)
     */
    public static function saveRequest(string $kind, array $user, array $options, array $given, ?array $wanted, array $photos = []): string
    {
        $prefix = $kind === 'trade_in' ? 'TI-' : 'BB-';
        $code = self::uniqueCode($prefix, 'buyback_requests');
        $note = $options['note'] !== '' ? 'Customer note: ' . $options['note'] : null;
        $cash = (float) $given['total_cash'];
        $credit = (float) $given['total_credit'];
        $area = trim((string) ($user['area'] ?? ''));
        db()->insert(
            'INSERT INTO buyback_requests (code, user_id, name, phone, area, kind, items, offered_total, estimate_cash, estimate_credit,
                                            preferred_method, collection, pickup_note, wanted_items, photos, status, admin_note)
             VALUES (:code, :user, :name, :phone, :area, :kind, :items, :total, :cash, :credit, :method, :collection, :pickup, :wanted, :photos, \'new\', :note)',
            [
                'code'       => $code,
                'user'       => (int) $user['id'],
                'name'       => mb_substr((string) $user['name'], 0, 120),
                'phone'      => mb_substr((string) $user['phone'], 0, 40),
                'area'       => $area !== '' ? mb_substr($area, 0, 120) : null,
                'kind'       => $kind,
                'items'      => json_encode(self::itemsForStorage($given['lines'], $options['method']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'total'      => number_format($options['method'] === 'cash' ? $cash : $credit, 2, '.', ''),
                'cash'       => number_format($cash, 2, '.', ''),
                'credit'     => number_format($credit, 2, '.', ''),
                'method'     => $options['method'],
                'collection' => $options['collection'],
                'pickup'     => $options['pickup_note'] !== '' ? $options['pickup_note'] : null,
                'wanted'     => $wanted === null ? null : json_encode(array_map(static fn (array $l): array => [
                    'title'    => $l['title'],
                    'platform' => $l['platform'],
                    'price'    => (float) $l['price'],
                    'matched'  => (bool) $l['matched'],
                ], $wanted['lines']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'photos'     => $photos ? json_encode(array_map(static fn (array $p): array => ['kind' => (string) $p['kind'], 'path' => (string) $p['path']], array_values($photos)), JSON_UNESCAPED_SLASHES) : null,
                'note'       => $note,
            ]
        );
        return $code;
    }

    // ---------- abuse protection ----------

    /**
     * Session + IP counter: at most PER_HOUR submissions per hour. $phone additionally checks the database, so
     * clearing cookies does not reset the limit for the same phone number.
     */
    public static function throttled(string $bucket, string $ip, ?string $phone = null, string $table = 'buyback_requests'): bool
    {
        $hits = self::recentHits($bucket, $ip);
        if (count($hits) >= self::PER_HOUR) {
            return true;
        }
        if ($phone !== null && $phone !== '') {
            $n = (int) db()->fetchValue("SELECT COUNT(*) FROM $table WHERE phone = :p AND created_at > (NOW() - INTERVAL 1 HOUR)", ['p' => $phone]);
            if ($n >= self::PER_HOUR) {
                return true;
            }
        }
        return false;
    }

    public static function recordHit(string $bucket, string $ip): void
    {
        $session = app()->session();
        $all = $session->get('_rate', []);
        $all[$bucket][$ip] = array_merge(self::recentHits($bucket, $ip), [time()]);
        $session->put('_rate', $all);
    }

    /** @return int[] timestamps within the last hour */
    private static function recentHits(string $bucket, string $ip): array
    {
        $all = app()->session()->get('_rate', []);
        $hits = $all[$bucket][$ip] ?? [];
        return array_values(array_filter(is_array($hits) ? $hits : [], static fn ($t): bool => is_int($t) && $t > time() - 3600));
    }

    // ---------- presentation helpers ----------

    /** Data for the form partial: old input if we came back with errors, otherwise blank rows. */
    public static function formRows(): array
    {
        $plat = old('game_platform', []);
        $title = old('game_title', []);
        $cond = old('game_condition', []);
        $flags = [];
        foreach (self::INCLUDES as $field => $key) {
            $flags[$key] = self::flags(old($field, []));
        }
        $rows = [];
        for ($i = 0; $i < self::MAX_GIVE; $i++) {
            $rows[] = [
                'platform'  => is_array($plat) && is_string($plat[$i] ?? null) ? $plat[$i] : '',
                'title'     => is_array($title) && is_string($title[$i] ?? null) ? $title[$i] : '',
                'condition' => is_array($cond) && is_string($cond[$i] ?? null) ? $cond[$i] : 'Good',
                'box'       => isset($flags['box'][$i]),
                'cover'     => isset($flags['cover'][$i]),
                'manual'    => isset($flags['manual'][$i]),
            ];
        }
        return $rows;
    }

    /** Sanitised copy of the posted rows for $this->back(): arrays only, capped, no tokens. */
    public static function inputForBack(Request $r): array
    {
        $cap = static function (mixed $v, int $n): array {
            $out = [];
            foreach (array_slice(is_array($v) ? array_values($v) : [], 0, $n) as $x) {
                $out[] = is_string($x) ? mb_substr($x, 0, 400) : '';
            }
            return $out;
        };
        $flags = [];
        foreach (self::INCLUDES as $field => $key) {
            $flags[$field] = [];
            foreach (self::flags($r->input($field)) as $i => $_) {
                if ($i < self::MAX_GIVE) {
                    $flags[$field][$i] = '1';
                }
            }
        }
        return $flags + [
            'game_platform'  => $cap($r->input('game_platform'), self::MAX_GIVE),
            'game_title'     => $cap($r->input('game_title'), self::MAX_GIVE),
            'game_condition' => $cap($r->input('game_condition'), self::MAX_GIVE),
            'want_title'     => $cap($r->input('want_title'), self::MAX_WANT),
            'want_slug'      => $cap($r->input('want_slug'), self::MAX_WANT),
        ];
    }

    /** "Title (PS4, Good): cash $12 / credit $14" lines for a WhatsApp message. */
    public static function summaryLines(array $items): string
    {
        $out = [];
        foreach ($items as $i) {
            $cash = (float) ($i['cash'] ?? $i['offer'] ?? 0);
            $credit = (float) ($i['credit'] ?? $i['offer'] ?? 0);
            $price = !empty($i['matched']) ? 'cash ' . money($cash) . ' / credit ' . money($credit) : 'to be priced';
            $out[] = '- ' . $i['title'] . ' (' . $i['platform'] . ', ' . $i['condition'] . ', ' . self::includesText($i) . '): ' . $price;
        }
        return implode("\n", $out);
    }
}
