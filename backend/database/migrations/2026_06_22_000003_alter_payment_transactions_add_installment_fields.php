<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->foreignUuid('payment_method_id')
                  ->nullable()
                  ->after('sale_id')
                  ->constrained('payment_methods', 'uuid')
                  ->nullOnDelete();

            $table->foreignUuid('payment_condition_id')
                  ->nullable()
                  ->after('payment_method_id')
                  ->constrained('payment_conditions', 'uuid')
                  ->nullOnDelete();

            $table->unsignedInteger('discount_cents')->default(0)->after('amount_cents');
            $table->unsignedInteger('interest_cents')->default(0)->after('discount_cents');
            $table->unsignedInteger('fine_cents')->default(0)->after('interest_cents');
            $table->unsignedSmallInteger('installment_number')->default(1)->after('fine_cents');
            $table->unsignedSmallInteger('total_installments')->default(1)->after('installment_number');
            $table->date('due_date')->nullable()->after('total_installments');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->dropForeign(['payment_method_id']);
            $table->dropForeign(['payment_condition_id']);
            $table->dropColumn([
                'payment_method_id',
                'payment_condition_id',
                'discount_cents',
                'interest_cents',
                'fine_cents',
                'installment_number',
                'total_installments',
                'due_date',
            ]);
        });
    }
};
