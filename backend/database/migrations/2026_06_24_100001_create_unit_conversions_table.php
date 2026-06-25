<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria tabela de conversão de unidades de medida.
 *
 * Permite definir fatores de conversão por tenant, produto ou variante.
 * Exemplo: 1 CX = 12 UN, 1 SC = 25 KG, 1 M² (piso 60x60) = 0.617 CX.
 *
 * Prioridade de resolução: variante > produto > global (product_id = null).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('unit_conversions')) {
            return;
        }

        Schema::create('unit_conversions', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('tenant_id')->index();

            // Escopo: null = global para o tenant, preenchido = específico
            $table->uuid('product_id')->nullable()->index();
            $table->uuid('variant_id')->nullable()->index();

            // Unidades: ex. from_unit='CX', to_unit='UN', factor=12
            $table->string('from_unit', 10);
            $table->string('to_unit', 10);
            $table->decimal('factor', 15, 6); // 1 from_unit = factor to_unit

            $table->string('notes', 255)->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Evita duplicatas: mesma conversão por escopo
            $table->unique(['tenant_id', 'product_id', 'variant_id', 'from_unit', 'to_unit'], 'unit_conversions_unique');

            $table->foreign('product_id')->references('uuid')->on('catalog_products')->nullOnDelete();
            $table->foreign('variant_id')->references('uuid')->on('catalog_variants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_conversions');
    }
};
