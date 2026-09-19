<?php
declare(strict_types=1);

// Local dev: app code sits next to public/. Production (cPanel): app code lives in a sibling folder outside the web root.
$base = is_dir(dirname(__DIR__) . '/app') ? dirname(__DIR__) : dirname(__DIR__) . '/cybergaminglb-app';
define('BASE_PATH', $base);
define('PUBLIC_PATH', __DIR__);

require BASE_PATH . '/app/Support/helpers.php';

spl_autoload_register(static function (string $class): void {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$app = new \App\Core\Application(BASE_PATH);

require BASE_PATH . '/routes/web.php';
require BASE_PATH . '/routes/admin.php';
require BASE_PATH . '/routes/seller.php';
require BASE_PATH . '/routes/account.php';
require BASE_PATH . '/routes/account_listings.php';

$app->run();
