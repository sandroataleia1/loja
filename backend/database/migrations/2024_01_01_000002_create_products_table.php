<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->string('name');
            $table->string('sku', 100);
            $table->text('description')->nullable();
            $table->integer('price')->default(0);
            $table->integer('cost')->nullable();
            $table->string('status', 30)->default('active');
            $table->jsonb('attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
