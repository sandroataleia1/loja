<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partner_referrals')) {
            return;
        }

        Schema::create('partner_referrals', function (Blueprint $table): void {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->foreign('tenant_id')->references('uuid')->on('tenants')->cascadeOnDelete();

            $table->uuid('partner_id');
            $table->foreign('partner_id')->references('uuid')->on('partner_professionals')->cascadeOnDelete();

            $table->uuid('customer_id');
            $table->foreign('customer_id')->references('uuid')->on('customers')->cascadeOnDelete();

            $table->date('referred_at');
            $table->date('first_purchase_at')->nullable();

            $table->decimal('commission_percent', 5, 2);
            $table->decimal('commission_value', 10, 2)->default(0);
            $table->string('commission_status', 20)->default('pending');
            $table->date('commission_paid_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'partner_id']);
            $table->index(['tenant_id', 'commission_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_referrals');
    }
};
