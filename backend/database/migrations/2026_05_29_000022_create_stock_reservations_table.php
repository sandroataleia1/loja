<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * StockReservation substitui o campo reserved_quantity em inventory_balances.
 *
 * Motivação: o contador reserved_quantity não permite saber QUEM reservou o quê.
 * Esta tabela mantém rastreabilidade completa de cada reserva.
 *
 * A coluna reserved_quantity em inventory_balances é mantida como cache
 * calculado (SUM de reservas ativas) e atualizada transacionalmente.
 *
 * Status: active → released | consumed | expired
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reservations', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores', 'uuid')->restrictOnDelete();
            $table->foreignUuid('variant_id')->constrained('catalog_variants', 'uuid')->restrictOnDelete();

            $table->unsignedSmallInteger('quantity');
            $table->string('status', 20)->default('active'); // active | released | consumed | expired

            // Referência polimórfica: quem fez a reserva (sale, conditional, transfer, etc.)
            $table->string('reference_type', 50)->nullable();
            $table->uuid('reference_id')->nullable();

            $table->timestamp('expires_at')->nullable(); // null = sem expiração
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'store_id', 'variant_id', 'status'],
                'stock_reservations_lookup');
            $table->index(['tenant_id', 'reference_type', 'reference_id'],
                'stock_reservations_reference');
            $table->index(['status', 'expires_at'],
                'stock_reservations_expiry'); // Para o job de expiração
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
