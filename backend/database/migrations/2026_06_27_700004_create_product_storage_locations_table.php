<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_storage_locations', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('tenant_id');
            $table->foreign('tenant_id')->references('uuid')->on('tenants')->cascadeOnDelete();

            $table->uuid('variant_id');
            $table->foreign('variant_id')->references('uuid')->on('catalog_variants')->cascadeOnDelete();

            $table->uuid('storage_address_id');
            $table->foreign('storage_address_id')->references('uuid')->on('storage_addresses')->cascadeOnDelete();

            $table->boolean('is_primary')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'variant_id', 'storage_address_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_storage_locations');
    }
};
