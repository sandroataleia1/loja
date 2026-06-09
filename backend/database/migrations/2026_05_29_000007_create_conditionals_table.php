<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Condicional: reserva de produtos para cliente (prova, aprovação, etc.)
     * Tabela stub — lógica de negócio será implementada no módulo Conditionals.
     */
    public function up(): void
    {
        Schema::create('conditionals', function (Blueprint $table): void {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id')->index();
            $table->uuid('store_id');
            $table->uuid('customer_id')->nullable();
            $table->string('code', 20)->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('discount_total_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')
                ->references('uuid')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conditionals');
    }
};
