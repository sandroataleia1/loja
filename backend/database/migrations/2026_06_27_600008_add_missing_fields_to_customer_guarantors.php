<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_guarantors', function (Blueprint $table): void {
            if (! Schema::hasColumn('customer_guarantors', 'birth_date')) {
                $table->date('birth_date')->nullable();
            }
            if (! Schema::hasColumn('customer_guarantors', 'marital_status')) {
                $table->string('marital_status', 20)->nullable();
            }
            if (! Schema::hasColumn('customer_guarantors', 'years_at_address')) {
                $table->unsignedTinyInteger('years_at_address')->nullable();
            }
            if (! Schema::hasColumn('customer_guarantors', 'housing_type')) {
                $table->string('housing_type', 20)->nullable();
            }
            if (! Schema::hasColumn('customer_guarantors', 'other_income')) {
                $table->decimal('other_income', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('customer_guarantors', 'assets_description')) {
                $table->text('assets_description')->nullable();
            }
            if (! Schema::hasColumn('customer_guarantors', 'is_same_address_as_customer')) {
                $table->boolean('is_same_address_as_customer')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_guarantors', function (Blueprint $table): void {
            $table->dropColumn([
                'birth_date', 'marital_status', 'years_at_address',
                'housing_type', 'other_income', 'assets_description',
                'is_same_address_as_customer',
            ]);
        });
    }
};
