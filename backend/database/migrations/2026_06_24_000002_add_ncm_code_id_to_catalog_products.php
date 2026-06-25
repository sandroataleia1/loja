<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona FK ncm_code_id a catalog_products.
 *
 * O campo varchar ncm é mantido para retrocompatibilidade — ele continua
 * funcionando como campo de entrada livre para quem ainda não usa a tabela
 * ncm_codes. A migração de dados (popular ncm_code_id a partir de ncm)
 * deve ser feita via job/script separado após popular o NcmCodeSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->uuid('ncm_code_id')->nullable()->after('ncm');
            $table->foreign('ncm_code_id')
                ->references('uuid')
                ->on('ncm_codes')
                ->nullOnDelete();
            $table->index(['tenant_id', 'ncm_code_id'], 'catalog_products_tenant_ncm_code_idx');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropForeign(['ncm_code_id']);
            $table->dropIndex('catalog_products_tenant_ncm_code_idx');
            $table->dropColumn('ncm_code_id');
        });
    }
};
