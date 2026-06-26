<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('customer_commercial_references')) return;
        Schema::create('customer_commercial_references', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 36)->index();
            $table->string('customer_id', 36)->index();
            $table->string('company_name', 200);
            $table->string('contact_person', 200)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();
            $table->foreign('customer_id')->references('uuid')->on('customers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_commercial_references');
    }
};
