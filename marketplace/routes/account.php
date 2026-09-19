<?php
declare(strict_types=1);

use App\Core\RequireCustomer;
use App\Modules\Account\AuthController;
use App\Modules\Account\DashboardController;
use App\Modules\Account\OfferController;
use App\Modules\Account\OrderController;
use App\Modules\Account\ProfileController;
use App\Modules\Account\WalletController;

$router = $app->router();

$customerOnly = [RequireCustomer::class];

// Public: register + sign in (register and login are the only indexable account pages; every other page sets noindex)
$router->get('/account/register', [AuthController::class, 'showRegister']);
$router->post('/account/register', [AuthController::class, 'register']);
$router->get('/account/login', [AuthController::class, 'showLogin']);
$router->post('/account/login', [AuthController::class, 'login']);
$router->post('/account/logout', [AuthController::class, 'logout']);

// Signed-in customers. Every handler scopes its queries to the current user.
$router->get('/account', [DashboardController::class, 'index'], $customerOnly);
$router->get('/account/wallet', [WalletController::class, 'index'], $customerOnly);

$router->get('/account/orders', [OrderController::class, 'index'], $customerOnly);
$router->get('/account/orders/{code}', [OrderController::class, 'show'], $customerOnly);

$router->get('/account/offers', [OfferController::class, 'index'], $customerOnly);
$router->get('/account/offers/{code}', [OfferController::class, 'show'], $customerOnly);
$router->post('/account/offers/{code}/accept', [OfferController::class, 'accept'], $customerOnly);
$router->post('/account/offers/{code}/decline', [OfferController::class, 'decline'], $customerOnly);
$router->post('/account/offers/{code}/cancel', [OfferController::class, 'cancel'], $customerOnly);

$router->get('/account/profile', [ProfileController::class, 'index'], $customerOnly);
$router->post('/account/profile', [ProfileController::class, 'update'], $customerOnly);
$router->post('/account/profile/password', [ProfileController::class, 'password'], $customerOnly);
