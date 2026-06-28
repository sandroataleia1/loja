<?php

declare(strict_types=1);

use App\Modules\Customers\Http\Controllers\CreditAnalysisController;
use App\Modules\Customers\Http\Controllers\CustomerAssetController;
use App\Modules\Customers\Http\Controllers\CustomerAuthorizedBuyerController;
use App\Modules\Customers\Http\Controllers\CustomerBankReferenceController;
use App\Modules\Customers\Http\Controllers\CustomerBlockController;
use App\Modules\Customers\Http\Controllers\CustomerDataExportController;
use App\Modules\Customers\Http\Controllers\CustomerDeletionRequestController;
use App\Modules\Customers\Http\Controllers\CustomerSensitiveViewController;
use App\Modules\Customers\Http\Controllers\CustomerCardController;
use App\Modules\Customers\Http\Controllers\CustomerController;
use App\Modules\Customers\Http\Controllers\CustomerDocumentController;
use App\Modules\Customers\Http\Controllers\CustomerFinancialSummaryController;
use App\Modules\Customers\Http\Controllers\CustomerGuarantorController;
use App\Modules\Customers\Http\Controllers\CustomerImportController;
use App\Modules\Customers\Http\Controllers\CustomerInteractionController;
use App\Modules\Customers\Http\Controllers\CustomerPriceListController;
use App\Modules\Customers\Http\Controllers\CustomerTagController;
use App\Modules\Customers\Http\Controllers\RegistrationCardController;
use Illuminate\Support\Facades\Route;

// ── CSV Import (deve vir antes dos parâmetros para evitar conflito) ──────────
Route::get('/import/template',             [CustomerImportController::class, 'template'])->name('import.template');
Route::post('/import',                     [CustomerImportController::class, 'store'])->name('import.store');
Route::get('/import/{importLogId}/status', [CustomerImportController::class, 'status'])->name('import.status');

// ── Customer resource ────────────────────────────────────────────────────────
Route::get('/',              [CustomerController::class, 'index'])->name('index');
Route::post('/',             [CustomerController::class, 'store'])->name('store');
Route::get('/{customer}',    [CustomerController::class, 'show'])->name('show');
Route::put('/{customer}',    [CustomerController::class, 'update'])->name('update');
Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');

// ── Address sub-resource ─────────────────────────────────────────────────────
Route::get('/{customer}/addresses',                    [CustomerController::class, 'addresses'])->name('addresses.index');
Route::post('/{customer}/addresses',                   [CustomerController::class, 'storeAddress'])->name('addresses.store');
Route::put('/{customer}/addresses/{address}',          [CustomerController::class, 'updateAddress'])->name('addresses.update');
Route::delete('/{customer}/addresses/{address}',       [CustomerController::class, 'destroyAddress'])->name('addresses.destroy');

// ── Contact sub-resource ─────────────────────────────────────────────────────
Route::get('/{customer}/contacts',                     [CustomerController::class, 'contacts'])->name('contacts.index');
Route::post('/{customer}/contacts',                    [CustomerController::class, 'storeContact'])->name('contacts.store');
Route::put('/{customer}/contacts/{contact}',           [CustomerController::class, 'updateContact'])->name('contacts.update');
Route::delete('/{customer}/contacts/{contact}',        [CustomerController::class, 'destroyContact'])->name('contacts.destroy');

// ── Tag assignment sub-resource ──────────────────────────────────────────────
Route::post('/{customer}/tags',        [CustomerController::class, 'attachTag'])->name('tags.attach');
Route::delete('/{customer}/tags/{tag}', [CustomerController::class, 'detachTag'])->name('tags.detach');

// ── Price list assignment ─────────────────────────────────────────────────────
Route::patch('/{customer}/price-list', [CustomerPriceListController::class, 'update'])->name('price-list.update');

// ── Ficha de cadastro (blank vem antes do {customer} param) ──────────────────
Route::get('/registration-card/blank', [RegistrationCardController::class, 'blank'])
    ->middleware('feature:customer.registration_card')
    ->name('registration-card.blank');
Route::get('/{customer}/registration-card', RegistrationCardController::class)
    ->middleware('feature:customer.registration_card')
    ->name('registration-card');

// ── Avalistas ─────────────────────────────────────────────────────────────────
Route::middleware('feature:customer.guarantor')->group(function (): void {
    Route::get('/{customer}/guarantors',                      [CustomerGuarantorController::class, 'index'])->name('guarantors.index');
    Route::post('/{customer}/guarantors',                     [CustomerGuarantorController::class, 'store'])->name('guarantors.store');
    Route::get('/{customer}/guarantors/{guarantor}',          [CustomerGuarantorController::class, 'show'])->name('guarantors.show');
    Route::put('/{customer}/guarantors/{guarantor}',          [CustomerGuarantorController::class, 'update'])->name('guarantors.update');
    Route::delete('/{customer}/guarantors/{guarantor}',       [CustomerGuarantorController::class, 'destroy'])->name('guarantors.destroy');
});

