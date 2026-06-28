<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('carrier_freight_tables')) {
            Schema::create('carrier_freight_tables', function (Blueprint $table): void {
                $table->uuid()->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
                $table->foreignUuid('carrier_id')->constrained('carriers', 'uuid')->cascadeOnDelete();
                $table->string('name', 150);
                // by_weight|by_value|by_cep_range|fixed|free
                $table->string('pricing_type', 20);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'carrier_id']);
            });
        }

        if (! Schema::hasTable('carrier_freight_ranges')) {
            Schema::create('carrier_freight_ranges', function (Blueprint $table): void {
                $table->uuid()->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
                $table->foreignUuid('freight_table_id')
                    ->constrained('carrier_freight_tables', 'uuid')->cascadeOnDelete();
                $table->decimal('min_weight_g', 12, 3)->nullable();
                $table->decimal('max_weight_g', 12, 3)->nullable();
                $table->unsignedBigInteger('min_value_cents')->nullable();
                $table->unsignedBigInteger('max_value_cents')->nullable();
                $table->string('min_cep', 8)->nullable();
                $table->string('max_cep', 8)->nullable();
                $table->unsignedBigInteger('price_cents');
                $table->unsignedSmallInteger('estimated_days')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'freight_table_id']);
            });
        }

        if (! Schema::hasTable('carrier_occurrences')) {
            Schema::create('carrier_occurrences', function (Blueprint $table): void {
                $table->uuid()->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
                $table->foreignUuid('carrier_id')->constrained('carriers', 'uuid')->cascadeOnDelete();
                $table->string('occurrence_type', 20); // delivered|not_found|refused|damaged|lost|returned
                $table->string('tracking_code', 80)->nullable();
                $table->string('order_reference', 80)->nullable();
                $table->text('description')->nullable();
                $table->timestamp('occurred_at');
                $table->foreignUuid('registered_by')->constrained('users', 'uuid');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'carrier_id', 'occurred_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_occurrences');
        Schema::dropIfExists('carrier_freight_ranges');
        Schema::dropIfExists('carrier_freight_tables');
    }
};
