<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

/** /guides (hub) and /guides/{slug}: answer-first articles from the articles table. */
final class GuideController extends Controller
{
    public function index(Request $request): void
    {
        $tagRaw = $request->query('tag', '');
        $tag = is_string($tagRaw) && isset(Guides::TAGS[$tagRaw]) ? $tagRaw : '';
        $rawPage = $request->query('page', 1);
        $page = is_string($rawPage) && ctype_digit($rawPage) ? max(1, (int) $rawPage) : 1;

        $result = Guides::paginate($tag, $page);
        if ($page > 1 && $page > $result['pages']) {
            Response::abort(404);
        }
        $filtered = $tag !== '';
        $crumbs = [['Home', '/'], ['Guides', null]];
        $query = $filtered ? ['tag' => $tag] : [];
        $href = static fn (int $p): string => url('/guides') . ($query || $p > 1 ? '?' . http_build_query($query + ($p > 1 ? ['page' => $p] : [])) : '');

        $title = 'Gaming Guides for Lebanon: Buying, Selling & Gear';
        $desc = 'Practical guides on buying, selling and trading used games in Lebanon, checking used consoles, choosing PC peripherals, store credit and delivery.';
        if ($filtered) {
            $title = Guides::tagLabel($tag) . ' Guides | Gaming Guides for Lebanon';
            $desc = 'CyberGaming ' . strtolower(Guides::tagLabel($tag)) . ' guides: practical, honest advice for gamers in Lebanon on buying, selling, delivery and gear.';
        }
        if ($page > 1) {
            $title .= ' (Page ' . $page . ')';
        }

        $meta = [
            'title'       => $title,
            'description' => Seo::clip($desc),
            'canonical'   => $href($page),
            'noindex'     => $filtered,
            'prev'        => !$filtered && $page > 1 ? $href($page - 1) : null,
            'next'        => !$filtered && $page < $result['pages'] ? $href($page + 1) : null,
            'jsonld'      => [
                Seo::breadcrumbs($crumbs),
                Seo::webPage('CollectionPage', 'Gaming guides for Lebanon', $href($page), Seo::clip($desc)) + ['mainEntity' => [
                    '@type'           => 'ItemList',
                    'numberOfItems'   => count($result['items']),
                    'itemListElement' => array_map(
                        static fn (array $a, int $i): array => ['@type' => 'ListItem', 'position' => $i + 1 + ($result['page'] - 1) * Guides::PER_PAGE, 'url' => url('/guides/' . $a['slug']), 'name' => Guides::text($a['title'])],
                        $result['items'],
                        array_keys($result['items'])
                    ),
                ]],
            ],
        ];

        $this->render('site/guides/index', [
            'nav'    => 'guides',
            'crumbs' => $crumbs,
            'meta'   => $meta,
            'result' => $result,
            'tag'    => $tag,
            'tags'   => Guides::tagCounts(),
            'query'  => $query,
        ]);
    }

    public function show(Request $request, string $slug): void
    {
        $preview = $request->query('preview') === '1' && auth()->hasRole('admin');
        $a = Guides::bySlug($slug, $preview) ?? Response::abort(404);
        $isLive = (int) $a['is_published'] === 1 && $a['published_at'] !== null && strtotime((string) $a['published_at']) <= time();

        $title = Guides::text($a['title']);
        $excerpt = Guides::text($a['excerpt']);
        $body = Guides::renderBody((string) $a['body']);
        $path = '/guides/' . $a['slug'];
        $canonical = url($path);
        $crumbs = [['Home', '/'], ['Guides', '/guides'], [$title, null]];
        $desc = trim(Guides::text($a['meta_description'])) !== '' ? Guides::text($a['meta_description']) : ($excerpt !== '' ? $excerpt : $title);
        $metaTitle = trim((string) $a['meta_title']) !== '' ? Guides::text($a['meta_title']) : $title . ' | CyberGaming';
        $published = (string) ($a['published_at'] ?: $a['created_at']);

        $this->render('site/guides/show', [
            'nav'       => 'guides',
            'a'         => $a,
            'title'     => $title,
            'excerpt'   => $excerpt,
            'body'      => $body,
            'crumbs'    => $crumbs,
            'related'   => Guides::related($a, 3),
            'shopLinks' => Guides::shopLinks((string) $a['tag']),
            'preview'   => $preview && !$isLive,
            'meta'      => [
                'title'       => $metaTitle,
                'description' => Seo::clip($desc),
                'canonical'   => $canonical,
                'noindex'     => $preview,
                'og_type'     => 'article',
                'og_extra'    => [
                    'article:published_time' => date('c', (int) strtotime($published)),
                    'article:modified_time'  => date('c', (int) strtotime((string) ($a['updated_at'] ?: $published))),
                    'article:section'       => Guides::tagLabel($a['tag']),
                ],
                'jsonld'      => [Seo::article($a + ['title' => $title], Seo::clip($desc), $canonical, Seo::defaultImage()), Seo::breadcrumbs($crumbs)],
            ],
        ]);
    }
}
