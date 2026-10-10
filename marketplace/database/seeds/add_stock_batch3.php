<?php
declare(strict_types=1);

/** Third stock drop: one more keyboard, plus a spec confirmation photo for the existing RK61 White. */
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

$keyboardsId = (int) db()->fetchValue("SELECT id FROM categories WHERE slug = 'keyboards'");

$slug = 'tilted-nation-tnspectrep-keyboard';
if (!db()->fetchValue('SELECT 1 FROM products WHERE slug = ?', [$slug])) {
    $specs = "Layout: 68-key\nLighting: RGB";
    $desc = 'Tilted Nation TNSpectreP. Price is an estimate — please confirm in Admin > Products.';
    $id = db()->insert(
        "INSERT INTO products (seller_id, category_id, platform_id, slug, title, brand, model, description, item_condition,
                               is_steelbook, is_digital, seller_price, cost_price, commission_pct, price, stock, image, specs, status)
         VALUES (NULL, ?, NULL, ?, ?, 'Tilted Nation', 'TNSpectreP', ?, 'New', 0, 0, 28, NULL, 0, 28, 2, ?, ?, 'active')",
        [$keyboardsId, $slug, 'Tilted Nation TNSpectreP 68-Key RGB Gaming Keyboard', $desc, 'hardware/tilted-nation-tnspectrep.jpg', $specs]
    );
    db()->insert('INSERT INTO product_images (product_id, path, kind, sort_order) VALUES (?, ?, ?, 0)', [$id, 'hardware/tilted-nation-tnspectrep.jpg', 'unit_front']);
    echo "added: Tilted Nation TNSpectreP ($28, stock 2)\n";
} else {
    echo "skip (exists): tilted-nation-tnspectrep-keyboard\n";
}

$rk = db()->fetch("SELECT id, specs FROM products WHERE slug = 'rk61-mechanical-keyboard-white'");
if ($rk && !str_contains((string) $rk['specs'], 'Switch')) {
    $specs = trim((string) $rk['specs']) . "\nSwitch: Brown";
    db()->execute('UPDATE products SET specs = ? WHERE id = ?', [$specs, $rk['id']]);
    db()->insert('INSERT INTO product_images (product_id, path, kind, sort_order) VALUES (?, ?, ?, 2)', [$rk['id'], 'hardware/rk61-white-label.jpg', 'box_accessories']);
    echo "updated: rk61-mechanical-keyboard-white specs + label photo\n";
}
echo "Done.\n";
