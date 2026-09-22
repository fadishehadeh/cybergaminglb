<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

final class CatalogController extends Controller
{
    private const PER_PAGE = 24;

    public function shop(Request $request): void
    {
        $this->listing($request, null, null);
    }

    public function category(Request $request, string $category): void
    {
        $this->listing($request, null, Catalog::categoryBySlug($category) ?? Response::abort(404));
    }

    public function platform(Request $request, string $platform): void
    {
        $this->listing($request, Catalog::platformBySlug($platform) ?? Response::abort(404), null);
    }

    public function platformCategory(Request $request, string $platform, string $category): void
    {
        $this->listing(
            $request,
            Catalog::platformBySlug($platform) ?? Response::abort(404),
            Catalog::categoryBySlug($category) ?? Response::abort(404)
        );
    }

    private static function str(mixed $v, int $max = 80): string
    {
        return is_string($v) ? mb_substr(trim($v), 0, $max) : '';
    }

    private function listing(Request $request, ?array $platform, ?array $category): void
    {
        $platformId = $platform ? (int) $platform['id'] : null;
        $categoryId = $category ? (int) $category['id'] : null;
        $isHardware = $category !== null && ($category['kind'] ?? '') === 'hardware';

        // Category pages (not already narrowed to a platform) offer a platform filter; hardware also gets a brand filter.
        // Both facets are built from the data: only platforms / brands that have purchasable products in this scope.
        $platformChoices = $category && !$platform && $isHardware ? Catalog::platformsIn((int) $category['id']) : [];
        $platformParam = self::str($request->query('platform'));
        $filterPlatform = null;
        foreach ($platformChoices as $pc) {
            if ($pc['slug'] === $platformParam) {
                $filterPlatform = $pc;
            }
        }
        $scopePlatformId = $platformId ?? ($filterPlatform ? (int) $filterPlatform['id'] : null);
        $brands = $isHardware ? Catalog::brands($scopePlatformId, $categoryId) : [];
        $brandNames = array_column($brands, 'brand');
        $genres = $isHardware ? [] : Catalog::genres($platformId, $categoryId);

        $filters = [
            'q'       => self::str($request->query('q')),
            'genre'   => in_array(self::str($request->query('genre')), $genres, true) ? self::str($request->query('genre')) : '',
            'edition' => self::str($request->query('edition')) === 'steelbook' ? 'steelbook' : '',
            'cond'    => in_array(self::str($request->query('cond')), ['new', 'used'], true) ? self::str($request->query('cond')) : '',
            'brand'   => in_array(self::str($request->query('brand')), $brandNames, true) ? self::str($request->query('brand')) : '',
            'platform' => $filterPlatform ? (string) $filterPlatform['slug'] : '',
            'sort'    => array_key_exists(self::str($request->query('sort')), Catalog::SORTS) ? self::str($request->query('sort')) : 'newest',
        ];
        $rawPage = $request->query('page', 1);
        $page = is_string($rawPage) && ctype_digit($rawPage) ? max(1, (int) $rawPage) : 1;

        $result = Catalog::listing($scopePlatformId, $categoryId, $filters, $page, self::PER_PAGE);
        if ($page > 1 && $page > $result['pages']) {
            Response::abort(404);
        }

        // base path + copy
        $short = $platform ? Ui::shortPlatform($platform['slug'], $platform['name']) : '';
        $isGames = $category && $category['slug'] === 'games';
        $isGift = $category && $category['slug'] === Catalog::GIFT_SLUG;
        if ($platform && $category) {
            $basePath = '/platform/' . $platform['slug'] . '/' . $category['slug'];
        } elseif ($platform) {
            $basePath = '/platform/' . $platform['slug'];
        } elseif ($category) {
            $basePath = '/shop/' . $category['slug'];
        } else {
            $basePath = '/shop';
        }
        $summary = Catalog::summary($platformId, $categoryId);
        $copy = $isGift ? $this->giftCopy($platform, $category, $short, $summary) : $this->copy($platform, $category, $short, $isGames, $summary, $isHardware);

        $filtered = $filters['q'] !== '' || $filters['genre'] !== '' || $filters['edition'] !== '' || $filters['cond'] !== ''
            || $filters['brand'] !== '' || $filters['platform'] !== '';
        $noindex = $filtered || $result['total'] === 0;
        $activeQuery = array_filter(
            ['q' => $filters['q'], 'genre' => $filters['genre'], 'edition' => $filters['edition'], 'cond' => $filters['cond'], 'brand' => $filters['brand'], 'platform' => $filters['platform'], 'sort' => $filters['sort'] !== 'newest' ? $filters['sort'] : ''],
            static fn (string $v): bool => $v !== ''
        );

        $h1 = $copy['h1'];
        $title = $copy['title'];
        if ($filters['q'] !== '') {
            $h1 = 'Results for “' . $filters['q'] . '”';
            $title = 'Search: ' . $filters['q'] . ' | ' . ($category['name'] ?? $short ?: 'Shop');
        }
        if ($page > 1) {
            $title .= ' – Page ' . $page;
        }

        if ($platform && $category) {
            $crumbs = [['Home', '/'], [$short, '/platform/' . $platform['slug']], [$category['name'], null]];
        } elseif ($platform) {
            $crumbs = [['Home', '/'], [$platform['name'], null]];
        } elseif ($category) {
            $crumbs = [['Home', '/'], ['Shop', '/shop'], [$category['name'], null]];
        } else {
            $crumbs = [['Home', '/'], ['Shop', null]];
        }

        $canonical = url($basePath) . ($page > 1 && !$filtered ? '?page=' . $page : '');
        $meta = [
            'title'       => $title . ' | CyberGaming',
            'description' => Seo::clip($copy['description'] . ($page > 1 ? " Page $page." : '')),
            'canonical'   => $canonical,
            'noindex'     => $noindex,
            'jsonld'      => [Seo::breadcrumbs($crumbs)],
        ];
        if (!$filtered && $page > 1) {
            $meta['prev'] = url($basePath) . ($page > 2 ? '?page=' . ($page - 1) : '');
        }
        if (!$filtered && $page < $result['pages']) {
            $meta['next'] = url($basePath) . '?page=' . ($page + 1);
        }

        $this->render('site/catalog', [
            'meta'       => $meta,
            'nav'        => 'shop',
            'h1'         => $h1,
            'intro'      => $filtered ? '' : $copy['intro'],
            'seoBlock'   => $filtered || $page > 1 ? [] : $copy['block'],
            'crumbs'     => $crumbs,
            'result'     => $result,
            'filters'    => $filters,
            'genres'     => $genres,
            'basePath'   => $basePath,
            'pageQuery'  => $activeQuery,
            'filtered'   => $filtered,
            'platform'   => $platform,
            'category'   => $category,
            'platforms'  => Catalog::platforms(),
            'categories' => Catalog::categories(),
            // navigation pills only offer combinations that have stock (no dead-end empty pages)
            'pillPlatforms'  => $platform ? [] : ($category ? Catalog::platformsIn((int) $category['id']) : Catalog::platforms()),
            'pillCategories' => $category ? [] : ($platform ? Catalog::categoriesOn((int) $platform['id']) : Catalog::categories()),
            'isHardware' => $isHardware,
            'brands'     => $brands,
            'platformChoices' => $platformChoices,
            'perPage'    => self::PER_PAGE,
            'isGift'     => $isGift,
        ]);
    }

