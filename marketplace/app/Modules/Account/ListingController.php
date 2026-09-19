<?php
declare(strict_types=1);

namespace App\Modules\Account;

use App\Core\Request;
use App\Core\Response;
use App\Modules\Admin\Forms;
use App\Modules\Admin\Pagination;
use App\Modules\Seller\ImageUpload;
use App\Modules\Seller\ListingCondition;
use App\Support\ContactFilter;
use App\Support\Pricing;
use App\Support\ProductPhotos;

/**
 * Member listings: a customer lists their own games for other members. We handle every sale and delivery.
 * Every query carries "seller_id = <this member>"; another member's id is a 404.
 * A member can never set a listing live (status 'active') or touch the commission: the server decides both.
 */
final class ListingController extends ListingBase
{
    public const STATUSES = ['pending', 'active', 'hidden', 'sold'];
    public const MAX_PENDING = 30;

    /** status => [label in member language, css modifier] */
    public const STATUS_LABEL = [
        'pending' => ['Waiting for approval', 'pending'],
        'active'  => ['Live', 'active'],
        'hidden'  => ['Hidden', 'hidden'],
        'sold'    => ['Sold', 'sold'],
    ];

    // ---------------------------------------------------------------- index

    public function index(Request $request): void
    {
        $seller = $this->memberRow();
        if ($seller === null) {
            $this->page('account/listings/intro', ['commission' => Pricing::commissionPct(['type' => 'member', 'commission_pct' => null])]);
            return;
        }
        $seller = $this->activeMember();
        $sid    = (int) $seller['id'];

        $status = Forms::text($request->query('status'));
        if (!in_array($status, self::STATUSES, true)) {
            $status = '';
        }
        $where  = ' WHERE p.seller_id = ?';
        $params = [$sid];
        if ($status !== '') {
            $where .= ' AND p.status = ?';
            $params[] = $status;
        }

        $total = (int) db()->fetchValue('SELECT COUNT(*) FROM products p' . $where, $params);
        $pager = Pagination::fromRequest($request, $total, 20);

        $products = db()->fetchAll(
            'SELECT p.id, p.title, p.image, p.seller_price, p.price, p.stock, p.status, p.item_condition, p.includes_box, p.includes_cover_art, p.includes_manual, pl.name AS platform,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.product_id = p.id) AS times_ordered
               FROM products p LEFT JOIN platforms pl ON pl.id = p.platform_id'
            . $where . ' ORDER BY p.created_at DESC, p.id DESC' . $pager->limitSql(),
            $params
        );

        $counts = ['' => 0];
        foreach (self::STATUSES as $s) {
            $counts[$s] = 0;
        }
        foreach (db()->fetchAll('SELECT status, COUNT(*) AS n FROM products WHERE seller_id = ? GROUP BY status', [$sid]) as $row) {
            $counts[$row['status']] = (int) $row['n'];
            $counts[''] += (int) $row['n'];
        }

        $this->page('account/listings/index', [
            'seller'     => $seller,
            'commission' => $this->commission($seller),
            'products'   => $products,
            'pager'      => $pager,
            'status'     => $status,
            'counts'     => $counts,
            'kinds'      => ListingCondition::kindsFor(array_column($products, 'id')),
        ]);
    }

    // ---------------------------------------------------------------- becoming a member

    public function join(Request $request): void
    {
        $user = auth()->user();
        if ($this->str($request, 'terms') !== '1') {
            $this->back('/account/listings/new', 'Please tick the box to accept the member selling terms.');
        }

        $existing = $this->memberRow();
        if ($existing === null) {
            $name = (string) ($user['name'] ?? 'Member');
            db()->insert(
                "INSERT INTO sellers (user_id, type, code, name, phone, area, payout_method, status)
                 VALUES (?, 'member', ?, ?, ?, ?, 'wallet credit', 'active')",
                [
                    (int) $user['id'],
                    $this->newCode(),
                    mb_substr($name, 0, 150),
                    $user['phone'] ?? null,
                    $user['area'] ?? null,
                ]
            );
            $this->ok('Welcome! You can now list items for other members.');
        }
        $this->redirect('/account/listings/new');
    }

    // ---------------------------------------------------------------- create / edit

    public function create(Request $request): void
    {
        $seller = $this->memberRow();
        if ($seller === null) {
            $this->page('account/listings/terms', ['commission' => Pricing::commissionPct(['type' => 'member', 'commission_pct' => null])]);
            return;
        }
        $this->form($this->activeMember(), null);
    }

    public function edit(Request $request, string $id): void
    {
        $seller = $this->activeMember();
        $this->form($seller, $this->find($seller, $this->id($id)));
    }

    public function store(Request $request): void
    {
        $this->save($request, $this->activeMember(), null);
    }

