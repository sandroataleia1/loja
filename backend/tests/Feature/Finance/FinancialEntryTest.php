<?php

declare(strict_types=1);

use App\Modules\Finance\Enums\FinancialEntryStatusEnum;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialCategory;
use App\Modules\Finance\Models\FinancialEntry;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

describe('POST /finance/entries — criar lançamento', function (): void {
    it('cria lançamento de receita sem parcelas', function (): void {
        $category = FinancialCategory::factory()->income()->create();

        $this->postJson('/api/v1/finance/entries', [
            'type'         => 'income',
            'category_id'  => $category->uuid,
            'amount_cents' => 50000,
            'due_date'     => now()->addDays(10)->toDateString(),
            'description'  => 'Pagamento cliente',
        ])->assertStatus(201)
            ->assertJsonPath('data.type', 'income')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.amount_cents', 50000);
    });

    it('cria lançamento com parcelas', function (): void {
        $this->postJson('/api/v1/finance/entries', [
            'type'         => 'expense',
            'amount_cents' => 30000,
            'due_date'     => now()->addDays(5)->toDateString(),
            'installments' => [
                ['due_date' => now()->addDays(5)->toDateString(),  'amount_cents' => 10000],
                ['due_date' => now()->addDays(35)->toDateString(), 'amount_cents' => 10000],
                ['due_date' => now()->addDays(65)->toDateString(), 'amount_cents' => 10000],
            ],
        ])->assertStatus(201)
            ->assertJsonCount(3, 'data.installments');
    });

    it('rejeita parcelas cuja soma difere do total', function (): void {
        $this->postJson('/api/v1/finance/entries', [
            'type'         => 'income',
            'amount_cents' => 30000,
            'due_date'     => now()->toDateString(),
            'installments' => [
                ['due_date' => now()->toDateString(), 'amount_cents' => 10000],
                ['due_date' => now()->toDateString(), 'amount_cents' => 5000], // soma = 15000 ≠ 30000
            ],
        ])->assertStatus(422);
    });
});

describe('POST /finance/entries/{entry}/pay — quitar lançamento', function (): void {
    it('quita lançamento inteiro e atualiza saldo da conta', function (): void {
        $account = FinancialAccount::factory()->withBalance(0)->create();
        $entry   = FinancialEntry::factory()->income()->create([
            'amount_cents'         => 20000,
            'status'               => FinancialEntryStatusEnum::Pending,
            'financial_account_id' => $account->uuid,
        ]);

        $this->postJson("/api/v1/finance/entries/{$entry->uuid}/pay", [
            'paid_at' => now()->toDateTimeString(),
        ])->assertOk()
            ->assertJsonPath('data.status', 'paid');

        expect($account->fresh()->current_balance_cents)->toBe(20000);
    });

    it('quita parcela específica — atualiza status para partially_paid', function (): void {
        $account = FinancialAccount::factory()->withBalance(0)->create();
        $entry   = FinancialEntry::factory()->income()->create([
            'amount_cents'         => 20000,
            'financial_account_id' => $account->uuid,
        ]);
        $entry->installments()->createMany([
            ['installment_number' => 1, 'due_date' => now(), 'amount_cents' => 10000, 'status' => 'pending'],
            ['installment_number' => 2, 'due_date' => now()->addMonth(), 'amount_cents' => 10000, 'status' => 'pending'],
        ]);

        $this->postJson("/api/v1/finance/entries/{$entry->uuid}/pay", [
            'installment_number' => 1,
            'paid_at'            => now()->toDateTimeString(),
        ])->assertOk()
            ->assertJsonPath('data.status', 'partially_paid');

        expect($account->fresh()->current_balance_cents)->toBe(10000);
    });

    it('quita todas as parcelas e atualiza status para paid', function (): void {
        $account = FinancialAccount::factory()->withBalance(0)->create();
        $entry   = FinancialEntry::factory()->income()->create([
            'amount_cents'         => 20000,
            'financial_account_id' => $account->uuid,
        ]);
        $entry->installments()->createMany([
            ['installment_number' => 1, 'due_date' => now(), 'amount_cents' => 10000, 'status' => 'pending'],
            ['installment_number' => 2, 'due_date' => now()->addMonth(), 'amount_cents' => 10000, 'status' => 'pending'],
        ]);

        $this->postJson("/api/v1/finance/entries/{$entry->uuid}/pay", [
            'installment_number' => 1,
        ])->assertOk();

        $this->postJson("/api/v1/finance/entries/{$entry->uuid}/pay", [
            'installment_number' => 2,
        ])->assertOk()
            ->assertJsonPath('data.status', 'paid');

        expect($account->fresh()->current_balance_cents)->toBe(20000);
    });

    it('rejeita pagamento de lançamento cancelado', function (): void {
        $entry = FinancialEntry::factory()->cancelled()->create();

        $this->postJson("/api/v1/finance/entries/{$entry->uuid}/pay")
            ->assertStatus(422);
    });
});

describe('POST /finance/entries/{entry}/cancel — cancelar lançamento', function (): void {
    it('cancela lançamento pendente sem reverter saldo', function (): void {
        $account = FinancialAccount::factory()->withBalance(0)->create();
        $entry   = FinancialEntry::factory()->create([
            'status'               => FinancialEntryStatusEnum::Pending,
            'financial_account_id' => $account->uuid,
        ]);

        $this->postJson("/api/v1/finance/entries/{$entry->uuid}/cancel", [
            'reason' => 'Venda cancelada',
        ])->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        expect($account->fresh()->current_balance_cents)->toBe(0);
    });

    it('cancela lançamento pago e reverte saldo da conta', function (): void {
        $account = FinancialAccount::factory()->withBalance(50000)->create();
        $entry   = FinancialEntry::factory()->income()->paid()->create([
            'amount_cents'         => 50000,
            'financial_account_id' => $account->uuid,
        ]);

        $this->postJson("/api/v1/finance/entries/{$entry->uuid}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        expect($account->fresh()->current_balance_cents)->toBe(0);
    });

    it('não cancela lançamento já cancelado', function (): void {
        $entry = FinancialEntry::factory()->cancelled()->create();

        $this->postJson("/api/v1/finance/entries/{$entry->uuid}/cancel")
            ->assertStatus(422);
    });
});

describe('GET /finance/entries — filtros', function (): void {
    it('filtra por status', function (): void {
        FinancialEntry::factory()->count(2)->create(['status' => FinancialEntryStatusEnum::Pending]);
        FinancialEntry::factory()->paid()->create();

        $this->getJson('/api/v1/finance/entries?status=pending')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('filtra por tipo', function (): void {
        FinancialEntry::factory()->income()->count(3)->create();
        FinancialEntry::factory()->expense()->count(2)->create();

        $this->getJson('/api/v1/finance/entries?type=expense')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('filtra por intervalo de vencimento', function (): void {
        FinancialEntry::factory()->create(['due_date' => now()->subDays(10)]);
        FinancialEntry::factory()->create(['due_date' => now()]);
        FinancialEntry::factory()->create(['due_date' => now()->addDays(10)]);

        $this->getJson('/api/v1/finance/entries?due_from=' . now()->subDays(1)->toDateString() . '&due_to=' . now()->addDays(1)->toDateString())
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });
});
