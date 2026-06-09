<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit log de operações de negócio — imutável, append-only.
 *
 * Separado dos logs técnicos (Auditable trait → arquivo de log).
 * Registra AÇÕES de negócio de alto valor: cancelamentos, descontos,
 * ajustes de estoque, emissões fiscais, mudanças de permissão, autenticação.
 *
 * Sem FK constraints para preservar histórico mesmo após exclusão de entidades.
 * Sem updated_at: registros nunca são modificados.
 *
 * Retenção: 1 ano (DataRetentionPolicyEnum::AuditLogs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();

            // Context — sem FK constraints (audit logs são permanentes)
            $table->uuid('tenant_id')->nullable()->index();
            $table->uuid('store_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->uuid('device_id')->nullable();   // PDV device
            $table->string('correlation_id', 64)->nullable();

            // Entity
            $table->string('entity_type', 100);
            $table->uuid('entity_uuid');
            $table->string('action', 100);

            // Payload
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->jsonb('metadata')->nullable();    // reason, approver_id, etc.

            // Request context
            $table->string('ip', 45)->nullable();     // IPv4 e IPv6
            $table->string('user_agent', 512)->nullable();

            // Immutable — apenas created_at
            $table->timestamp('created_at')->useCurrent();

            // ── Indexes para queries de negócio ─────────────────────────────
            // Timeline de uma entidade: audit_logs.forEntity(type, uuid)
            $table->index(['tenant_id', 'entity_type', 'entity_uuid'], 'audit_entity_idx');

            // Atividade de um usuário: quem fez o quê
            $table->index(['tenant_id', 'user_id', 'created_at'], 'audit_user_activity_idx');

            // Ações de alto risco por tipo: cancelamentos, estornos
            $table->index(['tenant_id', 'action', 'created_at'], 'audit_action_idx');

            // Rastreabilidade por correlation_id (debugging distribuído)
            $table->index('correlation_id', 'audit_correlation_idx');

            // Origem offline (PDV)
            $table->index('device_id', 'audit_device_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
