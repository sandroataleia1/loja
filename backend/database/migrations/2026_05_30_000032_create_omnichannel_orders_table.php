<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pedido omnichannel — entidade PRÉ-fulfillment, NÃO é Sale.
 *
 * Fluxo:  Order (placed) → payment → fulfillment → [Sale criada no PDV]
 *
 * Order vem de qualquer canal (Instagram DM, WhatsApp, site, marketplace).
 * Sale é a transação PDV, gerada na execução física do pedido.
 *
 * channel_id: canal de origem do pedido.
 * store_id:   loja responsável pelo fulfillment (nullable — definida na etapa de fulfillment).
 * placed_at:  quando o cliente efetivou o pedido (pode diferir de created_at para pedidos migrados).
 *
 * order_number: identificador legível para o cliente (OC-2024-00001).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('omnichannel_orders', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('tenant_id');                  // denormalized — orders cross tenants rarely
            $table->uuid('store_id')->nullable();       // fulfillment store (nullable until assigned)
            $table->foreignUuid('channel_id')->constrained('channels', 'uuid')->cascadeOnDelete();
            $table->uuid('customer_id')->nullable();    // references customers.uuid

            $table->string('order_number', 30)->unique(); // human-readable OC-YYYY-NNNNN
            $table->string('status', 30);               // OrderStatusEnum
            $table->decimal('total_amount', 12, 2);
            $table->jsonb('metadata')->nullable();       // shipping address, notes, line items snapshot

            $table->timestamp('placed_at');             // when the customer placed the order
            $table->timestamps();
            $table->softDeletes();

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index(['tenant_id', 'status', 'placed_at'], 'order_tenant_status_idx');
            $table->index(['tenant_id', 'channel_id'],          'order_channel_idx');
            $table->index(['tenant_id', 'customer_id'],         'order_customer_idx');
            $table->index('store_id',                           'order_store_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('omnichannel_orders');
    }
};
