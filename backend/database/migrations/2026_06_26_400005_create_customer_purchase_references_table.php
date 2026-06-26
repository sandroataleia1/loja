<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('customer_purchase_references')) {
            return;
        }

        Schema::create('customer_purchase_references', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('customer_id')->index();
            $table->string('person_type', 20); // CUSTOMER, SPOUSE, GUARANTOR
            $table->string('company_name', 200);
            $table->string('phone', 20)->nullable();
            $table->decimal('monthly_limit', 12, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('customer_id')->references('uuid')->on('customers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_purchase_references');
    }
};
