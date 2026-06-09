<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Domínio de devoluções e trocas.
 *
 * Design:
 * - sale_returns é imutável após criação (append-only, sem UPDATE destrutivo)
 * - return_items rastreia quantidade devolvida por item
 * - returned_quantity em sale_items é atualizado via trigger de negócio
 * - status: pending → approved → processed → refunded | rejected
 * - Devolução gera FinancialEntry reversal e StockMovement de entrada
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
        |----------------------------------------------------------------------
        | sale_returns — cabeçalho da devolução
        |----------------------------------------------------------------------
        */
        Schema::create('sale_returns', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores', 'uuid')->restrictOnDelete();
            $table->foreignUuid('original_sale_id')->constrained('sales', 'uuid')->restrictOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers', 'uuid')->nullOnDelete();
            $table->foreignUuid('processed_by')->nullable()->constrained('users', 'uuid')->nullOnDelete();

            $table->string('code', 30)->nullable();
            $table->string('status', 30)->default('pending');

            $table->string('return_type', 20)->default('return'); // return | exchange
            $table->string('reason', 50)->nullable();             // ReturnReasonEnum
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('refund_amount_cents')->default(0);
            $table->string('refund_method', 30)->nullable(); // PaymentMethodEnum | store_credit

            $table->boolean('stock_restocked')->default(false);
            $table->boolean('financial_reversed')->default(false);

            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'original_sale_id']);
            $table->index(['tenant_id', 'status', 'created_at']);
            $table->index(['tenant_id', 'store_id', 'created_at']);
        });

        /*
        |----------------------------------------------------------------------
        | sale_return_items — itens devolvidos
        |----------------------------------------------------------------------
        */
        Schema::create('sale_return_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('sale_return_id')->constrained('sale_returns', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('sale_item_id')->constrained('sale_items', 'uuid')->restrictOnDelete();
            $table->foreignUuid('variant_id')->nullable()->constrained('catalog_variants', 'uuid')->nullOnDelete();

            $table->unsignedSmallInteger('quantity_returned');
            $table->unsignedBigInteger('unit_price_cents');    // snapshot do preço na venda
            $table->unsignedBigInteger('refund_amount_cents'); // pode ser menor (taxa de reposição)

            $table->string('condition', 30)->nullable(); // good | damaged | defective
            $table->text('condition_notes')->nullable();

            $table->timestamps();

            $table->index(['sale_return_id']);
            $table->index(['sale_item_id']);
        });

        /*
        |----------------------------------------------------------------------
        | Adicionar returned_quantity em sale_items para rastreabilidade
        |----------------------------------------------------------------------
        */
        Schema::table('sale_items', function (Blueprint $table): void {
            $table->unsignedSmallInteger('returned_quantity')->default(0)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table): void {
            $table->dropColumn('returned_quantity');
        });

        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
    }
};
