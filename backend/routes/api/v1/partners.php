<?php

declare(strict_types=1);

use App\Modules\Partners\Http\Controllers\PartnerController;
use Illuminate\Support\Facades\Route;

Route::get('/',                                                    [PartnerController::class, 'index'])->name('index');
Route::get('/{partnerProfessional}',                               [PartnerController::class, 'show'])->name('show');

Route::middleware('permission:partners.create')->group(function (): void {
    Route::post('/', [PartnerController::class, 'store'])->name('store');
});

Route::middleware('permission:partners.update')->group(function (): void {
    Route::put('/{partnerProfessional}',                           [PartnerController::class, 'update'])->name('update');
    Route::post('/{partnerProfessional}/contacts',                 [PartnerController::class, 'storeContact'])->name('contacts.store');
    Route::delete('/{partnerProfessional}/contacts/{contact}',     [PartnerController::class, 'destroyContact'])->name('contacts.destroy');
});

Route::middleware('permission:partners.delete')->group(function (): void {
    Route::delete('/{partnerProfessional}', [PartnerController::class, 'destroy'])->name('destroy');
});
