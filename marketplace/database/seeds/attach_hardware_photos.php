<?php
declare(strict_types=1);

/**
 * Attaches real product photos (already copied into public/uploads/hardware/) to the first hardware batch:
 * sets products.image to the front shot and adds unit_front/unit_back rows to product_images. Idempotent:
 * re-running replaces the image path and does not duplicate product_images rows.
 *   php database/seeds/attach_hardware_photos.php
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

// slug => [front file, back file or null]
$photos = [
    'rk61-mechanical-keyboard-black' => ['hardware/rk61-mechanical-keyboard-black-front.jpg', 'hardware/rk61-mechanical-keyboard-black-back.jpg'],
    'rk61-mechanical-keyboard-white' => ['hardware/rk61-mechanical-keyboard-white-front.png', null],
    'glorious-gmmk-mini-keyboard-black-brown-switches' => ['hardware/glorious-gmmk-mini-keyboard-black-brown-switches-front.jpg', null],
    'ducky-mechanical-keyboard-white-brown-switches' => ['hardware/ducky-mechanical-keyboard-white-brown-switches-front.jpg', null],
    'ducky-x-hyperx-limited-edition-keyboard-red-switches' => ['hardware/ducky-x-hyperx-limited-edition-keyboard-red-switches-front.jpg', null],
    'hyperx-core-gaming-mouse' => ['hardware/hyperx-core-gaming-mouse-front.jpg', 'hardware/hyperx-core-gaming-mouse-back.jpg'],
];

foreach ($photos as $slug => [$front, $back]) {
    $product = db()->fetch('SELECT id FROM products WHERE slug = ?', [$slug]);
    if (!$product) {
        echo "skip (not found): $slug\n";
        continue;
    }
    $id = (int) $product['id'];
    if (!is_file(PUBLIC_PATH . '/uploads/' . $front)) {
        echo "skip (file missing): $front\n";
        continue;
    }
    db()->execute('UPDATE products SET image = ? WHERE id = ?', [$front, $id]);
    db()->execute('DELETE FROM product_images WHERE product_id = ? AND kind IN (?, ?)', [$id, 'unit_front', 'unit_back']);
    db()->insert('INSERT INTO product_images (product_id, path, kind, sort_order) VALUES (?, ?, ?, 0)', [$id, $front, 'unit_front']);
    if ($back !== null && is_file(PUBLIC_PATH . '/uploads/' . $back)) {
        db()->insert('INSERT INTO product_images (product_id, path, kind, sort_order) VALUES (?, ?, ?, 1)', [$id, $back, 'unit_back']);
    }
    echo "updated: $slug\n";
}
echo "Done.\n";
