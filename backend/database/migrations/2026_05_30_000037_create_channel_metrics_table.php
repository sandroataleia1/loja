<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Métricas por canal omnichannel.
 *
 * Uma linha por canal. Atualizada ao receber OrderPlaced/OrderPaid.
 * revenue: receita confirmada (apenas pedidos PAID/FULFILLED).
 * orders_count: total de pedidos (todos os status).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_metrics', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('tenant_id');
            $table->uuid('channel_id');                     // references channels.uuid

            $table->unsignedInteger('orders_count')->default(0);
            $table->decimal('revenue', 14, 2)->default(0);
            $table->decimal('average_ticket', 10, 2)->default(0);

            $table->timestamp('computed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['tenant_id', 'channel_id'], 'channel_metrics_unique');
            $table->index(['tenant_id', 'revenue'], 'channel_metrics_revenue_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_metrics');
    }
};
