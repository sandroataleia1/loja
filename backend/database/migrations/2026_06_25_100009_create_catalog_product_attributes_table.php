<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_product_attributes', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('product_id')
                ->constrained('catalog_products', 'uuid')
                ->cascadeOnDelete();
            $table->foreignUuid('attribute_group_id')
                ->constrained('catalog_attribute_groups', 'uuid')
                ->cascadeOnDelete();
            $table->foreignUuid('attribute_id')
                ->nullable()
                ->constrained('catalog_attributes', 'uuid')
                ->nullOnDelete();
            // Valor livre para atributos que não usam lista pré-definida
            $table->string('value_text', 200)->nullable();
            $table->decimal('value_number', 12, 4)->nullable();
            $table->foreignUuid('value_unit_id')
                ->nullable()
                ->constrained('units', 'uuid')
                ->nullOnDelete();
            $table->timestamps();

            // 1 atributo por grupo por produto
            $table->unique(['product_id', 'attribute_group_id'], 'uq_product_attribute_group');
            $table->index(['tenant_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_product_attributes');
    }
};
