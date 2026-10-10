<?php
declare(strict_types=1);

/** Applies the owner's reviewed prices from the price-review artifact (custom overrides + accepted estimates). */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

$root = dirname(__DIR__, 2);
define('BASE_PATH', $root);
define('PUBLIC_PATH', $root . '/public');
require $root . '/app/Support/helpers.php';
spl_autoload_register(static function (string $c) use ($root): void {
    $f = $root . '/app/' . str_replace('\\', '/', substr($c, 4)) . '.php';
    if (is_file($f)) {
        require $f;
    }
});
new App\Core\Application($root);

// slug => final price
$prices = [
    // owner's custom overrides
    'bajeal-tritium-60-keyboard' => 20,
    'benq-zowie-za12-gaming-mouse' => 55,
    'ducky-mechanical-keyboard-white-brown-switches' => 150,
    'ducky-x-hyperx-limited-edition-keyboard-red-switches' => 250,
    'glorious-gmmk-mini-keyboard-black-brown-switches' => 85,
    'glorious-large-mousepad-black' => 25,
    'glorious-large-mousepad-white' => 25,
    'glorious-xl-mousepad-black' => 35,
    'razer-deathadder-essential' => 25,
    'tilted-nation-tnspectrep-keyboard' => 85,
    // accepted estimates (left untouched in the review)
    'glorious-gmmk-modular-keyboard' => 95,
    'glorious-gmmk-compact-barebone' => 65,
    'glorious-gmmk-tkl-barebone' => 80,
    'hyperx-core-gaming-mouse' => 28,
    'x5-rgb-weightless-gaming-mouse' => 20,
    'royal-kludge-rk168-black-mouse' => 22,
    'glorious-padded-wrist-rest-full-size' => 24,
    'glorious-mouse-bungee-white' => 15,
    'amazon-echo-dot-3rd-gen' => 35,
];

$stmt_check = 'SELECT price FROM products WHERE slug = ?';
foreach ($prices as $slug => $price) {
    $row = db()->fetch($stmt_check, [$slug]);
    if (!$row) {
        echo "skip (not found): $slug\n";
        continue;
    }
    $old = (float) $row['price'];
    db()->execute('UPDATE products SET price = ?, seller_price = ? WHERE slug = ?', [$price, $price, $slug]);
    echo "updated: $slug  \$$old -> \$$price\n";
}
echo "Done.\n";
