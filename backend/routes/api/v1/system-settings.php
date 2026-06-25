<?php

declare(strict_types=1);

use App\Core\Tenancy\Http\Controllers\TenantSettingsController;
use App\Modules\Settings\Http\Controllers\ConfigHistoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| System Settings — Configurações operacionais do tenant
|--------------------------------------------------------------------------
|
| GET  /system-settings              → Todas as configurações
| PUT  /system-settings/{section}    → Atualiza uma seção (settings.update)
| GET  /system-settings/features     → Lista feature flags
| PUT  /system-settings/features     → Ativa/desativa feature flags
|
*/

Route::get('/',          [TenantSettingsController::class, 'show'])->name('show');
Route::get('/features',  [TenantSettingsController::class, 'showFeatures'])->name('features.show');
Route::put('/features',  [TenantSettingsController::class, 'updateFeatures'])->name('features.update')->middleware('permission:settings.update');
Route::get('/history',   [ConfigHistoryController::class, 'index'])->name('history.index')->middleware('permission:settings.history');
Route::put('/{section}', [TenantSettingsController::class, 'update'])->name('update')->middleware('permission:settings.update');
