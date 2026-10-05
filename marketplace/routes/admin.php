<?php
declare(strict_types=1);

use App\Core\RequireAdmin;
use App\Modules\Admin\AccountController;
use App\Modules\Admin\AuthController;
use App\Modules\Admin\CatalogController;
use App\Modules\Admin\DeliveryController;
use App\Modules\Admin\DashboardController;
use App\Modules\Admin\GuideController;
use App\Modules\Admin\OrderController;
use App\Modules\Admin\ProductController;
use App\Modules\Admin\SeoController;
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

// Sellers, payouts, buy-back/trade-in/swap requests, customers & wallet are retired (simple catalogue
// now, house stock only). The controllers are untouched in app/Modules/Admin — re-add these routes to
// bring the feature back.

// Orders
$router->get('/admin/orders', [OrderController::class, 'index'], $admin);
$router->get('/admin/orders/{id}', [OrderController::class, 'show'], $admin);
$router->post('/admin/orders/{id}/status', [OrderController::class, 'status'], $admin);
$router->post('/admin/orders/{id}/payment', [OrderController::class, 'payment'], $admin);
$router->post('/admin/orders/{id}/note', [OrderController::class, 'note'], $admin);

// Settings
$router->get('/admin/settings', [SettingsController::class, 'index'], $admin);
$router->post('/admin/settings', [SettingsController::class, 'update'], $admin);
$router->post('/admin/settings/recalculate', [SettingsController::class, 'recalculate'], $admin);
// Digital goods: master switch (hides/shows gift cards and Steam gifts on the whole storefront) and the starter catalogue.
$router->post('/admin/settings/digital', [SettingsController::class, 'digital'], $admin);
$router->post('/admin/settings/digital/starter', [SettingsController::class, 'starter'], $admin);
// Games: master switch (hides/shows all video games on the whole storefront; shop sells hardware only while off).
$router->post('/admin/settings/games', [SettingsController::class, 'games'], $admin);

// Delivery zones (edited from the Settings page)
$router->get('/admin/delivery', [DeliveryController::class, 'index'], $admin);
$router->post('/admin/delivery/zones', [DeliveryController::class, 'store'], $admin);
$router->post('/admin/delivery/zones/{id}', [DeliveryController::class, 'update'], $admin);

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
