<?php
declare(strict_types=1);

/**
 * CLI installer.
 *   php database/install.php [--admin-email=you@x.com --admin-password=secret] [--import-games=../public/covers] [--reset]
 * Reads DB settings from .env. On cPanel the database must already exist; locally it is created if missing.
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

$root = dirname(__DIR__);
define('BASE_PATH', $root);
define('PUBLIC_PATH', $root . '/public');
require $root . '/app/Support/helpers.php';

$opts = [];
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $arg, $m)) {
        $opts[$m[1]] = $m[2] ?? true;
    }
}

$host = env('DB_HOST', '127.0.0.1');
$port = (int) env('DB_PORT', 3306);
$name = env('DB_DATABASE', 'cybergaminglb');
$attr = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", env('DB_USERNAME', 'root'), env('DB_PASSWORD', ''), $attr);
} catch (PDOException) {
    $server = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", env('DB_USERNAME', 'root'), env('DB_PASSWORD', ''), $attr);
    $server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", env('DB_USERNAME', 'root'), env('DB_PASSWORD', ''), $attr);
    echo "Created database $name\n";
}

if (isset($opts['reset'])) {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t) {
        $pdo->exec("DROP TABLE `$t`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    echo "Dropped all tables\n";
}

$pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));
echo "Schema ready\n";

if (isset($opts['admin-email'], $opts['admin-password'])) {
    $email = strtolower((string) $opts['admin-email']);
    $hash = password_hash((string) $opts['admin-password'], PASSWORD_DEFAULT);
    $st = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES ('Admin', ?, ?, 'admin')
                         ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = 'admin', status = 'active'");
    $st->execute([$email, $hash]);
    echo "Admin user ready: $email\n";
}

if (isset($opts['import-games'])) {
    $file = __DIR__ . '/games.json';
    $games = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    $coversDir = is_string($opts['import-games']) ? $opts['import-games'] : null;

    $cat = (int) $pdo->query("SELECT id FROM categories WHERE slug = 'games'")->fetchColumn();
    $ps4 = (int) $pdo->query("SELECT id FROM platforms WHERE slug = 'ps4'")->fetchColumn();
    $ins = $pdo->prepare(
        'INSERT INTO products (seller_id, category_id, platform_id, slug, title, description, item_condition, edition, is_steelbook,
                               year, genres, seller_price, commission_pct, price, stock, image, status)
         VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 1, ?, ?)
         ON DUPLICATE KEY UPDATE title = VALUES(title), seller_price = VALUES(seller_price), price = VALUES(price), image = VALUES(image)'
    );

    $uploads = PUBLIC_PATH . '/uploads/covers';
    if ($coversDir && is_dir($coversDir) && !is_dir($uploads)) {
        mkdir($uploads, 0755, true);
    }

    $n = 0;
    foreach ($games as $g) {
        $image = null;
        if (!empty($g['image'])) {
            $base = basename($g['image']);
            $image = 'covers/' . $base;
            if ($coversDir && is_file("$coversDir/$base")) {
                copy("$coversDir/$base", "$uploads/$base");
            }
        }
        $steel = !empty($g['isSteelbook']);
        $title = (string) $g["title"] . ($steel ? " (Steelbook Edition)" : "");
        $cond = (string) ($g['condition'] ?? 'Good');
        $desc = "Used $title for PlayStation 4 in $cond condition" . ($steel ? ', steelbook edition' : '')
              . '. Inspected before sale. Buy online in Lebanon with delivery.';
        $ins->execute([
            $cat, $ps4, slugify($title . "-ps4"), $title, $desc, $cond, $g['edition'] ?? 'Standard', $steel ? 1 : 0,
            $g['year'] ?? null, implode(',', $g['genre'] ?? []), $g['price'], $g['price'], $image,
            ($g['inStock'] ?? true) ? 'active' : 'sold',
        ]);
        $n++;
    }
    echo "Imported $n games\n";
}

echo "Done\n";
