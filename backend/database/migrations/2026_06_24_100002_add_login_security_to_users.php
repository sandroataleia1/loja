<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona campos de bloqueio de conta por tentativas falhas.
 *
 * failed_login_count — contagem acumulada desde o último login bem-sucedido.
 * locked_until       — conta bloqueada até este timestamp (null = desbloqueada).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedTinyInteger('failed_login_count')->default(0)->after('is_active');
            $table->timestamp('locked_until')->nullable()->after('failed_login_count');

            // Índice para query de desbloqueio automático (cron / verificação no login)
            $table->index('locked_until', 'users_locked_until_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_locked_until_idx');
            $table->dropColumn(['failed_login_count', 'locked_until']);
        });
    }
};
