<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table): void {
            if (! Schema::hasColumn('suppliers', 'performance_score')) {
                $table->decimal('performance_score', 3, 1)->nullable()->after('notes');
            }
            if (! Schema::hasColumn('suppliers', 'avg_delivery_days')) {
                $table->decimal('avg_delivery_days', 4, 1)->nullable()->after('performance_score');
            }
            if (! Schema::hasColumn('suppliers', 'return_rate')) {
                $table->decimal('return_rate', 5, 2)->nullable()->after('avg_delivery_days');
            }
            if (! Schema::hasColumn('suppliers', 'supply_category')) {
                $table->string('supply_category', 50)->nullable()->after('return_rate');
                $table->index(['tenant_id', 'supply_category']);
            }
            if (! Schema::hasColumn('suppliers', 'default_payment_term_days')) {
                $table->unsignedInteger('default_payment_term_days')->nullable()->after('supply_category');
            }
            if (! Schema::hasColumn('suppliers', 'bank_name')) {
                $table->string('bank_name', 80)->nullable()->after('default_payment_term_days');
            }
            if (! Schema::hasColumn('suppliers', 'bank_agency')) {
                $table->string('bank_agency', 20)->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('suppliers', 'bank_account')) {
                $table->string('bank_account', 30)->nullable()->after('bank_agency');
            }
            if (! Schema::hasColumn('suppliers', 'bank_account_type')) {
                $table->string('bank_account_type', 10)->nullable()->after('bank_account');
            }
            if (! Schema::hasColumn('suppliers', 'bank_pix_key')) {
                $table->string('bank_pix_key', 150)->nullable()->after('bank_account_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'supply_category']);
            $table->dropColumn([
                'performance_score', 'avg_delivery_days', 'return_rate',
                'supply_category', 'default_payment_term_days',
                'bank_name', 'bank_agency', 'bank_account',
                'bank_account_type', 'bank_pix_key',
            ]);
        });
    }
};
