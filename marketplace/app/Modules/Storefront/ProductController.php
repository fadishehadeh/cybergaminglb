<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Support\ProductPhotos;

final class ProductController extends Controller
{
    public function show(Request $request, string $slug): void
    {
        // Catalog::product() applies the digital gate: with the master switch off a digital product is a plain 404.
        $p = Catalog::product($slug) ?? Response::abort(404);

        $available = $p['status'] === 'active' && (int) $p['stock'] > 0;
        $short = Ui::shortPlatform($p['platform_slug'], $p['platform_name']);
        $isDigital = Digital::is($p);
        $isGames = $p['category_slug'] === 'games';
        $isHardware = Ui::isHardware($p);
        $path = '/product/' . $p['slug'];

        $crumbs = [['Home', '/']];
        if ($p['platform_slug']) {
            $crumbs[] = [$short, '/platform/' . $p['platform_slug']];
            $crumbs[] = [$p['category_name'], '/platform/' . $p['platform_slug'] . '/' . $p['category_slug']];
        } else {
            $crumbs[] = [$p['category_name'], '/shop/' . $p['category_slug']];
        }
        $crumbs[] = [$p['title'], null];

        $what = $isGames ? 'used ' . $short . ' game' : ($short !== '' ? "$short " : '') . strtolower(rtrim($p['category_name'], 's'));
        if ($isDigital) {
            $region = Digital::region($p);
            $descBase = $p['title'] . ' in Lebanon: ' . strtolower(Digital::kindLabel($p['digital_kind'])) . ($region !== '' ? " for $region accounts" : '')
                . ', ' . money($p['price']) . '.';
            $description = $available
                ? Seo::clip($descBase . ' We send your code on WhatsApp after you pay by OMT or Whish.')
                : Seo::clip('Currently unavailable: ' . $p['title'] . '. See other gift cards and digital codes at CyberGaming Lebanon.');
            $title = $available
                ? 'Buy ' . $p['title'] . ' in Lebanon – ' . money($p['price'])
                : $p['title'] . ' – Unavailable';
        } else {
            $descBase = $p['title'] . ($short !== '' ? " for $short" : '') . ' in Lebanon: ' . (Ui::isUsed($p) ? 'used, ' . $p['item_condition'] . ' condition' : 'new and sealed')
                . ($p['is_steelbook'] ? ', steelbook edition' : '') . ', ' . money($p['price']) . '.';
            $description = $available
                ? Seo::clip($descBase . ($isHardware ? ' Tested before delivery, delivery across Lebanon, pay cash, OMT or Whish.' : ' Inspected before sale, delivery across Lebanon, pay cash, OMT or Whish.'))
                : Seo::clip('Sold out: ' . $p['title'] . ($short !== '' ? " ($short)" : '') . '. See similar titles in stock at CyberGaming Lebanon.');
            $title = $available
                ? 'Buy ' . $p['title'] . ($short !== '' ? " ($short)" : '') . ' in Lebanon – ' . money($p['price'])
                : $p['title'] . ($short !== '' ? " ($short)" : '') . ' – Sold out';
        }

        // Real photos of this exact copy (games: disc, box outside, box inside; hardware: unit front, unit back, box and accessories, powered on; extras). Digital goods never have any.
        $photos = $isDigital ? [] : ProductPhotos::forProduct((int) $p['id']);

        $this->render('site/product', [
            'p'         => $p,
            'available' => $available,
            'short'     => $short,
            'what'      => $what,
            'isHardware' => $isHardware,
            'crumbs'    => $crumbs,
            'photos'    => $photos,
            'related'   => Catalog::related($p, 4),
            'nav'       => 'shop',
            'meta'      => [
                'title'       => $title . ' | CyberGaming',
                'description' => $description,
                'canonical'   => url($path),
                'image'       => $photos ? media((string) $photos[0]['path']) : ($isDigital && !$p['image'] ? asset('img/logo.jpg') : media($p['image'])),
                'og_type'     => 'product',
                'jsonld'      => [Seo::product($p, $description, $photos), Seo::breadcrumbs($crumbs)],
                'og_extra'    => [
                    'product:price:amount'   => number_format((float) $p['price'], 2, '.', ''),
                    'product:price:currency' => 'USD',
                ],
            ],
        ]);
    }
}
