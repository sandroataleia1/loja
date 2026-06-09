<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Corrige o unique constraint de inventory_balances para incluir tenant_id.
 *
 * O constraint original (store_id, variant_id) estava incorreto para
 * ambiente multitenant — todos os unique indexes devem incluir tenant_id
 * conforme convenção do projeto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_balances', function (Blueprint $table): void {
            // Remove constraint antiga
            $table->dropUnique(['store_id', 'variant_id']);

            // Adiciona constraint correta com tenant_id
            $table->unique(['tenant_id', 'store_id', 'variant_id'], 'inventory_balances_tenant_store_variant_unique');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_balances', function (Blueprint $table): void {
            $table->dropUnique('inventory_balances_tenant_store_variant_unique');
            $table->unique(['store_id', 'variant_id']);
        });
    }
};
