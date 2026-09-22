<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Support\SafeHtml;
use App\Support\Tokens;

/** Guides = rows of the articles table. Only published rows (is_published = 1 and published_at in the past) are ever public. */
final class Guides
{
    public const PER_PAGE = 9;

    /** tag => label */
    public const TAGS = [
        'selling'     => 'Selling',
        'buying'      => 'Buying',
        'consoles'    => 'Consoles',
        'peripherals' => 'PC peripherals',
        'credit'      => 'Store credit',
        'delivery'    => 'Delivery',
        'privacy'     => 'Privacy',
        'collectors'  => 'Collectors',
    ];

    private const COLS = 'a.id, a.slug, a.title, a.excerpt, a.body, a.meta_title, a.meta_description, a.tag, a.author, a.is_published, a.published_at, a.created_at, a.updated_at';

    public static function tagLabel(?string $tag): string
    {
        $tag = (string) $tag;
        return self::TAGS[$tag] ?? ($tag !== '' ? ucfirst($tag) : '');
    }

    /** SQL condition for "public right now" (alias a). */
    public static function published(): string
    {
        return 'a.is_published = 1 AND a.published_at IS NOT NULL AND a.published_at <= NOW()';
    }

    /** @return array{items:array,total:int,pages:int,page:int} */
    public static function paginate(string $tag, int $page, int $per = self::PER_PAGE): array
    {
        $where = self::published();
        $params = [];
        if ($tag !== '') {
            $where .= ' AND a.tag = :tag';
            $params['tag'] = $tag;
        }
        $total = (int) db()->fetchValue("SELECT COUNT(*) FROM articles a WHERE $where", $params);
        $pages = max(1, (int) ceil($total / $per));
        $page = max(1, $page);
        $items = [];
        if ($page <= $pages) {
            $items = db()->fetchAll(
                'SELECT ' . self::COLS . " FROM articles a WHERE $where ORDER BY a.published_at DESC, a.id DESC LIMIT " . (int) $per . ' OFFSET ' . (int) (($page - 1) * $per),
                $params
            );
        }
        return ['items' => $items, 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    public static function latest(int $limit = 3): array
    {
        return db()->fetchAll('SELECT ' . self::COLS . ' FROM articles a WHERE ' . self::published() . ' ORDER BY a.published_at DESC, a.id DESC LIMIT ' . (int) $limit);
    }

    /** @return array<string,int> tag => number of published guides */
    public static function tagCounts(): array
    {
        $out = [];
        foreach (db()->fetchAll('SELECT a.tag, COUNT(*) AS n FROM articles a WHERE ' . self::published() . " AND a.tag IS NOT NULL AND a.tag <> '' GROUP BY a.tag ORDER BY n DESC, a.tag") as $r) {
            $out[(string) $r['tag']] = (int) $r['n'];
        }
        return $out;
    }

    public static function bySlug(string $slug, bool $preview = false): ?array
    {
        return db()->fetch(
            'SELECT ' . self::COLS . ' FROM articles a WHERE a.slug = :slug' . ($preview ? '' : ' AND ' . self::published()),
            ['slug' => $slug]
        );
    }

    /** Other published guides: same tag first, then the newest. */
    public static function related(array $article, int $limit = 3): array
    {
        return db()->fetchAll(
            'SELECT ' . self::COLS . ' FROM articles a WHERE ' . self::published() . ' AND a.id <> :id
              ORDER BY (a.tag = :tag) DESC, a.published_at DESC, a.id DESC LIMIT ' . (int) $limit,
            ['id' => (int) $article['id'], 'tag' => (string) ($article['tag'] ?? '')]
        );
    }

    /** Guides that share a tag (for collection pages). */
    public static function forTag(string $tag, int $limit = 3): array
    {
        return db()->fetchAll(
            'SELECT ' . self::COLS . ' FROM articles a WHERE ' . self::published() . ' ORDER BY (a.tag = :tag) DESC, a.published_at DESC, a.id DESC LIMIT ' . (int) $limit,
            ['tag' => $tag]
        );
    }

    /** Text shown for the article: tokens replaced. */
    public static function text(?string $value): string
    {
        return Tokens::replace((string) $value);
    }

    /**
     * Body HTML: tokens replaced, allow-list cleaned, then <h2>/<h3> get ids we generate ourselves (never taken from the author).
     * @return array{html:string,toc:array<int,array{level:int,id:string,text:string}>}
     */
    public static function renderBody(string $body): array
    {
        $html = SafeHtml::clean(Tokens::replace($body));
        if ($html === '') {
            return ['html' => '', 'toc' => []];
        }
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="g-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $root = $doc->getElementById('g-root');
        if (!$root) {
            return ['html' => $html, 'toc' => []];
        }
        $toc = [];
        $used = [];
        foreach ($doc->getElementsByTagName('a') as $link) {
            // root-relative links ("/sell") become full URLs so they also work when the site lives in a sub-folder
            $href = $link->getAttribute('href');
            if ($href !== '' && $href[0] === '/' && !str_starts_with($href, '//')) {
                $link->setAttribute('href', url($href));
            }
        }
        foreach ($doc->getElementsByTagName('*') as $node) {
            $tag = strtolower($node->tagName);
            if ($tag !== 'h2' && $tag !== 'h3') {
                continue;
            }
            $text = trim(preg_replace('/\s+/', ' ', $node->textContent) ?? '');
            if ($text === '') {
                continue;
            }
            $id = slugify($text);
            $base = $id;
            for ($i = 2; isset($used[$id]); $i++) {
                $id = $base . '-' . $i;
            }
            $used[$id] = true;
            $node->setAttribute('id', $id);
            $toc[] = ['level' => (int) substr($tag, 1), 'id' => $id, 'text' => $text];
        }
        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
        return ['html' => trim($out), 'toc' => $toc];
    }

    /**
     * Shop links related to a guide's tag. Only shop pages that have products (and useful pages) are offered.
     * @return array<int,array{0:string,1:string}> [label, path]
     */
    public static function shopLinks(string $tag): array
    {
        $links = [];
        $cats = [];
        foreach (Catalog::categories() as $c) {
            if ((int) $c['product_count'] > 0) {
                $cats[$c['slug']] = $c;
            }
        }
        $platforms = array_values(array_filter(Catalog::platforms(), static fn (array $p): bool => (int) $p['product_count'] > 0));
        $active = Collections::active();
        $coll = static function (string ...$slugs) use ($active, &$links): void {
            foreach ($slugs as $s) {
                if (isset($active[$s])) {
                    $links[] = [$active[$s]['h1'], '/collections/' . $s];
                }
            }
        };
        switch ($tag) {
            case 'selling':
                $links[] = ['Sell your games for cash or credit', '/sell'];
                $links[] = ['Trade in for store credit', '/trade'];
                $links[] = ['How store credit works', '/credit'];
                break;
            case 'credit':
                $links[] = ['Trade in your games', '/trade'];
                $links[] = ['Sell your games', '/sell'];
                $links[] = ['Shop with your credit', '/shop'];
                break;
            case 'buying':
                if (isset($cats['games'])) {
                    $links[] = ['Shop games', '/shop/games'];
                }
                foreach (array_slice($platforms, 0, 2) as $p) {
                    $links[] = [$p['name'] . ' games and gear', '/platform/' . $p['slug']];
                }
                $coll('ps4-games-under-15', 'new-arrivals');
                break;
            case 'consoles':
                foreach (['consoles' => 'Consoles', 'controllers' => 'Controllers'] as $slug => $label) {
                    if (isset($cats[$slug])) {
                        $links[] = [$label, '/shop/' . $slug];
                    }
                }
                foreach (array_slice($platforms, 0, 2) as $p) {
                    $links[] = [$p['name'] . ' games and gear', '/platform/' . $p['slug']];
                }
                break;
            case 'peripherals':
                foreach (['keyboards' => 'Keyboards', 'mice' => 'Mice', 'mousepads' => 'Mousepads', 'headsets' => 'Headsets'] as $slug => $label) {
                    if (isset($cats[$slug])) {
                        $links[] = [$label, '/shop/' . $slug];
                    }
                }
                $coll('gaming-keyboards', 'gaming-mice', 'mousepads', 'gaming-headsets');
                break;
            case 'collectors':
                $coll('ps4-steelbook-editions');
                $links[] = ['All steelbook editions in the shop', '/shop?edition=steelbook'];
                break;
            case 'delivery':
                $links[] = ['Delivery and payment', '/delivery-and-payment'];
                foreach (array_slice(SeoCatalog::zones(), 0, 3) as $z) {
                    $links[] = ['Delivery to ' . $z['short'], '/delivery-to/' . $z['slug']];
                }
                break;
            case 'privacy':
                $links[] = ['How CyberGaming works', '/how-it-works'];
                $links[] = ['Swap board', '/swap'];
                $links[] = ['Store credit', '/credit'];
                break;
        }
        if (!$links) {
            $links[] = ['Shop all products', '/shop'];
            $links[] = ['How it works', '/how-it-works'];
        }
        return array_slice($links, 0, 6);
    }
}
