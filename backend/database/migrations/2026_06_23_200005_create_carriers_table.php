<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carriers', function (Blueprint $table): void {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->string('code', 20)->nullable();
            $table->string('name', 200);
            $table->string('trade_name', 200)->nullable();
            $table->string('cnpj', 20)->nullable();
            $table->string('ie', 30)->nullable();
            $table->string('email', 254)->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active'], 'carriers_tenant_active_idx');
        });

        DB::statement(
            'CREATE UNIQUE INDEX carriers_tenant_code_unique '
            . 'ON carriers (tenant_id, code) '
            . 'WHERE deleted_at IS NULL AND code IS NOT NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('carriers');
    }
};
