<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_tags', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('tenant_id');
            $table->string('name', 100);
            $table->string('color', 7)->nullable(); // hex #RRGGBB
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')
                ->references('uuid')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('customer_tag_assignments', function (Blueprint $table): void {
            $table->uuid('customer_id');
            $table->uuid('tag_id');

            $table->primary(['customer_id', 'tag_id']);

            $table->foreign('customer_id')
                ->references('uuid')
                ->on('customers')
                ->cascadeOnDelete();

            $table->foreign('tag_id')
                ->references('uuid')
                ->on('customer_tags')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_tag_assignments');
        Schema::dropIfExists('customer_tags');
    }
};
