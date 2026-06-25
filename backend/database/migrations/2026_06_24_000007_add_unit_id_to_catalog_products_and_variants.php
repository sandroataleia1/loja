<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona FK unit_id a catalog_products e catalog_variants.
 *
 * O campo unit_of_measure (varchar/enum) é mantido para retrocompatibilidade.
 * A migração dos dados existentes (popular unit_id a partir de unit_of_measure)
 * deve ser feita via job/script após executar o UnitSeeder.
 *
 * Quando unit_id for preenchido, tem precedência sobre unit_of_measure.
 * Usar restrictOnDelete para evitar remoção acidental de unidades em uso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->uuid('unit_id')->nullable()->after('unit_of_measure');
            $table->foreign('unit_id')
                ->references('uuid')
                ->on('units')
                ->restrictOnDelete();
            $table->index(['tenant_id', 'unit_id'], 'catalog_products_tenant_unit_idx');
        });

        Schema::table('catalog_variants', function (Blueprint $table): void {
            $table->uuid('unit_id')->nullable()->after('sku');
            $table->foreign('unit_id')
                ->references('uuid')
                ->on('units')
                ->restrictOnDelete();
            $table->index(['tenant_id', 'unit_id'], 'catalog_variants_tenant_unit_idx');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_variants', function (Blueprint $table): void {
            $table->dropForeign(['unit_id']);
            $table->dropIndex('catalog_variants_tenant_unit_idx');
            $table->dropColumn('unit_id');
        });

        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropForeign(['unit_id']);
            $table->dropIndex('catalog_products_tenant_unit_idx');
            $table->dropColumn('unit_id');
        });
    }
};
