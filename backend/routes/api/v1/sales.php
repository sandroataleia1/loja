<?php

declare(strict_types=1);

use App\Modules\Sales\Http\Controllers\CashRegisterController;
use App\Modules\Sales\Http\Controllers\CashRegisterSessionController;
use App\Modules\Sales\Http\Controllers\SaleController;
use App\Modules\Sales\Http\Controllers\SaleReturnController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sales — Registros Físicos de Caixa (PDV)
|--------------------------------------------------------------------------
*/
Route::get('registers',                  [CashRegisterController::class, 'index'])->name('registers.index');
Route::post('registers',                 [CashRegisterController::class, 'store'])->name('registers.store');
Route::get('registers/{cashRegister}',   [CashRegisterController::class, 'show'])->name('registers.show');
Route::put('registers/{cashRegister}',   [CashRegisterController::class, 'update'])->name('registers.update');
Route::delete('registers/{cashRegister}',[CashRegisterController::class, 'destroy'])->name('registers.destroy');

/*
|--------------------------------------------------------------------------
| Sales — Cash Register Sessions (Sessões de Caixa)
|--------------------------------------------------------------------------
*/
Route::get('sessions',                                       [CashRegisterSessionController::class, 'index'])->name('sessions.index');
Route::post('sessions/open',                                 [CashRegisterSessionController::class, 'open'])->name('sessions.open');
Route::get('sessions/{cashRegisterSession}',                 [CashRegisterSessionController::class, 'show'])->name('sessions.show');
Route::post('sessions/{cashRegisterSession}/close',          [CashRegisterSessionController::class, 'close'])->name('sessions.close');
Route::post('sessions/{cashRegisterSession}/withdrawal',     [CashRegisterSessionController::class, 'withdrawal'])->name('sessions.withdrawal');
Route::post('sessions/{cashRegisterSession}/supply',         [CashRegisterSessionController::class, 'supply'])->name('sessions.supply');
Route::get('sessions/{cashRegisterSession}/movements',       [CashRegisterSessionController::class, 'movements'])->name('sessions.movements');
Route::get('sessions/{cashRegisterSession}/summary',         [CashRegisterSessionController::class, 'summary'])->name('sessions.summary');

/*
|--------------------------------------------------------------------------
| Sales — Vendas
|--------------------------------------------------------------------------
*/
Route::get('/',                              [SaleController::class, 'index'])->name('index');
Route::post('/',                             [SaleController::class, 'store'])->name('store');
Route::get('/{sale}',                        [SaleController::class, 'show'])->name('show');
Route::post('/{sale}/complete',              [SaleController::class, 'complete'])->name('complete');
Route::post('/{sale}/cancel',                [SaleController::class, 'cancel'])->name('cancel');
Route::post('/{sale}/payments',              [SaleController::class, 'addPayment'])->name('payments.add');
Route::post('/{sale}/discounts',             [SaleController::class, 'applyDiscount'])->name('discounts.apply');
Route::post('/{sale}/commissions',           [SaleController::class, 'addCommission'])->name('commissions.add');
Route::get('/{sale}/returns',                [SaleReturnController::class, 'bySale'])->name('returns.by_sale');

/*
|--------------------------------------------------------------------------
| Sales — Devoluções
|--------------------------------------------------------------------------
*/
Route::get('returns',           [SaleReturnController::class, 'index'])->name('returns.index');
Route::post('returns',          [SaleReturnController::class, 'store'])->name('returns.store');
Route::get('returns/{saleReturn}', [SaleReturnController::class, 'show'])->name('returns.show');
