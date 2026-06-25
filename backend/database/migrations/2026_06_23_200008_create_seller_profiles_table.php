<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perfil de vendedor — extensão 1:1 de users para usuários que atuam como vendedores.
 * Mantém seller_id → users.uuid nos orçamentos/pedidos existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_profiles', function (Blueprint $table): void {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users', 'uuid')->cascadeOnDelete();
            $table->string('code', 20)->nullable();
            $table->decimal('commission_rate', 5, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'user_id'], 'seller_profiles_tenant_user_unique');
            $table->index(['tenant_id', 'is_active'], 'seller_profiles_tenant_active_idx');
        });

        // Código único por tenant quando preenchido
        DB::statement(
            'CREATE UNIQUE INDEX seller_profiles_tenant_code_unique '
            . 'ON seller_profiles (tenant_id, code) '
            . 'WHERE deleted_at IS NULL AND code IS NOT NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_profiles');
    }
};
