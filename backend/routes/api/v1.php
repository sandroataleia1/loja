<?php

declare(strict_types=1);

use App\Core\Auth\Http\Controllers\AuthController;
use App\Core\Platform\Http\Controllers\PlatformAuthController;
use App\Modules\Pix\Http\Controllers\PixWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PIX Webhooks (public — called by Asaas, no auth middleware)
|--------------------------------------------------------------------------
*/
Route::post('webhooks/pix/{tenantUuid}', [PixWebhookController::class, 'handle'])
    ->name('webhooks.pix')
    ->middleware('throttle:60,1');

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
    | Catalog Module — products.view required for management
    | GET routes (product search) left open so PDV/sellers can search.
    | Write-level protection is enforced inside catalog.php per-route.
    |----------------------------------------------------------------------
    */
    Route::prefix('catalog')->name('catalog.')->group(base_path('routes/api/v1/catalog.php'));

    /*
    |----------------------------------------------------------------------
    | Inventory Module — inventory.view required
    |----------------------------------------------------------------------
    */
    Route::prefix('inventory')->name('inventory.')
        ->middleware('permission:inventory.view')
        ->group(base_path('routes/api/v1/inventory.php'));

    /*
    |----------------------------------------------------------------------
    | Customers Module — customers.view required
    |----------------------------------------------------------------------
    */
    Route::prefix('customers')->name('customers.')
        ->middleware('permission:customers.view')
        ->group(base_path('routes/api/v1/customers.php'));

    /*
    |----------------------------------------------------------------------
    | Customer Tags Management — customers.view required
    |----------------------------------------------------------------------
    */
    Route::prefix('customer-tags')->name('customer-tags.')
        ->middleware('permission:customers.view')
        ->group(base_path('routes/api/v1/customer-tags.php'));

    /*
    |----------------------------------------------------------------------
    | Sales Module — sales.view required
    |----------------------------------------------------------------------
    */
    Route::prefix('sales')->name('sales.')
        ->middleware('permission:sales.view')
        ->group(base_path('routes/api/v1/sales.php'));

    /*
    |----------------------------------------------------------------------
    | Finance Module — financial.view required
    |----------------------------------------------------------------------
    */
    Route::prefix('finance')->name('finance.')
        ->middleware('permission:financial.view')
        ->group(base_path('routes/api/v1/finance.php'));

    /*
    |----------------------------------------------------------------------
    | Fiscal Module — fiscal.view required
    |----------------------------------------------------------------------
    */
    Route::prefix('fiscal')->name('fiscal.')
        ->middleware('permission:fiscal.view')
        ->group(base_path('routes/api/v1/fiscal.php'));

    /*
    |----------------------------------------------------------------------
    | Reports Module — financial.view required
    |----------------------------------------------------------------------
    */
    Route::prefix('reports')->name('reports.')
        ->middleware('permission:financial.view')
        ->group(base_path('routes/api/v1/reports.php'));

    /*
    |----------------------------------------------------------------------
    | POS Module — sales.view required (cashier ops use cashier.open etc
    | but POS access in general is gated by sales.view)
    |----------------------------------------------------------------------
    */
    Route::prefix('pos')->name('pos.')
        ->middleware('permission:sales.view')
        ->group(base_path('routes/api/v1/pos.php'));

    /*
    |----------------------------------------------------------------------
    | CRM Module — customers.view required
    |----------------------------------------------------------------------
    */
    Route::prefix('crm')->name('crm.')
        ->middleware('permission:customers.view')
        ->group(base_path('routes/api/v1/crm.php'));

    /*
    |----------------------------------------------------------------------
    | Purchasing Module — purchase_orders.view required
    |----------------------------------------------------------------------
    */
    Route::prefix('purchasing')->name('purchasing.')
        ->middleware('permission:purchase_orders.view')
        ->group(base_path('routes/api/v1/purchasing.php'));

    /*
    |----------------------------------------------------------------------
    | Sync Module — sales.view required (offline PDV sync)
    |----------------------------------------------------------------------
    */
    Route::prefix('sync')->name('sync.')
        ->middleware('permission:sales.view')
        ->group(base_path('routes/api/v1/sync.php'));

    /*
    |----------------------------------------------------------------------
    | RBAC — users.view required
    |----------------------------------------------------------------------
    */
    Route::prefix('rbac')->name('rbac.')
        ->middleware('permission:users.view')
        ->group(base_path('routes/api/v1/rbac.php'));

    /*
    |----------------------------------------------------------------------
    | Omnichannel — products.view required
    |----------------------------------------------------------------------
    */
    Route::prefix('omnichannel')->name('omnichannel.')
        ->middleware('permission:products.view')
        ->group(base_path('routes/api/v1/omnichannel.php'));

    /*
    |----------------------------------------------------------------------
    | Conditionals Module — sales.view required
    |----------------------------------------------------------------------
    */
    Route::prefix('conditionals')->name('conditionals.')
        ->middleware('permission:sales.view')
        ->group(base_path('routes/api/v1/conditionals.php'));

    /*
    |----------------------------------------------------------------------
    | Orders Module — sales.view required
    |----------------------------------------------------------------------
    */
    Route::middleware('permission:sales.view')
        ->group(base_path('routes/api/v1/orders.php'));
});
