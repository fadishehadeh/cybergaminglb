<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;
use App\Support\SafeHtml;
use App\Support\Tokens;

/**
 * Guides (articles) CMS. The storefront renders /guides/{slug} from the `articles` table:
 * a guide is LIVE when is_published = 1 and published_at is not in the future (a future date = Scheduled).
 * The body is run through SafeHtml::clean() on save, so what is stored is already safe.
 */
final class GuideController extends AdminController
{
    public const TAGS = [
        'selling' => 'Selling', 'buying' => 'Buying', 'delivery' => 'Delivery', 'credit' => 'Store credit',
        'consoles' => 'Consoles', 'games' => 'Games', 'privacy' => 'Privacy', 'general' => 'General',
    ];
    public const STATUSES = ['draft' => 'Draft', 'published' => 'Published', 'scheduled' => 'Scheduled'];
    public const DEFAULT_AUTHOR = 'CyberGaming team';
    private const PER_PAGE = 20;

    /** SQL condition for a status filter (articles table has no alias). */
    private const STATUS_SQL = [
        'draft'     => 'is_published = 0',
        'published' => 'is_published = 1 AND (published_at IS NULL OR published_at <= NOW())',
        'scheduled' => 'is_published = 1 AND published_at > NOW()',
    ];

    public function index(Request $request): void
    {
        $q      = Forms::text($request->query('q'));
        $status = Forms::text($request->query('status'));
        if (!isset(self::STATUSES[$status])) {
            $status = '';
        }
        $tag = Forms::text($request->query('tag'));
        if (!isset(self::TAGS[$tag])) {
            $tag = '';
        }

        $where  = [];
        $params = [];
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where[] = '(title LIKE ? OR slug LIKE ? OR excerpt LIKE ?)';
            array_push($params, $like, $like, $like);
        }
        if ($status !== '') {
            $where[] = self::STATUS_SQL[$status];
        }
        if ($tag !== '') {
            $where[]  = 'tag = ?';
            $params[] = $tag;
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = (int) db()->fetchValue('SELECT COUNT(*) FROM articles' . $whereSql, $params);
        $pager = Pagination::fromRequest($request, $total, self::PER_PAGE);
        $guides = db()->fetchAll(
            'SELECT id, slug, title, tag, author, is_published, published_at, created_at, updated_at,
                    (is_published = 1 AND published_at > NOW()) AS is_scheduled,
                    (is_published = 1 AND (published_at IS NULL OR published_at <= NOW())) AS is_live
               FROM articles' . $whereSql . ' ORDER BY updated_at DESC, id DESC' . $pager->limitSql(),
            $params
        );

        $counts = [];
        foreach (self::STATUS_SQL as $key => $cond) {
            $counts[$key] = (int) db()->fetchValue('SELECT COUNT(*) FROM articles WHERE ' . $cond);
        }
        $this->view('guides/index', [
            'guides'  => $guides,
            'pager'   => $pager,
            'filters' => compact('q', 'status', 'tag'),
            'counts'  => $counts,
        ]);
    }

    public function create(Request $request): void
    {
        $this->form(null);
    }

    public function edit(Request $request, string $id): void
    {
        $this->form($this->find($this->id($id)));
    }

    public function store(Request $request): void
    {
        $this->save($request, null);
    }

    public function update(Request $request, string $id): void
    {
        $this->save($request, $this->find($this->id($id)));
    }

    public function publish(Request $request, string $id): void
    {
        $g = $this->find($this->id($id));
        db()->execute('UPDATE articles SET is_published = 1, published_at = COALESCE(published_at, NOW()) WHERE id = ?', [$g['id']]);
        $fresh = $this->find((int) $g['id']);
        $future = $fresh['published_at'] !== null && strtotime((string) $fresh['published_at']) > time();
        $this->ok($future
            ? '"' . $g['title'] . '" is scheduled: it goes live on ' . date('j M Y, H:i', strtotime((string) $fresh['published_at'])) . '.'
            : '"' . $g['title'] . '" is published.');
        $this->redirect($this->returnTo($request, '/admin/guides'));
    }

    public function unpublish(Request $request, string $id): void
    {
        $g = $this->find($this->id($id));
        db()->execute('UPDATE articles SET is_published = 0 WHERE id = ?', [$g['id']]);
        $this->ok('"' . $g['title'] . '" is back to draft: it is no longer on the website.');
        $this->redirect($this->returnTo($request, '/admin/guides'));
    }

    public function delete(Request $request, string $id): void
    {
        $g = $this->find($this->id($id));
        db()->execute('DELETE FROM articles WHERE id = ?', [$g['id']]);
        $this->ok('"' . $g['title'] . '" was deleted.');
        $this->redirect('/admin/guides');
    }

    // ------------------------------------------------------------------------------------------------

    private function find(int $id): array
    {
        $g = db()->fetch('SELECT * FROM articles WHERE id = ?', [$id]);
        if ($g === null) {
            \App\Core\Response::abort(404);
        }
        return $g;
    }

    private function form(?array $guide): void
    {
        $this->view('guides/form', ['guide' => $guide, 'tokens' => Tokens::help(), 'tags' => self::TAGS]);
    }

