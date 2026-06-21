<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            // Origem: pode vir de um orçamento ou ser criado diretamente
            $table->foreignUuid('quote_id')->nullable()->constrained('quotes', 'uuid')->nullOnDelete();
            // Venda vinculada (preenchida ao baixar no PDV)
            $table->foreignUuid('sale_id')->nullable()->constrained('sales', 'uuid')->nullOnDelete();

            $table->string('number', 20)->comment('PED000001 — gerado por SequenceEntity::Order');
            $table->string('status', 20)->default('pending');
            // Desconto do documento
            $table->string('discount_type', 10)->default('fixed')->comment('fixed | percent');
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->unsignedInteger('discount_cents')->default(0);
            // Totais
            $table->unsignedInteger('subtotal_cents')->default(0);
            $table->unsignedInteger('total_cents')->default(0);
            // Observações
            $table->text('notes')->nullable()->comment('Visível ao cliente');
            $table->text('internal_notes')->nullable()->comment('Uso interno');
            $table->string('payment_terms', 200)->nullable();
            // Datas de ciclo de vida
            $table->date('expected_at')->nullable()->comment('Previsão de entrega/retirada');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'sale_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
