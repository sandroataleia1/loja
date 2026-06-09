<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Cash Register Sessions ────────────────────────────────────────────
        Schema::create('cash_register_sessions', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users', 'uuid')->restrictOnDelete();
            $table->string('status', 20)->default('open');
            $table->integer('opening_amount_cents')->default(0);
            $table->integer('closing_amount_cents')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Apenas uma sessão aberta por usuário por loja
            $table->index(['tenant_id', 'store_id', 'status']);
            $table->index(['tenant_id', 'user_id', 'status']);
        });

        // ── Sales ─────────────────────────────────────────────────────────────
        Schema::create('sales', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores', 'uuid')->restrictOnDelete();
            $table->foreignUuid('session_id')
                ->nullable()
                ->constrained('cash_register_sessions', 'uuid')
                ->nullOnDelete();
            // customer_id sem FK — módulo Customers ainda não implementado
            $table->uuid('customer_id')->nullable();
            $table->foreignUuid('seller_id')
                ->nullable()
                ->constrained('users', 'uuid')
                ->nullOnDelete();
            $table->string('status', 30)->default('draft');
            $table->integer('subtotal_cents')->default(0);
            $table->integer('discount_total_cents')->default(0);
            $table->integer('tax_total_cents')->default(0);   // reservado para futuro fiscal
            $table->integer('total_cents')->default(0);
            // sync_uuid: UUID gerado pelo PDV — garante idempotência na sincronização
            $table->uuid('sync_uuid')->nullable()->unique();
            $table->string('fiscal_status', 20)->nullable();
            $table->string('fiscal_key', 48)->nullable();     // chave de acesso NFC-e futura
            $table->jsonb('fiscal_data')->nullable();         // payload fiscal futuro
            // reference_sale_id: aponta para venda original em devoluções/trocas
            $table->uuid('reference_sale_id')->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'store_id', 'status']);
            $table->index(['tenant_id', 'status', 'created_at']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'seller_id']);
            $table->index(['tenant_id', 'session_id']);
        });

        // Self-referencing FK added after primary key is committed
        Schema::table('sales', function (Blueprint $table): void {
            $table->foreign('reference_sale_id')->references('uuid')->on('sales')->nullOnDelete();
        });

        // ── Sale Items (imutáveis — snapshot histórico) ───────────────────────
        Schema::create('sale_items', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('sale_id')->constrained('sales', 'uuid')->cascadeOnDelete();
            // product_variant_id nullable: preserva histórico mesmo se variante for deletada
            $table->foreignUuid('product_variant_id')
                ->nullable()
                ->constrained('catalog_variants', 'uuid')
                ->nullOnDelete();
            $table->string('sku_snapshot', 100);
            $table->string('name_snapshot', 255);
            $table->jsonb('attributes_snapshot')->default('{}');
            $table->integer('quantity');
            $table->integer('unit_price_cents');
            $table->integer('cost_price_cents')->nullable();
            $table->integer('discount_amount_cents')->default(0);
            // subtotal = quantity * unit_price_cents
            $table->integer('subtotal_cents');
            // total = subtotal_cents - discount_amount_cents
            $table->integer('total_cents');
            $table->timestamps();

            $table->index(['sale_id']);
        });

        // ── Payment Transactions ──────────────────────────────────────────────
        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('sale_id')->constrained('sales', 'uuid')->cascadeOnDelete();
            $table->string('method', 30);
            $table->integer('amount_cents');
            $table->string('status', 20)->default('pending');
            $table->string('external_reference', 255)->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'sale_id']);
            $table->index(['tenant_id', 'method', 'status']);
        });

        // ── Sale Commissions ──────────────────────────────────────────────────
        Schema::create('sale_commissions', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('sale_id')->constrained('sales', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users', 'uuid')->restrictOnDelete();
            $table->decimal('percentage', 5, 2);
            $table->integer('amount_cents');
            $table->timestamps();

            $table->unique(['sale_id', 'user_id']);
        });

        // ── Sale Discounts (auditoria granular de descontos) ──────────────────
        Schema::create('sale_discounts', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('sale_id')->constrained('sales', 'uuid')->cascadeOnDelete();
            // null = desconto na venda; preenchido = desconto no item
            $table->foreignUuid('sale_item_id')
                ->nullable()
                ->constrained('sale_items', 'uuid')
                ->cascadeOnDelete();
            $table->string('type', 20);              // DiscountTypeEnum
            $table->decimal('percentage', 5, 2)->nullable();
            $table->integer('amount_cents');
            $table->string('reason', 255)->nullable();
            $table->foreignUuid('approved_by')
                ->nullable()
                ->constrained('users', 'uuid')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'sale_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_discounts');
        Schema::dropIfExists('sale_commissions');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('cash_register_sessions');
    }
};
