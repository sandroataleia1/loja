<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pool de estoque omnichannel — camada de abstração para roteamento futuro.
 *
 * Tipos previstos: STORE (loja física), ECOMMERCE (reservado online),
 *   MARKETPLACE (reservado marketplace), WAREHOUSE (CD).
 *
 * NÃO implementa lógica de roteamento ainda.
 * Prepara fundação para: ship-from-store, reservas ecommerce, rebalanceamento.
 *
 * A ligação InventoryPool → InventoryBalance é feita em migração futura.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_pools', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();

            $table->string('name', 100);
            $table->string('type', 50);               // InventoryPoolTypeEnum
            $table->jsonb('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index(['tenant_id', 'type'], 'inventory_pool_tenant_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_pools');
    }
};
