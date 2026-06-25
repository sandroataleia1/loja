<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── customers ────────────────────────────────────────────────────────
        if (! Schema::hasColumn('customers', 'search_vector')) {
            DB::statement("
                ALTER TABLE customers
                ADD COLUMN search_vector tsvector
                GENERATED ALWAYS AS (
                    to_tsvector('portuguese',
                        coalesce(name, '') || ' ' ||
                        coalesce(trade_name, '') || ' ' ||
                        coalesce(document, '')
                    )
                ) STORED
            ");

            DB::statement('CREATE INDEX customers_search_vector_gin ON customers USING GIN (search_vector)');
        }

        // ── suppliers ────────────────────────────────────────────────────────
        if (! Schema::hasColumn('suppliers', 'search_vector')) {
            DB::statement("
                ALTER TABLE suppliers
                ADD COLUMN search_vector tsvector
                GENERATED ALWAYS AS (
                    to_tsvector('portuguese',
                        coalesce(name, '') || ' ' ||
                        coalesce(trade_name, '') || ' ' ||
                        coalesce(document, '')
                    )
                ) STORED
            ");

            DB::statement('CREATE INDEX suppliers_search_vector_gin ON suppliers USING GIN (search_vector)');
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS customers_search_vector_gin');
        DB::statement('ALTER TABLE customers DROP COLUMN IF EXISTS search_vector');

        DB::statement('DROP INDEX IF EXISTS suppliers_search_vector_gin');
        DB::statement('ALTER TABLE suppliers DROP COLUMN IF EXISTS search_vector');
    }
};
