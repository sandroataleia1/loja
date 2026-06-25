<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // parent_id: auto-referência para kits/compostos
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->uuid('parent_id')->nullable()->after('grid_id');
            $table->foreign('parent_id')->references('uuid')->on('catalog_products')->nullOnDelete();
            $table->index(['tenant_id', 'parent_id']);
        });

        // catalog_kit_items: componentes de um produto tipo kit
        Schema::create('catalog_kit_items', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('kit_product_id')
                ->constrained('catalog_products', 'uuid')
                ->cascadeOnDelete();
            $table->foreignUuid('component_product_id')
                ->constrained('catalog_products', 'uuid')
                ->restrictOnDelete();
            $table->uuid('component_variant_id')->nullable();
            $table->foreign('component_variant_id')
                ->references('uuid')
                ->on('catalog_variants')
                ->nullOnDelete();
            $table->foreignUuid('unit_id')
                ->nullable()
                ->constrained('units', 'uuid')
                ->nullOnDelete();
            // decimal para quantidades fracionárias: 0.5m² de tinta
            $table->decimal('quantity', 10, 4)->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['kit_product_id', 'component_product_id', 'component_variant_id'], 'uq_kit_component');
            $table->index(['tenant_id', 'kit_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_kit_items');

        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['tenant_id', 'parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
