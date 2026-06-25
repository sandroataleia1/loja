<?php

declare(strict_types=1);

use App\Core\Auth\Http\Controllers\AuthController;
use App\Core\Auth\Http\Controllers\PinLoginController;
use App\Core\Platform\Http\Controllers\PlatformAuthController;
use App\Http\Controllers\CepLookupController;
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
    Route::post('pin-login',       PinLoginController::class)->name('pin-login')->middleware('throttle:pin-login');
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
    | Orders Module — sales.view required
    |----------------------------------------------------------------------
    */
    Route::middleware('permission:sales.view')
        ->group(base_path('routes/api/v1/orders.php'));

    /*
    |----------------------------------------------------------------------
    | Payment Conditions — read open (needed by quote/order forms),
    | writes require settings.view (enforced inside route file)
    |----------------------------------------------------------------------
    */
    Route::prefix('payment-conditions')->name('payment-conditions.')
        ->group(base_path('routes/api/v1/payment-conditions.php'));

    /*
    |----------------------------------------------------------------------
    | Payment Methods — same permission pattern as conditions
    |----------------------------------------------------------------------
    */
    Route::prefix('payment-methods')->name('payment-methods.')
        ->group(base_path('routes/api/v1/payment-methods.php'));

    /*
    |----------------------------------------------------------------------
    | Approvals — workflow de aprovação por PIN (sales.view base, resolve
    | via PIN não exige permissão extra — qualquer usuário com PIN válido)
    |----------------------------------------------------------------------
    */
    Route::prefix('approvals')->name('approvals.')
        ->middleware('permission:sales.view')
        ->group(base_path('routes/api/v1/approvals.php'));

    /*
    |----------------------------------------------------------------------
    | Approval Rules — regras de aprovação configuráveis por tenant
    | Leitura: settings.view | Escrita: settings.update (por rota)
    |----------------------------------------------------------------------
    */
    Route::prefix('approval-rules')->name('approval-rules.')
        ->middleware('permission:settings.view')
        ->group(base_path('routes/api/v1/approval-rules.php'));

    /*
    |----------------------------------------------------------------------
    | Audit Logs — users.view required
    |----------------------------------------------------------------------
    */
    Route::prefix('audit-logs')->name('audit-logs.')
        ->middleware('permission:users.view')
        ->group(base_path('routes/api/v1/audit-logs.php'));

    /*
    |----------------------------------------------------------------------
    | System Settings — settings.view required for read,
    | settings.update required for write (enforced per-route inside)
    |----------------------------------------------------------------------
    */
    Route::prefix('system-settings')->name('system-settings.')
        ->middleware('permission:settings.view')
        ->group(base_path('routes/api/v1/system-settings.php'));

    /*
    |----------------------------------------------------------------------
    | Carriers Module — carriers.view required
    |----------------------------------------------------------------------
    */
    Route::prefix('carriers')->name('carriers.')
        ->middleware('permission:carriers.view')
        ->group(base_path('routes/api/v1/carriers.php'));

    /*
    |----------------------------------------------------------------------
    | Sellers Module — sellers.view required
    |----------------------------------------------------------------------
    */
    Route::prefix('sellers')->name('sellers.')
        ->middleware('permission:sellers.view')
        ->group(base_path('routes/api/v1/sellers.php'));

    /*
    |----------------------------------------------------------------------
    | Partners Module — partners.view required
    |----------------------------------------------------------------------
    */
    Route::prefix('partner-professionals')->name('partners.')
        ->middleware('permission:partners.view')
        ->group(base_path('routes/api/v1/partners.php'));

    /*
    |----------------------------------------------------------------------
    | Cost Centers — cost_centers.view required
    |----------------------------------------------------------------------
    */
    Route::prefix('cost-centers')->name('cost-centers.')
        ->middleware('permission:cost_centers.view')
        ->group(base_path('routes/api/v1/cost-centers.php'));

    /*
    |----------------------------------------------------------------------
    | CEP Lookup — proxy ViaCEP (dado público), apenas auth:sanctum
    |----------------------------------------------------------------------
    */
    Route::get('addresses/cep/{zipcode}', CepLookupController::class)
        ->name('addresses.cep')
        ->middleware('throttle:cep');
});
