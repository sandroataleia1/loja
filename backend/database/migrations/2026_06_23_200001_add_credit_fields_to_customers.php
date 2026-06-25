<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona campos de crédito e fiscais à tabela customers.
 *
 * Campos novos:
 *   credit_limit  — limite de crédito do cliente
 *   situation     — situação comercial (active | blocked | overdue)
 *   rg            — registro geral (PF)
 *   ie            — inscrição estadual (PJ)
 *   im            — inscrição municipal (PJ)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->decimal('credit_limit', 12, 2)->default(0)->after('notes');
            $table->string('situation', 20)->default('active')->after('credit_limit'); // active|blocked|overdue
            $table->string('rg', 20)->nullable()->after('document');
            $table->string('ie', 30)->nullable()->after('rg');
            $table->string('im', 20)->nullable()->after('ie');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn(['credit_limit', 'situation', 'rg', 'ie', 'im']);
        });
    }
};
