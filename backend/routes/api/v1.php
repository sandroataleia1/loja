<?php

declare(strict_types=1);

use App\Core\Auth\Http\Controllers\AuthController;
use App\Core\Platform\Http\Controllers\PlatformAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes (public) — Tenant Users
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::post('register',        [AuthController::class, 'register'])->name('register')->middleware('throttle:auth.register');
    Route::post('login',           [AuthController::class, 'login'])->name('login')->middleware('throttle:auth.login');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password')->middleware('throttle:auth.forgot-password');
    Route::post('reset-password',  [AuthController::class, 'resetPassword'])->name('reset-password')->middleware('throttle:auth.reset-password');
});

/*
|--------------------------------------------------------------------------
| Platform Auth Routes — PlatformUser (super_admin)
| Sem middleware de tenant. Guard: 'platform'.
|--------------------------------------------------------------------------
*/
Route::prefix('platform/auth')->name('platform.auth.')->group(function (): void {
    Route::post('login', [PlatformAuthController::class, 'login'])->name('login')->middleware('throttle:auth.platform-login');

    Route::middleware('auth:platform')->group(function (): void {
        Route::post('logout', [PlatformAuthController::class, 'logout'])->name('logout');
        Route::get('me',     [PlatformAuthController::class, 'me'])->name('me');
    });
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'tenant'])->group(function (): void {

    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');
    });

    /*
    |----------------------------------------------------------------------
    | Catalog Module
    |----------------------------------------------------------------------
    */
    Route::prefix('catalog')->name('catalog.')->group(base_path('routes/api/v1/catalog.php'));

    /*
    |----------------------------------------------------------------------
    | Inventory Module
    |----------------------------------------------------------------------
    */
    Route::prefix('inventory')->name('inventory.')->group(base_path('routes/api/v1/inventory.php'));

    /*
    |----------------------------------------------------------------------
    | Customers Module
    |----------------------------------------------------------------------
    */
    Route::prefix('customers')->name('customers.')->group(base_path('routes/api/v1/customers.php'));

    /*
    |----------------------------------------------------------------------
    | Customer Tags Management
    |----------------------------------------------------------------------
    */
    Route::prefix('customer-tags')->name('customer-tags.')->group(base_path('routes/api/v1/customer-tags.php'));

    /*
    |----------------------------------------------------------------------
    | Sales Module
    |----------------------------------------------------------------------
    */
    Route::prefix('sales')->name('sales.')->group(base_path('routes/api/v1/sales.php'));

    /*
    |----------------------------------------------------------------------
    | Finance Module
    |----------------------------------------------------------------------
    */
    Route::prefix('finance')->name('finance.')->group(base_path('routes/api/v1/finance.php'));

    /*
    |----------------------------------------------------------------------
    | Fiscal Module
    |----------------------------------------------------------------------
    */
    Route::prefix('fiscal')->name('fiscal.')->group(base_path('routes/api/v1/fiscal.php'));

    /*
    |----------------------------------------------------------------------
    | Reports Module
    |----------------------------------------------------------------------
    */
    Route::prefix('reports')->name('reports.')->group(base_path('routes/api/v1/reports.php'));

    /*
    |----------------------------------------------------------------------
    | POS Module
    |----------------------------------------------------------------------
    */
    Route::prefix('pos')->name('pos.')->group(base_path('routes/api/v1/pos.php'));

    /*
    |----------------------------------------------------------------------
    | CRM Module
    |----------------------------------------------------------------------
    */
    Route::prefix('crm')->name('crm.')->group(base_path('routes/api/v1/crm.php'));

    /*
    |----------------------------------------------------------------------
    | Purchasing Module — Suppliers & Purchase Orders
    |----------------------------------------------------------------------
    */
    Route::prefix('purchasing')->name('purchasing.')->group(base_path('routes/api/v1/purchasing.php'));

    /*
    |----------------------------------------------------------------------
    | Sync Module — Offline-First PDV Protocol
    |----------------------------------------------------------------------
    */
    Route::prefix('sync')->name('sync.')->group(base_path('routes/api/v1/sync.php'));

    /*
    |----------------------------------------------------------------------
    | RBAC — Roles, Permissions, TenantUsers, Store Access
    |----------------------------------------------------------------------
    */
    Route::prefix('rbac')->name('rbac.')->group(base_path('routes/api/v1/rbac.php'));

    /*
    |----------------------------------------------------------------------
    | Omnichannel — Channels, Publication, Pricing, Orders
    |----------------------------------------------------------------------
    */
    Route::prefix('omnichannel')->name('omnichannel.')->group(base_path('routes/api/v1/omnichannel.php'));

    /*
    |----------------------------------------------------------------------
    | Conditionals Module
    |----------------------------------------------------------------------
    */
    Route::prefix('conditionals')->name('conditionals.')->group(base_path('routes/api/v1/conditionals.php'));
});
