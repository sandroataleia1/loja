<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Verifica se PostgreSQL >= 12 suporta GENERATED ALWAYS
        $version = DB::selectOne('SELECT current_setting(\'server_version_num\')::int AS ver');
        $pgVersionNum = (int) ($version->ver ?? 0);

        if ($pgVersionNum >= 120000) {
            // PostgreSQL 12+: coluna gerada automaticamente (zero manutenção)
            DB::statement("
                ALTER TABLE catalog_products
                ADD COLUMN search_vector tsvector
                GENERATED ALWAYS AS (
                    to_tsvector('portuguese',
                        coalesce(code, '') || ' ' ||
                        coalesce(name, '') || ' ' ||
                        coalesce(short_description, '')
                    )
                ) STORED
            ");
        } else {
            // PostgreSQL < 12: coluna + trigger
            DB::statement("ALTER TABLE catalog_products ADD COLUMN search_vector tsvector");

            DB::statement("
                CREATE OR REPLACE FUNCTION catalog_products_search_vector_update() RETURNS trigger AS \$\$
                BEGIN
                    NEW.search_vector := to_tsvector('portuguese',
                        coalesce(NEW.code, '') || ' ' ||
                        coalesce(NEW.name, '') || ' ' ||
                        coalesce(NEW.short_description, '')
                    );
                    RETURN NEW;
                END;
                \$\$ LANGUAGE plpgsql;
            ");

            DB::statement("
                CREATE TRIGGER trg_catalog_products_search_vector
                BEFORE INSERT OR UPDATE ON catalog_products
                FOR EACH ROW EXECUTE FUNCTION catalog_products_search_vector_update();
            ");

            // Popula retroativamente
            DB::statement("
                UPDATE catalog_products SET search_vector = to_tsvector('portuguese',
                    coalesce(code, '') || ' ' ||
                    coalesce(name, '') || ' ' ||
                    coalesce(short_description, '')
                )
            ");
        }

        // Índice GIN parcial (exclui soft-deleted)
        DB::statement("
            CREATE INDEX idx_products_search ON catalog_products
            USING GIN(search_vector)
            WHERE deleted_at IS NULL
        ");

        // Índices btree para lookup exato de barcode/sku
        DB::statement("CREATE INDEX IF NOT EXISTS idx_variants_barcode ON catalog_variants (barcode) WHERE barcode IS NOT NULL AND deleted_at IS NULL");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_variants_sku ON catalog_variants (sku) WHERE deleted_at IS NULL");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS idx_variants_sku");
        DB::statement("DROP INDEX IF EXISTS idx_variants_barcode");
        DB::statement("DROP INDEX IF EXISTS idx_products_search");
        DB::statement("DROP TRIGGER IF EXISTS trg_catalog_products_search_vector ON catalog_products");
        DB::statement("DROP FUNCTION IF EXISTS catalog_products_search_vector_update()");
        DB::statement("ALTER TABLE catalog_products DROP COLUMN IF EXISTS search_vector");
    }
};
