<?php
declare(strict_types=1);

/** Runs every database/migrations/*.sql that has not run yet.  php database/migrate.php  */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

$root = dirname(__DIR__);
define('BASE_PATH', $root);
define('PUBLIC_PATH', $root . '/public');
require $root . '/app/Support/helpers.php';

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', env('DB_HOST', '127.0.0.1'), (int) env('DB_PORT', 3306), env('DB_DATABASE', 'cybergaminglb')),
    (string) env('DB_USERNAME', 'root'),
    (string) env('DB_PASSWORD', ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (name VARCHAR(120) PRIMARY KEY, ran_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)');
$done = $pdo->query('SELECT name FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);

$files = glob(__DIR__ . '/migrations/*.sql') ?: [];
sort($files);
$ran = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $done, true)) {
        continue;
    }
    echo "Running $name ... ";
    $pdo->exec((string) file_get_contents($file));
    $pdo->prepare('INSERT INTO schema_migrations (name) VALUES (?)')->execute([$name]);
    echo "ok\n";
    $ran++;
}
echo $ran === 0 ? "Nothing to migrate\n" : "Migrated $ran file(s)\n";
