<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_payment_gateways', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            // Gateway identifier: 'asaas' | 'efi' | 'mock'
            $table->string('gateway', 30)->default('asaas');
            // Encrypted API key (stored via Laravel encrypted cast)
            $table->text('api_key')->nullable();
            // 'sandbox' | 'production'
            $table->string('environment', 20)->default('sandbox');
            // Unique token per tenant — Asaas sends this in the webhook header
            $table->string('webhook_token', 64)->unique();
            $table->boolean('is_active')->default(false);
            // Static PIX key for manual mode (separate from gateway)
            $table->string('pix_key')->nullable();
            // 'cpf' | 'cnpj' | 'email' | 'phone' | 'random'
            $table->string('pix_key_type', 20)->nullable();
            // Extra gateway-specific settings
            $table->jsonb('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'gateway']);
        });

        Schema::create('pix_charges', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            // Nullable: charge may be created before the sale is finalized
            $table->foreignUuid('sale_id')
                ->nullable()
                ->constrained('sales', 'uuid')
                ->nullOnDelete();
            $table->string('gateway', 30)->default('asaas');
            $table->string('external_id')->nullable()->index();
            // 'pending' | 'paid' | 'expired' | 'refunded'
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('amount_cents');
            $table->text('qr_code_image')->nullable();   // base64 PNG
            $table->text('pix_copy_paste')->nullable();  // copia-e-cola string
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->jsonb('gateway_response')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pix_charges');
        Schema::dropIfExists('tenant_payment_gateways');
    }
};
