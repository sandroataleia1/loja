<?php

declare(strict_types=1);

use App\Modules\Settings\Http\Controllers\CustomFieldController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Custom Fields — definições de campos extras por entidade
|--------------------------------------------------------------------------
| Leitura: settings.view (aplicado no grupo pai)
| Escrita: settings.update (aplicado por rota)
*/

Route::get('/', [CustomFieldController::class, 'index'])->name('index');

Route::middleware('permission:settings.update')->group(function (): void {
    Route::post('/', [CustomFieldController::class, 'store'])->name('store');
    Route::patch('/{definition}', [CustomFieldController::class, 'update'])->name('update');
    Route::delete('/{definition}', [CustomFieldController::class, 'destroy'])->name('destroy');
});
