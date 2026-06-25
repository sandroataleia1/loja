<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename columns preserving data, then convert decimal → integer cents.
        // Wrapped in explicit transaction to allow rollback on partial failure.
        DB::transaction(function (): void {
            // 1. Add new integer columns beside the old decimal ones
            DB::statement('ALTER TABLE catalog_products ADD COLUMN base_price_cents integer');
            DB::statement('ALTER TABLE catalog_products ADD COLUMN cost_price_cents integer');

            // 2. Populate: ROUND(decimal_reais * 100)
            DB::statement('UPDATE catalog_products SET base_price_cents = ROUND(base_price * 100) WHERE base_price IS NOT NULL');
            DB::statement('UPDATE catalog_products SET cost_price_cents = ROUND(cost_price * 100) WHERE cost_price IS NOT NULL');

            // 3. Drop old decimal columns
            Schema::table('catalog_products', function ($t): void {
                $t->dropColumn(['base_price', 'cost_price']);
            });
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement('ALTER TABLE catalog_products ADD COLUMN base_price numeric(10,2)');
            DB::statement('ALTER TABLE catalog_products ADD COLUMN cost_price numeric(10,2)');
            DB::statement('UPDATE catalog_products SET base_price = base_price_cents::numeric / 100 WHERE base_price_cents IS NOT NULL');
            DB::statement('UPDATE catalog_products SET cost_price = cost_price_cents::numeric / 100 WHERE cost_price_cents IS NOT NULL');

            Schema::table('catalog_products', function ($t): void {
                $t->dropColumn(['base_price_cents', 'cost_price_cents']);
            });
        });
    }
};
