<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Converte PINs armazenados em plaintext para SHA-256(APP_KEY + pin).
 *
 * Executa no deploy imediatamente após a detecção do problema de segurança C1.
 * PINs já hashed (64 chars hex) são ignorados para idempotência.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pepper = config('app.key', '');

        DB::table('users')
            ->whereNotNull('pin')
            // PINs hashed têm exatamente 64 caracteres hex — não reprocessar
            ->where(DB::raw('length(pin)'), '<', 64)
            ->lazyById(100, 'uuid')
            ->each(function (object $user) use ($pepper): void {
                DB::table('users')
                    ->where('uuid', $user->uuid)
                    ->update(['pin' => hash('sha256', $pepper . $user->pin)]);
            });
    }

    public function down(): void
    {
        // Irreversível: não é possível recuperar PINs em plaintext a partir do hash.
        // Para reverter, os usuários devem redefinir seus PINs.
    }
};
