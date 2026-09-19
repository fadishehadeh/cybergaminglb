<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;

final class CatalogController extends AdminController
{
    public function index(Request $request): void
    {
        $categories = db()->fetchAll(
            'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS products
               FROM categories c ORDER BY c.sort_order, c.name'
        );
        $platforms = db()->fetchAll(
            'SELECT pl.*, (SELECT COUNT(*) FROM products p WHERE p.platform_id = pl.id) AS products
               FROM platforms pl ORDER BY pl.sort_order, pl.name'
        );
        $this->view('catalog/index', compact('categories', 'platforms'));
    }

    public function update(Request $request): void
    {
        $section = $this->str($request, 'section');
        $back    = '/admin/catalog';

        match ($section) {
            'categories'   => $this->saveCategories($request, $back),
            'platforms'    => $this->savePlatforms($request, $back),
            'new_category' => $this->newCategory($request, $back),
            'new_platform' => $this->newPlatform($request, $back),
            default        => $this->back($back, 'Unknown form section.'),
        };
    }

    private function saveCategories(Request $request, string $back): void
    {
        $rows = $request->input('cat');
        if (!is_array($rows)) {
            $this->back($back, 'Nothing to save.');
        }
        $errors = [];
        $clean  = [];
        foreach ($rows as $id => $row) {
            if (!is_array($row) || !ctype_digit((string) $id)) {
                continue;
            }
            $exists = db()->fetchValue('SELECT COUNT(*) FROM categories WHERE id = ?', [(int) $id]);
            if (!$exists) {
                continue;
            }
            $name = Forms::text($row['name'] ?? '');
            $seoT = Forms::text($row['seo_title'] ?? '');
            $seoD = Forms::text($row['seo_description'] ?? '');
            $intro = Forms::text($row['intro_text'] ?? '');
            $sort = Forms::int($row['sort_order'] ?? '0') ?? 0;
            if ($name === '' || mb_strlen($name) > 60) {
                $errors[] = 'Category #' . (int) $id . ': name is required (max 60 characters).';
            }
            if (mb_strlen($seoT) > 160) {
                $errors[] = 'Category "' . $name . '": SEO title is over 160 characters.';
            }
            if (mb_strlen($seoD) > 320) {
                $errors[] = 'Category "' . $name . '": SEO description is over 320 characters.';
            }
            if (mb_strlen($intro) > 5000) {
                $errors[] = 'Category "' . $name . '": intro text is too long.';
            }
            $clean[(int) $id] = [$name, $seoT ?: null, $seoD ?: null, $intro ?: null, !empty($row['is_active']) ? 1 : 0, max(-9999, min(9999, $sort))];
        }
        if ($errors) {
            $this->invalid($back, $errors, $request);
        }
        db()->transaction(function () use ($clean): void {
            foreach ($clean as $id => $c) {
                db()->execute(
                    'UPDATE categories SET name = ?, seo_title = ?, seo_description = ?, intro_text = ?, is_active = ?, sort_order = ? WHERE id = ?',
                    [...$c, $id]
                );
            }
        });
        $this->ok('Categories saved.');
        $this->redirect($back);
    }

    private function savePlatforms(Request $request, string $back): void
    {
        $rows = $request->input('plat');
        if (!is_array($rows)) {
            $this->back($back, 'Nothing to save.');
        }
        $errors = [];
        $clean  = [];
        foreach ($rows as $id => $row) {
            if (!is_array($row) || !ctype_digit((string) $id)) {
                continue;
            }
            $name = Forms::text($row['name'] ?? '');
            $sort = Forms::int($row['sort_order'] ?? '0') ?? 0;
            if ($name === '' || mb_strlen($name) > 60) {
                $errors[] = 'Platform #' . (int) $id . ': name is required (max 60 characters).';
            }
            $clean[(int) $id] = [$name, !empty($row['is_active']) ? 1 : 0, max(-9999, min(9999, $sort))];
        }
        if ($errors) {
            $this->invalid($back, $errors, $request);
        }
        db()->transaction(function () use ($clean): void {
            foreach ($clean as $id => $c) {
                db()->execute('UPDATE platforms SET name = ?, is_active = ?, sort_order = ? WHERE id = ?', [...$c, $id]);
            }
        });
        $this->ok('Platforms saved.');
        $this->redirect($back);
    }

    private function newCategory(Request $request, string $back): void
    {
        $name = $this->str($request, 'new_name');
        if ($name === '' || mb_strlen($name) > 60) {
            $this->back($back, 'New category: name is required (max 60 characters).');
        }
        $slug = $this->uniqueSlug('categories', mb_substr(slugify($name), 0, 36));
        $sort = (int) db()->fetchValue('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM categories');
        db()->execute('INSERT INTO categories (slug, name, sort_order, is_active) VALUES (?, ?, ?, 1)', [$slug, $name, $sort]);
        $this->ok('Category "' . $name . '" added. Fill in its SEO title, description and intro text below.');
        $this->redirect($back);
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
