<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona dados fiscais/cadastrais à tabela tenants.
 *
 * Os campos estavam no Model Tenant.php mas ausentes na migration,
 * causando silent data loss em qualquer Tenant::create() com esses campos.
 *
 * Todos os campos são nullable para não quebrar tenants existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            if (! Schema::hasColumn('tenants', 'code')) {
                $table->string('code', 50)->nullable()->unique()->after('uuid');
            }
            if (! Schema::hasColumn('tenants', 'legal_name')) {
                $table->string('legal_name', 200)->nullable()->after('name');
            }
            if (! Schema::hasColumn('tenants', 'trade_name')) {
                $table->string('trade_name', 200)->nullable()->after('legal_name');
            }
            if (! Schema::hasColumn('tenants', 'document')) {
                $table->string('document', 18)->nullable()->after('trade_name');
            }
            if (! Schema::hasColumn('tenants', 'email')) {
                $table->string('email', 200)->nullable()->after('document');
            }
            if (! Schema::hasColumn('tenants', 'phone')) {
                $table->string('phone', 20)->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'legal_name', 'trade_name', 'document', 'email', 'phone']);
        });
    }
};
