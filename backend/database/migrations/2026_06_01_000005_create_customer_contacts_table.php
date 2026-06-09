<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_contacts', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('customer_id');
            $table->string('type', 20); // PHONE|WHATSAPP|EMAIL|INSTAGRAM|OTHER
            $table->string('value', 200);
            $table->string('label', 100)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->foreign('customer_id')
                ->references('uuid')
                ->on('customers')
                ->cascadeOnDelete();

            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_contacts');
    }
};
