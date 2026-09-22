<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

/** /collections and /collections/{slug}: programmatic landing pages from live data. Thin ones are noindex and stay out of the sitemap. */
final class CollectionController extends Controller
{
    public const GROUP_LABELS = [
        'price'    => 'Browse by price',
        'genre'    => 'Browse by genre',
        'edition'  => 'Collector editions',
        'new'      => 'Just in',
        'hardware' => 'Gaming gear',
    ];

    public function index(Request $request): void
    {
        $active = Collections::active();
        $crumbs = [['Home', '/'], ['Collections', null]];
        $desc = 'Curated collections of used and new games and gaming gear in Lebanon: browse by price, genre, steelbook editions and new arrivals, all built from what is in stock.';
        $this->render('site/collections/index', [
            'nav'    => 'shop',
            'crumbs' => $crumbs,
            'active' => $active,
            'meta'   => [
                'title'       => 'Game & Gaming Gear Collections in Lebanon | CyberGaming',
                'description' => Seo::clip($desc),
                'canonical'   => url('/collections'),
                'noindex'     => count($active) === 0,
                'jsonld'      => [Seo::breadcrumbs($crumbs), Seo::webPage('CollectionPage', 'Game and gaming gear collections', url('/collections'), Seo::clip($desc), false)],
            ],
        ]);
    }

    public function show(Request $request, string $slug): void
    {
        $def = Collections::get($slug) ?? Response::abort(404);
        $rawPage = $request->query('page', 1);
        $page = is_string($rawPage) && ctype_digit($rawPage) ? max(1, (int) $rawPage) : 1;
        $s = Collections::load($def, $page);
        if ($page > 1 && $page > $s['pages']) {
            Response::abort(404);
        }

        $indexable = Collections::isIndexable($s['n']);
        $base = url('/collections/' . $slug);
        $href = static fn (int $p): string => $base . ($p > 1 ? '?page=' . $p : '');
        $crumbs = [['Home', '/'], ['Collections', '/collections'], [(string) $def['h1'], null]];
        $intro = Collections::intro($def, $s);
        $desc = Collections::description($def, $s);
        $title = $def['title'] . ($def['group'] === 'new' ? '' : ' in Lebanon') . ($page > 1 ? ' - Page ' . $page : '') . ' | CyberGaming';

        $meta = [
            'title'       => $title,
            'description' => $desc,
            'canonical'   => $href($page),
            'noindex'     => !$indexable,
            'prev'        => $page > 1 ? $href($page - 1) : null,
            'next'        => $page < $s['pages'] ? $href($page + 1) : null,
            'jsonld'      => [
                Seo::breadcrumbs($crumbs),
                Seo::collectionPage((string) $def['h1'], $href($page), $desc, $s['items'], ($page - 1) * Collections::PER_PAGE),
            ],
        ];

        $this->render('site/collections/show', [
            'nav'     => 'shop',
            'def'     => $def,
            'stats'   => $s,
            'intro'   => $intro,
            'crumbs'  => $crumbs,
            'base'    => '/collections/' . $slug,
            'related' => Collections::related($def, 6),
            'guides'  => Guides::forTag((string) $def['tag'], 3),
            'meta'    => $meta,
        ]);
    }
}
