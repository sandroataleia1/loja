<?php

declare(strict_types=1);

use App\Modules\Sync\Http\Controllers\SyncController;
use App\Modules\Sync\Http\Controllers\SyncDeviceController;
use App\Modules\Sync\Http\Controllers\SyncLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sync Module Routes — Offline-First PDV Sync Protocol
|
| Rota base: /api/v1/sync
| Todos os endpoints requerem auth:sanctum + tenant middleware (herdado).
|--------------------------------------------------------------------------
*/

// ── Device Registration ───────────────────────────────────────────────────
Route::prefix('devices')->name('devices.')->group(function (): void {
    Route::get('/',               [SyncDeviceController::class, 'index'])->name('index');
    Route::post('/register',      [SyncDeviceController::class, 'register'])->name('register');
    Route::get('/{device}',       [SyncDeviceController::class, 'show'])->name('show');
    Route::patch('/{device}/deactivate', [SyncDeviceController::class, 'deactivate'])->name('deactivate');
});

// ── Push & Pull ───────────────────────────────────────────────────────────
Route::post('/push',     [SyncController::class, 'push'])->name('push');
Route::post('/pull',     [SyncController::class, 'pull'])->name('pull');
Route::post('/pull/ack', [SyncController::class, 'ack'])->name('pull.ack');

// ── Audit Logs ────────────────────────────────────────────────────────────
Route::prefix('logs')->name('logs.')->group(function (): void {
    Route::get('/',              [SyncLogController::class, 'index'])->name('index');
    Route::get('/{log}',         [SyncLogController::class, 'show'])->name('show');
    Route::get('/batch/{batchId}/operations', [SyncLogController::class, 'operations'])->name('operations');
});
