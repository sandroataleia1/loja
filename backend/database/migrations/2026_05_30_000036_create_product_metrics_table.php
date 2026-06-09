<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projeção materializada de métricas por produto.
 *
 * Agrega por product_id (não por variant) — visão comercial.
 * Para análise de variant, usar analytics_events com aggregate_type='variant'.
 *
 * return_rate: 0.0000–1.0000 (ex: 0.0350 = 3.5% de devolução).
 * stock_turnover: unidades vendidas / estoque médio (calculado periodicamente).
 * gross_revenue: receita bruta em reais (não em centavos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_metrics', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('tenant_id');
            $table->uuid('product_id');                     // references catalog_products.uuid

            // Volume
            $table->unsignedInteger('units_sold')->default(0);
            $table->decimal('gross_revenue', 14, 2)->default(0);

            // Qualidade
            $table->decimal('return_rate', 5, 4)->default(0);    // 0.0000 to 1.0000
            $table->decimal('stock_turnover', 8, 4)->default(0); // units_sold / avg_stock

            // Recência
            $table->timestamp('last_sale_at')->nullable();
            $table->unsignedInteger('days_without_sale')->nullable();

            $table->timestamp('computed_at')->useCurrent();
            $table->timestamps();

            // ── Constraints ──────────────────────────────────────────────────
            $table->unique(['tenant_id', 'product_id'], 'product_metrics_unique');

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index(['tenant_id', 'units_sold'],       'product_metrics_velocity_idx');
            $table->index(['tenant_id', 'gross_revenue'],    'product_metrics_revenue_idx');
            $table->index(['tenant_id', 'return_rate'],      'product_metrics_return_idx');
            $table->index(['tenant_id', 'days_without_sale'], 'product_metrics_stale_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_metrics');
    }
};
