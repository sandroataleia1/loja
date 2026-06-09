<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |----------------------------------------------------------------------
        | sales — decisão fiscal imutável congelada no momento da criação
        |
        | fiscal_mode é armazenado na venda e NÃO derivado da config atual
        | do tenant. Isso garante que mudanças de configuração não afetam
        | vendas já registradas.
        |----------------------------------------------------------------------
        */
        Schema::table('sales', function (Blueprint $table): void {
            $table->string('fiscal_mode', 10)->nullable()->after('fiscal_key');
        });

        /*
        |----------------------------------------------------------------------
        | tenant_fiscal_settings — política de emissão automática
        |----------------------------------------------------------------------
        */
        Schema::table('tenant_fiscal_settings', function (Blueprint $table): void {
            $table->boolean('auto_issue_nfce')->default(true)->after('is_active');
            $table->boolean('allow_manual_nfce')->default(true)->after('auto_issue_nfce');
            $table->string('default_fiscal_mode', 10)->nullable()->after('allow_manual_nfce');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_fiscal_settings', function (Blueprint $table): void {
            $table->dropColumn(['auto_issue_nfce', 'allow_manual_nfce', 'default_fiscal_mode']);
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->dropColumn('fiscal_mode');
        });
    }
};
