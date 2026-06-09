<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('customer_id');
            $table->string('zipcode', 10);
            $table->string('street', 200);
            $table->string('number', 20);
            $table->string('complement', 100)->nullable();
            $table->string('district', 100);
            $table->string('city', 100);
            $table->char('state', 2);
            $table->char('country', 2)->default('BR');
            $table->boolean('is_default')->default(false);
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
        Schema::dropIfExists('customer_addresses');
    }
};
