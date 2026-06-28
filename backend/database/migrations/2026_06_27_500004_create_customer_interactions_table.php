<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_interactions')) {
            return;
        }

        Schema::create('customer_interactions', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers', 'uuid')->cascadeOnDelete();
            // visit|call|whatsapp|email|complaint|note
            $table->string('interaction_type', 20);
            $table->string('subject', 200)->nullable();
            $table->text('description');
            $table->string('outcome', 100)->nullable();    // resultado da interação
            $table->timestamp('interacted_at');
            $table->foreignUuid('created_by')->constrained('users', 'uuid');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'customer_id', 'interacted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_interactions');
    }
};
