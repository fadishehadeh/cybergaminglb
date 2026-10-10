<?php
declare(strict_types=1);

/** Fourth stock drop: a Glorious padded wrist rest (accessories) and a GMMK Tenkeyless Barebone keyboard. */
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

$accessoriesId = (int) db()->fetchValue("SELECT id FROM categories WHERE slug = 'accessories'");
$keyboardsId = (int) db()->fetchValue("SELECT id FROM categories WHERE slug = 'keyboards'");

$items = [
    ['glorious-padded-wrist-rest-full-size', $accessoriesId, 'Glorious Padded Keyboard Wrist Rest — Full Size', 'Glorious', 'Padded Wrist Rest (Full Size)', 18, 1,
        'hardware/glorious-wrist-rest-full-size.jpg', ['Fits' => 'Full-size mechanical keyboards', 'Size' => '17.5" x 4" / 444mm x 102mm']],
    ['glorious-gmmk-tkl-barebone', $keyboardsId, 'Glorious GMMK Tenkeyless Keyboard — Barebone Edition', 'Glorious', 'GMMK TKL (Barebone)', 85, 1,
        'hardware/gmmk-tkl-barebone.jpg', ['Layout' => 'Tenkeyless (TKL)', 'Kit type' => 'Barebone (no switches or keycaps included)']],
];

foreach ($items as [$slug, $catId, $title, $brand, $model, $price, $stock, $front, $specLines]) {
    if (db()->fetchValue('SELECT 1 FROM products WHERE slug = ?', [$slug])) {
        echo "skip (exists): $title\n";
        continue;
    }
    $specs = '';
    foreach ($specLines as $k => $v) {
        $specs .= "$k: $v\n";
    }
    $specs = trim($specs) ?: null;
    $desc = trim($brand . ' ' . $model) . '. Price is an estimate — please confirm in Admin > Products.';

    $id = db()->insert(
        "INSERT INTO products (seller_id, category_id, platform_id, slug, title, brand, model, description, item_condition,
                               is_steelbook, is_digital, seller_price, cost_price, commission_pct, price, stock, image, specs, status)
         VALUES (NULL, ?, NULL, ?, ?, ?, ?, ?, 'New', 0, 0, ?, NULL, 0, ?, ?, ?, ?, 'active')",
        [$catId, $slug, $title, $brand, $model, $desc, $price, $price, $stock, $front, $specs]
    );
    db()->insert('INSERT INTO product_images (product_id, path, kind, sort_order) VALUES (?, ?, ?, 0)', [$id, $front, 'unit_front']);
    echo "added: $title (\$$price, stock $stock)\n";
}
echo "Done.\n";
