<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela de unidades de medida configuráveis.
 *
 * tenant_id nullable:
 *   - NULL  = unidade global da plataforma (disponível a todos os tenants)
 *   - UUID  = unidade personalizada do tenant
 *
 * A unique constraint em [tenant_id, code] usa NULL como valor distinto no
 * PostgreSQL — dois tenants diferentes podem ter o código "PC" sem conflito.
 * Globais (tenant_id IS NULL) também respeitam unicidade de code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->string('code', 10);
            $table->string('name', 50);
            $table->string('symbol', 10);
            $table->tinyInteger('decimal_places')->default(2)->unsigned();
            $table->string('unit_group', 20)->default('quantity'); // length|area|volume|weight|quantity|capacity|other
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unicidade: código único por tenant (NULL para globais)
            // PostgreSQL: NULL != NULL em unique index, então dois NULLs não conflitam
            $table->unique(['tenant_id', 'code'], 'units_tenant_code_unique');
            $table->index(['tenant_id', 'is_active', 'unit_group'], 'units_tenant_active_group_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
