<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Core\Controller;
use App\Core\Request;

/**
 * Product feeds: /feeds/products.xml (Google Merchant Center RSS 2.0) and /feeds/products.json (for AI agents and aggregators).
 * Only products a customer can buy right now, never seller data or serial numbers.
 */
final class FeedController extends Controller
{
    public function xml(Request $request): void
    {
        $rows = SeoCatalog::feedProducts();
        $ship = number_format(SeoCatalog::cheapestFee(), 2, '.', '') . ' USD';
        $newest = SeoCatalog::lastUpdated();

        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n<channel>\n"
            . '<title>' . self::x(Seo::storeName()) . '</title>' . "\n"
            . '<link>' . self::x(url('/')) . '</link>' . "\n"
            . '<description>' . self::x((string) setting('tagline', 'Buy, sell & trade games and gaming gear in Lebanon')) . '</description>' . "\n";

        foreach ($rows as $p) {
            $cover = (string) ($p['image'] ?? '');
            $photos = $p['photos'];
            $main = $cover !== '' ? $cover : ($photos[0] ?? '');
            if ($main === '') {
                continue; // Merchant Center rejects items without an image
            }
            $extra = array_values(array_unique(array_filter($photos, static fn (string $x): bool => $x !== $main)));
            $title = self::title($p);
            $brand = self::brand($p);

            $out .= "<item>\n"
                . '<g:id>' . self::x('CG-' . $p['id']) . "</g:id>\n"
                . '<title>' . self::x(Seo::clip($title, 150)) . "</title>\n"
                . '<description>' . self::x(self::description($p)) . "</description>\n"
                . '<link>' . self::x(url('/product/' . $p['slug'])) . "</link>\n"
                . '<g:image_link>' . self::x(media($main)) . "</g:image_link>\n";
            foreach (array_slice($extra, 0, 10) as $x) {
                $out .= '<g:additional_image_link>' . self::x(media($x)) . "</g:additional_image_link>\n";
            }
            $out .= "<g:availability>in_stock</g:availability>\n"
                . '<g:price>' . number_format((float) $p['price'], 2, '.', '') . " USD</g:price>\n"
                . '<g:condition>' . (self::isNew($p) ? 'new' : 'used') . "</g:condition>\n";
            if ($brand !== '') {
                $out .= '<g:brand>' . self::x($brand) . "</g:brand>\n";
            }
            $out .= "<g:identifier_exists>no</g:identifier_exists>\n"
                . '<g:google_product_category>' . self::x(self::googleCategory($p)) . "</g:google_product_category>\n"
                . '<g:product_type>' . self::x(self::productType($p)) . "</g:product_type>\n"
                . '<g:shipping><g:country>LB</g:country><g:service>Standard</g:service><g:price>' . $ship . "</g:price></g:shipping>\n";
            if (!self::isNew($p)) {
                $out .= '<g:custom_label_0>' . self::x((string) $p['item_condition']) . "</g:custom_label_0>\n";
            }
            $out .= "</item>\n";
        }
        $out .= "</channel>\n</rss>\n";

        Cacheable::send($out, 'application/rss+xml; charset=utf-8', Cacheable::newest([$newest, (int) filemtime(__FILE__)]));
    }

