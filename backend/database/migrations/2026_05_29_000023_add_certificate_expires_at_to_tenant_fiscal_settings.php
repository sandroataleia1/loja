<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona certificate_expires_at em tenant_fiscal_settings.
 *
 * Permite alertas proativos antes da expiração do certificado digital A1/A3.
 * Sem este campo, o certificado expira silenciosamente e todas as emissões falham.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_fiscal_settings', function (Blueprint $table): void {
            $table->date('certificate_expires_at')
                ->nullable()
                ->after('certificate_password_encrypted')
                ->comment('Data de expiração do certificado digital (A1/A3)');

            $table->unsignedTinyInteger('fiscal_retry_max')
                ->default(5)
                ->after('allow_manual_nfce')
                ->comment('Máximo de tentativas de reemissão antes de marcar como falha definitiva');

            $table->index(['certificate_expires_at'], 'fiscal_settings_cert_expiry_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_fiscal_settings', function (Blueprint $table): void {
            $table->dropIndex('fiscal_settings_cert_expiry_idx');
            $table->dropColumn(['certificate_expires_at', 'fiscal_retry_max']);
        });
    }
};
