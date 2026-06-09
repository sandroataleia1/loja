<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Métricas de campanha — série temporal por campanha.
 *
 * campaign_id: UUID opaco — tabela de campanhas será criada em sprint futura.
 * metric_name: impressions, clicks, conversions, revenue, ctr, roas, etc.
 * period_date: agrupamento diário — permite análise de evolução da campanha.
 *
 * Sem unique constraint em (campaign_id, metric_name, period_date) — permite
 * múltiplos registros intraday para campanhas de alto volume.
 * Use last value por date para consultas de dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_metrics', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('tenant_id');
            $table->uuid('campaign_id');                    // references future campaigns table

            $table->string('metric_name', 100);
            $table->decimal('metric_value', 14, 4);
            $table->date('period_date')->nullable();
            $table->jsonb('metadata')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index(['tenant_id', 'campaign_id', 'metric_name', 'period_date'],
                'campaign_metric_series_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_metrics');
    }
};
