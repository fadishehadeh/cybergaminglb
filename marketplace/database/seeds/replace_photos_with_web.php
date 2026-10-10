<?php
declare(strict_types=1);

/**
 * Replaces the owner's rough shelf photos with clean web-sourced product photos for the second stock batch,
 * and removes the owner's own box photo added to the HyperX Core mouse listing. Idempotent: safe to re-run.
 *   php database/seeds/replace_photos_with_web.php [--public=/path/to/web/root]
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

// slug => [front image, back image or null]
$replacements = [
    'amazon-echo-dot-3rd-gen' => ['hardware/echo-dot-web-front.jpg', 'hardware/echo-dot-web-back.jpg'],
    'x5-rgb-weightless-gaming-mouse' => ['hardware/x5-mouse-web.png', null],
    'razer-deathadder-essential' => ['hardware/razer-deathadder-web.jpg', null],
    'royal-kludge-rk168-black-mouse' => ['hardware/rk168-web.png', null],
    'benq-zowie-za12-gaming-mouse' => ['hardware/benq-za12-web.jpg', null],
    'glorious-gmmk-modular-keyboard' => ['hardware/gmmk-modular-web.jpg', null],
    'glorious-gmmk-compact-barebone' => ['hardware/gmmk-compact-barebone-web.jpg', null],
    'bajeal-tritium-60-keyboard' => ['hardware/bajeal-tritium-web.png', null],
    'glorious-gmmk-tkl-barebone' => ['hardware/gmmk-tkl-barebone-web.jpg', null],
    'glorious-padded-wrist-rest-full-size' => ['hardware/glorious-wrist-rest-web.jpg', null],
    'glorious-mouse-bungee-white' => ['hardware/glorious-bungee-web.jpg', null],
    'glorious-large-mousepad-black' => ['hardware/glorious-mousepad-large-web.jpg', null],
    'glorious-xl-mousepad-black' => ['hardware/glorious-mousepad-xl-web.jpg', null],
];

foreach ($replacements as $slug => [$front, $back]) {
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
    db()->execute("DELETE FROM product_images WHERE product_id = ?", [$id]);
    db()->insert('INSERT INTO product_images (product_id, path, kind, sort_order) VALUES (?, ?, ?, 0)', [$id, $front, 'unit_front']);
    if ($back !== null && is_file(PUBLIC_PATH . '/uploads/' . $back)) {
        db()->insert('INSERT INTO product_images (product_id, path, kind, sort_order) VALUES (?, ?, ?, 1)', [$id, $back, 'unit_back']);
    }
    echo "replaced: $slug\n";
}

// Remove the owner's own box photo that was attached to the existing HyperX Core listing.
$hx = db()->fetch("SELECT id FROM products WHERE slug = 'hyperx-core-gaming-mouse'");
if ($hx) {
    db()->execute("DELETE FROM product_images WHERE product_id = ? AND path = 'hardware/hyperx-core-own-box.jpg'", [$hx['id']]);
    echo "cleaned: hyperx-core-gaming-mouse (removed owner's box photo)\n";
}
echo "Done.\n";
