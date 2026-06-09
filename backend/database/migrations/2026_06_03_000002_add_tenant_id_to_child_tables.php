<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona tenant_id às tabelas filho que não possuíam isolamento direto.
 *
 * Sem tenant_id próprio, queries diretas nesses models ignoravam o TenantScope
 * e poderiam expor dados de outros tenants. Agora cada tabela tem isolamento
 * independente do parent, permitindo uso de BelongsToTenant nas queries diretas.
 *
 * Backfill: o tenant_id é propagado do parent já existente na mesma transação.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── sale_items ────────────────────────────────────────────────────────
        Schema::table('sale_items', function (Blueprint $table): void {
            $table->uuid('tenant_id')->nullable()->after('uuid');
        });

        // Backfill: copiar tenant_id da venda pai
        DB::statement('
            UPDATE sale_items si
            SET tenant_id = s.tenant_id
            FROM sales s
            WHERE si.sale_id = s.uuid
        ');

        Schema::table('sale_items', function (Blueprint $table): void {
            $table->uuid('tenant_id')->nullable(false)->change();
            $table->foreign('tenant_id')->references('uuid')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'sale_id'], 'sale_items_tenant_sale_idx');
        });

        // ── conditional_items ─────────────────────────────────────────────────
        Schema::table('conditional_items', function (Blueprint $table): void {
            $table->uuid('tenant_id')->nullable()->after('uuid');
        });

        DB::statement('
            UPDATE conditional_items ci
            SET tenant_id = c.tenant_id
            FROM conditionals c
            WHERE ci.conditional_id = c.uuid
        ');

        Schema::table('conditional_items', function (Blueprint $table): void {
            $table->uuid('tenant_id')->nullable(false)->change();
            $table->foreign('tenant_id')->references('uuid')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'conditional_id'], 'conditional_items_tenant_conditional_idx');
        });

        // ── stock_transfer_items ──────────────────────────────────────────────
        Schema::table('stock_transfer_items', function (Blueprint $table): void {
            $table->uuid('tenant_id')->nullable()->after('uuid');
        });

        DB::statement('
            UPDATE stock_transfer_items sti
            SET tenant_id = st.tenant_id
            FROM stock_transfers st
            WHERE sti.transfer_id = st.uuid
        ');

        Schema::table('stock_transfer_items', function (Blueprint $table): void {
            $table->uuid('tenant_id')->nullable(false)->change();
            $table->foreign('tenant_id')->references('uuid')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'transfer_id'], 'stock_transfer_items_tenant_transfer_idx');
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table): void {
            $table->dropIndex('stock_transfer_items_tenant_transfer_idx');
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('conditional_items', function (Blueprint $table): void {
            $table->dropIndex('conditional_items_tenant_conditional_idx');
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('sale_items', function (Blueprint $table): void {
            $table->dropIndex('sale_items_tenant_sale_idx');
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
