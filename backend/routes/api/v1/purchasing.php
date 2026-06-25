<?php

declare(strict_types=1);

use App\Modules\Purchasing\Http\Controllers\PurchaseOrderController;
use App\Modules\Purchasing\Http\Controllers\SupplierController;
use App\Modules\Purchasing\Http\Controllers\SupplierImportController;
use Illuminate\Support\Facades\Route;

// ── Supplier CSV Import (deve vir antes dos parâmetros) ──────────────────────
Route::get('/suppliers/import/template',             [SupplierImportController::class, 'template'])->name('suppliers.import.template');
Route::post('/suppliers/import',                     [SupplierImportController::class, 'store'])->name('suppliers.import.store');
Route::get('/suppliers/import/{importLogId}/status', [SupplierImportController::class, 'status'])->name('suppliers.import.status');

// ── Suppliers ─────────────────────────────────────────────────────────────────
Route::get('/suppliers',              [SupplierController::class, 'index'])->name('suppliers.index');
Route::post('/suppliers',             [SupplierController::class, 'store'])->name('suppliers.store');
Route::get('/suppliers/{supplier}',   [SupplierController::class, 'show'])->name('suppliers.show');
Route::put('/suppliers/{supplier}',   [SupplierController::class, 'update'])->name('suppliers.update');
Route::delete('/suppliers/{supplier}',[SupplierController::class, 'destroy'])->name('suppliers.destroy')->middleware('permission:suppliers.delete');

// Supplier addresses
Route::get('/suppliers/{supplier}/addresses',                       [SupplierController::class, 'addresses'])->name('suppliers.addresses.index');
Route::post('/suppliers/{supplier}/addresses',                      [SupplierController::class, 'storeAddress'])->name('suppliers.addresses.store');
Route::put('/suppliers/{supplier}/addresses/{address}',             [SupplierController::class, 'updateAddress'])->name('suppliers.addresses.update');
Route::delete('/suppliers/{supplier}/addresses/{address}',          [SupplierController::class, 'destroyAddress'])->name('suppliers.addresses.destroy');

// Supplier contacts
Route::get('/suppliers/{supplier}/contacts',                        [SupplierController::class, 'contacts'])->name('suppliers.contacts.index');
Route::post('/suppliers/{supplier}/contacts',                       [SupplierController::class, 'storeContact'])->name('suppliers.contacts.store');
Route::put('/suppliers/{supplier}/contacts/{contact}',              [SupplierController::class, 'updateContact'])->name('suppliers.contacts.update');
Route::delete('/suppliers/{supplier}/contacts/{contact}',           [SupplierController::class, 'destroyContact'])->name('suppliers.contacts.destroy');

// ── Purchase Orders ───────────────────────────────────────────────────────────
Route::get('/orders',           [PurchaseOrderController::class, 'index'])->name('orders.index');
Route::post('/orders',          [PurchaseOrderController::class, 'store'])->name('orders.store');
Route::get('/orders/{purchaseOrder}',          [PurchaseOrderController::class, 'show'])->name('orders.show');
Route::post('/orders/{purchaseOrder}/send',    [PurchaseOrderController::class, 'send'])->name('orders.send');
Route::post('/orders/{purchaseOrder}/cancel',  [PurchaseOrderController::class, 'cancel'])->name('orders.cancel');
Route::post('/orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('orders.receive');
