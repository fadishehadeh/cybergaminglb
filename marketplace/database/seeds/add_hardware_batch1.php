<?php
declare(strict_types=1);

/**
 * Adds the owner's first batch of hardware (keyboards + a mouse) as HIDDEN drafts (idempotent: existing slugs
 * are skipped) with a placeholder tile image. Price is 0.00 and cost is empty on purpose: edit price, cost,
 * stock and upload the required photos in Admin > Products before publishing each one.
 *   php database/seeds/add_hardware_batch1.php [--public=/path/to/web/root]
 * --public defaults to marketplace/public (local). On cPanel pass the domain's document root.
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

// category slug, title, brand, model, specs lines, accent colour, dark background colour
$items = [
    ['keyboards', 'RK61 Mechanical Keyboard — Black', 'Royal Kludge', 'RK61', ['Layout' => '61-key (60%)', 'Color' => 'Black'], '#e74c3c', '#1a1414'],
    ['keyboards', 'RK61 Mechanical Keyboard — White', 'Royal Kludge', 'RK61', ['Layout' => '61-key (60%)', 'Color' => 'White'], '#95a5a6', '#20201f'],
    ['keyboards', 'Glorious GMMK Mini Keyboard — Black, Brown Switches', 'Glorious', 'GMMK Mini', ['Layout' => '61-key (60%)', 'Switches' => 'Brown', 'Color' => 'Black'], '#f39c12', '#1c1507'],
    ['keyboards', 'Ducky Mechanical Keyboard — White, Brown Switches', 'Ducky', '', ['Switches' => 'Brown', 'Color' => 'White'], '#3498db', '#0e1a24'],
    ['keyboards', 'Ducky x HyperX Limited Edition Keyboard, Red Switches', 'Ducky', 'x HyperX Limited Edition', ['Switches' => 'Red', 'Edition' => 'HyperX Limited Edition'], '#c0392b', '#1a0d0d'],
    ['mice', 'HyperX Core Gaming Mouse', 'HyperX', 'Core', [], '#9b59b6', '#160e1c'],
];

$dir = $public . '/uploads/hardware';
if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
    exit("Cannot create $dir\n");
}

function tile_svg(string $title, string $accent, string $bg): string
{
    $words = preg_split('/\s+/', strtoupper(preg_replace('/[\x{2014}].*$/u', '', $title)));
    $lines = [];
    $cur = '';
    foreach ($words as $w) {
        if ($cur !== '' && strlen($cur . ' ' . $w) > 14) {
            $lines[] = $cur;
            $cur = $w;
        } else {
            $cur = trim($cur . ' ' . $w);
        }
    }
    $lines[] = $cur;
    $y = 300 - (count($lines) - 1) * 24;
    $text = '';
    foreach ($lines as $i => $l) {
        $text .= sprintf('<text x="300" y="%d" text-anchor="middle" font-family="Arial Black, Arial, sans-serif" font-size="32" font-weight="900" fill="white" letter-spacing="2">%s</text>', $y + $i * 44, htmlspecialchars($l, ENT_XML1));
    }
    return '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="600" viewBox="0 0 600 600"><defs><linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="' . $bg . '"/><stop offset="100%" stop-color="#0b0b10"/></linearGradient></defs>'
        . '<rect width="600" height="600" fill="url(#bg)"/><rect width="600" height="4" fill="' . $accent . '" opacity="0.8"/><rect y="596" width="600" height="4" fill="' . $accent . '" opacity="0.4"/>'
        . $text . '</svg>';
}

$keyboardsCat = (int) db()->fetchValue("SELECT id FROM categories WHERE slug = 'keyboards'");
$miceCat = (int) db()->fetchValue("SELECT id FROM categories WHERE slug = 'mice'");
$catIds = ['keyboards' => $keyboardsCat, 'mice' => $miceCat];

$added = 0;
foreach ($items as [$catSlug, $title, $brand, $model, $specLines, $accent, $bg]) {
    $slug = slugify($title);
    if (db()->fetchValue('SELECT 1 FROM products WHERE slug = ?', [$slug])) {
        echo "skip (exists): $title\n";
        continue;
    }
    $file = "hardware/$slug.svg";
    file_put_contents($public . '/uploads/' . $file, tile_svg($title, $accent, $bg));
    $specs = '';
    foreach ($specLines as $k => $v) {
        $specs .= "$k: $v\n";
    }
    $specs = trim($specs) ?: null;
    $desc = trim($brand . ' ' . $model) . '. New, sealed. Add a description and photos, then set the price, cost and stock before publishing.';

    db()->insert(
        "INSERT INTO products (seller_id, category_id, platform_id, slug, title, brand, model, description, item_condition,
                               is_steelbook, is_digital, seller_price, cost_price, commission_pct, price, stock, image, specs, status)
         VALUES (NULL, ?, NULL, ?, ?, ?, ?, ?, 'New', 0, 0, 0, NULL, 0, 0, 1, ?, ?, 'hidden')",
        [$catIds[$catSlug], $slug, $title, $brand, $model, $desc, $file, $specs]
    );
    echo "added (hidden draft): $title\n";
    $added++;
}
echo "Done: $added added. Edit price, cost, stock, description and photos in Admin > Products, then publish (Active).\n";
