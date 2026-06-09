<?php

declare(strict_types=1);

use App\Modules\Inventory\Http\Controllers\InventoryAdjustmentController;
use App\Modules\Inventory\Http\Controllers\InventoryBalanceController;
use App\Modules\Inventory\Http\Controllers\StockCountController;
use App\Modules\Inventory\Http\Controllers\StockReservationController;
use App\Modules\Inventory\Http\Controllers\StockTransferController;
use App\Modules\Inventory\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Inventory — Stores
|--------------------------------------------------------------------------
*/
Route::apiResource('stores', StoreController::class);

/*
|--------------------------------------------------------------------------
| Inventory — Balances & Movements
|--------------------------------------------------------------------------
*/
Route::get('balances',          [InventoryBalanceController::class, 'index'])->name('balances.index');
Route::post('balances/adjust',  [InventoryBalanceController::class, 'adjust'])->name('balances.adjust');
Route::get('movements',         [InventoryBalanceController::class, 'movements'])->name('movements.index');

/*
|--------------------------------------------------------------------------
| Inventory — Transfers
|--------------------------------------------------------------------------
*/
Route::get('transfers',                                [StockTransferController::class, 'index'])->name('transfers.index');
Route::post('transfers',                               [StockTransferController::class, 'store'])->name('transfers.store');
Route::get('transfers/{stockTransfer}',                [StockTransferController::class, 'show'])->name('transfers.show');
Route::post('transfers/{stockTransfer}/dispatch',      [StockTransferController::class, 'ship'])->name('transfers.dispatch');
Route::post('transfers/{stockTransfer}/receive',       [StockTransferController::class, 'receive'])->name('transfers.receive');
Route::post('transfers/{stockTransfer}/cancel',        [StockTransferController::class, 'cancel'])->name('transfers.cancel');

/*
|--------------------------------------------------------------------------
| Inventory — Stock Counts (Inventário Físico)
|--------------------------------------------------------------------------
*/
Route::get('counts',                              [StockCountController::class, 'index'])->name('counts.index');
Route::post('counts',                             [StockCountController::class, 'store'])->name('counts.store');
Route::get('counts/{stockCount}',                 [StockCountController::class, 'show'])->name('counts.show');
Route::post('counts/{stockCount}/commit',         [StockCountController::class, 'commit'])->name('counts.commit');

/*
|--------------------------------------------------------------------------
| Inventory — Adjustments (audit trail)
|--------------------------------------------------------------------------
*/
Route::get('adjustments',  [InventoryAdjustmentController::class, 'index'])->name('adjustments.index');
Route::post('adjustments', [InventoryAdjustmentController::class, 'store'])->name('adjustments.store');

/*
|--------------------------------------------------------------------------
| Inventory — Reservations
|--------------------------------------------------------------------------
*/
Route::get('reservations',                      [StockReservationController::class, 'index'])->name('reservations.index');
Route::post('reservations',                     [StockReservationController::class, 'store'])->name('reservations.store');
Route::delete('reservations/{reservation}',     [StockReservationController::class, 'destroy'])->name('reservations.destroy');
