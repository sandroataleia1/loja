<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_referrals', function (Blueprint $table): void {
            // first_purchase|all_purchases
            if (! Schema::hasColumn('partner_referrals', 'commission_base')) {
                $table->string('commission_base', 20)->default('first_purchase')->after('commission_rate');
            }
            if (! Schema::hasColumn('partner_referrals', 'first_purchase_amount_cents')) {
                $table->unsignedBigInteger('first_purchase_amount_cents')->nullable()->after('commission_base');
            }
        });
    }

    public function down(): void
    {
        Schema::table('partner_referrals', function (Blueprint $table): void {
            foreach (['commission_base', 'first_purchase_amount_cents'] as $col) {
                if (Schema::hasColumn('partner_referrals', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
