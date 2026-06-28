<?php

declare(strict_types=1);

use App\Modules\Sellers\Http\Controllers\SellerCommissionController;
use App\Modules\Sellers\Http\Controllers\SellerController;
use App\Modules\Sellers\Http\Controllers\SellerRegionController;
use App\Modules\Sellers\Http\Controllers\SellerTargetController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:sellers.view')->group(function (): void {
    Route::get('/',         [SellerController::class, 'index'])->name('index');
    Route::get('/{seller}', [SellerController::class, 'show'])->name('show');

    // ── Metas ────────────────────────────────────────────────────────────────
    Route::get('/{seller}/targets',         [SellerTargetController::class, 'index'])->name('targets.index');
    // ── Comissões ────────────────────────────────────────────────────────────
    Route::get('/{seller}/commissions',     [SellerCommissionController::class, 'index'])->name('commissions.index');
    // ── Regiões ──────────────────────────────────────────────────────────────
    Route::get('/{seller}/regions',         [SellerRegionController::class, 'index'])->name('regions.index');
});

Route::middleware('permission:sellers.create')->group(function (): void {
    Route::post('/', [SellerController::class, 'store'])->name('store');
});

Route::middleware('permission:sellers.update')->group(function (): void {
    Route::put('/{seller}', [SellerController::class, 'update'])->name('update');

    // ── Metas ────────────────────────────────────────────────────────────────
    Route::post('/{seller}/targets',              [SellerTargetController::class, 'store'])->name('targets.store');
    Route::delete('/{seller}/targets/{target}',   [SellerTargetController::class, 'destroy'])->name('targets.destroy');

    // ── Comissões ────────────────────────────────────────────────────────────
    Route::post('/{seller}/commissions',                     [SellerCommissionController::class, 'store'])->name('commissions.store');
    Route::patch('/{seller}/commissions/{commission}',       [SellerCommissionController::class, 'update'])->name('commissions.update');

    // ── Regiões ──────────────────────────────────────────────────────────────
    Route::post('/{seller}/regions',              [SellerRegionController::class, 'store'])->name('regions.store');
    Route::delete('/{seller}/regions/{region}',   [SellerRegionController::class, 'destroy'])->name('regions.destroy');
});

Route::middleware('permission:sellers.delete')->group(function (): void {
    Route::delete('/{seller}', [SellerController::class, 'destroy'])->name('destroy');
});
