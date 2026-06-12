<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Marca como verificados todos os usuários ativos que ainda têm email_verified_at = null.
 * Esses usuários foram criados antes de email_verified_at ser adicionado ao $fillable
 * ou antes da verificação de e-mail ser removida do fluxo de login.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->where('is_active', true)
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // irreversível — não faz sentido desmarcar e-mails como não verificados
    }
};
