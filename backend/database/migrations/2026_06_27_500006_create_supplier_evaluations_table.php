<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplier_evaluations')) {
            return;
        }

        Schema::create('supplier_evaluations', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('supplier_id')->constrained('suppliers', 'uuid')->cascadeOnDelete();
            $table->date('reference_date');
            $table->unsignedTinyInteger('delivery_score');       // 1–5
            $table->unsignedTinyInteger('quality_score');        // 1–5
            $table->unsignedTinyInteger('price_score');          // 1–5
            $table->unsignedTinyInteger('service_score');        // 1–5
            $table->decimal('overall_score', 4, 2);              // média automática
            $table->unsignedSmallInteger('avg_delivery_days')->nullable();
            $table->decimal('on_time_delivery_rate', 5, 2)->nullable(); // 0–100 %
            $table->decimal('return_rate', 5, 2)->nullable();           // 0–100 %
            $table->text('notes')->nullable();
            $table->foreignUuid('evaluated_by')->constrained('users', 'uuid');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'supplier_id', 'reference_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_evaluations');
    }
};
