<?php

declare(strict_types=1);

use App\Modules\Finance\Http\Controllers\PaymentConditionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PaymentConditionController::class, 'index'])->name('index');
Route::post('/{uuid}/calculate', [PaymentConditionController::class, 'calculate'])->name('calculate');

Route::middleware('permission:settings.view')->group(function (): void {
    Route::post('/',          [PaymentConditionController::class, 'store'])->name('store');
    Route::put('/{uuid}',     [PaymentConditionController::class, 'update'])->name('update');
    Route::delete('/{uuid}',  [PaymentConditionController::class, 'destroy'])->name('destroy');
});
