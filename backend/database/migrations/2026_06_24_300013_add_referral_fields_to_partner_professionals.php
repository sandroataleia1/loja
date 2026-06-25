<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_professionals', function (Blueprint $table): void {
            if (! Schema::hasColumn('partner_professionals', 'referral_commission_percent')) {
                $table->decimal('referral_commission_percent', 5, 2)->default(0.00)->after('is_active');
            }
            if (! Schema::hasColumn('partner_professionals', 'person_type')) {
                $table->string('person_type', 2)->default('PF')->after('type');
            }
            if (! Schema::hasColumn('partner_professionals', 'company_name')) {
                $table->string('company_name', 150)->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('partner_professionals', function (Blueprint $table): void {
            $table->dropColumn(['referral_commission_percent', 'person_type', 'company_name']);
        });
    }
};
