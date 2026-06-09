<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_sequences', function (Blueprint $table): void {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id')->index();
            $table->string('entity', 50);
            // sub_entity_id: escopo secundário (ex.: product_uuid para variantes)
            // empty string '' como sentinel de "sem sub-entidade" para UNIQUE index funcionar
            // (NULL != NULL no PostgreSQL, impossibilitando o índice único)
            $table->string('sub_entity_id', 36)->default('');
            $table->unsignedBigInteger('current_value')->default(0);

            $table->timestamps();

            $table->unique(['tenant_id', 'entity', 'sub_entity_id'], 'tenant_sequences_unique');

            $table->foreign('tenant_id')
                ->references('uuid')
                ->on('tenants')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_sequences');
    }
};
