<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_addresses', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('tenant_id');
            $table->foreign('tenant_id')->references('uuid')->on('tenants')->cascadeOnDelete();

            $table->uuid('warehouse_id')->nullable();

            $table->string('aisle', 10)->nullable();
            $table->string('rack', 10)->nullable();
            $table->string('shelf', 10)->nullable();
            $table->string('position', 10)->nullable();

            $table->decimal('capacity', 10, 4)->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'warehouse_id', 'aisle', 'rack', 'shelf', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_addresses');
    }
};
