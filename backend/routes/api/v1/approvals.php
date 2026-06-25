<?php

declare(strict_types=1);

use App\Core\Auth\Http\Controllers\ApprovalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Approvals — Workflow de aprovação por PIN
|--------------------------------------------------------------------------
*/

Route::get('/',                    [ApprovalController::class, 'index'])->name('index');
Route::post('/',                   [ApprovalController::class, 'store'])->name('store');
Route::get('/{uuid}',              [ApprovalController::class, 'show'])->name('show');
Route::post('/{uuid}/resolve',     [ApprovalController::class, 'resolve'])->name('resolve')->middleware('throttle:resolve-seller-pin');
Route::delete('/{uuid}',           [ApprovalController::class, 'cancel'])->name('cancel');
