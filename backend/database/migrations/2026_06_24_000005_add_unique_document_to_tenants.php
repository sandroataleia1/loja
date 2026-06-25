<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona constraint de unicidade ao campo `document` (CNPJ) da tabela tenants.
 *
 * Antes de criar o índice, remove linhas duplicadas mantendo apenas a
 * primeira (menor uuid lexicográfico) por documento. Tenants com document
 * NULL são ignorados (NULL não viola unicidade no PostgreSQL).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicatas: mantém o uuid mínimo por documento não-nulo
        DB::statement("
            DELETE FROM tenants
            WHERE document IS NOT NULL
              AND uuid::text NOT IN (
                SELECT MIN(uuid::text)
                FROM tenants
                WHERE document IS NOT NULL
                GROUP BY document
              )
        ");

        Schema::table('tenants', function (Blueprint $table): void {
            // unique parcial só para registros com document preenchido
            // Em PostgreSQL usa-se índice condicional via DB::statement abaixo
            // O unique padrão do Laravel cobre NULLs corretamente no Postgres
            $table->unique('document', 'tenants_document_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropUnique('tenants_document_unique');
        });
    }
};
