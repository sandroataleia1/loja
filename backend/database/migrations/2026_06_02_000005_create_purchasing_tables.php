<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Suppliers ─────────────────────────────────────────────────────────
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->string('code', 20)->nullable();
            $table->string('person_type', 20)->default('COMPANY'); // INDIVIDUAL | COMPANY
            $table->string('name', 200);
            $table->string('trade_name', 200)->nullable();
            $table->string('document', 20)->nullable();       // CPF or CNPJ
            $table->string('email', 254)->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'suppliers_tenant_code_unique');
            $table->index(['tenant_id', 'is_active'], 'suppliers_tenant_active_idx');
        });

        // ── Purchase Orders ───────────────────────────────────────────────────
        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores', 'uuid')->restrictOnDelete();
            $table->foreignUuid('supplier_id')->constrained('suppliers', 'uuid')->restrictOnDelete();
            $table->string('code', 20)->nullable();
            $table->string('status', 30)->default('draft'); // PurchaseOrderStatusEnum
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users', 'uuid')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'purchase_orders_tenant_code_unique');
            $table->index(['tenant_id', 'status'], 'purchase_orders_tenant_status_idx');
            $table->index(['tenant_id', 'supplier_id'], 'purchase_orders_tenant_supplier_idx');
        });

        // ── Purchase Order Items ──────────────────────────────────────────────
        Schema::create('purchase_order_items', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('purchase_order_id')->constrained('purchase_orders', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->constrained('catalog_variants', 'uuid')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total_cost', 12, 2)->storedAs('quantity * unit_cost');
            $table->unsignedInteger('received_quantity')->default(0);
            $table->timestamps();

            $table->unique(['purchase_order_id', 'product_variant_id'], 'poi_order_variant_unique');
            $table->index('purchase_order_id', 'poi_order_idx');
        });

        // ── Purchase Receipts ─────────────────────────────────────────────────
        Schema::create('purchase_receipts', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('purchase_order_id')->constrained('purchase_orders', 'uuid')->restrictOnDelete();
            $table->foreignUuid('received_by')->nullable()->constrained('users', 'uuid')->nullOnDelete();
            $table->string('status', 20)->default('pending'); // pending | completed
            $table->timestamp('received_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('purchase_order_id', 'receipts_order_idx');
        });

        // ── Purchase Receipt Items ────────────────────────────────────────────
        Schema::create('purchase_receipt_items', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('receipt_id')->constrained('purchase_receipts', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('purchase_order_item_id')->constrained('purchase_order_items', 'uuid')->restrictOnDelete();
            $table->foreignUuid('product_variant_id')->constrained('catalog_variants', 'uuid')->restrictOnDelete();
            $table->unsignedInteger('quantity_received');
            $table->decimal('unit_cost', 12, 2);
            $table->timestamps();

            $table->index('receipt_id', 'receipt_items_receipt_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_receipt_items');
        Schema::dropIfExists('purchase_receipts');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
    }
};