// ── Documentos ───────────────────────────────────────────────────────────────
Route::get('/{customer}/documents',              [CustomerDocumentController::class, 'index'])->name('documents.index');
Route::post('/{customer}/documents',             [CustomerDocumentController::class, 'store'])->name('documents.store');
Route::delete('/{customer}/documents/{document}', [CustomerDocumentController::class, 'destroy'])->name('documents.destroy');

// ── Interações / CRM ─────────────────────────────────────────────────────────
Route::get('/{customer}/interactions',               [CustomerInteractionController::class, 'index'])->name('interactions.index');
Route::post('/{customer}/interactions',              [CustomerInteractionController::class, 'store'])->name('interactions.store');
Route::delete('/{customer}/interactions/{interaction}', [CustomerInteractionController::class, 'destroy'])->name('interactions.destroy');

// ── Análise de crédito ───────────────────────────────────────────────────────
Route::middleware('feature:customer.credit_analysis')->group(function (): void {
    Route::post('/{customer}/credit-analysis', [CreditAnalysisController::class, 'store'])->name('credit-analysis.store');
});

// ── Bens materiais ────────────────────────────────────────────────────────────
Route::middleware('feature:customer.credit_analysis')->group(function (): void {
    Route::get('/{customer}/assets',               [CustomerAssetController::class, 'index'])->name('assets.index');
    Route::post('/{customer}/assets',              [CustomerAssetController::class, 'store'])->name('assets.store');
    Route::put('/{customer}/assets/{asset}',       [CustomerAssetController::class, 'update'])->name('assets.update');
    Route::delete('/{customer}/assets/{asset}',    [CustomerAssetController::class, 'destroy'])->name('assets.destroy');
});

// ── Cartões ───────────────────────────────────────────────────────────────────
Route::get('/{customer}/cards',              [CustomerCardController::class, 'index'])->name('cards.index');
Route::post('/{customer}/cards',             [CustomerCardController::class, 'store'])->name('cards.store');
Route::delete('/{customer}/cards/{card}',    [CustomerCardController::class, 'destroy'])->name('cards.destroy');

// ── Referências bancárias ─────────────────────────────────────────────────────
Route::get('/{customer}/bank-references',                            [CustomerBankReferenceController::class, 'index'])->name('bank-references.index');
Route::post('/{customer}/bank-references',                           [CustomerBankReferenceController::class, 'store'])->name('bank-references.store');
Route::put('/{customer}/bank-references/{bankReference}',            [CustomerBankReferenceController::class, 'update'])->name('bank-references.update');
Route::delete('/{customer}/bank-references/{bankReference}',         [CustomerBankReferenceController::class, 'destroy'])->name('bank-references.destroy');

// ── Dependentes autorizados a comprar (crediário) ─────────────────────────────
Route::middleware('feature:sales.installment')->group(function (): void {
    Route::get('/{customer}/authorized-buyers',                          [CustomerAuthorizedBuyerController::class, 'index'])->name('authorized-buyers.index');
    Route::post('/{customer}/authorized-buyers',                         [CustomerAuthorizedBuyerController::class, 'store'])->name('authorized-buyers.store');
    Route::put('/{customer}/authorized-buyers/{authorizedBuyer}',        [CustomerAuthorizedBuyerController::class, 'update'])->name('authorized-buyers.update');
    Route::delete('/{customer}/authorized-buyers/{authorizedBuyer}',     [CustomerAuthorizedBuyerController::class, 'destroy'])->name('authorized-buyers.destroy');
});

// ── Resumo financeiro ─────────────────────────────────────────────────────────
Route::get('/{customer}/financial-summary', [CustomerFinancialSummaryController::class, 'show'])->name('financial-summary.show');

// ── Contrato de crediário ─────────────────────────────────────────────────────
Route::middleware('feature:sales.installment')->group(function (): void {
    Route::get('/contract/template',           [RegistrationCardController::class, 'contractTemplate'])->name('contract.template');
    Route::get('/{customer}/contract',         [RegistrationCardController::class, 'contract'])->name('contract');
    Route::get('/{customer}/contract/blank',   [RegistrationCardController::class, 'contractBlank'])->name('contract.blank');
});

// ── Bloquear / Desbloquear ────────────────────────────────────────────────────
Route::patch('/{customer}/block',   [CustomerBlockController::class, 'block'])->name('block');
Route::patch('/{customer}/unblock', [CustomerBlockController::class, 'unblock'])->name('unblock');

// ── LGPD ──────────────────────────────────────────────────────────────────────
Route::post('/{customer}/sensitive-view',    [CustomerSensitiveViewController::class, 'store'])->name('sensitive-view');
Route::get('/{customer}/export-data',        CustomerDataExportController::class)->name('export-data');
Route::post('/{customer}/request-deletion',  [CustomerDeletionRequestController::class, 'store'])->name('request-deletion');
