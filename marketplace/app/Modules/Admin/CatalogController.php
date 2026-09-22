<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;

/**
 * Categories (kind, enabled, member listing, SEO) and platforms (enabled, order).
 * Disabling never deletes anything: the storefront simply hides a disabled category / platform and its products.
 * The admin always sees everything.
 */
final class CatalogController extends AdminController
{
    public const KINDS = [
        'game'     => 'Game',
        'hardware' => 'Hardware',
        'digital'  => 'Digital',
    ];

    public const KIND_HELP = [
        'game'     => 'Discs and cartridges: box, cover and manual questions, three game photos.',
        'hardware' => 'Consoles, controllers, peripherals: brand, specs, warranty, three unit photos. Admin only for now.',
        'digital'  => 'Gift cards and codes: no photos, follows the master Digital switch.',
    ];

    public function index(Request $request): void
    {
        $categories = db()->fetchAll(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS products,
                    (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = 'active') AS active_products
               FROM categories c ORDER BY c.sort_order, c.name"
        );
        $platforms = db()->fetchAll(
            "SELECT pl.*,
                    (SELECT COUNT(*) FROM products p WHERE p.platform_id = pl.id) AS products,
                    (SELECT COUNT(*) FROM products p WHERE p.platform_id = pl.id AND p.status = 'active') AS active_products
               FROM platforms pl ORDER BY pl.sort_order, pl.name"
        );
        $this->view('catalog/index', ['categories' => $categories, 'platforms' => $platforms, 'kinds' => self::KINDS, 'digitalOn' => digital_enabled()]);
    }

    public function update(Request $request): void
    {
        $section = $this->str($request, 'section');
        $back    = '/admin/catalog';

        match ($section) {
            'category'     => $this->saveCategory($request, $back),
            'platform'     => $this->savePlatform($request, $back),
            'new_category' => $this->newCategory($request, $back),
            'new_platform' => $this->newPlatform($request, $back),
            default        => $this->back($back, 'Unknown form section.'),
        };
    }

    /** Enable / disable one category. `to` = 1 or 0 (explicit, so a double click cannot flip it back). */
    public function toggleCategory(Request $request, string $id): void
    {
        $id  = $this->id($id);
        $cat = db()->fetch('SELECT id, name, kind FROM categories WHERE id = ?', [$id]);
        if ($cat === null) {
            \App\Core\Response::abort(404);
        }
        $to = $this->str($request, 'to') === '1' ? 1 : 0;
        db()->execute('UPDATE categories SET is_active = ? WHERE id = ?', [$to, $id]);
        $n = (int) db()->fetchValue('SELECT COUNT(*) FROM products WHERE category_id = ?', [$id]);
        $this->ok($to === 1
            ? 'Category "' . $cat['name'] . '" is enabled again: it and its ' . $n . ' product' . ($n === 1 ? '' : 's') . ' can show on the website.'
            : 'Category "' . $cat['name'] . '" is disabled: it and its ' . $n . ' product' . ($n === 1 ? ' is' : 's are') . ' hidden on the website. Nothing was deleted.');
        $this->redirect('/admin/catalog#cat-' . $id);
    }

    /** Enable / disable one platform. */
    public function togglePlatform(Request $request, string $id): void
    {
        $id = $this->id($id);
        $pl = db()->fetch('SELECT id, name FROM platforms WHERE id = ?', [$id]);
        if ($pl === null) {
            \App\Core\Response::abort(404);
        }
        $to = $this->str($request, 'to') === '1' ? 1 : 0;
        db()->execute('UPDATE platforms SET is_active = ? WHERE id = ?', [$to, $id]);
        $n = (int) db()->fetchValue('SELECT COUNT(*) FROM products WHERE platform_id = ?', [$id]);
        $this->ok($to === 1
            ? 'Platform "' . $pl['name'] . '" is enabled again: it and its ' . $n . ' product' . ($n === 1 ? '' : 's') . ' can show on the website.'
            : 'Platform "' . $pl['name'] . '" is disabled: it and its ' . $n . ' product' . ($n === 1 ? ' is' : 's are') . ' hidden on the website. Nothing was deleted.');
        $this->redirect('/admin/catalog#plat-' . $id);
    }

