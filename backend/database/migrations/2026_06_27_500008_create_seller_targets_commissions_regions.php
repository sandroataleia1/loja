<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seller_targets')) {
            Schema::create('seller_targets', function (Blueprint $table): void {
                $table->uuid()->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
                $table->foreignUuid('seller_id')->constrained('seller_profiles', 'uuid')->cascadeOnDelete();
                $table->unsignedSmallInteger('year');
                $table->unsignedTinyInteger('month');          // 1–12
                $table->unsignedBigInteger('target_cents');
                $table->unsignedBigInteger('achieved_cents')->default(0);
                $table->decimal('commission_rate_override', 5, 2)->nullable(); // sobrescreve taxa padrão
                $table->timestamps();

                $table->unique(['seller_id', 'year', 'month'], 'uq_seller_target_period');
                $table->index(['tenant_id', 'seller_id', 'year', 'month']);
            });
        }

        if (! Schema::hasTable('seller_commissions')) {
            Schema::create('seller_commissions', function (Blueprint $table): void {
                $table->uuid()->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
                $table->foreignUuid('seller_id')->constrained('seller_profiles', 'uuid')->cascadeOnDelete();
                $table->unsignedSmallInteger('reference_year');
                $table->unsignedTinyInteger('reference_month');  // 1–12
                $table->unsignedBigInteger('gross_amount_cents');
                $table->decimal('commission_rate', 5, 2);
                $table->unsignedBigInteger('commission_cents');
                $table->unsignedBigInteger('discount_given_cents')->default(0);
                $table->unsignedBigInteger('net_commission_cents');
                $table->string('status', 20)->default('pending'); // pending|approved|paid
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['seller_id', 'reference_year', 'reference_month'], 'uq_seller_commission_period');
                $table->index(['tenant_id', 'seller_id', 'status']);
            });
        }

        if (! Schema::hasTable('seller_regions')) {
            Schema::create('seller_regions', function (Blueprint $table): void {
                $table->uuid()->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
                $table->foreignUuid('seller_id')->constrained('seller_profiles', 'uuid')->cascadeOnDelete();
                // city|state|cep_range|neighborhood
                $table->string('region_type', 20);
                $table->string('value', 100);   // "SP", "São Paulo", "01000-09999", "Centro"
                $table->timestamps();

                $table->index(['tenant_id', 'seller_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_regions');
        Schema::dropIfExists('seller_commissions');
        Schema::dropIfExists('seller_targets');
    }
};
