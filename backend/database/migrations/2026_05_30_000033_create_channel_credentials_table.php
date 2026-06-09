<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Credenciais de canal externo — criptografadas em repouso.
 *
 * encrypted_credentials: JSON criptografado com Crypt::encryptString().
 *   Contém: access_token, refresh_token, webhook_secret, etc. (por provider).
 *
 * provider: string livre ('instagram', 'mercado_livre', 'shopify', 'vtex', etc.)
 *   Permite novos canais sem migração.
 *
 * expires_at: para tokens OAuth com TTL. Null = sem expiração (API keys).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_credentials', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('tenant_id');
            $table->foreignUuid('channel_id')->constrained('channels', 'uuid')->cascadeOnDelete();

            $table->string('provider', 100);            // 'instagram', 'mercado_livre', etc.
            $table->text('encrypted_credentials');      // Crypt::encryptString(json_encode([...]))
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            // ── Constraints ──────────────────────────────────────────────────
            $table->unique(['channel_id', 'provider'], 'channel_credential_unique');

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index(['tenant_id', 'channel_id'], 'channel_credential_tenant_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_credentials');
    }
};
