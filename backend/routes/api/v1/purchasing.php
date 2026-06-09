<?php

declare(strict_types=1);

use App\Modules\Purchasing\Http\Controllers\PurchaseOrderController;
use App\Modules\Purchasing\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

// ── Suppliers ─────────────────────────────────────────────────────────────────
Route::apiResource('suppliers', SupplierController::class);

// ── Purchase Orders ───────────────────────────────────────────────────────────
Route::get('/orders',           [PurchaseOrderController::class, 'index'])->name('orders.index');
Route::post('/orders',          [PurchaseOrderController::class, 'store'])->name('orders.store');
Route::get('/orders/{purchaseOrder}',          [PurchaseOrderController::class, 'show'])->name('orders.show');
Route::post('/orders/{purchaseOrder}/send',    [PurchaseOrderController::class, 'send'])->name('orders.send');
Route::post('/orders/{purchaseOrder}/cancel',  [PurchaseOrderController::class, 'cancel'])->name('orders.cancel');
Route::post('/orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('orders.receive');
