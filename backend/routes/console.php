<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Horizon snapshot — necessário para o gráfico de métricas no dashboard
|--------------------------------------------------------------------------
*/
Schedule::command('horizon:snapshot')->everyFiveMinutes();

/*
|--------------------------------------------------------------------------
| Limpar tokens expirados do Sanctum diariamente
|--------------------------------------------------------------------------
*/
Schedule::command('sanctum:prune-expired --hours=24')->daily();

/*
|--------------------------------------------------------------------------
| Fiscal: reprocessar documentos com falha (a cada 15 min)
|--------------------------------------------------------------------------
*/
Schedule::job(new \App\Jobs\RetryFailedFiscalDocumentsJob())->everyFifteenMinutes()->name('retry-failed-fiscal')->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Fiscal: alertar sobre certificados próximos do vencimento (diário, 8h)
|--------------------------------------------------------------------------
*/
Schedule::job(new \App\Jobs\AlertCertificateExpiryJob())->dailyAt('08:00')->name('alert-certificate-expiry')->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Sync: reprocessar operações de sync com falha (a cada 5 min)
|--------------------------------------------------------------------------
*/
Schedule::job(new \App\Jobs\RetryFailedSyncOperationsJob())->everyFiveMinutes()->name('retry-failed-sync')->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Analytics: snapshot diário de métricas (DWH bridge) — 00:30 após virada
|--------------------------------------------------------------------------
*/
Schedule::call(\App\Modules\Analytics\Jobs\TakeDailySnapshotJob::class . '::dispatchForAllTenants')
    ->dailyAt('00:30')
    ->name('analytics-daily-snapshot')
    ->withoutOverlapping();

