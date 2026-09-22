<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Core\Controller;
use App\Core\Request;

/** robots.txt and sitemap.xml. Both are sent with public caching headers and honour conditional requests (see Cacheable). */
final class SeoController extends Controller
{
    /** Crawlers that collect content to train models. */
    public const TRAINING_BOTS = ['GPTBot', 'ClaudeBot', 'Google-Extended', 'CCBot', 'Applebot-Extended', 'Amazonbot'];
    /** Crawlers that fetch pages to answer a user's question or to build an AI search index. */
    public const SEARCH_BOTS = ['OAI-SearchBot', 'ChatGPT-User', 'Claude-SearchBot', 'Claude-User', 'PerplexityBot', 'Perplexity-User'];

    /** Private or transactional areas: never crawled by anyone. */
    private const PRIVATE_RULES = [
        'Disallow: /admin',
        'Allow: /seller/apply$',
        'Disallow: /seller/',
        'Disallow: /account/',
        'Allow: /account/register$',
        'Allow: /account/login$',
        'Disallow: /cart',
        'Disallow: /checkout',
        'Disallow: /order/',
        'Disallow: /sell/thanks/',
        'Disallow: /trade/thanks/',
        'Disallow: /swap/thanks/',
    ];

    public function robots(Request $request): void
    {
        $allowAi = (string) setting('seo_ai_crawlers_allowed', '1') === '1';
        $lines = ['User-agent: *', 'Allow: /', ...self::PRIVATE_RULES, ''];

        $lines[] = '# AI assistants: a plain-text summary of this site is at ' . url('/llms.txt') . ' and a fuller one at ' . url('/llms-full.txt');
        $lines[] = '# Product feeds: ' . url('/feeds/products.xml') . ' (Merchant Center RSS) and ' . url('/feeds/products.json') . ' (JSON)';
        $lines[] = '';
        if ($allowAi) {
            $lines[] = '# AI search and assistant crawlers are welcome (setting: seo_ai_crawlers_allowed = 1)';
            foreach (array_merge(self::SEARCH_BOTS, self::TRAINING_BOTS) as $bot) {
                $lines[] = 'User-agent: ' . $bot;
            }
            $lines[] = 'Allow: /';
            array_push($lines, ...self::PRIVATE_RULES);
            $lines[] = '';
        } else {
            $lines[] = '# AI training crawlers are not allowed (setting: seo_ai_crawlers_allowed = 0); AI search and user-triggered fetchers still are';
            foreach (self::TRAINING_BOTS as $bot) {
                $lines[] = 'User-agent: ' . $bot;
            }
            $lines[] = 'Disallow: /';
            $lines[] = '';
            foreach (self::SEARCH_BOTS as $bot) {
                $lines[] = 'User-agent: ' . $bot;
            }
            $lines[] = 'Allow: /';
            array_push($lines, ...self::PRIVATE_RULES);
            $lines[] = '';
        }
        $lines[] = 'Sitemap: ' . url('/sitemap.xml');
        $lines[] = '';

        Cacheable::send(
            implode("\n", $lines),
            'text/plain; charset=utf-8',
            Cacheable::newest([(int) filemtime(__FILE__)])
        );
    }

