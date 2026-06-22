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
        if (! Schema::hasTable('tax_profiles')) {
            Schema::create('tax_profiles', function (Blueprint $table): void {
                $table->uuid()->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
                $table->string('name', 150);
                $table->string('regime', 30)->default('simples_nacional');
                $table->jsonb('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['tenant_id', 'regime']);
            });
        }

        // Adiciona a FK em catalog_variants apenas se ainda não existir
        $fkExists = \DB::table('information_schema.table_constraints')
            ->where('constraint_type', 'FOREIGN KEY')
            ->where('table_name', 'catalog_variants')
            ->where('constraint_name', 'catalog_variants_tax_profile_id_foreign')
            ->exists();

        if (! $fkExists) {
            Schema::table('catalog_variants', function (Blueprint $table): void {
                $table->foreign('tax_profile_id')
                    ->references('uuid')
                    ->on('tax_profiles')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('catalog_variants', function (Blueprint $table): void {
            $table->dropForeign(['tax_profile_id']);
        });

        Schema::dropIfExists('tax_profiles');
    }
};
