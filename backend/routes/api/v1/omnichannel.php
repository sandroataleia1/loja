<?php

declare(strict_types=1);

use App\Modules\Omnichannel\Http\Controllers\ChannelController;
use App\Modules\Omnichannel\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Omnichannel Routes
|--------------------------------------------------------------------------
| All routes are tenant-scoped (tenant middleware applied upstream).
|--------------------------------------------------------------------------
*/

// ── Channels ──────────────────────────────────────────────────────────────────
Route::prefix('channels')->name('channels.')->group(function (): void {
    Route::get('/',                              [ChannelController::class, 'index'])->name('index');
    Route::post('/',                             [ChannelController::class, 'store'])->name('store');
    Route::get('/{channel}',                     [ChannelController::class, 'show'])->name('show');

    // Publication pipeline
    Route::post('/{channel}/products',           [ChannelController::class, 'publishProduct'])->name('products.publish');
    Route::delete('/{channel}/products/{productId}', [ChannelController::class, 'unpublishProduct'])->name('products.unpublish');

    // Per-channel pricing
    Route::put('/{channel}/prices',              [ChannelController::class, 'updatePrice'])->name('prices.update');
});

// ── Omnichannel Orders ────────────────────────────────────────────────────────
Route::prefix('orders')->name('orders.')->group(function (): void {
    Route::get('/',              [OrderController::class, 'index'])->name('index');
    Route::post('/',             [OrderController::class, 'store'])->name('store');
    Route::get('/{order}',       [OrderController::class, 'show'])->name('show');
    Route::post('/{order}/pay',  [OrderController::class, 'markPaid'])->name('pay');
    Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');
});
