<?php

declare(strict_types=1);

use App\Modules\Carriers\Http\Controllers\CarrierController;
use Illuminate\Support\Facades\Route;

Route::get('/',                                             [CarrierController::class, 'index'])->name('index');
Route::get('/{carrier}',                                   [CarrierController::class, 'show'])->name('show');

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
});

Route::middleware('permission:carriers.delete')->group(function (): void {
    Route::delete('/{carrier}', [CarrierController::class, 'destroy'])->name('destroy');
});