    /**
     * Copy for the gift-cards category (only reachable while the digital master switch is on). The category's own
     * seo_* / intro fields win when the owner filled them in; otherwise these defaults apply.
     * @return array{h1:string,title:string,description:string,intro:string,block:array}
     */
    private function giftCopy(?array $platform, array $category, string $short, array $summary): array
    {
        $n = (int) $summary['n'];
        $from = $summary['min_price'] !== null ? ' from ' . money($summary['min_price']) : '';
        $delivery = ' Codes are sent on WhatsApp after you pay by OMT or Whish.';
        $block = [
            'How gift card delivery works' => 'Order online, pay by OMT or Whish, and we send your code on WhatsApp once the payment is confirmed, usually within minutes during opening hours. Digital sales are final once the code is delivered.',
            'Check the region before you buy' => 'Gift cards are region-locked: a US card only works on US accounts. The region is shown on every card, so match it to your account before you order.',
        ];
        if ($platform) {
            $what = $short . ' gift cards';
            return [
                'h1'          => "$short gift cards in Lebanon",
                'title'       => "$short Gift Cards & Digital Codes in Lebanon",
                'description' => "Buy $what in Lebanon from CyberGaming: $n " . ($n === 1 ? 'card' : 'cards') . " available$from." . $delivery,
                'intro'       => "Digital $what for your {$platform['name']} account, priced in US dollars. Pay by OMT or Whish and we send the code on WhatsApp.",
                'block'       => $block,
            ];
        }
        $desc = trim((string) ($category['seo_description'] ?? ''));
        $intro = trim((string) ($category['intro_text'] ?? ''));
        $seoTitle = trim((string) ($category['seo_title'] ?? ''));
        return [
            'h1'          => $seoTitle !== '' ? $seoTitle : 'Gift cards & digital codes in Lebanon',
            'title'       => $seoTitle !== '' ? $seoTitle : 'PlayStation, Xbox, Nintendo & Steam Gift Cards in Lebanon',
            'description' => $desc !== '' ? $desc : "Buy PSN, Xbox, Nintendo eShop and Steam gift cards in Lebanon: $n " . ($n === 1 ? 'item' : 'items') . " available$from." . $delivery,
            'intro'       => $intro !== '' ? $intro : 'PlayStation Store, Xbox, Nintendo eShop and Steam cards and gifts, delivered as a code on WhatsApp. Pay by OMT or Whish, no cash on delivery.',
            'block'       => $block,
        ];
    }

