<?php
declare(strict_types=1);

/** Sixth stock drop: Glorious mousepads. Quantities counted directly from the owner's photos (visible boxes). */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

$root = dirname(__DIR__, 2);
$public = $root . '/public';
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--public=')) {
        $public = rtrim(substr($arg, 9), '/\\');
    }
}
define('BASE_PATH', $root);
define('PUBLIC_PATH', $public);
require $root . '/app/Support/helpers.php';
spl_autoload_register(static function (string $c) use ($root): void {
    $f = $root . '/app/' . str_replace('\\', '/', substr($c, 4)) . '.php';
    if (is_file($f)) {
        require $f;
    }
});
new App\Core\Application($root);

$mousepadsId = (int) db()->fetchValue("SELECT id FROM categories WHERE slug = 'mousepads'");

$items = [
    ['glorious-large-mousepad-black', 'Glorious Large Pro Gaming Mousepad — Black', 15, 2, 'hardware/glorious-mousepad-large.jpg', ['Size' => 'Large, 13" x 11" x 0.12" (330 x 279 x 2mm)', 'Color' => 'Black']],
    ['glorious-large-mousepad-white', 'Glorious Large Pro Gaming Mousepad — White', 15, 3, 'hardware/glorious-mousepad-large.jpg', ['Size' => 'Large, 13" x 11" x 0.12" (330 x 279 x 2mm)', 'Color' => 'White']],
    ['glorious-xl-mousepad-black', 'Glorious XL Pro Gaming Mousepad — Black', 20, 3, 'hardware/glorious-mousepad-xl-black.jpg', ['Size' => 'XL, 18" x 16" x 0.08" (457 x 406 x 2mm)', 'Color' => 'Black']],
];

foreach ($items as [$slug, $title, $price, $stock, $front, $specLines]) {
    if (db()->fetchValue('SELECT 1 FROM products WHERE slug = ?', [$slug])) {
        echo "skip (exists): $title\n";
        continue;
    }
    $specs = '';
    foreach ($specLines as $k => $v) {
        $specs .= "$k: $v\n";
    }
    $specs = trim($specs) ?: null;
    $desc = 'Glorious mousepad. Price is an estimate — please confirm in Admin > Products.';

    $id = db()->insert(
        "INSERT INTO products (seller_id, category_id, platform_id, slug, title, brand, model, description, item_condition,
                               is_steelbook, is_digital, seller_price, cost_price, commission_pct, price, stock, image, specs, status)
         VALUES (NULL, ?, NULL, ?, ?, 'Glorious', 'Pro Gaming Mousepad', ?, 'New', 0, 0, ?, NULL, 0, ?, ?, ?, ?, 'active')",
        [$mousepadsId, $slug, $title, $desc, $price, $price, $stock, $front, $specs]
    );
    db()->insert('INSERT INTO product_images (product_id, path, kind, sort_order) VALUES (?, ?, ?, 0)', [$id, $front, 'unit_front']);
    echo "added: $title (\$$price, stock $stock)\n";
}
echo "Done.\n";
