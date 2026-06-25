<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();

            // Tipo de operação que requer aprovação (discount, cancellation, reversal, large_refund)
            $table->string('operation_type', 50);

            // Entidade associada (ex.: quote, order, sale)
            $table->string('entity_type', 50)->nullable();
            $table->uuid('entity_uuid')->nullable();

            // Solicitante
            $table->foreignUuid('requested_by')->constrained('users', 'uuid');
            $table->timestamp('requested_at')->useCurrent();

            // Metadados da operação (ex.: desconto solicitado, valor original)
            $table->json('context')->nullable();

            // Status: pending, approved, rejected, expired
            $table->string('status', 20)->default('pending');

            // Aprovador (preenchido quando aprovado ou rejeitado via PIN)
            $table->foreignUuid('resolved_by')->nullable()->constrained('users', 'uuid')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'entity_type', 'entity_uuid']);
            $table->index(['tenant_id', 'requested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
