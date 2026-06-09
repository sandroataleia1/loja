<?php

declare(strict_types=1);

use App\Modules\Fiscal\Http\Controllers\FiscalDocumentController;
use App\Modules\Fiscal\Http\Controllers\FiscalSettingsController;
use App\Modules\Fiscal\Http\Controllers\TaxProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fiscal Documents — emissão e consulta de NFC-e / NF-e
|--------------------------------------------------------------------------
*/
Route::get('documents',                              [FiscalDocumentController::class, 'index'])->name('documents.index');
Route::post('documents',                             [FiscalDocumentController::class, 'store'])->name('documents.store');
Route::get('documents/{fiscalDocument}',             [FiscalDocumentController::class, 'show'])->name('documents.show');
Route::post('documents/{fiscalDocument}/cancel',     [FiscalDocumentController::class, 'cancel'])->name('documents.cancel');
Route::post('documents/{fiscalDocument}/retry',      [FiscalDocumentController::class, 'retry'])->name('documents.retry');

/*
|--------------------------------------------------------------------------
| Fiscal Settings — configuração fiscal por tenant
|--------------------------------------------------------------------------
*/
Route::get('settings',  [FiscalSettingsController::class, 'show'])->name('settings.show');
Route::put('settings',  [FiscalSettingsController::class, 'upsert'])->name('settings.upsert');

/*
|--------------------------------------------------------------------------
| Tax Profiles — perfis tributários reutilizáveis
|--------------------------------------------------------------------------
*/
Route::apiResource('tax-profiles', TaxProfileController::class)
    ->parameters(['tax-profiles' => 'taxProfile']);
