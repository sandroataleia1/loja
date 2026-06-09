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
            // Visibility: PRIVATE (not listed), PUBLIC (visible everywhere), UNLISTED (direct link only)
            $table->string('visibility', 20)->default('PRIVATE')->after('status');

            // Product-level default prices (decimal, optional — variants can override)
            $table->decimal('base_price', 10, 2)->nullable()->after('visibility');
            $table->decimal('cost_price', 10, 2)->nullable()->after('base_price');
        });

        Schema::table('catalog_variants', function (Blueprint $table): void {
            // JSONB snapshot of which attribute values form this variant (for display/search)
            $table->jsonb('grid_combination')->nullable()->after('sku');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropColumn(['visibility', 'base_price', 'cost_price']);
        });

        Schema::table('catalog_variants', function (Blueprint $table): void {
            $table->dropColumn('grid_combination');
        });
    }
};
