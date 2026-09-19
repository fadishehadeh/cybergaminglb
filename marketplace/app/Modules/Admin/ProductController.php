<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;
use App\Modules\Seller\ListingCondition;
use App\Support\ContactFilter;
use App\Support\Pricing;
use App\Support\ProductPhotos;

final class ProductController extends AdminController
{
    public const STATUSES   = ['pending', 'active', 'sold', 'hidden'];
    public const CONDITIONS = ['New', 'Like New', 'Good', 'Fair'];
    private const REVIEW_BASE = '/admin/products?status=pending';
    private const MAX_IMAGE = 3 * 1024 * 1024;

    public function index(Request $request): void
    {
        $q        = Forms::text($request->query('q'));
        $status   = Forms::text($request->query('status'));
        $category = Forms::int($request->query('category')) ?? 0;
        $platform = Forms::int($request->query('platform')) ?? 0;
        $seller   = Forms::text($request->query('seller'));
        $steelbook = Forms::text($request->query('steelbook'));
        if (!in_array($steelbook, ['1', '0'], true)) {
            $steelbook = '';
        }
        $kind = Forms::text($request->query('kind'));
        if (!in_array($kind, ['physical', 'digital'], true)) {
            $kind = '';
        }
        $cond = Forms::text($request->query('condition'));
        if (!in_array($cond, ['new', 'used'], true)) {
            $cond = '';
        }
        $missing = Forms::text($request->query('missing')) === '1' ? '1' : '';

        $where  = [];
        $params = [];
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where[]  = '(p.title LIKE ? OR p.slug LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }
        if (in_array($status, self::STATUSES, true)) {
            $where[]  = 'p.status = ?';
            $params[] = $status;
        }
        if ($category > 0) {
            $where[]  = 'p.category_id = ?';
            $params[] = $category;
        }
        if ($platform > 0) {
            $where[]  = 'p.platform_id = ?';
            $params[] = $platform;
        }
        if ($steelbook !== '') {
            $where[]  = 'p.is_steelbook = ?';
            $params[] = (int) $steelbook;
        }
        if ($kind !== '') {
            $where[]  = 'p.is_digital = ?';
            $params[] = $kind === 'digital' ? 1 : 0;
        }
        if ($cond !== '') {
            $where[] = $cond === 'new' ? "p.item_condition = 'New'" : "p.item_condition <> 'New'";
        }
        if ($missing === '1') {
            $where[] = ListingRules::missingSql('p');
        }
        if ($seller === 'house') {
            $where[] = 'p.seller_id IS NULL';
        } elseif (ctype_digit($seller) && (int) $seller > 0) {
            $where[]  = 'p.seller_id = ?';
            $params[] = (int) $seller;
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = (int) db()->fetchValue('SELECT COUNT(*) FROM products p' . $whereSql, $params);
        $pager = Pagination::fromRequest($request, $total, 25);

        $products = db()->fetchAll(
            'SELECT p.id, p.title, p.description, p.image, p.seller_id, p.seller_price, p.price, p.commission_pct, p.stock, p.status, p.is_steelbook,
                    p.is_digital, p.digital_kind, p.digital_region, p.item_condition, p.includes_box, p.includes_cover_art, p.includes_manual,
                    ' . ListingRules::photoCountSql('p') . ' AS photo_count,
                    pl.name AS platform, c.name AS category, s.code AS seller_code
               FROM products p
               LEFT JOIN platforms pl ON pl.id = p.platform_id
               LEFT JOIN categories c ON c.id = p.category_id
               LEFT JOIN sellers s ON s.id = p.seller_id'
            . $whereSql . ' ORDER BY p.created_at DESC, p.id DESC' . $pager->limitSql(),
            $params
        );

        $this->view('products/index', [
            'products'   => $products,
            'pager'      => $pager,
            'filters'    => compact('q', 'status', 'category', 'platform', 'seller', 'steelbook', 'kind', 'cond', 'missing'),
            'missingCount' => (int) db()->fetchValue('SELECT COUNT(*) FROM products p WHERE ' . ListingRules::missingSql('p')),
            'digital'    => Digital::counts(),
            'categories' => db()->fetchAll('SELECT id, name FROM categories ORDER BY sort_order, name'),
            'platforms'  => db()->fetchAll('SELECT id, name FROM platforms ORDER BY sort_order, name'),
            'sellers'    => db()->fetchAll('SELECT id, code, name FROM sellers ORDER BY code'),
            'pendingCount' => (int) db()->fetchValue("SELECT COUNT(*) FROM products WHERE status = 'pending'"),
            'steelbooks'   => db()->fetch(
                "SELECT COALESCE(SUM(status = 'active'), 0) AS active, COALESCE(SUM(status = 'hidden' AND stock > 0), 0) AS hidden, COUNT(*) AS total
                   FROM products WHERE is_steelbook = 1"
            ),
        ]);
    }

    public function create(Request $request): void
    {
        $preselect = Forms::int($request->query('seller'));
        $this->form(null, $preselect);
    }

    public function edit(Request $request, string $id): void
    {
        $product = $this->find($this->id($id));
        $this->form($product, null);
    }

    public function store(Request $request): void
    {
        $this->save($request, null);
    }

    public function update(Request $request, string $id): void
    {
        $this->save($request, $this->find($this->id($id)));
    }

    /** Review page for any listing (built for pending member/store listings): everything the owner must check before approving. */
    public function review(Request $request, string $id): void
    {
        $product = $this->find($this->id($id));
        $seller  = $product['seller_id']
            ? db()->fetch('SELECT id, code, name, type, phone, area, status, commission_pct FROM sellers WHERE id = ?', [$product['seller_id']])
            : null;
        $category = db()->fetchValue('SELECT name FROM categories WHERE id = ?', [$product['category_id']]);
        $platform = $product['platform_id'] ? db()->fetchValue('SELECT name FROM platforms WHERE id = ?', [$product['platform_id']]) : null;

        $this->view('products/review', [
            'p'        => $product,
            'seller'   => $seller,
            'category' => $category ?: '',
            'platform' => $platform ?: '',
            'photos'   => ProductPhotos::forProduct((int) $product['id']),
            'missing'  => ListingRules::missing($product),
            'contact'  => $seller && (ContactFilter::containsContact((string) $product['title']) || ContactFilter::containsContact((string) ($product['description'] ?? ''))),
            'next'     => self::REVIEW_BASE,
        ]);
    }

    public function approve(Request $request, string $id): void
    {
        $product = $this->find($this->id($id));
        $back    = $this->returnTo($request, '/admin/products');
        if ((int) $product['stock'] < 1) {
            $this->back($back, 'Set the stock above 0 before publishing "' . $product['title'] . '".');
        }
        $missing = ListingRules::missing($product);
        if ($missing && $request->input('legacy_ok') !== '1') {
            $this->back(
                '/admin/products/' . $product['id'] . '/review',
                '"' . $product['title'] . '" is a used game and is missing the ' . ListingRules::kindList($missing) . ' photo' . (count($missing) === 1 ? '' : 's')
                . '. It cannot be published yet. Add the photos, or tick "Publish without photos (legacy stock)" below if this is old stock.'
            );
        }
        $changed = db()->execute(
            "UPDATE products SET status = 'active' WHERE id = ? AND status IN ('pending', 'hidden')",
            [$product['id']]
        );
        $changed ? $this->ok('"' . $product['title'] . '" is now live.' . ($missing ? ' Published without photos (legacy stock).' : '')) : $this->fail('That listing is not waiting for approval.');
        $this->redirect($back);
    }

    public function hide(Request $request, string $id): void
    {
        $product = $this->find($this->id($id));
        db()->execute("UPDATE products SET status = 'hidden' WHERE id = ?", [$product['id']]);
        $this->ok('"' . $product['title'] . '" is hidden from the shop.');
        $this->redirect($this->returnTo($request, '/admin/products'));
    }

    public function sold(Request $request, string $id): void
    {
        $product = $this->find($this->id($id));
        db()->execute("UPDATE products SET status = 'sold', stock = 0 WHERE id = ?", [$product['id']]);
        $this->ok('"' . $product['title'] . '" marked as sold.');
        $this->redirect($this->returnTo($request, '/admin/products'));
    }

    public function delete(Request $request, string $id): void
    {
        $product = $this->find($this->id($id));
        // Listing photos (files and rows) first, then the product, then its main image if it is a separate file.
        ProductPhotos::deleteForProduct((int) $product['id']);
        db()->execute('DELETE FROM products WHERE id = ?', [$product['id']]);
        Forms::deleteUpload($product['image']);
        $this->ok('"' . $product['title'] . '" was deleted.');
        $this->redirect('/admin/products');
    }

    public function approveAll(Request $request): void
    {
        // Used games without their three photos are never bulk-approved: they go through the review page.
        $skipped = (int) db()->fetchValue("SELECT COUNT(*) FROM products p WHERE p.status = 'pending' AND p.stock > 0 AND " . ListingRules::missingSql('p'));
        $count   = db()->execute("UPDATE products p SET p.status = 'active' WHERE p.status = 'pending' AND p.stock > 0 AND NOT " . ListingRules::missingSql('p'));
        $note    = $skipped > 0 ? ' ' . $skipped . ' used listing' . ($skipped === 1 ? ' was' : 's were') . ' skipped because photos are missing: review them one by one.' : '';
        $count > 0
            ? $this->ok($count . ' pending listing' . ($count === 1 ? '' : 's') . ' approved.' . $note)
            : $this->fail('No pending listings with stock and complete photos to approve.' . $note);
        $this->redirect($this->returnTo($request, '/admin/products'));
    }

    /** Bulk: take every live steelbook edition out of the shop. */
    public function hideSteelbooks(Request $request): void
    {
        $count = db()->execute("UPDATE products SET status = 'hidden' WHERE is_steelbook = 1 AND status = 'active'");
        $count > 0
            ? $this->ok($count . ' steelbook product' . ($count === 1 ? ' was' : 's were') . ' hidden from the shop.')
            : $this->fail('There are no active steelbook products to hide.');
        $this->redirect($this->returnTo($request, '/admin/products'));
    }

    /** Bulk: put hidden steelbook editions (with stock) back in the shop. */
    public function showSteelbooks(Request $request): void
    {
        $count = db()->execute("UPDATE products SET status = 'active' WHERE is_steelbook = 1 AND status = 'hidden' AND stock > 0");
        $count > 0
            ? $this->ok($count . ' steelbook product' . ($count === 1 ? ' is' : 's are') . ' live in the shop again.')
            : $this->fail('There are no hidden steelbook products with stock to show.');
        $this->redirect($this->returnTo($request, '/admin/products'));
    }

    // ------------------------------------------------------------------------------------------------

    private function find(int $id): array
    {
        $product = db()->fetch('SELECT * FROM products WHERE id = ?', [$id]);
        if ($product === null) {
            \App\Core\Response::abort(404);
        }
        return $product;
    }

    private function form(?array $product, ?int $preselectSeller): void
    {
        $sellers = db()->fetchAll('SELECT id, code, name, commission_pct, status FROM sellers ORDER BY code');
        foreach ($sellers as &$s) {
            $s['pct'] = Pricing::commissionPct($s);
        }
        unset($s);

        $this->view('products/form', [
            'product'    => $product,
            'photos'     => $product ? ProductPhotos::forProduct((int) $product['id']) : [],
            'missing'    => $product ? ListingRules::missing($product) : [],
            'alreadyLive' => $product !== null && $product['status'] === 'active' && ListingRules::needsPhotos($product),
            'preselect'  => $preselectSeller,
            'sellers'    => $sellers,
            'categories' => db()->fetchAll('SELECT id, name, slug FROM categories ORDER BY sort_order, name'),
            'platforms'  => db()->fetchAll('SELECT id, name FROM platforms ORDER BY sort_order, name'),
        ]);
    }

    private function save(Request $request, ?array $product): void
    {
        $isEdit = $product !== null;
        $back   = $isEdit ? '/admin/products/' . $product['id'] . '/edit' : '/admin/products/create';
        $errors = [];

        $title = $this->str($request, 'title');
        if ($title === '' || mb_strlen($title) > 190) {
            $errors[] = 'Title is required (max 190 characters).';
        }

        // Digital item (gift card / Steam gift / game key): house stock only, condition always New, no physical-only fields.
        $isDigital     = (bool) $request->input('is_digital');
        $digitalKind   = null;
        $digitalRegion = null;
        if ($isDigital) {
            $kindIn = $this->str($request, 'digital_kind');
            if (!isset(Digital::KINDS[$kindIn])) {
                $errors[] = 'Choose what kind of digital item this is (gift card, Steam game gift or game key).';
            } else {
                $digitalKind = $kindIn;
            }
            $regionIn = $this->str($request, 'digital_region');
            if ($regionIn === 'Other') {
                $other = $this->str($request, 'digital_region_other');
                if ($other === '' || mb_strlen($other) > 40) {
                    $errors[] = 'Type the region (max 40 characters) or pick one from the list.';
                } else {
                    $digitalRegion = $other;
                }
            } elseif (in_array($regionIn, Digital::REGIONS, true)) {
                $digitalRegion = $regionIn;
            } else {
                $errors[] = 'Choose the region of the digital item (Global, US, UAE, KSA, EU, Turkey or Other).';
            }
        }

        $category = db()->fetch('SELECT id FROM categories WHERE id = ?', [Forms::int($request->input('category_id')) ?? 0]);
        if (!$category && $isDigital) {
            $category = db()->fetch('SELECT id FROM categories WHERE slug = ?', [Digital::CATEGORY_SLUG]);
        }
        if (!$category) {
            $errors[] = 'Choose a category.';
        }

        $platform   = null;
        $platformId = Forms::int($request->input('platform_id')) ?? 0;
        if ($platformId > 0) {
            $platform = db()->fetch('SELECT id, slug FROM platforms WHERE id = ?', [$platformId]);
            if (!$platform) {
                $errors[] = 'That platform does not exist.';
            }
        }

        // Physical items: New (sealed) or Used + grade, and what is included. Digital items are always New, nothing included.
        $cond      = $isDigital ? null : ListingRules::read($request, $product, $errors);
        $condition = $isDigital ? 'New' : ($cond['condition'] !== '' ? $cond['condition'] : 'Good');
        $includes  = $isDigital ? array_fill_keys(array_keys(ListingCondition::INCLUDES), null) : $cond['includes'];
        $usedPhysical = !$isDigital && $cond['used'];

        $edition = $isDigital ? 'Standard' : ($this->str($request, 'edition') ?: 'Standard');
        if (mb_strlen($edition) > 30) {
            $errors[] = 'Edition can be at most 30 characters.';
        }

        $year    = null;
        $yearRaw = $isDigital ? '' : $this->str($request, 'year');
        if ($yearRaw !== '') {
            $year = Forms::int($yearRaw);
            if ($year === null || $year < 1970 || $year > (int) date('Y') + 1) {
                $errors[] = 'Year must be between 1970 and ' . ((int) date('Y') + 1) . '.';
                $year = null;
            }
        }

        $genres = [];
        foreach (explode(',', $isDigital ? '' : $this->str($request, 'genres')) as $g) {
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
        if (mb_strlen($description) > 5000) {
            $errors[] = 'Description is too long (max 5000 characters).';
        }

        $seller   = null;
        $sellerIn = $this->str($request, 'seller_id');
        if ($sellerIn !== '' && $isDigital) {
            $errors[] = 'Digital items are house inventory only: set Seller to "House inventory".';
        } elseif ($sellerIn !== '') {
            $seller = ctype_digit($sellerIn) ? db()->fetch('SELECT id, commission_pct FROM sellers WHERE id = ?', [(int) $sellerIn]) : null;
            if (!$seller) {
                $errors[] = 'Choose a valid seller or "House inventory".';
            }
        }

        $priceIn = Forms::decimal($request->input('seller_price'));
        if ($priceIn === null || $priceIn <= 0 || $priceIn > 99999) {
            $errors[] = ($seller ? 'Seller price' : 'Price') . ' must be a positive amount (e.g. 12 or 12.50).';
            $priceIn = 0.0;
        }

        $stock = Forms::int($request->input('stock'));
        if ($stock === null || $stock < 0 || $stock > 9999) {
            $errors[] = 'Stock must be a whole number from 0 to 9999.';
            $stock = 0;
        }

        $status = $this->str($request, 'status');
        if (!in_array($status, self::STATUSES, true)) {
            $errors[] = 'Choose a status.';
        }

        $upload = $this->checkImage($request->file('image'), $errors);

        // Photos (physical items only). Never attached here: staged files are processed, and dropped again on any error.
        $photoData   = ['staged' => [], 'deleteExtra' => [], 'hadFiles' => false];
        $existing    = $isEdit ? ProductPhotos::existingKinds((int) $product['id']) : [];
        $liveStatus  = $status === 'active' && $stock >= 1 ? 'active' : ($status === 'active' ? 'sold' : $status);
        $legacyOk    = $request->input('legacy_ok') === '1';
        $publishedNoPhotos = false;
        if (!$isDigital) {
            $photoData = ListingCondition::photos($request, $product, false, $errors);
            if ($usedPhysical && $liveStatus === 'active' && !$errors) {
                $have    = array_unique(array_merge($existing, array_column($photoData['staged'], 'kind')));
                $lacking = array_values(array_diff(ListingRules::REQUIRED, $have));
                if ($lacking) {
                    // Listings that are already live as used stock keep their status when edited (old stock without photos).
                    $alreadyLive = $isEdit && $product['status'] === 'active' && !ListingRules::isNew($product) && (int) $product['is_digital'] === 0;
                    if (!$isEdit) {
                        $errors[] = 'A used game needs all three photos before it can go live. Missing: ' . ListingRules::kindList($lacking) . '. Add them, or save it as Hidden or Pending to finish later.';
                    } elseif (!$alreadyLive && !$legacyOk) {
                        $errors[] = 'This used game cannot go live yet. Missing photos: ' . ListingRules::kindList($lacking) . '. Add them, save it as Hidden or Pending, or tick "Publish without photos (legacy stock)".';
                    } else {
                        $publishedNoPhotos = true;
                    }
                    if ($errors) {
                        ProductPhotos::discard($photoData['staged']);
                        $photoData['staged'] = [];
                    }
                }
            }
        }
        if ($errors && $photoData['hadFiles']) {
            $errors[] = 'Nothing was saved, so please choose the photos again.';
        }

        if ($errors) {
            $this->invalid($back, $errors, $request);
        }

        // Money: house stock has no commission (the typed price is what the buyer pays, in $0.50 steps).
        $commission = $seller ? Pricing::commissionPct($seller) : 0.0;
        $price      = round(Pricing::buyerPrice($priceIn, $commission), 2);
        $sellerPrice = $seller ? $priceIn : $price;

        if ($status === 'active' && $stock < 1) {
            $status = 'sold';
        }

        // Image
        $newImage = null;
        if ($upload !== null) {
            $newImage = $this->storeImage($upload);
            if ($newImage === null) {
                ProductPhotos::discard($photoData['staged']);
                $this->invalid($back, ['The image could not be saved. Check that public/uploads is writable.'], $request);
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

        $fields = [
            $seller['id'] ?? null, (int) $category['id'], $platform['id'] ?? null, $title, $description !== '' ? $description : null,
            $condition, $includes['includes_box'], $includes['includes_cover_art'], $includes['includes_manual'],
            $edition, !$isDigital && $request->input('is_steelbook') ? 1 : 0, $isDigital ? 1 : 0, $digitalKind, $digitalRegion,
            $year, $genres !== '' ? $genres : null,
            $sellerPrice, $commission, $price, $stock, $image, $status,
        ];

        try {
            $id = db()->transaction(function () use ($isEdit, $product, $fields, $title, $platform, $photoData, $image): int {
                if ($isEdit) {
                    db()->execute(
                        'UPDATE products SET seller_id = ?, category_id = ?, platform_id = ?, title = ?, description = ?, item_condition = ?,
                                includes_box = ?, includes_cover_art = ?, includes_manual = ?,
                                edition = ?, is_steelbook = ?, is_digital = ?, digital_kind = ?, digital_region = ?, year = ?, genres = ?, seller_price = ?, commission_pct = ?, price = ?,
                                stock = ?, image = ?, status = ? WHERE id = ?',
                        [...$fields, $product['id']]
                    );
                    $id = (int) $product['id'];
                } else {
                    $slug = $this->uniqueSlug(slugify($title . ' ' . ($platform['slug'] ?? '')), 0);
                    $id = db()->insert(
                        'INSERT INTO products (seller_id, category_id, platform_id, title, description, item_condition, includes_box, includes_cover_art, includes_manual,
                                edition, is_steelbook, is_digital, digital_kind, digital_region, year, genres, seller_price, commission_pct, price, stock, image, status, slug)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [...$fields, $slug]
                    );
                }
                // Photos: replace-per-slot, delete ticked extras; a product without a main image uses the "box outside" photo.
                ListingCondition::commit($id, $photoData['staged'], $photoData['deleteExtra'], $image);
                return $id;
            });
        } catch (\Throwable $e) {
            Forms::deleteUpload($newImage);
            ProductPhotos::discard($photoData['staged']);
            throw $e;
        }

        if ($oldImage !== null) {
            ListingCondition::dropMainImage($oldImage); // keeps the file when a listing photo still uses it
        }

        $msg = '"' . $title . '" saved. Buyer pays ' . money($price);
        if ($seller) {
            $msg .= ' (seller gets ' . money($sellerPrice) . ', commission ' . money($price - $sellerPrice) . ' at ' . Forms::pct($commission) . ')';
        }
        if ($status === 'sold' && $request->input('status') === 'active') {
            $msg .= '. Stock is 0, so it was marked sold';
        }
        $msg .= '.';
        if ($usedPhysical && $publishedNoPhotos) {
            $msg .= ' Published without photos (legacy stock).';
        } elseif ($usedPhysical && ($lack = ListingRules::missing(['id' => $id, 'is_digital' => 0, 'item_condition' => $condition]))) {
            $msg .= ' Photos still missing: ' . ListingRules::kindList($lack) . '.' . ($status === 'active' ? '' : ' It cannot go live until all three are added.');
        }
        if ($isDigital && !digital_enabled() && $status === 'active') {
            $msg .= ' This is a digital item and the digital goods switch is OFF, so it is hidden on the website until you turn it on in Settings.';
        }
        // Seller-owned listings only: warn (never block) when the text looks like it contains contact details.
        if ($seller && (ContactFilter::containsContact($title) || ContactFilter::containsContact($description))) {
            $msg .= ' WARNING: the title or description looks like it contains contact details (phone, link, email or social handle). Check it before approving.';
        }
        $this->ok($msg);
        $this->redirect('/admin/products/' . $id . '/edit');
    }

    private function uniqueSlug(string $base, int $ignoreId): string
    {
        $base = mb_substr($base, 0, 170);
        $slug = $base;
        $n    = 2;
        while (db()->fetchValue('SELECT COUNT(*) FROM products WHERE slug = ? AND id <> ?', [$slug, $ignoreId]) > 0) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }

    /** @return array{tmp:string, ext:string}|null */
    private function checkImage(?array $file, array &$errors): ?array
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? 'The image is too large (max 3 MB).'
                : 'The image upload failed. Please try again.';
            return null;
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Invalid upload.';
            return null;
        }
        if ($file['size'] > self::MAX_IMAGE) {
            $errors[] = 'The image is too large (max 3 MB).';
            return null;
        }
        $info = @getimagesize($file['tmp_name']);
        $map  = [IMAGETYPE_JPEG => ['image/jpeg', 'jpg'], IMAGETYPE_PNG => ['image/png', 'png'], IMAGETYPE_WEBP => ['image/webp', 'webp']];
        if ($info === false || !isset($map[$info[2]])) {
            $errors[] = 'The image must be a JPG, PNG or WebP file.';
            return null;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        if ($finfo->file($file['tmp_name']) !== $map[$info[2]][0]) {
            $errors[] = 'The image file type does not match its contents.';
            return null;
        }
        return ['tmp' => $file['tmp_name'], 'ext' => $map[$info[2]][1]];
    }

    private function storeImage(array $upload): ?string
    {
        $dir = PUBLIC_PATH . '/uploads/products';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }
        $name = bin2hex(random_bytes(10)) . '.' . $upload['ext'];
        if (!@move_uploaded_file($upload['tmp'], $dir . '/' . $name)) {
            return null;
        }
        return 'products/' . $name;
    }
}