    public function sitemap(Request $request): void
    {
        $urls = [];
        $newest = SeoCatalog::lastUpdated();
        $add = static function (string $path, $lastmod, string $freq, string $priority, array $images = []) use (&$urls): void {
            $t = $lastmod === null || $lastmod === '' ? 0 : (is_int($lastmod) ? $lastmod : (int) strtotime((string) $lastmod));
            $urls[] = ['loc' => url($path), 'ts' => $t, 'freq' => $freq, 'prio' => $priority, 'images' => $images];
        };
        $view = static fn (string $v): int => SeoCatalog::viewMtime($v);

        $add('/', Cacheable::newest([$newest, $view('site/home.php')]), 'daily', '1.0');
        $add('/shop', $newest, 'daily', '0.9');

        $catMods = SeoCatalog::categoryLastmods();
        foreach (Catalog::categories() as $c) {
            if ((int) $c['product_count'] > 0) {
                $add('/shop/' . $c['slug'], $catMods[$c['slug']] ?? $newest, 'daily', '0.8');
            }
        }
        $platMods = SeoCatalog::platformLastmods();
        foreach (Catalog::platforms() as $p) {
            if ((int) $p['product_count'] > 0) {
                $add('/platform/' . $p['slug'], $platMods[$p['slug']] ?? $newest, 'daily', '0.8');
            }
        }
        foreach (Catalog::sitemapCombos() as $c) {
            $add('/platform/' . $c['platform'] . '/' . $c['category'], $c['updated_at'], 'daily', '0.8');
        }

        // collections with enough products (thin ones are noindex and stay out)
        $active = Collections::active();
        if ($active) {
            $add('/collections', $newest, 'daily', '0.7');
        }
        foreach ($active as $slug => $d) {
            $add('/collections/' . $slug, $newest, 'daily', '0.7');
        }

        // information pages: last modified = when the page template last changed
        $static = [
            'sell' => ['sell/index.php', '0.7'], 'trade' => ['trade/index.php', '0.7'], 'credit' => ['pages/credit.php', '0.7'],
            'swap' => ['swap/index.php', '0.6'], 'how-it-works' => ['pages/how-it-works.php', '0.6'], 'seller/apply' => [null, '0.6'],
            'account/register' => [null, '0.4'], 'delivery-and-payment' => ['pages/delivery.php', '0.6'], 'about' => ['pages/about.php', '0.4'], 'contact' => ['pages/contact.php', '0.4'],
        ];
        foreach ($static as $slug => [$file, $prio]) {
            $add('/' . $slug, $file !== null ? $view('site/' . $file) : null, 'monthly', $prio);
        }

        // delivery zone pages
        $zoneView = $view('site/zone/show.php');
        foreach (SeoCatalog::zones() as $z) {
            $add('/delivery-to/' . $z['slug'], $zoneView, 'monthly', '0.5');
        }

        // guides
        $guides = Guides::paginate('', 1, 1000)['items'];
        if ($guides) {
            $add('/guides', Cacheable::newest(array_merge(array_column($guides, 'updated_at'), [$view('site/guides/index.php')])), 'weekly', '0.7');
            foreach ($guides as $g) {
                $add('/guides/' . $g['slug'], Cacheable::newest([$g['updated_at'], $g['published_at']]), 'monthly', '0.7');
            }
        }

        // products with their images (cover + real photos)
        foreach (SeoCatalog::sitemapProducts() as $p) {
            $live = $p["status"] === "active";
            $imgs = [];
            if (!empty($p['image'])) {
                $imgs[media((string) $p['image'])] = (string) $p['title'];
            }
            foreach ($p['photos'] as $path) {
                $imgs[media((string) $path)] = (string) $p['title'];
            }
            $add('/product/' . $p['slug'], $p['updated_at'], $live ? "weekly" : "monthly", $live ? "0.7" : "0.3", $imgs);
        }

        $lastmod = 0;
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
        foreach ($urls as $u) {
            $lastmod = max($lastmod, $u['ts']);
            $xml .= '  <url><loc>' . self::x($u['loc']) . '</loc>' . ($u['ts'] > 0 ? '<lastmod>' . date('c', $u['ts']) . '</lastmod>' : '')
                . '<changefreq>' . $u['freq'] . '</changefreq><priority>' . $u['prio'] . '</priority>';
            foreach ($u['images'] as $loc => $title) {
                $xml .= '<image:image><image:loc>' . self::x((string) $loc) . '</image:loc><image:title>' . self::x($title) . '</image:title></image:image>';
            }
            $xml .= '</url>' . "\n";
        }
        Cacheable::send($xml . '</urlset>' . "\n", 'application/xml; charset=utf-8', $lastmod ?: (int) filemtime(__FILE__));
    }

    private static function x(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
