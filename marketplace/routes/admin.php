<?php
declare(strict_types=1);

use App\Core\RequireAdmin;
use App\Modules\Admin\AccountController;
use App\Modules\Admin\AuthController;
use App\Modules\Admin\CatalogController;
use App\Modules\Admin\CustomerController;
use App\Modules\Admin\DeliveryController;
use App\Modules\Admin\WalletController;
use App\Modules\Admin\DashboardController;
use App\Modules\Admin\GuideController;
use App\Modules\Admin\OrderController;
use App\Modules\Admin\PayoutController;
use App\Modules\Admin\ProductController;
use App\Modules\Admin\RequestController;
use App\Modules\Admin\SeoController;
use App\Modules\Admin\SellerController;
use App\Modules\Admin\SettingsController;

$router = $app->router();

// The admin panel must never be indexed.
if (str_starts_with(request()->path(), '/admin')) {
    header('X-Robots-Tag: noindex, nofollow');
}

$admin = [RequireAdmin::class];

// Auth
$router->get('/admin/login', [AuthController::class, 'showLogin']);
$router->post('/admin/login', [AuthController::class, 'login']);
$router->post('/admin/logout', [AuthController::class, 'logout'], $admin);

// Dashboard
$router->get('/admin', [DashboardController::class, 'index'], $admin);

// Products
$router->get('/admin/products', [ProductController::class, 'index'], $admin);
$router->get('/admin/products/create', [ProductController::class, 'create'], $admin);
$router->post('/admin/products/create', [ProductController::class, 'store'], $admin);
$router->post('/admin/products/approve-all', [ProductController::class, 'approveAll'], $admin);
// Bulk steelbook toggles: registered before the {id} routes so "steelbooks" is never read as an id.
$router->post('/admin/products/steelbooks/hide', [ProductController::class, 'hideSteelbooks'], $admin);
$router->post('/admin/products/steelbooks/show', [ProductController::class, 'showSteelbooks'], $admin);
$router->get('/admin/products/{id}/edit', [ProductController::class, 'edit'], $admin);
$router->post('/admin/products/{id}/edit', [ProductController::class, 'update'], $admin);
$router->get('/admin/products/{id}/review', [ProductController::class, 'review'], $admin);
$router->post('/admin/products/{id}/approve', [ProductController::class, 'approve'], $admin);
$router->post('/admin/products/{id}/hide', [ProductController::class, 'hide'], $admin);
$router->post('/admin/products/{id}/sold', [ProductController::class, 'sold'], $admin);
$router->post('/admin/products/{id}/delete', [ProductController::class, 'delete'], $admin);

// Sellers (private directory)
$router->get('/admin/sellers', [SellerController::class, 'index'], $admin);
$router->get('/admin/sellers/create', [SellerController::class, 'create'], $admin);
$router->post('/admin/sellers/create', [SellerController::class, 'store'], $admin);
$router->get('/admin/sellers/{id}', [SellerController::class, 'show'], $admin);
$router->get('/admin/sellers/{id}/edit', [SellerController::class, 'edit'], $admin);
$router->post('/admin/sellers/{id}/edit', [SellerController::class, 'update'], $admin);
$router->post('/admin/sellers/{id}/approve', [SellerController::class, 'approve'], $admin);
$router->post('/admin/sellers/{id}/suspend', [SellerController::class, 'suspend'], $admin);

// Orders
$router->get('/admin/orders', [OrderController::class, 'index'], $admin);
$router->get('/admin/orders/{id}', [OrderController::class, 'show'], $admin);
$router->post('/admin/orders/{id}/status', [OrderController::class, 'status'], $admin);
$router->post('/admin/orders/{id}/payment', [OrderController::class, 'payment'], $admin);
$router->post('/admin/orders/{id}/note', [OrderController::class, 'note'], $admin);

// Payouts
$router->get('/admin/payouts', [PayoutController::class, 'index'], $admin);
$router->post('/admin/payouts/{seller}/pay', [PayoutController::class, 'pay'], $admin);

