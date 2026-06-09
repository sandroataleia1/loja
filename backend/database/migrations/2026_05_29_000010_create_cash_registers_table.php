<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table): void {
            $table->uuid()->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores', 'uuid')->cascadeOnDelete();
            // Código operacional legível: "CAIXA-01", "PDV-02" — definido pelo operador
            $table->string('code', 30);
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'store_id', 'code'], 'cash_registers_store_code_unique');
            $table->index(['tenant_id', 'store_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};
