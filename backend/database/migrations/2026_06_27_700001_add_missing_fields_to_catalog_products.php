<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            if (! Schema::hasColumn('catalog_products', 'origin')) {
                $table->string('origin', 20)->nullable()->after('location');
            }

            if (! Schema::hasColumn('catalog_products', 'min_sale_qty')) {
                $table->decimal('min_sale_qty', 10, 4)->default(1)->after('origin');
            }

            if (! Schema::hasColumn('catalog_products', 'sale_multiplier')) {
                $table->decimal('sale_multiplier', 10, 4)->default(1)->after('min_sale_qty');
            }

            if (! Schema::hasColumn('catalog_products', 'is_on_sale')) {
                $table->boolean('is_on_sale')->default(false)->after('sale_multiplier');
            }

            if (! Schema::hasColumn('catalog_products', 'sale_ends_at')) {
                $table->timestamp('sale_ends_at')->nullable()->after('is_on_sale');
            }

            if (! Schema::hasColumn('catalog_products', 'seo_title')) {
                $table->string('seo_title', 200)->nullable()->after('sale_ends_at');
            }

            if (! Schema::hasColumn('catalog_products', 'seo_description')) {
                $table->string('seo_description', 500)->nullable()->after('seo_title');
            }

            if (! Schema::hasColumn('catalog_products', 'supplier_id')) {
                $table->uuid('supplier_id')->nullable()->after('seo_description');
                $table->foreign('supplier_id')
                    ->references('uuid')
                    ->on('suppliers')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('catalog_products', 'supplier_code')) {
                $table->string('supplier_code', 60)->nullable()->after('supplier_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            if (Schema::hasColumn('catalog_products', 'supplier_id')) {
                $table->dropForeign(['supplier_id']);
            }
            $cols = ['origin', 'min_sale_qty', 'sale_multiplier', 'is_on_sale',
                'sale_ends_at', 'seo_title', 'seo_description', 'supplier_id', 'supplier_code'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('catalog_products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
