<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Garante no máximo um is_default=true por tenant antes de criar o índice
        DB::statement("
            UPDATE price_lists SET is_default = false
            WHERE uuid NOT IN (
                SELECT DISTINCT ON (tenant_id) uuid
                FROM price_lists
                WHERE is_default = true AND deleted_at IS NULL
                ORDER BY tenant_id, created_at
            ) AND is_default = true
        ");

        // Unique partial index: no PostgreSQL, índices parciais são o mecanismo correto
        // para unicidade condicional — não existe UNIQUE WHERE em nenhuma outra forma
        DB::statement("
            CREATE UNIQUE INDEX udx_price_lists_default_per_tenant
            ON price_lists (tenant_id)
            WHERE is_default = true AND deleted_at IS NULL
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS udx_price_lists_default_per_tenant');
    }
};
