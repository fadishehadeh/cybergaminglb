<?php
declare(strict_types=1);

use App\Modules\Storefront\CartController;
use App\Modules\Storefront\CatalogController;
use App\Modules\Storefront\CheckoutController;
use App\Modules\Storefront\HomeController;
use App\Modules\Storefront\PageController;
use App\Modules\Storefront\ProductController;
use App\Modules\Storefront\SeoController;

$router = $app->router();

// Home + catalogue
$router->get('/', [HomeController::class, 'index']);
$router->get('/shop', [CatalogController::class, 'shop']);
$router->get('/shop/{category}', [CatalogController::class, 'category']);
$router->get('/platform/{platform}', [CatalogController::class, 'platform']);
$router->get('/platform/{platform}/{category}', [CatalogController::class, 'platformCategory']);
$router->get('/product/{slug}', [ProductController::class, 'show']);

// Cart + checkout
$router->get('/cart', [CartController::class, 'index']);
$router->post('/cart/add', [CartController::class, 'add']);
$router->post('/cart/update', [CartController::class, 'update']);
$router->post('/cart/remove', [CartController::class, 'remove']);
$router->post('/cart/clear', [CartController::class, 'clear']);
$router->get('/checkout', [CheckoutController::class, 'form']);
$router->post('/checkout', [CheckoutController::class, 'place']);
$router->get('/order/{code}', [CheckoutController::class, 'confirmation']);

// Info pages
$router->get('/about', [PageController::class, 'about']);
$router->get('/how-it-works', [PageController::class, 'howItWorks']);
$router->get('/delivery-and-payment', [PageController::class, 'delivery']);
$router->get('/contact', [PageController::class, 'contact']);

// Sell / trade / swap / store credit are retired (simple catalogue now). The controllers are untouched
// in app/Modules/Storefront — re-add these routes to bring the feature back.

// SEO
$router->get('/robots.txt', [SeoController::class, 'robots']);
$router->get('/sitemap.xml', [SeoController::class, 'sitemap']);
