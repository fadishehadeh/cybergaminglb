<?php
declare(strict_types=1);

/** Fifth stock drop: a Glorious mouse bungee (accessories). */
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

$slug = 'glorious-mouse-bungee-white';
if (!db()->fetchValue('SELECT 1 FROM products WHERE slug = ?', [$slug])) {
    $specs = "Color: White\nFunction: Flexible mouse cable management";
    $desc = 'Glorious Gaming Mouse Bungee. Price is an estimate — please confirm in Admin > Products.';
    $id = db()->insert(
        "INSERT INTO products (seller_id, category_id, platform_id, slug, title, brand, model, description, item_condition,
                               is_steelbook, is_digital, seller_price, cost_price, commission_pct, price, stock, image, specs, status)
         VALUES (NULL, ?, NULL, ?, ?, 'Glorious', 'Mouse Bungee', ?, 'New', 0, 0, 12, NULL, 0, 12, 1, ?, ?, 'active')",
        [$accessoriesId, $slug, 'Glorious Mouse Bungee — White', $desc, 'hardware/glorious-mouse-bungee-white.jpg', $specs]
    );
    db()->insert('INSERT INTO product_images (product_id, path, kind, sort_order) VALUES (?, ?, ?, 0)', [$id, 'hardware/glorious-mouse-bungee-white.jpg', 'unit_front']);
    echo "added: Glorious Mouse Bungee White (\$12, stock 1)\n";
} else {
    echo "skip (exists): $slug\n";
}
echo "Done.\n";
