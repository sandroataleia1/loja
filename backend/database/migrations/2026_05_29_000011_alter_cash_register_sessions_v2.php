<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_register_sessions', function (Blueprint $table): void {
            // Registro físico (nullable para não quebrar sessões existentes)
            $table->foreignUuid('cash_register_id')
                ->nullable()
                ->after('store_id')
                ->constrained('cash_registers', 'uuid')
                ->nullOnDelete();

            // Quem fechou (pode ser diferente de quem abriu em turnos com handoff)
            $table->foreignUuid('closed_by')
                ->nullable()
                ->after('user_id')
                ->constrained('users', 'uuid')
                ->nullOnDelete();

            // Snapshot financeiro calculado no fechamento
            $table->integer('expected_balance_cents')->nullable()->after('closing_amount_cents');
            $table->integer('difference_amount_cents')->nullable()->after('expected_balance_cents');
            $table->text('difference_reason')->nullable()->after('difference_amount_cents');

            $table->index(['tenant_id', 'cash_register_id', 'status'],
                'cash_register_sessions_register_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('cash_register_sessions', function (Blueprint $table): void {
            $table->dropIndex('cash_register_sessions_register_status_idx');
            $table->dropForeign(['cash_register_id']);
            $table->dropForeign(['closed_by']);
            $table->dropColumn([
                'cash_register_id',
                'closed_by',
                'expected_balance_cents',
                'difference_amount_cents',
                'difference_reason',
            ]);
        });
    }
};