    public function json(Request $request): void
    {
        $rows = SeoCatalog::feedProducts();
        $products = [];
        foreach ($rows as $p) {
            $cover = (string) ($p['image'] ?? '');
            $photos = array_map(static fn (string $x): string => media($x), $p['photos']);
            $images = array_values(array_unique(array_merge($cover !== '' ? [media($cover)] : [], $photos)));
            $specs = [];
            foreach (preg_split('/\R/', (string) $p['specs']) ?: [] as $line) {
                if (str_contains($line, ':')) {
                    [$k, $v] = array_map('trim', explode(':', $line, 2));
                    if ($k !== '' && $v !== '') {
                        $specs[$k] = $v;
                    }
                }
            }
            $included = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $p['included_items']) ?: [])));
            $item = [
                'id'              => 'CG-' . $p['id'],
                'title'           => (string) $p['title'],
                'url'             => url('/product/' . $p['slug']),
                'image'           => $images[0] ?? null,
                'images'          => $images,
                'price'           => (float) number_format((float) $p['price'], 2, '.', ''),
                'currency'        => 'USD',
                'condition'       => self::isNew($p) ? 'new' : 'used',
                'condition_grade' => (string) $p['item_condition'],
                'availability'    => 'in_stock',
                'platform'        => $p['platform_name'] !== null ? (string) $p['platform_name'] : null,
                'category'        => (string) $p['category_name'],
                'brand'           => self::brand($p) !== '' ? self::brand($p) : null,
                'model'           => trim((string) $p['model']) !== '' ? trim((string) $p['model']) : null,
                'genres'          => Ui::genres((string) $p['genres']),
                'edition'         => (int) $p['is_steelbook'] === 1 ? 'Steelbook' : (string) $p['edition'],
                'year'            => $p['year'] !== null ? (int) $p['year'] : null,
                'specs'           => (object) $specs,
                'included'        => $included,
                'warranty_months' => (int) $p['warranty_months'] > 0 ? (int) $p['warranty_months'] : null,
            ];
            $products[] = $item;
        }

        $last = Cacheable::newest([SeoCatalog::lastUpdated(), (int) filemtime(__FILE__)]);
        $doc = [
            'updated_at' => date('c', $last),
            'store' => [
                'name'        => Seo::storeName(),
                'url'         => url('/'),
                'country'     => 'LB',
                'currency'    => 'USD',
                'language'    => 'en',
                'description' => 'Online marketplace in Lebanon to buy, sell, trade and swap used and new video games and gaming gear. Every item is inspected before delivery.',
                'policies'    => self::policies(),
                'links'       => [
                    'sitemap'  => url('/sitemap.xml'),
                    'llms'     => url('/llms.txt'),
                    'llms_full' => url('/llms-full.txt'),
                    'merchant_feed' => url('/feeds/products.xml'),
                ],
            ],
            'product_count' => count($products),
            'products'      => $products,
        ];
        $json = (string) json_encode($doc, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION);
        Cacheable::send($json, 'application/json; charset=utf-8', $last);
    }

    /** @return array<string,string> policies quoted by the JSON feed and llms-full.txt, all numbers from settings */
    public static function policies(): array
    {
        $prepay = Rules::prepayOn();
        $after = Rules::codAfter();
        $local = Rules::nameList(Rules::names('local'));
        $remote = Rules::nameList(Rules::names('remote'));
        return [
            'delivery'   => 'Delivery across Lebanon with a flat fee per area. Local areas (' . $local . ') are served by our own courier for ' . Rules::feeRange('local')
                . '. Remote areas (' . ($remote !== '' ? $remote : 'everywhere else') . ') are served by a third-party courier for ' . (Rules::feeRange('remote') ?: money(SeoCatalog::cheapestFee()))
                . '. Pickup at our hub is also possible.',
            'payment'    => 'Prices are in US dollars. Local areas: cash on delivery, store credit, OMT or Whish. Remote areas: '
                . ($prepay ? 'prepayment by OMT or Whish' . ($after > 0 ? ' (cash on delivery unlocks after ' . $after . ' delivered orders)' : '') : 'cash on delivery, OMT or Whish')
                . '. Store credit (1 credit = 1 USD, no expiry) can be applied at checkout. No card payments are taken on the site.',
            'inspection' => 'Every item is inspected before it is listed and before it is delivered. Used items carry a condition grade (Like New, Good, Fair). Remote orders are photographed and sealed at our hub because the courier cannot inspect.',
            'returns'    => 'If an item is not as described, contact us on WhatsApp straight away and we will make it right. In local areas the courier lets you check the item at the door.',
            'privacy'    => 'Buyers and sellers never see each other and their names, phone numbers and addresses are never shared. Only an anonymous ID is ever visible.',
        ];
    }

    // ------------------------------------------------------------------ helpers

    private static function x(string $s): string
    {
        $s = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function isNew(array $p): bool
    {
        return (int) $p['is_digital'] === 1 || (string) $p['item_condition'] === 'New';
    }

    private static function brand(array $p): string
    {
        $b = trim((string) $p['brand']);
        if ($b !== '') {
            return $b;
        }
        return (string) Seo::platformMaker((string) $p['platform_slug']);
    }

    private static function title(array $p): string
    {
        $t = (string) $p['title'];
        $short = Ui::shortPlatform($p['platform_slug'], $p['platform_name']);
        if ($short !== '' && stripos($t, $short) === false) {
            $t .= ' (' . $short . ')';
        }
        return $t;
    }

    private static function description(array $p): string
    {
        $d = trim(strip_tags((string) $p['description']));
        if ($d === '') {
            $short = Ui::shortPlatform($p['platform_slug'], $p['platform_name']);
            $d = $p['title'] . ($short !== '' ? " for $short" : '') . ': ' . (self::isNew($p) ? 'new' : 'used, ' . $p['item_condition'] . ' condition') . '. Inspected before sale.';
        }
        $d = (string) preg_replace('/\s+/', ' ', $d);
        return mb_substr($d, 0, 4900);
    }

    private static function googleCategory(array $p): string
    {
        $slug = (string) $p['category_slug'];
        return match (true) {
            $slug === 'games' => 'Software > Video Game Software',
            $slug === 'consoles' => 'Electronics > Video Game Consoles',
            $slug === 'controllers' || $slug === 'accessories' => 'Electronics > Video Game Console Accessories',
            $slug === 'keyboards' => 'Electronics > Electronics Accessories > Computer Components > Input Devices > Keyboards',
            $slug === 'mice' => 'Electronics > Electronics Accessories > Computer Components > Input Devices > Mice & Trackballs',
            $slug === 'mousepads' => 'Electronics > Electronics Accessories > Computer Components > Input Devices > Mouse Pads',
            $slug === 'headsets' => 'Electronics > Audio > Audio Components > Headphones & Headsets > Headsets',
            (int) $p['is_digital'] === 1 || $slug === Catalog::GIFT_SLUG => 'Arts & Entertainment > Party & Celebration > Gift Giving > Gift Cards & Certificates',
            $p['category_kind'] === 'game' => 'Software > Video Game Software',
            default => 'Electronics > Video Game Console Accessories',
        };
    }

    private static function productType(array $p): string
    {
        $parts = [(string) $p['category_name']];
        if ($p['platform_name'] !== null && $p['platform_name'] !== '') {
            $parts[] = (string) $p['platform_name'];
        }
        $genres = Ui::genres((string) $p['genres']);
        if ($genres) {
            $parts[] = $genres[0];
        }
        return implode(' > ', $parts);
    }
}
