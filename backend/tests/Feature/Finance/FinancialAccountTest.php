<?php

declare(strict_types=1);

use App\Modules\Finance\Models\FinancialAccount;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

describe('POST /finance/accounts — criar conta financeira', function (): void {
    it('manager cria conta com sucesso', function (): void {
        $this->postJson('/api/v1/finance/accounts', [
            'code'                  => 'CAIXA-01',
            'name'                  => 'Caixa Principal',
            'type'                  => 'cash',
            'opening_balance_cents' => 50000,
        ])->assertStatus(201)
            ->assertJsonPath('data.code', 'CAIXA-01')
            ->assertJsonPath('data.name', 'Caixa Principal')
            ->assertJsonPath('data.type', 'cash')
            ->assertJsonPath('data.current_balance_cents', 50000);
    });

    it('operador não pode criar conta', function (): void {
        $this->restrictUserToPermissions(); // sem financial.create

        $this->postJson('/api/v1/finance/accounts', [
            'code' => 'ACC-01',
            'name' => 'Conta',
            'type' => 'cash',
        ])->assertStatus(403);
    });

    it('rejeita código duplicado no mesmo tenant', function (): void {
        FinancialAccount::factory()->create(['code' => 'ACC-DUP']);

        $this->postJson('/api/v1/finance/accounts', [
            'code' => 'ACC-DUP',
            'name' => 'Outra Conta',
            'type' => 'bank',
        ])->assertStatus(409);
    });

    it('rejeita tipo inválido', function (): void {
        $this->postJson('/api/v1/finance/accounts', [
            'code' => 'ACC-01',
            'name' => 'Conta',
            'type' => 'invalid_type',
        ])->assertStatus(422);
    });
});

describe('GET /finance/accounts — listar contas', function (): void {
    it('lista contas do tenant', function (): void {
        FinancialAccount::factory()->count(3)->create();

        $this->getJson('/api/v1/finance/accounts')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('filtra por type', function (): void {
        FinancialAccount::factory()->create(['type' => 'cash']);
        FinancialAccount::factory()->bank()->create();

        $this->getJson('/api/v1/finance/accounts?type=cash')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('filtra por active_only', function (): void {
        FinancialAccount::factory()->create(['is_active' => true]);
        FinancialAccount::factory()->inactive()->create();

        $this->getJson('/api/v1/finance/accounts?active_only=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('não retorna contas de outro tenant', function (): void {
        FinancialAccount::factory()->count(2)->create();

        $this->actingAsTenantUser(); // novo tenant
        FinancialAccount::factory()->count(1)->create();

        $this->getJson('/api/v1/finance/accounts')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('PUT /finance/accounts/{account} — atualizar conta', function (): void {
    it('atualiza nome e tipo', function (): void {
        $account = FinancialAccount::factory()->create(['name' => 'Antiga']);

        $this->putJson("/api/v1/finance/accounts/{$account->uuid}", [
            'code' => $account->code,
            'name' => 'Nova',
            'type' => 'bank',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Nova')
            ->assertJsonPath('data.type', 'bank');
    });
});

describe('DELETE /finance/accounts/{account} — excluir conta', function (): void {
    it('manager exclui conta', function (): void {
        $account = FinancialAccount::factory()->create();

        $this->deleteJson("/api/v1/finance/accounts/{$account->uuid}")
            ->assertStatus(204);

        $this->assertSoftDeleted('financial_accounts', ['uuid' => $account->uuid]);
    });
});

describe('Transferência entre contas', function (): void {
    it('transfere valor entre duas contas e atualiza saldos', function (): void {
        $origin      = FinancialAccount::factory()->withBalance(100000)->create();
        $destination = FinancialAccount::factory()->withBalance(0)->create();

        $this->postJson('/api/v1/finance/transfers', [
            'origin_account_id'      => $origin->uuid,
            'destination_account_id' => $destination->uuid,
            'amount_cents'           => 30000,
            'description'            => 'Aporte caixa',
        ])->assertStatus(201)
            ->assertJsonPath('data.amount_cents', 30000);

        expect($origin->fresh()->current_balance_cents)->toBe(70000)
            ->and($destination->fresh()->current_balance_cents)->toBe(30000);
    });

    it('rejeita transferência para mesma conta', function (): void {
        $account = FinancialAccount::factory()->withBalance(50000)->create();

        $this->postJson('/api/v1/finance/transfers', [
            'origin_account_id'      => $account->uuid,
            'destination_account_id' => $account->uuid,
            'amount_cents'           => 1000,
        ])->assertStatus(422); // different rule
    });

    it('lista transferências do tenant', function (): void {
        $origin      = FinancialAccount::factory()->withBalance(100000)->create();
        $destination = FinancialAccount::factory()->create();

        $this->postJson('/api/v1/finance/transfers', [
            'origin_account_id'      => $origin->uuid,
            'destination_account_id' => $destination->uuid,
            'amount_cents'           => 5000,
        ])->assertStatus(201);

        $this->getJson('/api/v1/finance/transfers')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });
});
