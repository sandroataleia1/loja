<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_guarantors')) {
            return;
        }

        Schema::create('customer_guarantors', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers', 'uuid')->cascadeOnDelete();
            $table->string('guarantor_type', 10)->default('person'); // person|company
            $table->string('name', 150);
            $table->string('document', 20);          // CPF ou CNPJ
            $table->string('rg', 20)->nullable();
            $table->string('profession', 100)->nullable();
            $table->string('employer', 150)->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('zip_code', 9)->nullable();
            $table->string('street', 200)->nullable();
            $table->string('number', 20)->nullable();
            $table->string('complement', 100)->nullable();
            $table->string('neighborhood', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 2)->nullable();
            $table->string('relationship', 80)->nullable();  // "cônjuge", "sócio", "parente"
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_guarantors');
    }
};
