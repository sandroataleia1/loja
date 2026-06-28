<?php

declare(strict_types=1);

use App\Modules\Pricing\Http\Controllers\ApplyDiscountController;
use App\Modules\Pricing\Http\Controllers\PriceResolverController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pricing — resolução e aplicação de preços/descontos
| Usados por PDV, orçamentos e pedidos.
|--------------------------------------------------------------------------
*/

// Resolve preço de uma variante para um cliente (GET para ser cacheable pelo CDN)
Route::get('/resolve', [PriceResolverController::class, 'resolve'])->name('pricing.resolve');

// Calcula desconto com validação de limites (requer sales.discount)
Route::middleware('permission:sales.discount')
    ->post('/apply-discount', ApplyDiscountController::class)
    ->name('pricing.apply-discount');
