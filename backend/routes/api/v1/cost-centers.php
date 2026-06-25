<?php

declare(strict_types=1);

use App\Modules\Finance\Http\Controllers\CostCenterController;
use Illuminate\Support\Facades\Route;

Route::get('/',              [CostCenterController::class, 'index'])->name('index');
Route::get('/tree',          [CostCenterController::class, 'tree'])->name('tree');
Route::get('/{costCenter}',  [CostCenterController::class, 'show'])->name('show');

Route::middleware('permission:cost_centers.create')->group(function (): void {
    Route::post('/', [CostCenterController::class, 'store'])->name('store');
});

Route::middleware('permission:cost_centers.update')->group(function (): void {
    Route::put('/{costCenter}', [CostCenterController::class, 'update'])->name('update');
});

Route::middleware('permission:cost_centers.delete')->group(function (): void {
    Route::delete('/{costCenter}', [CostCenterController::class, 'destroy'])->name('destroy');
});
