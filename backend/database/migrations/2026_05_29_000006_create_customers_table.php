<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id')->index();
            $table->string('code', 20)->nullable();
            $table->string('name', 200);
            // CPF nullable — consumidor final não tem CPF
            $table->string('cpf', 14)->nullable();
            $table->string('cnpj', 18)->nullable();
            $table->string('email', 254)->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_default_consumer')->default(false);
            $table->boolean('is_active')->default(true);
            $table->jsonb('address')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')
                ->references('uuid')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->unique(['tenant_id', 'code']);
            $table->unique(['tenant_id', 'cpf']);
        });

        // Índice único parcial: apenas UM consumidor padrão por tenant
        DB::statement(
            'CREATE UNIQUE INDEX customers_one_default_per_tenant '
            . 'ON customers (tenant_id) '
            . 'WHERE is_default_consumer = TRUE AND deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
