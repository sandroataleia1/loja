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
            if (! Schema::hasColumn('suppliers', 'bank_code')) {
                $table->string('bank_code', 10)->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('suppliers', 'bank_account_holder')) {
                $table->string('bank_account_holder', 150)->nullable()->after('bank_code');
            }
            if (! Schema::hasColumn('suppliers', 'bank_pix_type')) {
                // cpf|cnpj|phone|email|random
                $table->string('bank_pix_type', 10)->nullable()->after('bank_pix_key');
            }
            if (! Schema::hasColumn('suppliers', 'total_purchased_cents')) {
                $table->unsignedBigInteger('total_purchased_cents')->default(0)->after('return_rate');
            }
            if (! Schema::hasColumn('suppliers', 'last_purchase_at')) {
                $table->timestamp('last_purchase_at')->nullable()->after('total_purchased_cents');
            }
            if (! Schema::hasColumn('suppliers', 'purchase_count')) {
                $table->unsignedInteger('purchase_count')->default(0)->after('last_purchase_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table): void {
            foreach (['bank_code', 'bank_account_holder', 'bank_pix_type', 'total_purchased_cents', 'last_purchase_at', 'purchase_count'] as $col) {
                if (Schema::hasColumn('suppliers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
