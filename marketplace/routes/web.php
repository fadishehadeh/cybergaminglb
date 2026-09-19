<?php
declare(strict_types=1);

use App\Modules\Storefront\CartController;
use App\Modules\Storefront\CatalogController;
use App\Modules\Storefront\CheckoutController;
use App\Modules\Storefront\HomeController;
use App\Modules\Storefront\PageController;
use App\Modules\Storefront\ProductController;
use App\Modules\Storefront\SeoController;
use App\Modules\Storefront\SellController;
use App\Modules\Storefront\SwapController;
use App\Modules\Storefront\TradeController;

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
$router->get('/credit', [PageController::class, 'credit']);

// ---- Sell / Trade / Swap (customer money-making flows) ----------------------------------------
// Instant buy-back quote: form -> itemised quote -> details -> thanks. Prices are always recomputed server-side.
$router->get('/sell', [SellController::class, 'form']);
$router->post('/sell/quote', [SellController::class, 'quote']);
$router->post('/sell/edit', [SellController::class, 'edit']);
$router->post('/sell/submit', [SellController::class, 'submit']);
$router->get('/sell/quote', [SellController::class, 'resume']);
$router->get('/sell/submit', [SellController::class, 'toForm']);
$router->get('/sell/thanks/{code}', [SellController::class, 'thanks']);

// Trade-in calculator: games in, shop products out, pay or keep the balance.
$router->get('/trade', [TradeController::class, 'form']);
$router->post('/trade/quote', [TradeController::class, 'quote']);
$router->post('/trade/edit', [TradeController::class, 'edit']);
$router->post('/trade/submit', [TradeController::class, 'submit']);
$router->get('/trade/quote', [TradeController::class, 'resume']);
$router->get('/trade/submit', [TradeController::class, 'toForm']);
$router->get('/trade/thanks/{code}', [TradeController::class, 'thanks']);

// Swap board: public listings are first name + area only; new listings wait for admin approval.
$router->get('/swap', [SwapController::class, 'index']);
$router->post('/swap/submit', [SwapController::class, 'submit']);
$router->get('/swap/submit', static fn () => redirect('/swap'));
$router->get('/swap/thanks/{code}', [SwapController::class, 'thanks']);

// SEO
$router->get('/robots.txt', [SeoController::class, 'robots']);
$router->get('/sitemap.xml', [SeoController::class, 'sitemap']);
