<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignUuid('payment_method_id')
                  ->nullable()
                  ->after('customer_id')
                  ->constrained('payment_methods', 'uuid')
                  ->nullOnDelete();

            $table->foreignUuid('payment_condition_id')
                  ->nullable()
                  ->after('payment_method_id')
                  ->constrained('payment_conditions', 'uuid')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['payment_method_id']);
            $table->dropForeign(['payment_condition_id']);
            $table->dropColumn(['payment_method_id', 'payment_condition_id']);
        });
    }
};
