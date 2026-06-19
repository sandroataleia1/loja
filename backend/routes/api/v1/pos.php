<?php

declare(strict_types=1);

use App\Modules\Pix\Http\Controllers\PixController;
use App\Modules\Pix\Http\Controllers\TenantGatewayController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PIX — Gateway Configuration (Settings)
|--------------------------------------------------------------------------
*/
Route::prefix('pix')->name('pix.')->group(function (): void {
    Route::get('settings',    [TenantGatewayController::class, 'show'])->name('settings.show');
    Route::put('settings',    [TenantGatewayController::class, 'update'])->name('settings.update');
    Route::get('public-info', [TenantGatewayController::class, 'publicInfo'])->name('public-info');

    Route::post('charges',                         [PixController::class, 'createCharge'])->name('charges.create');
    Route::get('charges/{charge}',                 [PixController::class, 'getCharge'])->name('charges.show');
    Route::post('charges/{charge}/simulate-payment', [PixController::class, 'simulatePayment'])->name('charges.simulate');
});
