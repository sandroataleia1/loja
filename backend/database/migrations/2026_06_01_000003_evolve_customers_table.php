<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Evolui a tabela customers para o modelo CRM Foundation (Sprint 5).
 *
 * Adiciona: person_type, trade_name, document, birth_date, last_purchase_at,
 *           total_purchases, total_orders.
 * Remove: phone, address, metadata.
 * Substitui: índice único customers_tenant_id_cpf_unique por índice parcial
 *             customers_tenant_document_unique (partial index no PostgreSQL).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            // Adiciona after 'code'
            $table->string('person_type', 20)->default('INDIVIDUAL')->after('code');

            // Adiciona after 'name'
            $table->string('trade_name', 150)->nullable()->after('name');

            // Adiciona after 'trade_name'
            $table->string('document', 20)->nullable()->after('trade_name');

            // Adiciona after 'document'
            $table->date('birth_date')->nullable()->after('document');

            // Adiciona after 'notes'
            $table->timestamp('last_purchase_at')->nullable()->after('notes');
            $table->decimal('total_purchases', 12, 2)->default(0)->after('last_purchase_at');
            $table->unsignedInteger('total_orders')->default(0)->after('total_purchases');

            // Remove colunas legadas
            $table->dropColumn(['phone', 'address', 'metadata']);

            // Remove o índice único antigo baseado em CPF
            $table->dropUnique('customers_tenant_id_cpf_unique');
        });

        // Cria índice único parcial no PostgreSQL:
        // apenas quando document IS NOT NULL e deleted_at IS NULL
        DB::statement(
            'CREATE UNIQUE INDEX customers_tenant_document_unique '
            . 'ON customers (tenant_id, document) '
            . 'WHERE deleted_at IS NULL AND document IS NOT NULL'
        );
    }

    public function down(): void
    {
        // Remove índice parcial criado manualmente
        DB::statement('DROP INDEX IF EXISTS customers_tenant_document_unique');

        Schema::table('customers', function (Blueprint $table): void {
            // Restaura colunas removidas
            $table->string('phone', 30)->nullable()->after('email');
            $table->jsonb('address')->nullable()->after('is_active');
            $table->jsonb('metadata')->nullable()->after('address');

            // Remove colunas adicionadas
            $table->dropColumn([
                'person_type',
                'trade_name',
                'document',
                'birth_date',
                'last_purchase_at',
                'total_purchases',
                'total_orders',
            ]);

            // Restaura índice único por CPF
            $table->unique(['tenant_id', 'cpf'], 'customers_tenant_id_cpf_unique');
        });
    }
};
