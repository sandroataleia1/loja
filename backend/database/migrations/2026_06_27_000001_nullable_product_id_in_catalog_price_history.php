<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * product_id pode ser null em registros de histórico criados pelo observer
 * quando o ProductPrice está vinculado apenas à variante (product_id = null).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_price_history', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
            $table->uuid('product_id')->nullable()->change();
            $table->foreign('product_id')
                ->references('uuid')
                ->on('catalog_products')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('catalog_price_history', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
            $table->uuid('product_id')->nullable(false)->change();
            $table->foreignUuid('product_id')->constrained('catalog_products', 'uuid')->cascadeOnDelete();
        });
    }
};
