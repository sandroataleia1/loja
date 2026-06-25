<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Torna unit_of_measure nullable — substituído por unit_id → units table (M01).
        // A coluna permanece para leitura de registros legados mas não é mais obrigatória.
        DB::statement('ALTER TABLE catalog_products ALTER COLUMN unit_of_measure DROP NOT NULL');

        // Limpa unit_of_measure onde unit_id já está preenchido (evita dupla fonte de verdade)
        DB::statement("UPDATE catalog_products SET unit_of_measure = NULL WHERE unit_id IS NOT NULL AND unit_of_measure IS NOT NULL");
    }

    public function down(): void
    {
        // Restaura NOT NULL somente se não houver NULLs — seguro para rollback
        DB::statement("UPDATE catalog_products SET unit_of_measure = 'UN' WHERE unit_of_measure IS NULL");
        DB::statement('ALTER TABLE catalog_products ALTER COLUMN unit_of_measure SET NOT NULL');
    }
};
