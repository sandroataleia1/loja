<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Stores ────────────────────────────────────────────────────────────
        Schema::create('stores', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 20);
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->jsonb('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_ecommerce')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active']);
        });

        // ── Inventory Balances ────────────────────────────────────────────────
        // Saldo atual por variante × loja. Atualizado em cada movimentação.
        Schema::create('inventory_balances', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('variant_id')->constrained('catalog_variants', 'uuid')->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->timestamps();

            // Um saldo por variante por loja (dentro do tenant via cascade)
            $table->unique(['store_id', 'variant_id']);
            $table->index(['tenant_id', 'store_id']);
            $table->index(['tenant_id', 'variant_id']);
        });

        // ── Stock Movements (imutável — sem updated_at, sem soft delete) ──────
        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('variant_id')->constrained('catalog_variants', 'uuid')->cascadeOnDelete();
            $table->string('type', 20);                  // MovementTypeEnum value
            $table->integer('quantity');                  // positivo = entrada, negativo = saída
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->integer('reserved_before')->default(0);
            $table->integer('reserved_after')->default(0);
            $table->string('reference_type', 100)->nullable(); // ex: 'sale', 'purchase_order'
            $table->uuid('reference_id')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users', 'uuid')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index(['tenant_id', 'store_id', 'created_at']);
            $table->index(['tenant_id', 'variant_id', 'created_at']);
            $table->index(['tenant_id', 'type', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        // ── Stock Transfers ───────────────────────────────────────────────────
        Schema::create('stock_transfers', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('origin_store_id')->constrained('stores', 'uuid')->restrictOnDelete();
            $table->foreignUuid('destination_store_id')->constrained('stores', 'uuid')->restrictOnDelete();
            $table->string('status', 20)->default('pending');  // TransferStatusEnum
            $table->string('notes', 500)->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users', 'uuid')->nullOnDelete();
            $table->foreignUuid('dispatched_by')->nullable()->constrained('users', 'uuid')->nullOnDelete();
            $table->foreignUuid('received_by')->nullable()->constrained('users', 'uuid')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'origin_store_id']);
            $table->index(['tenant_id', 'destination_store_id']);
        });

        Schema::create('stock_transfer_items', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('transfer_id')->constrained('stock_transfers', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('variant_id')->constrained('catalog_variants', 'uuid')->restrictOnDelete();
            $table->integer('quantity_requested');
            $table->integer('quantity_sent')->nullable();
            $table->integer('quantity_received')->nullable();
            $table->timestamps();

            $table->unique(['transfer_id', 'variant_id']);
        });

        // ── Stock Counts (inventário físico) ──────────────────────────────────
        Schema::create('stock_counts', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores', 'uuid')->cascadeOnDelete();
            $table->string('status', 20)->default('draft');  // StockCountStatusEnum
            $table->string('notes', 500)->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users', 'uuid')->nullOnDelete();
            $table->foreignUuid('committed_by')->nullable()->constrained('users', 'uuid')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'store_id', 'status']);
        });

        Schema::create('stock_count_items', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('count_id')->constrained('stock_counts', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('variant_id')->constrained('catalog_variants', 'uuid')->restrictOnDelete();
            $table->integer('system_quantity');    // snapshot no momento da abertura
            $table->integer('counted_quantity')->nullable();
            $table->integer('difference')->nullable()->storedAs('counted_quantity - system_quantity');
            $table->timestamps();

            $table->unique(['count_id', 'variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_count_items');
        Schema::dropIfExists('stock_counts');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_balances');
        Schema::dropIfExists('stores');
    }
};