    public function update(Request $request, string $id): void
    {
        $seller = $this->activeMember();
        $this->save($request, $seller, $this->find($seller, $this->id($id)));
    }

    // ---------------------------------------------------------------- withdraw / relist / delete

    /** Live listing -> hidden (out of the shop until relisted). */
    public function withdraw(Request $request, string $id): void
    {
        $seller  = $this->activeMember();
        $product = $this->find($seller, $this->id($id));
        $changed = db()->execute(
            "UPDATE products SET status = 'hidden' WHERE id = ? AND seller_id = ? AND status = 'active'",
            [$product['id'], $seller['id']]
        );
        if ($changed) {
            $this->ok('"' . $product['title'] . '" was taken off the shop. You can relist it any time.');
        } else {
            $this->app->session()->flash('error', 'Only live listings can be withdrawn.');
        }
        $this->redirect('/account/listings');
    }

    /** Hidden listing -> pending (goes back through approval). */
    public function relist(Request $request, string $id): void
    {
        $seller  = $this->activeMember();
        $product = $this->find($seller, $this->id($id));
        if ((int) $product['stock'] < 1) {
            $this->back('/account/listings/' . $product['id'] . '/edit', 'Set the stock above 0 before relisting "' . $product['title'] . '".');
        }
        $changed = db()->execute(
            "UPDATE products SET status = 'pending' WHERE id = ? AND seller_id = ? AND status = 'hidden'",
            [$product['id'], $seller['id']]
        );
        if ($changed) {
            $this->ok('"' . $product['title'] . '" was sent for approval. It goes live again once we approve it.');
        } else {
            $this->app->session()->flash('error', 'Only withdrawn listings can be relisted.');
        }
        $this->redirect('/account/listings');
    }

    /** Only possible while the item has never been ordered. */
    public function delete(Request $request, string $id): void
    {
        $seller  = $this->activeMember();
        $product = $this->find($seller, $this->id($id));
        if ((int) db()->fetchValue('SELECT COUNT(*) FROM order_items WHERE product_id = ?', [$product['id']]) > 0) {
            $this->back('/account/listings', '"' . $product['title'] . '" has been ordered before, so it cannot be deleted. You can withdraw it instead.');
        }
        $deleted = db()->transaction(static function () use ($product, $seller): int {
            ProductPhotos::deleteForProduct((int) $product['id']); // disc / box / extra photos: files + rows
            return db()->execute(
                'DELETE FROM products WHERE id = ? AND seller_id = ?
                    AND NOT EXISTS (SELECT 1 FROM order_items oi WHERE oi.product_id = products.id)',
                [$product['id'], $seller['id']]
            );
        });
        if ($deleted === 0) {
            $this->back('/account/listings', '"' . $product['title'] . '" has been ordered before, so it cannot be deleted. You can withdraw it instead.');
        }
        Forms::deleteUpload($product['image']);
        $this->ok('"' . $product['title'] . '" was deleted.');
        $this->redirect('/account/listings');
    }

    // ---------------------------------------------------------------- internals

    /** This member's OWN product or a 404: the IDOR guard for every action. */
    private function find(array $seller, int $id): array
    {
        $product = db()->fetch('SELECT * FROM products WHERE id = ? AND seller_id = ?', [$id, $seller['id']]);
        if ($product === null) {
            Response::abort(404);
        }
        return $product;
    }

    private function form(array $seller, ?array $product): void
    {
        $this->page('account/listings/form', [
            'seller'     => $seller,
            'product'    => $product,
            'commission' => $this->commission($seller),
            'categories' => db()->fetchAll('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order, name'),
            'platforms'  => db()->fetchAll('SELECT id, name FROM platforms WHERE is_active = 1 ORDER BY sort_order, name'),
            'hasOrders'  => $product !== null && (int) db()->fetchValue('SELECT COUNT(*) FROM order_items WHERE product_id = ?', [$product['id']]) > 0,
            'photos'     => $product !== null ? ProductPhotos::forProduct((int) $product['id']) : [],
            'errors'    => $this->app->session()->getFlash('ls_errors', []),
        ]);
    }

    private function invalid(string $path, array $errors, Request $request): void
    {
        $this->app->session()->flash('ls_errors', array_values($errors));
        $this->back($path, null, $request->all() + ['_form' => '1']);
    }

