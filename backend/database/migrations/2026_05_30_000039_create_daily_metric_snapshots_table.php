<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Série temporal de métricas — ponto de captura diário.
 *
 * Ponte para Data Warehouse futuro: cada linha representa um valor de métrica
 * em uma data específica. ETL pode fazer SELECT * ORDER BY metric_date.
 *
 * metric_name: nome da métrica (MetricNameEnum).
 * metric_value: valor numérico (decimal 14,4 para % e valores monetários).
 * metadata: dimensões extras — store_id, channel_id, segment, etc.
 *
 * Uma linha por (tenant_id, metric_name, metric_date) — upsert idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_metric_snapshots', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('tenant_id');

            $table->string('metric_name', 100);
            $table->date('metric_date');
            $table->decimal('metric_value', 14, 4);
            $table->jsonb('metadata')->nullable();     // store_id, channel_id, segment, etc.

            $table->timestamp('created_at')->useCurrent();

            // ── Constraints ──────────────────────────────────────────────────
            $table->unique(['tenant_id', 'metric_name', 'metric_date'], 'daily_snapshot_unique');

            // ── Indexes ──────────────────────────────────────────────────────
            // DWH export: all metrics for a tenant in date range
            $table->index(['tenant_id', 'metric_date'],              'daily_snapshot_date_idx');
            // Trend: specific metric over time
            $table->index(['tenant_id', 'metric_name', 'metric_date'], 'daily_snapshot_series_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_metric_snapshots');
    }
};
