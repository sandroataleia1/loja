<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Domain Event Log — event sourcing light, append-only.
 *
 * Persiste eventos de domínio importantes para:
 * - Replay futuro e projeções
 * - Analytics e dashboards
 * - Treinamento de modelos de IA
 * - Integrações via webhook futuro
 * - Troubleshooting distribuído
 *
 * Sem FK constraints. Sem updated_at.
 * occurred_at = quando aconteceu no domínio (PDV pode ter lag).
 * created_at  = quando foi persistido nesta tabela.
 *
 * Retenção: 2 anos (DataRetentionPolicyEnum::DomainEvents).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_event_logs', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();

            // Sem FK (mesmo para tenant_id — eventos de plataforma são globais)
            $table->uuid('tenant_id')->nullable();
            $table->string('correlation_id', 64)->nullable();

            // Evento
            $table->string('event_name', 150);        // ex: SaleCompleted, StockAdjusted
            $table->jsonb('payload');                 // payload completo do evento

            // Temporal
            $table->timestamp('occurred_at');         // quando aconteceu no domínio
            $table->timestamp('created_at')->useCurrent(); // quando foi gravado

            // ── Indexes ──────────────────────────────────────────────────────
            // Queries por tipo de evento + tempo (analytics, replay)
            $table->index(['event_name', 'occurred_at'],   'domain_event_type_time_idx');

            // Timeline de um tenant
            $table->index(['tenant_id', 'occurred_at'],    'domain_event_tenant_idx');

            // Rastreabilidade
            $table->index('correlation_id',                'domain_event_correlation_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_event_logs');
    }
};
