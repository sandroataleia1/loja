<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fundação de sincronização offline-first.
 *
 * Design principles:
 * - sync_operations é append-only (nunca UPDATE destrutivo)
 * - idempotency_key garante replay safety
 * - tombstones para deleções (operation_type = 'delete')
 * - sync_pull_checkpoints rastreia estado por device × entity_type
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
        |----------------------------------------------------------------------
        | sync_devices — dispositivos PDV registrados
        |
        | Um device pertence a uma loja específica.
        | device_uuid é gerado pelo cliente (app PDV).
        | last_seen_at atualizado a cada sincronização.
        |----------------------------------------------------------------------
        */
        Schema::create('sync_devices', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores', 'uuid')->cascadeOnDelete();
            $table->uuid('device_uuid');  // UUID gerado pelo app PDV
            $table->string('name', 100);  // ex: "Caixa 01 - Loja Centro"
            $table->string('platform', 30)->nullable(); // windows|android|ios
            $table->string('app_version', 20)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'store_id', 'device_uuid']);
            $table->index(['tenant_id', 'store_id', 'is_active']);
            $table->index(['tenant_id', 'last_seen_at']);
        });

        /*
        |----------------------------------------------------------------------
        | sync_operations — log imutável de operações do PDV
        |
        | Cada operação enviada pelo PDV é registrada aqui.
        | Nunca sofre UPDATE destrutivo — apenas status transitions.
        | idempotency_key (por tenant) garante que replay é seguro.
        |----------------------------------------------------------------------
        */
        Schema::create('sync_operations', function (Blueprint $table): void {
            $table->uuid('operation_uuid')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('device_id')->constrained('sync_devices', 'uuid')->cascadeOnDelete();

            $table->string('entity_type', 40);   // SyncEntityTypeEnum
            $table->uuid('entity_uuid');          // UUID da entidade no PDV
            $table->string('operation_type', 10); // SyncOperationTypeEnum: create|update|delete
            $table->string('batch_id', 36);       // agrupa operações de um lote

            $table->jsonb('payload');             // dados da operação (imutável após criação)
            $table->string('status', 20);         // SyncOperationStatusEnum
            $table->string('idempotency_key', 100); // UUID único por tenant para replay safety

            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->text('last_error')->nullable();

            $table->timestamp('created_at');       // timestamp do dispositivo
            $table->timestamp('received_at');      // timestamp do servidor (quando recebeu)
            $table->timestamp('processed_at')->nullable();

            $table->unique(['tenant_id', 'idempotency_key']);
            $table->index(['tenant_id', 'device_id', 'status']);
            $table->index(['tenant_id', 'entity_type', 'entity_uuid']);
            $table->index(['tenant_id', 'batch_id']);
            $table->index(['tenant_id', 'status', 'received_at']);
        });

        /*
        |----------------------------------------------------------------------
        | sync_logs — log de batches para auditoria e diagnóstico
        |
        | Um registro por batch (conjunto de operações).
        | Armazena request/response completo para suporte e replay.
        |----------------------------------------------------------------------
        */
        Schema::create('sync_logs', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('device_id')->constrained('sync_devices', 'uuid')->cascadeOnDelete();
            $table->string('batch_id', 36);

            $table->string('direction', 4); // push|pull
            $table->integer('operation_count')->default(0);
            $table->integer('synced_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->integer('conflict_count')->default(0);
            $table->integer('duration_ms')->nullable();

            $table->jsonb('request_summary')->nullable();  // entity_types, counts
            $table->jsonb('response_summary')->nullable(); // results map
            $table->text('ip_address')->nullable();

            $table->timestamp('created_at');
            $table->timestamp('completed_at')->nullable();

            $table->index(['tenant_id', 'device_id', 'created_at']);
            $table->index(['tenant_id', 'batch_id']);
            $table->index(['tenant_id', 'direction', 'created_at']);
        });

        /*
        |----------------------------------------------------------------------
        | sync_pull_checkpoints — rastreia estado de pull por device × entity
        |
        | Cada device mantém um checkpoint por tipo de entidade.
        | O PDV envia o last_sync_at ao pull; backend retorna apenas
        | registros modificados após esse timestamp.
        |----------------------------------------------------------------------
        */
        Schema::create('sync_pull_checkpoints', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('device_id')->constrained('sync_devices', 'uuid')->cascadeOnDelete();
            $table->string('entity_type', 40);
            $table->timestamp('last_synced_at');
            $table->timestamp('updated_at');

            $table->unique(['device_id', 'entity_type']);
            $table->index(['tenant_id', 'device_id', 'entity_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_pull_checkpoints');
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('sync_operations');
        Schema::dropIfExists('sync_devices');
    }
};
