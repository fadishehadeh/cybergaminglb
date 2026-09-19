<?php
declare(strict_types=1);

use App\Core\RequireCustomer;
use App\Modules\Account\ListingController;
use App\Modules\Account\SalesController;

$router = $app->router();

// Member selling pages hold private data: never index them.
$listingsPath = rtrim(request()->path(), '/');
if ($listingsPath === '/account/listings' || $listingsPath === '/account/sales'
    || str_starts_with($listingsPath, '/account/listings/')) {
    header('X-Robots-Tag: noindex, nofollow');
}

$customerOnly = [RequireCustomer::class];

// My listings
$router->get('/account/listings', [ListingController::class, 'index'], $customerOnly);
$router->get('/account/listings/new', [ListingController::class, 'create'], $customerOnly);
$router->post('/account/listings/new', [ListingController::class, 'store'], $customerOnly);
$router->post('/account/listings/join', [ListingController::class, 'join'], $customerOnly);
$router->get('/account/listings/{id}/edit', [ListingController::class, 'edit'], $customerOnly);
$router->post('/account/listings/{id}/edit', [ListingController::class, 'update'], $customerOnly);
$router->post('/account/listings/{id}/withdraw', [ListingController::class, 'withdraw'], $customerOnly);
$router->post('/account/listings/{id}/relist', [ListingController::class, 'relist'], $customerOnly);
$router->post('/account/listings/{id}/delete', [ListingController::class, 'delete'], $customerOnly);

// Items sold from my listings + my payouts
$router->get('/account/sales', [SalesController::class, 'index'], $customerOnly);
