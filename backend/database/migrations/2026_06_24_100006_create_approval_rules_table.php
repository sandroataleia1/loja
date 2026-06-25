<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela de regras de aprovação configuráveis por tenant.
 *
 * Cada registro define quando uma operação requer aprovação:
 *   always      — sempre requer aprovação
 *   amount      — requer se o valor >= threshold_value
 *   percentage  — requer se o desconto% >= threshold_value
 *
 * Uma regra por (tenant, operation_type). Se não existe regra, não requer aprovação.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_rules', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();

            // Tipo de operação (discount, cancellation, reversal, large_refund)
            $table->string('operation_type', 50);

            // Condição de disparo: always | amount | percentage
            $table->string('threshold_type', 20)->default('always');

            // Valor limite (null = always)
            $table->decimal('threshold_value', 10, 2)->nullable();

            // Role mínima exigida para aprovar (null = qualquer usuário com PIN)
            $table->foreignUuid('required_role_id')
                ->nullable()
                ->constrained('roles', 'uuid')
                ->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Um tipo de operação por tenant
            $table->unique(['tenant_id', 'operation_type'], 'approval_rules_tenant_op_unique');
            $table->index(['tenant_id', 'is_active'], 'approval_rules_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_rules');
    }
};