    private function save(Request $request, ?array $guide): void
    {
        $isEdit = $guide !== null;
        $back   = $isEdit ? '/admin/guides/' . $guide['id'] . '/edit' : '/admin/guides/create';
        $errors = [];

        $title = $this->str($request, 'title');
        if ($title === '' || mb_strlen($title) > 200) {
            $errors[] = 'Title is required (max 200 characters).';
        }

        // Slug: typed by the owner (must be free) or made from the title (made unique automatically).
        $slugIn = $this->str($request, 'slug');
        $ignore = $isEdit ? (int) $guide['id'] : 0;
        if ($slugIn === '') {
            $slug = $this->uniqueSlug(mb_substr(slugify($title), 0, 150), $ignore);
        } else {
            $slug = mb_substr(slugify($slugIn), 0, 160);
            $taken = db()->fetch('SELECT id, title FROM articles WHERE slug = ? AND id <> ?', [$slug, $ignore]);
            if ($taken !== null) {
                $errors[] = 'The address "' . $slug . '" is already used by the guide "' . $taken['title'] . '". Choose another slug.';
            }
        }

        $excerpt = $this->str($request, 'excerpt');
        if (mb_strlen($excerpt) > 400) {
            $errors[] = 'The excerpt (quick answer) can be at most 400 characters.';
        }
        $metaTitle = $this->str($request, 'meta_title');
        if (mb_strlen($metaTitle) > 160) {
            $errors[] = 'The SEO title can be at most 160 characters.';
        }
        $metaDesc = $this->str($request, 'meta_description');
        if (mb_strlen($metaDesc) > 320) {
            $errors[] = 'The SEO description can be at most 320 characters.';
        }
        $tag = $this->str($request, 'tag');
        if ($tag !== '' && !isset(self::TAGS[$tag])) {
            $errors[] = 'Choose a topic from the list.';
            $tag = '';
        }
        $author = $this->str($request, 'author');
        if (mb_strlen($author) > 80) {
            $errors[] = 'Author can be at most 80 characters.';
        }
        $author = $author !== '' ? $author : self::DEFAULT_AUTHOR;

        // Body: plain text (no tags at all) becomes paragraphs; anything else goes through the allow-list cleaner.
        $rawBody = str_replace(["\r\n", "\r"], "\n", (string) $request->input('body', ''));
        $rawBody = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $rawBody) ?? '';
        if (mb_strlen($rawBody) > 300000) {
            $errors[] = 'The body is too long (max 300,000 characters).';
        }
        $body = '';
        if (trim($rawBody) === '') {
            $errors[] = 'The body is required.';
        } else {
            $body = SafeHtml::clean(self::plainToHtml($rawBody));
            if ($body === '') {
                $errors[] = 'Nothing is left of the body after unsafe HTML was removed. Use the allowed tags listed under the editor.';
            }
        }

        $publish = $request->input('is_published') === '1';
        $when    = null;
        $whenIn  = $this->str($request, 'published_at');
        if ($whenIn !== '') {
            $when = self::parseWhen($whenIn);
            if ($when === null) {
                $errors[] = 'The publish date is not valid. Pick a date and time.';
            }
        }
        if ($publish && $when === null && !$errors) {
            $when = date('Y-m-d H:i:s'); // blank date + published = now
        }

        if ($errors) {
            $this->invalid($back, $errors, $request);
        }

        $params = [$slug, $title, $excerpt !== '' ? $excerpt : null, $body, $metaTitle !== '' ? $metaTitle : null, $metaDesc !== '' ? $metaDesc : null,
            $tag !== '' ? $tag : null, $author, $publish ? 1 : 0, $when];
        if ($isEdit) {
            db()->execute(
                'UPDATE articles SET slug = ?, title = ?, excerpt = ?, body = ?, meta_title = ?, meta_description = ?, tag = ?, author = ?, is_published = ?, published_at = ? WHERE id = ?',
                [...$params, $guide['id']]
            );
            $id = (int) $guide['id'];
        } else {
            $id = db()->insert(
                'INSERT INTO articles (slug, title, excerpt, body, meta_title, meta_description, tag, author, is_published, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                $params
            );
        }

        $state = !$publish ? 'saved as a draft' : ($when !== null && strtotime($when) > time() ? 'scheduled for ' . date('j M Y, H:i', strtotime($when)) : 'published');
        $this->ok('"' . $title . '" ' . $state . '.');
        $this->redirect('/admin/guides/' . $id . '/edit');
    }

    /** Text without any tag is written as paragraphs (blank line = new paragraph, line break = <br>). HTML is left as typed. */
    public static function plainToHtml(string $raw): string
    {
        if (str_contains($raw, '<')) {
            return $raw;
        }
        $paras = preg_split('/\n{2,}/', trim($raw)) ?: [];
        $out = [];
        foreach ($paras as $p) {
            $p = trim($p);
            if ($p !== '') {
                $out[] = '<p>' . str_replace("\n", '<br>', htmlspecialchars($p, ENT_NOQUOTES, 'UTF-8')) . '</p>';
            }
        }
        return implode("\n", $out);
    }

    /** "2026-09-30T14:30" (datetime-local) or "2026-09-30 14:30[:00]" to "Y-m-d H:i:s"; null when invalid. */
    public static function parseWhen(string $raw): ?string
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})(?::(\d{2}))?$/', trim($raw), $m)) {
            return null;
        }
        if (!checkdate((int) $m[2], (int) $m[3], (int) $m[1]) || (int) $m[4] > 23 || (int) $m[5] > 59 || (int) $m[1] < 2000 || (int) $m[1] > 2100) {
            return null;
        }
        return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $m[1], $m[2], $m[3], $m[4], $m[5], $m[6] ?? 0);
    }

    private function uniqueSlug(string $base, int $ignoreId): string
    {
        $slug = $base;
        $n    = 2;
        while (db()->fetchValue('SELECT COUNT(*) FROM articles WHERE slug = ? AND id <> ?', [$slug, $ignoreId]) > 0) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }
}
