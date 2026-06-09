<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table): void {
            $table->uuid()->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores', 'uuid')->restrictOnDelete();
            $table->foreignUuid('cash_register_session_id')
                ->constrained('cash_register_sessions', 'uuid')
                ->restrictOnDelete();
            $table->string('type', 30);         // CashMovementTypeEnum
            // Positivo = entrada (supply, sale, opening)
            // Negativo = saída  (withdrawal, refund)
            $table->integer('amount_cents');
            $table->text('description')->nullable();
            $table->foreignUuid('created_by')
                ->constrained('users', 'uuid')
                ->restrictOnDelete();
            // Sem updated_at — movimentações são imutáveis
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'cash_register_session_id', 'type'],
                'cash_movements_session_type_idx');
            $table->index(['tenant_id', 'store_id', 'created_at'],
                'cash_movements_store_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
