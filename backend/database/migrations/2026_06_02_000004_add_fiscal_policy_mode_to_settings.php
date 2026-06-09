<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_fiscal_settings', function (Blueprint $table): void {
            $table->string('policy_mode', 30)->default('ask')->after('default_fiscal_mode');
            $table->unsignedInteger('min_sale_amount_cents')->default(0)->after('policy_mode');
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->jsonb('policy')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn('policy');
        });

        Schema::table('tenant_fiscal_settings', function (Blueprint $table): void {
            $table->dropColumn(['policy_mode', 'min_sale_amount_cents']);
        });
    }
};
