<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table): void {
            // Denormalização intencional para offline-first: PDV precisa vincular
            // pagamento à sessão sem depender de carregar a venda completa no sync.
            $table->foreignUuid('cash_register_session_id')
                ->nullable()
                ->after('sale_id')
                ->constrained('cash_register_sessions', 'uuid')
                ->nullOnDelete();

            $table->index(['tenant_id', 'cash_register_session_id', 'method'],
                'payment_transactions_session_method_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->dropIndex('payment_transactions_session_method_idx');
            $table->dropForeign(['cash_register_session_id']);
            $table->dropColumn('cash_register_session_id');
        });
    }
};
