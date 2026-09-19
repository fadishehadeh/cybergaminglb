<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

final class SeoController extends Controller
{
    public function robots(Request $request): void
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
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
            '',
            'Sitemap: ' . url('/sitemap.xml'),
            '',
        ];
        Response::text(implode("\n", $lines), 'text/plain; charset=utf-8');
    }

    public function sitemap(Request $request): void
    {
        $urls = [];
        $add = static function (string $path, ?string $lastmod, string $freq, string $priority) use (&$urls): void {
            $urls[] = [url($path), $lastmod !== null ? date('c', strtotime($lastmod) ?: time()) : null, $freq, $priority];
        };

        $newest = db()->fetchValue("SELECT MAX(p.updated_at) FROM products p WHERE p.status IN ('active','sold')" . Catalog::gate());
        $add('/', $newest, 'daily', '1.0');
        $add('/shop', $newest, 'daily', '0.9');

        foreach (Catalog::categories() as $c) {
            if ((int) $c['product_count'] > 0) {
                $add('/shop/' . $c['slug'], $newest, 'daily', '0.8');
            }
        }
        foreach (Catalog::platforms() as $p) {
            if ((int) $p['product_count'] > 0) {
                $add('/platform/' . $p['slug'], $newest, 'daily', '0.8');
            }
        }
        foreach (Catalog::sitemapCombos() as $c) {
            $add('/platform/' . $c['platform'] . '/' . $c['category'], $c['updated_at'], 'daily', '0.8');
        }
        foreach (['sell' => '0.7', 'trade' => '0.7', 'credit' => '0.7', 'swap' => '0.6', 'how-it-works' => '0.6', 'seller/apply' => '0.6', 'account/register' => '0.4', 'delivery-and-payment' => '0.5', 'about' => '0.4', 'contact' => '0.4'] as $slug => $prio) {
            $add('/' . $slug, null, 'monthly', $prio);
        }
        foreach (Catalog::sitemapProducts() as $p) {
            $active = $p['status'] === 'active';
            $add('/product/' . $p['slug'], $p['updated_at'], $active ? 'weekly' : 'monthly', $active ? '0.7' : '0.3');
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as [$loc, $lastmod, $freq, $prio]) {
            $xml .= '  <url><loc>' . e($loc) . '</loc>' . ($lastmod ? '<lastmod>' . $lastmod . '</lastmod>' : '')
                . '<changefreq>' . $freq . '</changefreq><priority>' . $prio . '</priority></url>' . "\n";
        }
        Response::text($xml . '</urlset>' . "\n", 'application/xml; charset=utf-8');
    }
}
