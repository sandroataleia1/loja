<?php

declare(strict_types=1);

use App\Modules\Finance\Http\Controllers\FinancialAccountController;
use App\Modules\Finance\Http\Controllers\FinancialCategoryController;
use App\Modules\Finance\Http\Controllers\FinancialEntryController;
use App\Modules\Finance\Http\Controllers\FinancialTransferController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Financial Accounts
|--------------------------------------------------------------------------
*/
Route::apiResource('accounts', FinancialAccountController::class)
    ->parameters(['accounts' => 'financialAccount']);

/*
|--------------------------------------------------------------------------
| Financial Categories
|--------------------------------------------------------------------------
*/
Route::apiResource('categories', FinancialCategoryController::class)
    ->parameters(['categories' => 'financialCategory']);

/*
|--------------------------------------------------------------------------
| Financial Entries
|--------------------------------------------------------------------------
*/
Route::apiResource('entries', FinancialEntryController::class)
    ->only(['index', 'store', 'show'])
    ->parameters(['entries' => 'financialEntry']);

Route::post('entries/{financialEntry}/pay',    [FinancialEntryController::class, 'pay']);
Route::post('entries/{financialEntry}/cancel', [FinancialEntryController::class, 'cancel']);

/*
|--------------------------------------------------------------------------
| Financial Transfers
|--------------------------------------------------------------------------
*/
Route::apiResource('transfers', FinancialTransferController::class)
    ->only(['index', 'store', 'show'])
    ->parameters(['transfers' => 'financialTransfer']);
