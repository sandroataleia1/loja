<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Pivot: produto ↔ categoria (N:N) ──────────────────────────────────
        Schema::create('catalog_product_categories', function (Blueprint $table): void {
            $table->foreignUuid('product_id')->constrained('catalog_products', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('catalog_categories', 'uuid')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);

            $table->primary(['product_id', 'category_id']);
            $table->index(['category_id', 'sort_order']);
        });

        // ── Remove relacionamento direto 1:N ──────────────────────────────────
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'category_id']);
            $table->dropConstrainedForeignId('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->foreignUuid('category_id')
                ->nullable()
                ->constrained('catalog_categories', 'uuid')
                ->nullOnDelete();
            $table->index(['tenant_id', 'category_id']);
        });

        Schema::dropIfExists('catalog_product_categories');
    }
};
