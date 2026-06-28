<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Renomeia days_after_due → trigger_days e atualiza enum de action
        Schema::table('collection_rules', function (Blueprint $table): void {
            $table->renameColumn('days_after_due', 'trigger_days');
            $table->renameColumn('action', 'action_type');
        });

        // Log de ações executadas pelas regras de cobrança
        Schema::create('collection_actions_log', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('collection_rule_id')
                ->constrained('collection_rules', 'uuid')
                ->cascadeOnDelete();
            // Parcela afetada (installment pode ser de qualquer tabela)
            $table->uuid('installment_id')->nullable();
            $table->uuid('customer_id')->nullable();
            $table->string('action_type', 30);
            $table->string('status', 20)->default('executed'); // executed, failed, skipped
            $table->text('notes')->nullable();
            $table->timestamp('executed_at');
            $table->timestamps();

            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'executed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_actions_log');

        Schema::table('collection_rules', function (Blueprint $table): void {
            $table->renameColumn('trigger_days', 'days_after_due');
            $table->renameColumn('action_type', 'action');
        });
    }
};
