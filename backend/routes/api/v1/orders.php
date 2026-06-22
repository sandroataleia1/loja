<?php

declare(strict_types=1);

use App\Modules\Orders\Http\Controllers\OrderController;
use App\Modules\Orders\Http\Controllers\QuoteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Quotes (Orçamentos)
|--------------------------------------------------------------------------
*/
Route::prefix('quotes')->name('quotes.')->group(function (): void {
    Route::get('/',                        [QuoteController::class, 'index'])->name('index');
    Route::post('/',                       [QuoteController::class, 'store'])->name('store');
    Route::post('/resolve-seller-pin',     [QuoteController::class, 'resolveSellerPin'])->name('resolve-seller-pin')->middleware('throttle:10,1');
    Route::get('/by-number/{number}',      [QuoteController::class, 'showByNumber'])->name('by-number');
    Route::get('/{uuid}',                  [QuoteController::class, 'show'])->name('show');
    Route::delete('/{uuid}',               [QuoteController::class, 'destroy'])->name('destroy');
    Route::patch('/{uuid}/mark-sent',      [QuoteController::class, 'markSent'])->name('mark-sent');
    Route::post('/{uuid}/convert',         [QuoteController::class, 'convert'])->name('convert');
});

/*
|--------------------------------------------------------------------------
| Orders (Pedidos)
|--------------------------------------------------------------------------
*/
Route::prefix('orders')->name('orders.')->group(function (): void {
    Route::get('/',                        [OrderController::class, 'index'])->name('index');
    Route::post('/',                       [OrderController::class, 'store'])->name('store');
    Route::get('/by-number/{number}',      [OrderController::class, 'showByNumber'])->name('by-number');
    Route::get('/{uuid}',                  [OrderController::class, 'show'])->name('show');
    Route::delete('/{uuid}',              [OrderController::class, 'destroy'])->name('destroy');
    Route::patch('/{uuid}/status',         [OrderController::class, 'updateStatus'])->name('update-status');
});
