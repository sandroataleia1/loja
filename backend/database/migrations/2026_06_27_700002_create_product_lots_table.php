<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_lots', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('tenant_id');
            $table->foreign('tenant_id')->references('uuid')->on('tenants')->cascadeOnDelete();

            $table->uuid('product_id');
            $table->foreign('product_id')->references('uuid')->on('catalog_products')->cascadeOnDelete();

            $table->uuid('variant_id')->nullable();
            $table->foreign('variant_id')->references('uuid')->on('catalog_variants')->nullOnDelete();

            $table->string('lot_number', 60);
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('received_at');
            $table->decimal('initial_qty', 10, 4);
            $table->decimal('current_qty', 10, 4);

            $table->uuid('purchase_order_id')->nullable();
            $table->uuid('supplier_id')->nullable();
            $table->foreign('supplier_id')->references('uuid')->on('suppliers')->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'product_id', 'lot_number']);
            $table->index(['tenant_id', 'variant_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_lots');
    }
};
