<?php
declare(strict_types=1);

use App\Modules\Storefront\CollectionController;
use App\Modules\Storefront\FeedController;
use App\Modules\Storefront\GuideController;
use App\Modules\Storefront\LlmsController;
use App\Modules\Storefront\ZoneController;

$router = $app->router();

// Machine-readable files (robots.txt and sitemap.xml are registered in routes/web.php and served by SeoController)
$router->get('/feeds/products.xml', [FeedController::class, 'xml']);
$router->get('/feeds/products.json', [FeedController::class, 'json']);
$router->get('/llms.txt', [LlmsController::class, 'short']);
$router->get('/llms-full.txt', [LlmsController::class, 'full']);

// Guides (articles table)
$router->get('/guides', [GuideController::class, 'index']);
$router->get('/guides/{slug}', [GuideController::class, 'show']);

// Local delivery pages, one per active delivery zone
$router->get('/delivery-to', [ZoneController::class, 'hub']);
$router->get('/delivery-to/{zone}', [ZoneController::class, 'show']);

// Programmatic collections
$router->get('/collections', [CollectionController::class, 'index']);
$router->get('/collections/{slug}', [CollectionController::class, 'show']);
