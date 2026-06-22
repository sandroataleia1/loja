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
            // Código interno único (ex: "LOJA-001") — gerado pelo sistema
            $table->string('code', 50)->nullable()->unique()->after('uuid');
            // Razão social oficial (CNPJ)
            $table->string('legal_name', 200)->nullable()->after('name');
            // Nome fantasia
            $table->string('trade_name', 200)->nullable()->after('legal_name');
            // CNPJ ou CPF (sem formatação: apenas dígitos)
            $table->string('document', 18)->nullable()->after('trade_name');
            // E-mail de contato principal
            $table->string('email', 200)->nullable()->after('document');
            // Telefone de contato principal
            $table->string('phone', 20)->nullable()->after('email');
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
