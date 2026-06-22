<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela tax_profiles.
 *
 * O model App\Modules\Fiscal\Models\TaxProfile e a FK em catalog_variants
 * referenciam esta tabela, que estava ausente nas migrations.
 * Sem esta tabela, a validação `exists:tax_profiles,uuid` em StoreVariantRequest
 * lança PDOException em runtime.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_profiles', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();

            // Nome de exibição (ex: "Perfil Padrão — Simples Nacional")
            $table->string('name', 150);

            // Regime tributário (TaxRegimeEnum)
            $table->string('regime', 30)->default('simples_nacional');

            // Dados extras livres (alíquotas, CSOSN, etc.)
            $table->jsonb('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'regime']);
        });

        // Agora que a tabela existe, adiciona a FK real em catalog_variants
        Schema::table('catalog_variants', function (Blueprint $table): void {
            $table->foreign('tax_profile_id')
                ->references('uuid')
                ->on('tax_profiles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('catalog_variants', function (Blueprint $table): void {
            $table->dropForeign(['tax_profile_id']);
        });

        Schema::dropIfExists('tax_profiles');
    }
};