// Buy-back / trade-in / swap requests
$router->get('/admin/requests', [RequestController::class, 'index'], $admin);
$router->get('/admin/requests/buyback/{id}', [RequestController::class, 'showBuyback'], $admin);
$router->post('/admin/requests/buyback/{id}', [RequestController::class, 'updateBuyback'], $admin);
$router->post('/admin/requests/buyback/{id}/contact', [RequestController::class, 'contact'], $admin);
$router->post('/admin/requests/buyback/{id}/offer', [RequestController::class, 'offer'], $admin);
$router->post('/admin/requests/buyback/{id}/decline', [RequestController::class, 'decline'], $admin);
$router->post('/admin/requests/buyback/{id}/collect', [RequestController::class, 'collect'], $admin);
$router->post('/admin/requests/buyback/{id}/complete', [RequestController::class, 'complete'], $admin);
$router->post('/admin/requests/buyback/{id}/cancel', [RequestController::class, 'cancel'], $admin);
// Local courier pickup (checked on the spot) and the hub-inspection reject flow.
$router->post('/admin/requests/buyback/{id}/spot-accept', [RequestController::class, 'spotAccept'], $admin);
$router->post('/admin/requests/buyback/{id}/spot-decline', [RequestController::class, 'spotDecline'], $admin);
$router->post('/admin/requests/buyback/{id}/inspect-reject', [RequestController::class, 'inspectReject'], $admin);
$router->post('/admin/requests/buyback/{id}/decision', [RequestController::class, 'decision'], $admin);
$router->post('/admin/requests/buyback/{id}/complete-revised', [RequestController::class, 'completeRevised'], $admin);
$router->post('/admin/requests/buyback/{id}/returned', [RequestController::class, 'markReturned'], $admin);
$router->post('/admin/requests/buyback/{id}/recycle', [RequestController::class, 'recycle'], $admin);
$router->post('/admin/requests/swap/{id}', [RequestController::class, 'updateSwap'], $admin);
$router->post('/admin/requests/swap/{id}/publish', [RequestController::class, 'publishSwap'], $admin);
$router->post('/admin/requests/swap/{id}/unpublish', [RequestController::class, 'unpublishSwap'], $admin);

// Settings
$router->get('/admin/settings', [SettingsController::class, 'index'], $admin);
$router->post('/admin/settings', [SettingsController::class, 'update'], $admin);
$router->post('/admin/settings/recalculate', [SettingsController::class, 'recalculate'], $admin);
// Digital goods: master switch (hides/shows gift cards and Steam gifts on the whole storefront) and the starter catalogue.
$router->post('/admin/settings/digital', [SettingsController::class, 'digital'], $admin);
$router->post('/admin/settings/digital/starter', [SettingsController::class, 'starter'], $admin);

// Delivery zones (edited from the Settings page)
$router->get('/admin/delivery', [DeliveryController::class, 'index'], $admin);
$router->post('/admin/delivery/zones', [DeliveryController::class, 'store'], $admin);
$router->post('/admin/delivery/zones/{id}', [DeliveryController::class, 'update'], $admin);

// Customers & wallet
$router->get('/admin/customers', [CustomerController::class, 'index'], $admin);
$router->get('/admin/customers/{id}', [CustomerController::class, 'show'], $admin);
$router->post('/admin/customers/{id}/adjust', [CustomerController::class, 'adjust'], $admin);
$router->post('/admin/customers/{id}/status', [CustomerController::class, 'status'], $admin);
$router->post('/admin/customers/{id}/password', [CustomerController::class, 'password'], $admin);
$router->get('/admin/wallet', [WalletController::class, 'index'], $admin);

// Categories & platforms
$router->get('/admin/catalog', [CatalogController::class, 'index'], $admin);
$router->post('/admin/catalog', [CatalogController::class, 'update'], $admin);
// Enable / disable is its own POST so it can ask for confirmation with the product count.
$router->post('/admin/catalog/category/{id}/toggle', [CatalogController::class, 'toggleCategory'], $admin);
$router->post('/admin/catalog/platform/{id}/toggle', [CatalogController::class, 'togglePlatform'], $admin);

// SEO & AI search
$router->get('/admin/seo', [SeoController::class, 'index'], $admin);
$router->post('/admin/seo', [SeoController::class, 'update'], $admin);

// Guides (articles) CMS. "create" is registered before the {id} routes.
$router->get('/admin/guides', [GuideController::class, 'index'], $admin);
$router->get('/admin/guides/create', [GuideController::class, 'create'], $admin);
$router->post('/admin/guides/create', [GuideController::class, 'store'], $admin);
$router->get('/admin/guides/{id}/edit', [GuideController::class, 'edit'], $admin);
$router->post('/admin/guides/{id}/edit', [GuideController::class, 'update'], $admin);
$router->post('/admin/guides/{id}/publish', [GuideController::class, 'publish'], $admin);
$router->post('/admin/guides/{id}/unpublish', [GuideController::class, 'unpublish'], $admin);
$router->post('/admin/guides/{id}/delete', [GuideController::class, 'delete'], $admin);

// Account
$router->get('/admin/account', [AccountController::class, 'index'], $admin);
$router->post('/admin/account', [AccountController::class, 'update'], $admin);
