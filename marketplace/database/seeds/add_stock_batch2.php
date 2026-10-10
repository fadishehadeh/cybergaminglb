<?php
declare(strict_types=1);

/**
 * Second stock batch: owner's own photos of real inventory (Echo Dot, several mice, several keyboards).
 * Adds an "Electronics" category for the Echo Dot (non-gaming). Prices are informed market estimates,
 * flagged for the owner to correct; photos are the owner's own uploaded photos of the actual boxes.
 * Idempotent on slug. Also bumps HyperX Core Gaming Mouse stock to the owner's confirmed real count.
 *   php database/seeds/add_stock_batch2.php [--public=/path/to/web/root]
 */
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

// Electronics category (non-gaming), after the existing ones.
$maxSort = (int) db()->fetchValue('SELECT MAX(sort_order) FROM categories');
$electronicsId = (int) db()->fetchValue("SELECT id FROM categories WHERE slug = 'electronics'");
if ($electronicsId === 0) {
    $electronicsId = db()->insert(
        "INSERT INTO categories (slug, kind, name, is_active, sort_order) VALUES ('electronics', 'hardware', 'Electronics', 1, ?)",
        [$maxSort + 1]
    );
    echo "created category: electronics (id $electronicsId)\n";
}
$keyboardsId = (int) db()->fetchValue("SELECT id FROM categories WHERE slug = 'keyboards'");
$miceId = (int) db()->fetchValue("SELECT id FROM categories WHERE slug = 'mice'");

// slug, category_id, title, brand, model, price, stock, front image, extra images (same kind: unit_front/box_accessories), specs lines
$items = [
    ['amazon-echo-dot-3rd-gen', $electronicsId, 'Amazon Echo Dot (3rd Gen) Smart Speaker with Alexa', 'Amazon', 'Echo Dot (3rd Gen)', 30, 3,
        'hardware/echo-dot-1.jpg', ['hardware/echo-dot-2.jpg', 'hardware/echo-dot-3.jpg'],
        ['Voice assistant' => 'Alexa', 'Connectivity' => 'Wi-Fi, Bluetooth']],
    ['x5-rgb-weightless-gaming-mouse', $miceId, 'X5 RGB Weightless Gaming Mouse (Wired/2.4G/Bluetooth)', '', 'X5', 18, 2,
        'hardware/x5-rgb-mouse.jpg', [], ['Connectivity' => 'Wired / 2.4GHz / Bluetooth', 'Lighting' => 'RGB']],
    ['razer-deathadder-essential', $miceId, 'Razer DeathAdder Essential Gaming Mouse', 'Razer', 'DeathAdder Essential', 25, 1,
        'hardware/razer-deathadder-essential.jpg', [], ['Sensor' => '6,400 DPI optical', 'Buttons' => '5 programmable']],
    ['royal-kludge-rk168-black-mouse', $miceId, 'Royal Kludge RK168 Mouse — Black', 'Royal Kludge', 'RK168', 18, 1,
        'hardware/rk168-label.jpg', [], []],
    ['benq-zowie-za12-gaming-mouse', $miceId, 'BenQ Zowie ZA12 Gaming Mouse', 'BenQ Zowie', 'ZA12', 65, 2,
        'hardware/benq-zowie-za12.jpg', [], []],
    ['glorious-gmmk-modular-keyboard', $keyboardsId, 'Glorious GMMK Modular Mechanical Keyboard', 'Glorious', 'GMMK', 90, 3,
        'hardware/gmmk-modular.jpg', [], []],
    ['glorious-gmmk-compact-barebone', $keyboardsId, 'Glorious GMMK Compact Keyboard — Barebone Edition', 'Glorious', 'GMMK Compact (Barebone)', 70, 1,
        'hardware/gmmk-compact-barebone.jpg', [], ['Kit type' => 'Barebone (no switches or keycaps included)']],
    ['bajeal-tritium-60-keyboard', $keyboardsId, 'Bajeal Tritium 60% Mechanical RGB Gaming Keyboard', 'Bajeal', 'Tritium', 30, 4,
        'hardware/bajeal-tritium-60.jpg', [], ['Layout' => '60%', 'Lighting' => 'RGB']],
];

foreach ($items as [$slug, $catId, $title, $brand, $model, $price, $stock, $front, $extra, $specLines]) {
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
    foreach ($extra as $i => $path) {
        db()->insert('INSERT INTO product_images (product_id, path, kind, sort_order) VALUES (?, ?, ?, ?)', [$id, $path, 'box_accessories', $i + 1]);
    }
    echo "added: $title (\$$price, stock $stock)\n";
}

// Real count the owner confirmed for stock already listed.
db()->execute("UPDATE products SET stock = 2 WHERE slug = 'hyperx-core-gaming-mouse'");
$hxId = (int) db()->fetchValue("SELECT id FROM products WHERE slug = 'hyperx-core-gaming-mouse'");
if ($hxId > 0 && is_file($public . '/uploads/hardware/hyperx-core-own-box.jpg')) {
    db()->execute("DELETE FROM product_images WHERE product_id = ? AND path = 'hardware/hyperx-core-own-box.jpg'", [$hxId]);
    db()->insert('INSERT INTO product_images (product_id, path, kind, sort_order) VALUES (?, ?, ?, 2)', [$hxId, 'hardware/hyperx-core-own-box.jpg', 'box_accessories']);
    echo "updated: hyperx-core-gaming-mouse stock -> 2, added owner's box photo\n";
}
echo "Done.\n";
