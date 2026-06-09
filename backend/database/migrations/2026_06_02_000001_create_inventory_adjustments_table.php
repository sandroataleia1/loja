<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustments', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores', 'uuid')->restrictOnDelete();
            $table->foreignUuid('variant_id')->constrained('catalog_variants', 'uuid')->restrictOnDelete();
            $table->uuid('movement_id')->nullable();
            $table->integer('previous_quantity');
            $table->integer('new_quantity');
            $table->integer('difference')->storedAs('new_quantity - previous_quantity');
            $table->string('reason', 500);
            $table->foreignUuid('created_by')->nullable()->constrained('users', 'uuid')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'store_id']);
            $table->index(['tenant_id', 'variant_id']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
