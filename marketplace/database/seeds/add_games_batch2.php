<?php
declare(strict_types=1);

/**
 * Adds the second batch of house PS4 games (idempotent: existing slugs are skipped) and writes a placeholder cover for each.
 *   php database/seeds/add_games_batch2.php [--public=/path/to/web/root]
 * --public defaults to marketplace/public (local). On cPanel pass the domain's document root.
 * Prices are starting points; edit them in Admin > Products.
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

// title, edition, genres, year, price, accent colour, dark background colour
$games = [
    ['God of War', 'Standard', 'Action,Adventure', 2018, 18, '#c0392b', '#2a1210'],
    ['It Takes Two', 'Standard', 'Adventure,Platformer', 2021, 22, '#e67e22', '#2b1a0c'],
    ['EA Sports FC 26', 'Standard', 'Sports', 2025, 30, '#2ecc71', '#0f2418'],
    ['Dark Souls II', 'Standard', 'RPG,Action', 2014, 13, '#b8a06a', '#1c1810'],
    ['Star Wars Battlefront II: Elite Trooper Deluxe Edition', 'Deluxe', 'Shooter', 2017, 12, '#3498db', '#0d1b2a'],
    ['Resident Evil Revelations 2', 'Standard', 'Horror,Action', 2015, 12, '#e74c3c', '#240d0d'],
    ['Rayman Legends', 'Standard', 'Platformer', 2013, 12, '#f1c40f', '#2a2408'],
    ['Knack', 'Standard', 'Platformer,Action', 2013, 10, '#5dade2', '#0f2030'],
];

$dir = $public . '/uploads/covers';
if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
    exit("Cannot create $dir\n");
}
$cat = (int) db()->fetchValue("SELECT id FROM categories WHERE slug = 'games'");
$plat = (int) db()->fetchValue("SELECT id FROM platforms WHERE slug = 'ps4'");

function cover_svg(string $title, string $accent, string $bg): string
{
    $words = preg_split('/\s+/', strtoupper(preg_replace('/:.*$/', '', $title)));
    $lines = [];
    $cur = '';
    foreach ($words as $w) {
        if ($cur !== '' && strlen($cur . ' ' . $w) > 13) {
            $lines[] = $cur;
            $cur = $w;
        } else {
            $cur = trim($cur . ' ' . $w);
        }
    }
    $lines[] = $cur;
    $y = 450 - (count($lines) - 1) * 26;
    $text = '';
    foreach ($lines as $i => $l) {
        $text .= sprintf('<text x="300" y="%d" text-anchor="middle" font-family="Arial Black, Arial, sans-serif" font-size="40" font-weight="900" fill="white" letter-spacing="3">%s</text>', $y + $i * 52, htmlspecialchars($l, ENT_XML1));
    }
    return '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="900" viewBox="0 0 600 900"><defs><linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="' . $bg . '"/><stop offset="100%" stop-color="#0b0b10"/></linearGradient></defs>'
        . '<rect width="600" height="900" fill="url(#bg)"/><rect width="600" height="4" fill="' . $accent . '" opacity="0.8"/><rect y="896" width="600" height="4" fill="' . $accent . '" opacity="0.4"/>'
        . '<line x1="50" y1="' . ($y + count($lines) * 52 + 10) . '" x2="550" y2="' . ($y + count($lines) * 52 + 10) . '" stroke="' . $accent . '" stroke-width="3" opacity="0.7"/>'
        . $text . '<text x="300" y="820" text-anchor="middle" font-family="Arial, sans-serif" font-size="18" fill="' . $accent . '" opacity="0.8" letter-spacing="4">PS4</text></svg>';
}

$added = 0;
foreach ($games as [$title, $edition, $genres, $year, $price, $accent, $bg]) {
    $slug = slugify($title . '-ps4');
    if (db()->fetchValue('SELECT 1 FROM products WHERE slug = ?', [$slug])) {
        echo "skip (exists): $title\n";
        continue;
    }
    $file = "covers/$slug.jpg";
    if (!is_file($public . "/uploads/" . $file)) { // no downloaded cover art: draw a placeholder tile
        $file = "covers/$slug.svg";
        file_put_contents($public . "/uploads/" . $file, cover_svg($title, $accent, $bg));
    }
    $desc = "Used $title for PlayStation 4 in Good condition. Inspected before sale. Buy online in Lebanon with delivery.";
    db()->insert(
        "INSERT INTO products (seller_id, category_id, platform_id, slug, title, description, item_condition, edition, is_steelbook,
                               year, genres, seller_price, commission_pct, price, stock, image, status)
         VALUES (NULL, ?, ?, ?, ?, ?, 'Good', ?, 0, ?, ?, ?, 0, ?, 1, ?, 'active')",
        [$cat, $plat, $slug, $title, $desc, $edition, $year, $genres, $price, $price, $file]
    );
    echo "added: $title (\$$price)\n";
    $added++;
}
echo "Done: $added added\n";