    private function save(Request $request, array $seller, ?array $product): void
    {
        $isEdit = $product !== null;
        $back   = $isEdit ? '/account/listings/' . $product['id'] . '/edit' : '/account/listings/new';
        $errors = [];

        if (!$isEdit && (int) db()->fetchValue("SELECT COUNT(*) FROM products WHERE seller_id = ? AND status = 'pending'", [$seller['id']]) >= self::MAX_PENDING) {
            $errors[] = 'You already have ' . self::MAX_PENDING . ' listings waiting for approval. Please wait until we have checked some of them.';
        }

        $title = $this->str($request, 'title');
        if ($title === '' || mb_strlen($title) > 190) {
            $errors[] = 'Title is required (max 190 characters).';
        }

        $category = db()->fetch('SELECT id FROM categories WHERE id = ? AND is_active = 1', [Forms::int($request->input('category_id')) ?? 0]);
        if (!$category) {
            $errors[] = 'Choose a category.';
        }

        $platform   = null;
        $platformId = Forms::int($request->input('platform_id')) ?? 0;
        if ($platformId > 0) {
            $platform = db()->fetch('SELECT id, slug FROM platforms WHERE id = ? AND is_active = 1', [$platformId]);
            if (!$platform) {
                $errors[] = 'That platform does not exist.';
            }
        }

        // New (sealed) or Used (grade + what is included). New copies always count as complete.
        $cond      = ListingCondition::read($request, $errors);
        $condition = $cond['condition'];
        $includes  = $cond['includes'];

        $edition = $this->str($request, 'edition') ?: 'Standard';
        if (mb_strlen($edition) > 30) {
            $errors[] = 'Edition can be at most 30 characters.';
        }

        $year    = null;
        $yearRaw = $this->str($request, 'year');
        if ($yearRaw !== '') {
            $year = Forms::int($yearRaw);
            if ($year === null || $year < 1970 || $year > (int) date('Y') + 1) {
                $errors[] = 'Year must be between 1970 and ' . ((int) date('Y') + 1) . '.';
                $year = null;
            }
        }

        $genres = [];
        foreach (explode(',', $this->str($request, 'genres')) as $g) {
            $g = trim(preg_replace('/\s+/', ' ', $g) ?? '');
            if ($g !== '' && !isset($genres[mb_strtolower($g)])) {
                $genres[mb_strtolower($g)] = $g;
            }
        }
        $genres = implode(',', array_values($genres));
        if (mb_strlen($genres) > 255) {
            $errors[] = 'Genres are too long (max 255 characters).';
        }

        $description = $this->str($request, 'description');
        if (mb_strlen($description) > 3000) {
            $errors[] = 'Description is too long (max 3000 characters).';
        }

        // Anti-bypass: buyers only ever talk to CyberGaming. Check the raw text AND the cleaned genres.
        foreach (['Title' => $title, 'Description' => $description, 'Edition' => $edition, 'Genres' => $genres] as $label => $text) {
            if ($text !== '' && ContactFilter::containsContact($text)) {
                $errors[] = ContactFilter::message($label);
            }
        }

        $priceIn = Forms::decimal($request->input('seller_price'));
        if ($priceIn === null || $priceIn < 1 || $priceIn > 9999) {
            $errors[] = 'The price you receive must be between $1 and $9,999 (e.g. 12 or 12.50).';
            $priceIn = 0.0;
        }

        $stock    = Forms::int($request->input('stock'));
        $minStock = $isEdit ? 0 : 1;
        if ($stock === null || $stock < $minStock || $stock > 99) {
            $errors[] = 'Stock must be a whole number from ' . $minStock . ' to 99.';
            $stock = $minStock;
        }

        $upload = ImageUpload::check($request->file('image'), $errors);

        // Photos: a USED copy needs disc + box outside + box inside (photos already saved count). Nothing is kept on any error.
        $photos = ListingCondition::photos($request, $product, $cond['used'], $errors);

        if ($errors) {
            if ($photos['hadFiles']) {
                $errors[] = 'Nothing was saved, so please choose your photos again.';
            }
            $this->invalid($back, $errors, $request);
        }

        // Money: the server decides the commission and the buyer price. Nothing the browser sends can change either.
        $commission = $this->commission($seller);
        $price      = round(Pricing::buyerPrice($priceIn, $commission), 2);

        $newImage = null;
        if ($upload !== null) {
            $newImage = ImageUpload::store($upload);
            if ($newImage === null) {
                ProductPhotos::discard($photos['staged']);
                $this->invalid($back, ['The image could not be saved. Please try again later.'], $request);
            }
        }
        $image    = $product['image'] ?? null;
        $oldImage = null;
        if ($newImage !== null) {
            $oldImage = $image;
            $image    = $newImage;
        } elseif ($isEdit && $request->input('remove_image') === '1') {
            $oldImage = $image;
            $image    = null;
        }

        $steelbook   = $request->input('is_steelbook') === '1' ? 1 : 0;
        $descValue   = $description !== '' ? $description : null;
        $genresVal   = $genres !== '' ? $genres : null;
        $platformVal = isset($platform['id']) ? (int) $platform['id'] : null;

        $resubmitted = false;
        $note        = '';
        if (!$isEdit) {
            $status = 'pending';
        } else {
            $status = (string) $product['status'];
            // Anything a buyer can see, or more stock, needs a fresh look from us before it stays live.
            $contentChanged = $product['title'] !== $title
                || (string) $product['description'] !== (string) $descValue
                || abs((float) $product['seller_price'] - $priceIn) > 0.001
                || (int) $product['category_id'] !== (int) $category['id']
                || ($product['platform_id'] === null ? null : (int) $product['platform_id']) !== $platformVal
                || $product['item_condition'] !== $condition
                || ListingCondition::includesChanged($product, $includes)
                || $photos['staged'] !== []
                || $photos['deleteExtra'] !== []
                || $product['edition'] !== $edition
                || (int) $product['is_steelbook'] !== $steelbook
                || ($product['year'] === null ? null : (int) $product['year']) !== $year
                || (string) $product['genres'] !== (string) $genresVal
                || $image !== $product['image']
                || $stock > (int) $product['stock'];

            if ($status === 'active' && $contentChanged) {
                $status      = 'pending';
                $resubmitted = true;
            } elseif ($status === 'sold' && $stock > 0) {
                $status = 'pending'; // restocked: goes through approval again
                $note   = ' It was sold, so it is waiting for approval again.';
            } elseif ($status === 'active' && $stock < 1) {
                $status = 'sold';
            }
        }

        $fields = [(int) $category['id'], $platformVal, $title, $descValue, $condition, $edition, $steelbook, $year, $genresVal, $priceIn, $commission, $price, $stock, $image, $status,
            $includes['includes_box'], $includes['includes_cover_art'], $includes['includes_manual']];

        try {
            $id = db()->transaction(function () use ($isEdit, $product, $seller, $fields, $title, $platform, $photos, $image): int {
                if ($isEdit) {
                    db()->execute(
                        'UPDATE products SET category_id = ?, platform_id = ?, title = ?, description = ?, item_condition = ?, edition = ?,
                                is_steelbook = ?, year = ?, genres = ?, seller_price = ?, commission_pct = ?, price = ?, stock = ?, image = ?, status = ?,
                                includes_box = ?, includes_cover_art = ?, includes_manual = ?
                          WHERE id = ? AND seller_id = ?',
                        [...$fields, $product['id'], $seller['id']]
                    );
                    $pid = (int) $product['id'];
                } else {
                    $slug = $this->uniqueSlug(slugify($title . ' ' . ($platform['slug'] ?? '')), 0);
                    $pid = db()->insert(
                        'INSERT INTO products (category_id, platform_id, title, description, item_condition, edition, is_steelbook,
                                year, genres, seller_price, commission_pct, price, stock, image, status,
                                includes_box, includes_cover_art, includes_manual, slug, seller_id)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [...$fields, $slug, $seller['id']]
                    );
                }
                ListingCondition::commit($pid, $photos['staged'], $photos['deleteExtra'], $image);
                return $pid;
            });
        } catch (\Throwable $e) {
            Forms::deleteUpload($newImage);
            ProductPhotos::discard($photos['staged']);
            throw $e;
        }

