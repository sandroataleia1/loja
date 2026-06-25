<?php

declare(strict_types=1);

use App\Core\Auth\Http\Controllers\ApprovalRuleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Approval Rules — Regras de aprovação configuráveis por tenant
| Acesso restrito a admin/manager via permission:settings.update
|--------------------------------------------------------------------------
*/

Route::get('/',                     [ApprovalRuleController::class, 'index'])->name('index');
Route::post('/',                    [ApprovalRuleController::class, 'upsert'])->name('upsert')->middleware('permission:settings.update');
Route::delete('/{approvalRule}',    [ApprovalRuleController::class, 'destroy'])->name('destroy')->middleware('permission:settings.update');
