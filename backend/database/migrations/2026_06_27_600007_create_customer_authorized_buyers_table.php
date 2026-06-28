<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_authorized_buyers')) {
            return;
        }

        Schema::create('customer_authorized_buyers', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers', 'uuid')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('cpf', 14)->nullable();
            $table->string('rg', 20)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('relationship', 80)->nullable();
            $table->unsignedBigInteger('credit_limit_cents')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('authorized_at');
            $table->foreignUuid('authorized_by')->nullable()->constrained('users', 'uuid')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason', 200)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'customer_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_authorized_buyers');
    }
};
