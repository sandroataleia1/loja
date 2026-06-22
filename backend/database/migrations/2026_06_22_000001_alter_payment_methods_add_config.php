<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->boolean('accepts_change')->default(false)->after('sort_order');
            $table->boolean('allow_installments')->default(false)->after('accepts_change');
            $table->unsignedSmallInteger('max_installments')->default(1)->after('allow_installments');
            $table->unsignedInteger('min_installment_value_cents')->default(0)->after('max_installments');
            $table->boolean('requires_authorization')->default(false)->after('min_installment_value_cents');
            $table->boolean('integrates_financial')->default(true)->after('requires_authorization');
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->dropColumn([
                'accepts_change',
                'allow_installments',
                'max_installments',
                'min_installment_value_cents',
                'requires_authorization',
                'integrates_financial',
            ]);
        });
    }
};
