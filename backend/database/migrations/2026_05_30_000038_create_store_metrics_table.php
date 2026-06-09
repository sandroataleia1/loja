<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Métricas por loja física (PDV).
 *
 * inventory_value: calculado periodicamente, não em tempo real.
 * customer_count: clientes únicos com ao menos 1 compra na loja.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_metrics', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('tenant_id');
            $table->uuid('store_id');                       // references stores.uuid

            $table->unsignedInteger('sales_count')->default(0);
            $table->decimal('revenue', 14, 2)->default(0);
            $table->decimal('inventory_value', 14, 2)->default(0);
            $table->unsignedInteger('customer_count')->default(0);

            $table->timestamp('computed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['tenant_id', 'store_id'], 'store_metrics_unique');
            $table->index(['tenant_id', 'revenue'], 'store_metrics_revenue_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_metrics');
    }
};
