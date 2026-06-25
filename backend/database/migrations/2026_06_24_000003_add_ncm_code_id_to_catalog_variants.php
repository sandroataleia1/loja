<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona FK ncm_code_id a catalog_variants.
 *
 * Variantes podem sobrescrever o NCM do produto pai — quando ncm_code_id
 * é null na variante, herda-se o ncm_code_id do produto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_variants', function (Blueprint $table): void {
            $table->uuid('ncm_code_id')->nullable()->after('ncm');
            $table->foreign('ncm_code_id')
                ->references('uuid')
                ->on('ncm_codes')
                ->nullOnDelete();
            $table->index(['tenant_id', 'ncm_code_id'], 'catalog_variants_tenant_ncm_code_idx');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_variants', function (Blueprint $table): void {
            $table->dropForeign(['ncm_code_id']);
            $table->dropIndex('catalog_variants_tenant_ncm_code_idx');
            $table->dropColumn('ncm_code_id');
        });
    }
};
