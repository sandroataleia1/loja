<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Campo "usuário" para identificação rápida (opcional, único por tenant)
            $table->string('username', 60)->nullable()->after('name');
            // Telefone para contato
            $table->string('phone', 30)->nullable()->after('email');
        });

        // Índice único username por tenant (ignorando nulls no PostgreSQL)
        Schema::table('users', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'username'], 'users_tenant_username_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_tenant_username_unique');
            $table->dropColumn(['username', 'phone']);
        });
    }
};