    private function saveCategory(Request $request, string $back): void
    {
        $id  = Forms::int($request->input('id')) ?? 0;
        $cat = $id > 0 ? db()->fetch('SELECT * FROM categories WHERE id = ?', [$id]) : null;
        if ($cat === null) {
            $this->back($back, 'That category no longer exists.');
        }
        $back .= '#cat-' . $id;

        $errors = [];
        $name  = Forms::text($request->input('name'));
        $seoT  = Forms::text($request->input('seo_title'));
        $seoD  = Forms::text($request->input('seo_description'));
        $intro = Forms::text($request->input('intro_text'));
        $sort  = Forms::int($request->input('sort_order', '0')) ?? 0;
        $kind  = Forms::text($request->input('kind'));
        if ($name === '' || mb_strlen($name) > 60) {
            $errors[] = 'Name is required (max 60 characters).';
        }
        if (mb_strlen($seoT) > 160) {
            $errors[] = 'SEO title is over 160 characters.';
        }
        if (mb_strlen($seoD) > 320) {
            $errors[] = 'SEO description is over 320 characters.';
        }
        if (mb_strlen($intro) > 5000) {
            $errors[] = 'Intro text is too long (max 5000 characters).';
        }
        if (!isset(self::KINDS[$kind])) {
            $errors[] = 'Choose the kind of category: Game, Hardware or Digital.';
            $kind = (string) $cat['kind'];
        }
        if ($kind !== $cat['kind'] && $request->input('kind_confirm') !== '1') {
            $n = (int) db()->fetchValue('SELECT COUNT(*) FROM products WHERE category_id = ?', [$id]);
            $errors[] = 'To change the kind of "' . $cat['name'] . '" tick "I understand" next to it. The category has ' . $n . ' product' . ($n === 1 ? '' : 's')
                . ' whose data and photos are not converted: they keep what they have.';
            $kind = (string) $cat['kind'];
        }
        // Members and stores list games only for now; digital categories never take member listings.
        $member = $kind === 'digital' ? 0 : (!empty($request->input('member_listing')) ? 1 : 0);

        if ($errors) {
            $this->invalid($back, $errors, $request);
        }
        db()->execute(
            'UPDATE categories SET name = ?, kind = ?, member_listing = ?, seo_title = ?, seo_description = ?, intro_text = ?, sort_order = ? WHERE id = ?',
            [$name, $kind, $member, $seoT ?: null, $seoD ?: null, $intro ?: null, max(-9999, min(9999, $sort)), $id]
        );
        $this->ok('Category "' . $name . '" saved.' . ($kind !== $cat['kind'] ? ' Its kind is now ' . self::KINDS[$kind] . '.' : ''));
        $this->redirect($back);
    }

    private function savePlatform(Request $request, string $back): void
    {
        $id = Forms::int($request->input('id')) ?? 0;
        if ($id < 1 || !db()->fetchValue('SELECT COUNT(*) FROM platforms WHERE id = ?', [$id])) {
            $this->back($back, 'That platform no longer exists.');
        }
        $back .= '#plat-' . $id;
        $name = Forms::text($request->input('name'));
        $sort = Forms::int($request->input('sort_order', '0')) ?? 0;
        if ($name === '' || mb_strlen($name) > 60) {
            $this->invalid($back, ['Platform name is required (max 60 characters).'], $request);
        }
        db()->execute('UPDATE platforms SET name = ?, sort_order = ? WHERE id = ?', [$name, max(-9999, min(9999, $sort)), $id]);
        $this->ok('Platform "' . $name . '" saved.');
        $this->redirect($back);
    }

    private function newCategory(Request $request, string $back): void
    {
        $name = $this->str($request, 'new_name');
        $kind = $this->str($request, 'new_kind');
        if ($name === '' || mb_strlen($name) > 60) {
            $this->back($back, 'New category: name is required (max 60 characters).');
        }
        if (!isset(self::KINDS[$kind])) {
            $this->back($back, 'New category: choose its kind (Game, Hardware or Digital). It decides which fields and photos its products need.');
        }
        $member = $kind !== 'digital' && $request->input('new_member_listing') === '1' ? 1 : 0;
        $slug = $this->uniqueSlug('categories', mb_substr(slugify($name), 0, 36));
        $sort = (int) db()->fetchValue('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM categories');
        $id = db()->insert(
            'INSERT INTO categories (slug, kind, name, sort_order, is_active, member_listing) VALUES (?, ?, ?, ?, 1, ?)',
            [$slug, $kind, $name, $sort, $member]
        );
        $this->ok('Category "' . $name . '" (' . self::KINDS[$kind] . ') added. Fill in its SEO title, description and intro text below.');
        $this->redirect($back . '#cat-' . $id);
    }

    private function newPlatform(Request $request, string $back): void
    {
        $name = $this->str($request, 'new_name');
        if ($name === '' || mb_strlen($name) > 60) {
            $this->back($back, 'New platform: name is required (max 60 characters).');
        }
        $slug = $this->uniqueSlug('platforms', mb_substr(slugify($name), 0, 36));
        $sort = (int) db()->fetchValue('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM platforms');
        db()->execute('INSERT INTO platforms (slug, name, sort_order, is_active) VALUES (?, ?, ?, 1)', [$slug, $name, $sort]);
        $this->ok('Platform "' . $name . '" added.');
        $this->redirect($back);
    }

    private function uniqueSlug(string $table, string $base): string
    {
        $slug = $base;
        $n    = 2;
        while ((int) db()->fetchValue("SELECT COUNT(*) FROM $table WHERE slug = ?", [$slug]) > 0) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }
}
