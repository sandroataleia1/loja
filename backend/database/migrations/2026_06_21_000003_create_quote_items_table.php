<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_items', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('quote_id')->constrained('quotes', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->nullable()->constrained('catalog_variants', 'uuid')->nullOnDelete();
            // Snapshots — preservam o preço/nome no momento da emissão
            $table->string('name_snapshot', 255);
            $table->string('sku_snapshot', 100)->nullable();
            $table->string('unit_of_measure', 10)->default('UN');
            // Quantidade e preço
            $table->decimal('quantity', 15, 3)->default(1);
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('discount_cents')->default(0);
            $table->unsignedInteger('subtotal_cents');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['quote_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_items');
    }
};
