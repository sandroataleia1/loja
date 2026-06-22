<?php

declare(strict_types=1);

use App\Modules\Finance\Http\Controllers\PaymentMethodController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PaymentMethodController::class, 'index'])->name('index');

Route::middleware('permission:settings.view')->group(function (): void {
    Route::post('/',          [PaymentMethodController::class, 'store'])->name('store');
    Route::put('/{uuid}',     [PaymentMethodController::class, 'update'])->name('update');
    Route::delete('/{uuid}',  [PaymentMethodController::class, 'destroy'])->name('destroy');
});
