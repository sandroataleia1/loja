<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('customers', 'seller_id')) {
                $table->uuid('seller_id')->nullable()->after('tenant_id');
                $table->foreign('seller_id')
                    ->references('uuid')->on('seller_profiles')
                    ->nullOnDelete();
                $table->index(['tenant_id', 'seller_id']);
            }

            if (! Schema::hasColumn('customers', 'carrier_id')) {
                $table->uuid('carrier_id')->nullable()->after('seller_id');
                $table->foreign('carrier_id')
                    ->references('uuid')->on('carriers')
                    ->nullOnDelete();
                $table->index(['tenant_id', 'carrier_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropForeign(['seller_id']);
            $table->dropForeign(['carrier_id']);
            $table->dropIndex(['tenant_id', 'seller_id']);
            $table->dropIndex(['tenant_id', 'carrier_id']);
            $table->dropColumn(['seller_id', 'carrier_id']);
        });
    }
};
