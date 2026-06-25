<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela global de NCMs (Nomenclatura Comum do Mercosul).
 *
 * Não tem tenant_id — é uma referência fiscal da plataforma, compartilhada
 * por todos os tenants. Os tenants referenciam via ncm_code_id (FK) em
 * catalog_products e catalog_variants, mantendo o campo ncm varchar para
 * compatibilidade retroativa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ncm_codes', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->string('code', 10)->unique(); // formato: XXXX.XX.XX
            $table->string('description', 500);
            $table->string('unit_of_measure', 10)->nullable();
            $table->decimal('ipi_rate', 8, 4)->default(0);
            $table->string('ncm_type', 10)->default('product'); // product | service
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index(['is_active', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ncm_codes');
    }
};
