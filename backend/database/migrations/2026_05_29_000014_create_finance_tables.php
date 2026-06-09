<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Financial Accounts ────────────────────────────────────────────────
        // Contas financeiras: caixa físico, banco, carteira digital.
        // Separadas do caixa PDV (cash_registers) — são contas contábeis.
        Schema::create('financial_accounts', function (Blueprint $table): void {
            $table->uuid()->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            // Null = conta do tenant inteiro; preenchido = conta da loja específica
            $table->foreignUuid('store_id')->nullable()->constrained('stores', 'uuid')->nullOnDelete();
            $table->string('code', 30);
            $table->string('name', 100);
            $table->string('type', 30);           // FinancialAccountTypeEnum
            $table->boolean('is_active')->default(true);
            // Saldo corrente mantido atomicamente via lockForUpdate (não recompute a cada query)
            $table->integer('current_balance_cents')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'financial_accounts_tenant_code_unique');
            $table->index(['tenant_id', 'type', 'is_active']);
        });

        // ── Financial Categories ──────────────────────────────────────────────
        Schema::create('financial_categories', function (Blueprint $table): void {
            $table->uuid()->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('type', 20);           // INCOME | EXPENSE
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name', 'type'], 'financial_categories_tenant_name_type_unique');
        });

        // ── Financial Entries ─────────────────────────────────────────────────
        // Lançamentos financeiros: contas a pagar e a receber.
        // Nunca deletados — cancelamentos geram registro CANCELLED.
        Schema::create('financial_entries', function (Blueprint $table): void {
            $table->uuid()->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('store_id')->nullable()->constrained('stores', 'uuid')->nullOnDelete();
            $table->string('type', 20);           // INCOME | EXPENSE
            $table->foreignUuid('category_id')
                ->nullable()
                ->constrained('financial_categories', 'uuid')
                ->nullOnDelete();
            // Null = não vinculado a conta ainda (a reconciliar)
            $table->foreignUuid('financial_account_id')
                ->nullable()
                ->constrained('financial_accounts', 'uuid')
                ->nullOnDelete();
            $table->integer('amount_cents');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->string('status', 30)->default('pending'); // FinancialEntryStatusEnum
            $table->text('description')->nullable();
            // Polimorfismo simples: 'sale', 'purchase_order', 'commission', etc.
            $table->string('reference_type', 50)->nullable();
            $table->uuid('reference_id')->nullable();
            $table->string('reconciliation_status', 30)->nullable(); // ReconciliationStatusEnum
            $table->string('external_reference', 255)->nullable();
            $table->timestamps();
            // Sem softDeletes — cancelamentos ficam com status=CANCELLED

            $table->index(['tenant_id', 'status', 'due_date'], 'financial_entries_status_due_idx');
            $table->index(['tenant_id', 'type', 'status'],     'financial_entries_type_status_idx');
            $table->index(['tenant_id', 'reference_type', 'reference_id'], 'financial_entries_ref_idx');
            $table->index(['tenant_id', 'financial_account_id', 'status'], 'financial_entries_account_idx');
        });

        // ── Financial Installments ────────────────────────────────────────────
        Schema::create('financial_installments', function (Blueprint $table): void {
            $table->uuid()->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('financial_entry_id')
                ->constrained('financial_entries', 'uuid')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('installment_number');
            $table->date('due_date');
            $table->integer('amount_cents');
            $table->string('status', 30)->default('pending'); // FinancialInstallmentStatusEnum
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['financial_entry_id', 'installment_number'],
                'financial_installments_entry_number_unique');
        });

        // ── Financial Transfers ───────────────────────────────────────────────
        Schema::create('financial_transfers', function (Blueprint $table): void {
            $table->uuid()->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('origin_account_id')
                ->constrained('financial_accounts', 'uuid')
                ->restrictOnDelete();
            $table->foreignUuid('destination_account_id')
                ->constrained('financial_accounts', 'uuid')
                ->restrictOnDelete();
            $table->integer('amount_cents');
            $table->timestamp('transferred_at');
            $table->text('description')->nullable();
            $table->foreignUuid('created_by')
                ->constrained('users', 'uuid')
                ->restrictOnDelete();
            $table->timestamps();
            // Sem softDeletes: transferências são imutáveis

            $table->index(['tenant_id', 'origin_account_id']);
            $table->index(['tenant_id', 'destination_account_id']);
            $table->index(['tenant_id', 'transferred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transfers');
        Schema::dropIfExists('financial_installments');
        Schema::dropIfExists('financial_entries');
        Schema::dropIfExists('financial_categories');
        Schema::dropIfExists('financial_accounts');
    }
};
