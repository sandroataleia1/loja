<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_bank_references')) {
            return;
        }

        Schema::create('customer_bank_references', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers', 'uuid')->cascadeOnDelete();
            $table->string('bank_name', 80);
            $table->string('bank_agency', 20)->nullable();
            $table->string('account_type', 20)->nullable();  // checking|savings|investment
            $table->string('contact_name', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->date('consulted_at')->nullable();
            $table->string('email_1', 150)->nullable();
            $table->string('email_2', 150)->nullable();
            $table->date('first_purchase_at')->nullable();
            $table->unsignedBigInteger('first_purchase_value_cents')->nullable();
            $table->unsignedBigInteger('highest_purchase_value_cents')->nullable();
            $table->date('last_purchase_at')->nullable();
            $table->unsignedBigInteger('last_purchase_value_cents')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_bank_references');
    }
};
