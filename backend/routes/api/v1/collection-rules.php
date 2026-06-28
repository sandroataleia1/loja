<?php

declare(strict_types=1);

use App\Modules\Financial\Http\Controllers\CollectionRuleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Collection Rules — regras de cobrança automática por tenant
|--------------------------------------------------------------------------
| Leitura: financial.view (aplicado no grupo pai)
| Escrita: financial.create (aplicado por rota)
*/

Route::get('/', [CollectionRuleController::class, 'index'])->name('index');

Route::middleware('permission:financial.create')->group(function (): void {
    Route::post('/', [CollectionRuleController::class, 'store'])->name('store');
    Route::patch('/{rule}', [CollectionRuleController::class, 'update'])->name('update');
    Route::delete('/{rule}', [CollectionRuleController::class, 'destroy'])->name('destroy');
});
