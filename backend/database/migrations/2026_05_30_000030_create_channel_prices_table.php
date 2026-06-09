<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preços por canal — desacoplados do catálogo base.
 *
 * Um canal pode ter preço diferente do preço base do catálogo.
 * Suporta preço promocional com vigência (starts_at / ends_at).
 *
 * Um registro por (channel_id, product_id) = preço ativo do canal.
 * Histórico de mudanças de preço fica no audit_log.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_prices', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('tenant_id');                  // denormalized
            $table->foreignUuid('channel_id')->constrained('channels', 'uuid')->cascadeOnDelete();
            $table->uuid('product_id');                 // references catalog_products.uuid

            $table->decimal('price', 12, 2);
            $table->decimal('promotional_price', 12, 2)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();

            // ── Constraints ──────────────────────────────────────────────────
            $table->unique(['channel_id', 'product_id'], 'channel_price_unique');

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index(['tenant_id', 'channel_id'], 'channel_price_tenant_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_prices');
    }
};
