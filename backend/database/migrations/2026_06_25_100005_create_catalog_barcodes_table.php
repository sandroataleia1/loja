<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_barcodes', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->uuid('product_id')->nullable();
            $table->uuid('variant_id')->nullable();
            $table->foreign('product_id')->references('uuid')->on('catalog_products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('uuid')->on('catalog_variants')->cascadeOnDelete();
            // ean13 | ean8 | dun14 | code128 | qrcode | custom
            $table->string('barcode_type', 20);
            $table->string('value', 100);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            // Código único por tenant (sem duplicata entre produtos/variantes diferentes)
            $table->unique(['tenant_id', 'value']);
            $table->index(['tenant_id', 'barcode_type']);
            $table->index(['tenant_id', 'value']); // lookup O(1) via leitor
        });

        DB::statement(
            'ALTER TABLE catalog_barcodes ADD CONSTRAINT chk_barcode_product_or_variant
             CHECK (product_id IS NOT NULL OR variant_id IS NOT NULL)'
        );

        // Migra barcode e gtin de catalog_variants para catalog_barcodes
        // Mantém colunas originais — apenas popula a nova tabela
        DB::statement("
            INSERT INTO catalog_barcodes (uuid, tenant_id, variant_id, barcode_type, value, is_primary, created_at, updated_at)
            SELECT gen_random_uuid(), tenant_id, uuid, 'ean13', gtin, true, NOW(), NOW()
            FROM catalog_variants
            WHERE gtin IS NOT NULL AND gtin <> ''
              AND NOT EXISTS (
                SELECT 1 FROM catalog_barcodes cb WHERE cb.tenant_id = catalog_variants.tenant_id AND cb.value = catalog_variants.gtin
              )
        ");

        DB::statement("
            INSERT INTO catalog_barcodes (uuid, tenant_id, variant_id, barcode_type, value, is_primary, created_at, updated_at)
            SELECT gen_random_uuid(), tenant_id, uuid,
                   CASE WHEN length(barcode) = 14 THEN 'dun14' ELSE 'code128' END,
                   barcode,
                   (gtin IS NULL OR gtin = ''),
                   NOW(), NOW()
            FROM catalog_variants
            WHERE barcode IS NOT NULL AND barcode <> ''
              AND NOT EXISTS (
                SELECT 1 FROM catalog_barcodes cb WHERE cb.tenant_id = catalog_variants.tenant_id AND cb.value = catalog_variants.barcode
              )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_barcodes');
    }
};
