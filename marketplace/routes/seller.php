<?php
declare(strict_types=1);

use App\Core\RequireSeller;
use App\Modules\Seller\AccountController;
use App\Modules\Seller\AuthController;
use App\Modules\Seller\DashboardController;
use App\Modules\Seller\PayoutController;
use App\Modules\Seller\ProductController;
use App\Modules\Seller\SalesController;

$router = $app->router();

// The seller portal must never be indexed. Only the public application page (/seller/apply) may be.
$sellerPath = rtrim(request()->path(), '/');
if (($sellerPath === '/seller' || str_starts_with($sellerPath, '/seller/')) && $sellerPath !== '/seller/apply') {
    header('X-Robots-Tag: noindex, nofollow');
}

$sellerOnly = [RequireSeller::class];

// Public: apply + sign in (storefront layout)
$router->get('/seller/apply', [AuthController::class, 'showApply']);
$router->post('/seller/apply', [AuthController::class, 'apply']);
$router->get('/seller/apply/thanks', [AuthController::class, 'thanks']);
$router->get('/seller/login', [AuthController::class, 'showLogin']);
$router->post('/seller/login', [AuthController::class, 'login']);
$router->post('/seller/logout', [AuthController::class, 'logout'], $sellerOnly);

// Portal (every handler also re-checks that the seller record is ACTIVE and scopes queries to it)
$router->get('/seller', [DashboardController::class, 'index'], $sellerOnly);

$router->get('/seller/products', [ProductController::class, 'index'], $sellerOnly);
$router->get('/seller/products/new', [ProductController::class, 'create'], $sellerOnly);
$router->post('/seller/products/new', [ProductController::class, 'store'], $sellerOnly);
$router->get('/seller/products/{id}/edit', [ProductController::class, 'edit'], $sellerOnly);
$router->post('/seller/products/{id}/edit', [ProductController::class, 'update'], $sellerOnly);
$router->post('/seller/products/{id}/withdraw', [ProductController::class, 'withdraw'], $sellerOnly);
$router->post('/seller/products/{id}/relist', [ProductController::class, 'relist'], $sellerOnly);
$router->post('/seller/products/{id}/delete', [ProductController::class, 'delete'], $sellerOnly);

$router->get('/seller/sales', [SalesController::class, 'index'], $sellerOnly);
$router->get('/seller/payouts', [PayoutController::class, 'index'], $sellerOnly);

$router->get('/seller/account', [AccountController::class, 'index'], $sellerOnly);
$router->post('/seller/account', [AccountController::class, 'update'], $sellerOnly);
$router->post('/seller/account/password', [AccountController::class, 'password'], $sellerOnly);
