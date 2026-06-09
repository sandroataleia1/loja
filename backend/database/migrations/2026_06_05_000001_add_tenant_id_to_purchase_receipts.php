<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A2-03: adiciona tenant_id a purchase_receipts.
 *
 * O recebimento de compra é tratado como entidade (criado e retornado, e
 * referenciado por títulos a pagar), não apenas como item-filho. Sem tenant_id
 * próprio, uma futura consulta direta (ex.: GET /receipts/{id} via route binding)
 * ignoraria o TenantScope. Backfill a partir do pedido de compra pai.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_receipts', function (Blueprint $table): void {
            $table->uuid('tenant_id')->nullable()->after('uuid');
        });

        DB::statement('
            UPDATE purchase_receipts pr
            SET tenant_id = po.tenant_id
            FROM purchase_orders po
            WHERE pr.purchase_order_id = po.uuid
        ');

        Schema::table('purchase_receipts', function (Blueprint $table): void {
            $table->uuid('tenant_id')->nullable(false)->change();
            $table->foreign('tenant_id')->references('uuid')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'purchase_order_id'], 'purchase_receipts_tenant_order_idx');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_receipts', function (Blueprint $table): void {
            $table->dropIndex('purchase_receipts_tenant_order_idx');
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
