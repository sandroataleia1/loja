<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projeção materializada de métricas por cliente.
 *
 * Uma linha por cliente. Atualizada incrementalmente por listeners.
 * Recalculada do zero por ConsolidateCustomerMetricsJob quando necessário.
 *
 * total_spent: em reais (decimal), não em centavos — normalizado na ingestão.
 * purchase_frequency: média de dias entre compras (null = menos de 2 compras).
 * days_since_last_purchase: recomputed daily via TakeDailySnapshotJob.
 *
 * computed_at: timestamp da última atualização — detectar projeções stale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_metrics', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('tenant_id');
            $table->uuid('customer_id');                    // references customers.uuid

            // Volume
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('total_spent', 14, 2)->default(0);
            $table->decimal('average_ticket', 10, 2)->default(0);

            // Recência e frequência
            $table->decimal('purchase_frequency', 8, 2)->nullable(); // avg days between purchases
            $table->timestamp('last_purchase_at')->nullable();
            $table->unsignedInteger('days_since_last_purchase')->nullable();

            $table->timestamp('computed_at')->useCurrent();
            $table->timestamps();

            // ── Constraints ──────────────────────────────────────────────────
            $table->unique(['tenant_id', 'customer_id'], 'customer_metrics_unique');

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index(['tenant_id', 'total_spent'],            'customer_metrics_spend_idx');
            $table->index(['tenant_id', 'last_purchase_at'],       'customer_metrics_recency_idx');
            $table->index(['tenant_id', 'days_since_last_purchase'], 'customer_metrics_dormant_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_metrics');
    }
};
