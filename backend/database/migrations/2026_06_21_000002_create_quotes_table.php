<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            $table->string('number', 20)->comment('ORC000001 — gerado por SequenceEntity::Quote');
            $table->string('status', 20)->default('draft');
            // Validade
            $table->unsignedSmallInteger('validity_days')->default(7);
            $table->date('valid_until')->nullable();
            // Desconto do documento
            $table->string('discount_type', 10)->default('fixed')->comment('fixed | percent');
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->unsignedInteger('discount_cents')->default(0);
            // Totais (atualizados ao salvar itens)
            $table->unsignedInteger('subtotal_cents')->default(0);
            $table->unsignedInteger('total_cents')->default(0);
            // Mensagem e condições
            $table->text('notes')->nullable()->comment('Visível ao cliente');
            $table->text('internal_notes')->nullable()->comment('Uso interno');
            $table->string('payment_terms', 200)->nullable();
            // Rastreio de ciclo de vida
            $table->foreignUuid('converted_to_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
