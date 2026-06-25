<?php

declare(strict_types=1);

use App\Core\Audit\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Audit Logs — Rastreabilidade operacional
|--------------------------------------------------------------------------
*/

Route::get('/',        [AuditLogController::class, 'index'])->name('index');
Route::get('/filters', [AuditLogController::class, 'filters'])->name('filters');
