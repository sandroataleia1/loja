<?php

declare(strict_types=1);

use App\Modules\Sellers\Http\Controllers\SellerController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:sellers.view')->group(function (): void {
    Route::get('/',           [SellerController::class, 'index'])->name('index');
    Route::get('/{seller}',   [SellerController::class, 'show'])->name('show');
});

Route::middleware('permission:sellers.create')->group(function (): void {
    Route::post('/', [SellerController::class, 'store'])->name('store');
});

Route::middleware('permission:sellers.update')->group(function (): void {
    Route::put('/{seller}', [SellerController::class, 'update'])->name('update');
});

Route::middleware('permission:sellers.delete')->group(function (): void {
    Route::delete('/{seller}', [SellerController::class, 'destroy'])->name('destroy');
});
