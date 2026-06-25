<?php

declare(strict_types=1);

use App\Modules\Customers\Http\Controllers\CustomerController;
use App\Modules\Customers\Http\Controllers\CustomerImportController;
use App\Modules\Customers\Http\Controllers\CustomerTagController;
use Illuminate\Support\Facades\Route;

// ── CSV Import (deve vir antes dos parâmetros para evitar conflito) ──────────
Route::get('/import/template',             [CustomerImportController::class, 'template'])->name('import.template');
Route::post('/import',                     [CustomerImportController::class, 'store'])->name('import.store');
Route::get('/import/{importLogId}/status', [CustomerImportController::class, 'status'])->name('import.status');

// ── Customer resource ────────────────────────────────────────────────────────
Route::get('/',              [CustomerController::class, 'index'])->name('index');
Route::post('/',             [CustomerController::class, 'store'])->name('store');
Route::get('/{customer}',    [CustomerController::class, 'show'])->name('show');
Route::put('/{customer}',    [CustomerController::class, 'update'])->name('update');
Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');

// ── Address sub-resource ─────────────────────────────────────────────────────
Route::get('/{customer}/addresses',                    [CustomerController::class, 'addresses'])->name('addresses.index');
Route::post('/{customer}/addresses',                   [CustomerController::class, 'storeAddress'])->name('addresses.store');
Route::put('/{customer}/addresses/{address}',          [CustomerController::class, 'updateAddress'])->name('addresses.update');
Route::delete('/{customer}/addresses/{address}',       [CustomerController::class, 'destroyAddress'])->name('addresses.destroy');

// ── Contact sub-resource ─────────────────────────────────────────────────────
Route::get('/{customer}/contacts',                     [CustomerController::class, 'contacts'])->name('contacts.index');
Route::post('/{customer}/contacts',                    [CustomerController::class, 'storeContact'])->name('contacts.store');
Route::put('/{customer}/contacts/{contact}',           [CustomerController::class, 'updateContact'])->name('contacts.update');
Route::delete('/{customer}/contacts/{contact}',        [CustomerController::class, 'destroyContact'])->name('contacts.destroy');

// ── Tag assignment sub-resource ──────────────────────────────────────────────
Route::post('/{customer}/tags',        [CustomerController::class, 'attachTag'])->name('tags.attach');
Route::delete('/{customer}/tags/{tag}', [CustomerController::class, 'detachTag'])->name('tags.detach');