    /** SEO text blocks for hardware categories (keyboards, mice, ...). */
    private static function hardwareBlock(string $name): array
    {
        $cat = strtolower($name);
        return [
            "Buying $cat in Lebanon" => 'Every used item is tested by us before delivery, and new items come sealed. Prices are in US dollars, and we ship across Lebanon: pay cash on delivery, by OMT or by Whish.',
            'Brand, condition and warranty' => 'Filter by brand and condition. Used items are graded Like New, Good or Fair, and any warranty is shown on the product page.',
        ];
    }

    /** @return array{h1:string,title:string,description:string,intro:string,block:array} */
    private function copy(?array $platform, ?array $category, string $short, bool $isGames, array $summary, bool $isHw = false): array
    {
        $n = (int) $summary['n'];
        $from = $summary['min_price'] !== null ? ' from ' . money($summary['min_price']) : '';
        $delivery = ' Delivery across Lebanon, pay cash on delivery, OMT or Whish.';

        if ($platform && $category && $isHw) {
            $cat = strtolower($category['name']);
            return [
                'h1'          => "$short {$category['name']} in Lebanon",
                'title'       => "$short {$category['name']} in Lebanon",
                'description' => "Buy $short $cat in Lebanon from CyberGaming: $n tested " . ($n === 1 ? 'item' : 'items') . " in stock$from." . $delivery,
                'intro'       => "Browse $n $short $cat in stock, new and used, each one tested before delivery. Prices are in US dollars, and we confirm every order on WhatsApp.",
                'block'       => self::hardwareBlock($category['name']),
            ];
        }
        if ($platform && $category) {
            $what = $isGames ? "used $short games" : "$short " . strtolower($category['name']);
            $title = $isGames ? "Used $short Games in Lebanon" : "$short {$category['name']} in Lebanon";
            return [
                'h1'          => $isGames ? "Used $short games in Lebanon" : "$short {$category['name']} in Lebanon",
                'title'       => $title,
                'description' => "Buy $what in Lebanon from CyberGaming: $n inspected " . ($n === 1 ? 'item' : 'items') . " in stock$from." . $delivery,
                'intro'       => "Browse $n $what in stock, each one inspected before sale. Prices are in US dollars, and we confirm every order on WhatsApp before we deliver.",
                'block'       => [
                    "Why buy $what from CyberGaming?" => "Every item is checked by our team before it is listed, so what you see is what you get. We ship across Lebanon, and you can pay cash on delivery, by OMT or by Whish.",
                    "Sell or trade your $short " . ($isGames ? 'games' : 'gear') => 'Have items you no longer use? We buy used games for cash or store credit, and we can swap titles between players.',
                ],
            ];
        }
        if ($platform) {
            return [
                'h1'          => "Used {$platform['name']} ($short) games & gaming gear in Lebanon",
                'title'       => "Used $short Games & Gaming Gear in Lebanon",
                'description' => "Buy used $short games and gaming gear in Lebanon from CyberGaming: $n inspected items in stock$from." . $delivery,
                'intro'       => "Everything we have in stock for the {$platform['name']}: $n inspected items, priced in US dollars. Order online and we confirm on WhatsApp.",
                'block'       => [
                    "Buying {$platform['name']} games in Lebanon" => "CyberGaming sells inspected used and new {$platform['name']} titles, including collectable steelbook editions, with delivery across Lebanon or pickup at our hub. Pay cash on delivery, by OMT or by Whish.",
                ],
            ];
        }
        if ($category) {
            $desc = trim((string) ($category['seo_description'] ?? ''));
            return [
                'h1'          => (string) ($category['seo_title'] ?: $category['name'] . ' in Lebanon'),
                'title'       => (string) ($category['seo_title'] ?: $category['name'] . ' in Lebanon'),
                'description' => $desc !== '' ? $desc : "Buy {$category['name']} in Lebanon from CyberGaming: $n items in stock$from." . $delivery,
                'intro'       => trim((string) ($category['intro_text'] ?? '')),
                'block'       => $isHw ? self::hardwareBlock($category['name']) : [],
            ];
        }
        return [
            'h1'          => 'Shop used games & gaming gear',
            'title'       => 'Shop Used Games & Gaming Gear in Lebanon',
            'description' => "Browse $n inspected used and new games and gaming gear in Lebanon$from. Steelbooks, PS4, PS5, Switch and Xbox titles with delivery across Lebanon.",
            'intro'       => "Every item here has been inspected by our team. Filter by genre or edition, or search for a title.",
            'block'       => [],
        ];
    }
}
