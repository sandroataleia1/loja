<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configurações operacionais do tenant — um registro por tenant.
 *
 * Cada seção é um JSONB com campos tipados e defaults semânticos.
 * Seções: commercial, financial, credit, inventory, billing, commission, logistics.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_settings', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();

            // Configurações comerciais
            $table->jsonb('commercial')->default('{}');

            // Configurações financeiras
            $table->jsonb('financial')->default('{}');

            // Configurações de crédito
            $table->jsonb('credit')->default('{}');

            // Configurações de estoque
            $table->jsonb('inventory')->default('{}');

            // Configurações de faturamento
            $table->jsonb('billing')->default('{}');

            // Configurações de comissão
            $table->jsonb('commission')->default('{}');

            // Configurações de logística
            $table->jsonb('logistics')->default('{}');

            $table->timestamps();

            $table->unique('tenant_id', 'tenant_settings_tenant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};
