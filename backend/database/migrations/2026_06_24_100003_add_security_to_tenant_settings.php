<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona coluna `security` ao tenant_settings com configurações de bloqueio de conta.
 *
 * max_login_attempts — tentativas falhas antes de bloquear (default 5)
 * lockout_minutes    — duração do bloqueio em minutos (default 15)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_settings', function (Blueprint $table): void {
            $table->jsonb('security')->default('{}')->after('logistics');
        });

        // Preenche tenants existentes com os valores default
        DB::table('tenant_settings')->update([
            'security' => json_encode([
                'max_login_attempts' => 5,
                'lockout_minutes'    => 15,
            ]),
        ]);
    }

    public function down(): void
    {
        Schema::table('tenant_settings', function (Blueprint $table): void {
            $table->dropColumn('security');
        });
    }
};
