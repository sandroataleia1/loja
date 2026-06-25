<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('seller_profiles', 'nickname')) {
                $table->string('nickname', 50)->nullable()->after('code');
            }
            if (! Schema::hasColumn('seller_profiles', 'seller_type')) {
                $table->string('seller_type', 20)->default('internal')->after('nickname');
                $table->index(['tenant_id', 'seller_type']);
            }
            if (! Schema::hasColumn('seller_profiles', 'monthly_target')) {
                $table->decimal('monthly_target', 12, 2)->nullable()->after('commission_rate');
            }
            if (! Schema::hasColumn('seller_profiles', 'supervisor_id')) {
                $table->uuid('supervisor_id')->nullable()->after('monthly_target');
                $table->foreign('supervisor_id')
                    ->references('uuid')->on('seller_profiles')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('seller_profiles', 'region')) {
                $table->string('region', 100)->nullable()->after('supervisor_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table): void {
            $table->dropForeign(['supervisor_id']);
            $table->dropIndex(['tenant_id', 'seller_type']);
            $table->dropColumn(['nickname', 'seller_type', 'monthly_target', 'supervisor_id', 'region']);
        });
    }
};
