<?php

declare(strict_types=1);

use App\Modules\Carriers\Http\Controllers\CarrierController;
use App\Modules\Carriers\Http\Controllers\CarrierFreightController;
use App\Modules\Carriers\Http\Controllers\CarrierOccurrenceController;
use Illuminate\Support\Facades\Route;

Route::get('/',          [CarrierController::class, 'index'])->name('index');
Route::get('/{carrier}', [CarrierController::class, 'show'])->name('show');

Route::middleware('permission:carriers.create')->group(function (): void {
    Route::post('/', [CarrierController::class, 'store'])->name('store');
});

Route::middleware('permission:carriers.update')->group(function (): void {
    Route::put('/{carrier}',                               [CarrierController::class, 'update'])->name('update');
    Route::get('/{carrier}/addresses',                     [CarrierController::class, 'addresses'])->name('addresses.index');
    Route::post('/{carrier}/addresses',                    [CarrierController::class, 'storeAddress'])->name('addresses.store');
    Route::put('/{carrier}/addresses/{address}',           [CarrierController::class, 'updateAddress'])->name('addresses.update');
    Route::delete('/{carrier}/addresses/{address}',        [CarrierController::class, 'destroyAddress'])->name('addresses.destroy');
    Route::get('/{carrier}/contacts',                      [CarrierController::class, 'contacts'])->name('contacts.index');
    Route::post('/{carrier}/contacts',                     [CarrierController::class, 'storeContact'])->name('contacts.store');
    Route::put('/{carrier}/contacts/{contact}',            [CarrierController::class, 'updateContact'])->name('contacts.update');
    Route::delete('/{carrier}/contacts/{contact}',         [CarrierController::class, 'destroyContact'])->name('contacts.destroy');

    // ── Tabelas de frete ─────────────────────────────────────────────────────
    Route::get('/{carrier}/freight-tables',                                              [CarrierFreightController::class, 'tables'])->name('freight-tables.index');
    Route::post('/{carrier}/freight-tables',                                             [CarrierFreightController::class, 'storeTable'])->name('freight-tables.store');
    Route::put('/{carrier}/freight-tables/{table}',                                      [CarrierFreightController::class, 'updateTable'])->name('freight-tables.update');
    Route::delete('/{carrier}/freight-tables/{table}',                                   [CarrierFreightController::class, 'destroyTable'])->name('freight-tables.destroy');
    Route::post('/{carrier}/freight-tables/{table}/ranges',                              [CarrierFreightController::class, 'storeRange'])->name('freight-tables.ranges.store');
    Route::delete('/{carrier}/freight-tables/{table}/ranges/{range}',                    [CarrierFreightController::class, 'destroyRange'])->name('freight-tables.ranges.destroy');
    Route::post('/{carrier}/freight-calculate',                                          [CarrierFreightController::class, 'calculate'])->name('freight-calculate');

    // ── Ocorrências ──────────────────────────────────────────────────────────
    Route::get('/{carrier}/occurrences',               [CarrierOccurrenceController::class, 'index'])->name('occurrences.index');
    Route::post('/{carrier}/occurrences',              [CarrierOccurrenceController::class, 'store'])->name('occurrences.store');
    Route::delete('/{carrier}/occurrences/{occurrence}', [CarrierOccurrenceController::class, 'destroy'])->name('occurrences.destroy');
});

Route::middleware('permission:carriers.delete')->group(function (): void {
    Route::delete('/{carrier}', [CarrierController::class, 'destroy'])->name('destroy');
});