        ListingCondition::dropMainImage($oldImage);

        $msg = '"' . $title . '" saved. Buyers will pay ' . money($price) . ' and you receive ' . money($priceIn) . '.';
        if (!$isEdit) {
            $msg .= ' It is waiting for approval and goes live once we have checked it.';
        } elseif ($resubmitted) {
            $msg .= ' Because you changed the listing, it is waiting for approval again and is off the shop until we approve it.';
        } else {
            $msg .= $note;
        }
        $this->ok($msg);
        $this->redirect($isEdit ? '/account/listings/' . $id . '/edit' : '/account/listings');
    }

    /** Unique, and stable on edit (the slug is only generated on create). */
    private function uniqueSlug(string $base, int $ignoreId): string
    {
        $base = mb_substr($base, 0, 170);
        $slug = $base;
        $n    = 2;
        while ((int) db()->fetchValue('SELECT COUNT(*) FROM products WHERE slug = ? AND id <> ?', [$slug, $ignoreId]) > 0) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }

    /** Anonymous public code: S-1234, unique (same approach as the seller apply flow). */
    private function newCode(): string
    {
        for ($i = 0; $i < 50; $i++) {
            $code = 'S-' . random_int(1000, 9999);
            if ((int) db()->fetchValue('SELECT COUNT(*) FROM sellers WHERE code = ?', [$code]) === 0) {
                return $code;
            }
        }
        return 'S-' . random_int(100000, 999999);
    }
}
