<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_commercial_references', function (Blueprint $table): void {
            if (! Schema::hasColumn('customer_commercial_references', 'first_purchase_at')) {
                $table->date('first_purchase_at')->nullable();
            }
            if (! Schema::hasColumn('customer_commercial_references', 'first_purchase_value_cents')) {
                $table->unsignedBigInteger('first_purchase_value_cents')->nullable();
            }
            if (! Schema::hasColumn('customer_commercial_references', 'highest_purchase_value_cents')) {
                $table->unsignedBigInteger('highest_purchase_value_cents')->nullable();
            }
            if (! Schema::hasColumn('customer_commercial_references', 'last_purchase_at')) {
                $table->date('last_purchase_at')->nullable();
            }
            if (! Schema::hasColumn('customer_commercial_references', 'last_purchase_value_cents')) {
                $table->unsignedBigInteger('last_purchase_value_cents')->nullable();
            }
            if (! Schema::hasColumn('customer_commercial_references', 'consulted_at')) {
                $table->date('consulted_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_commercial_references', function (Blueprint $table): void {
            $table->dropColumn([
                'first_purchase_at', 'first_purchase_value_cents',
                'highest_purchase_value_cents', 'last_purchase_at',
                'last_purchase_value_cents', 'consulted_at',
            ]);
        });
    }
};
