<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Produtos
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->string('code', 20)->nullable()->after('uuid');
            $table->unique(['tenant_id', 'code'], 'catalog_products_tenant_code_unique');
        });

        // Variantes (código derivado do produto: PRO000001-001)
        Schema::table('catalog_variants', function (Blueprint $table): void {
            $table->string('code', 30)->nullable()->after('uuid');
            $table->unique(['tenant_id', 'code'], 'catalog_variants_tenant_code_unique');
        });

        // Vendas
        Schema::table('sales', function (Blueprint $table): void {
            $table->string('code', 20)->nullable()->after('uuid');
            $table->unique(['tenant_id', 'code'], 'sales_tenant_code_unique');
        });

        // Pagamentos
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->string('code', 20)->nullable()->after('uuid');
        });

        // Transferências de estoque
        Schema::table('stock_transfers', function (Blueprint $table): void {
            $table->string('code', 20)->nullable()->after('uuid');
            $table->unique(['tenant_id', 'code'], 'stock_transfers_tenant_code_unique');
        });

        // Sessões de caixa
        Schema::table('cash_register_sessions', function (Blueprint $table): void {
            $table->string('code', 20)->nullable()->after('uuid');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropUnique('catalog_products_tenant_code_unique');
            $table->dropColumn('code');
        });

        Schema::table('catalog_variants', function (Blueprint $table): void {
            $table->dropUnique('catalog_variants_tenant_code_unique');
            $table->dropColumn('code');
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->dropUnique('sales_tenant_code_unique');
            $table->dropColumn('code');
        });

        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->dropColumn('code');
        });

        Schema::table('stock_transfers', function (Blueprint $table): void {
            $table->dropUnique('stock_transfers_tenant_code_unique');
            $table->dropColumn('code');
        });

        Schema::table('cash_register_sessions', function (Blueprint $table): void {
            $table->dropColumn('code');
        });
    }
};
