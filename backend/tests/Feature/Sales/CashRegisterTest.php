<?php

declare(strict_types=1);

use App\Modules\Inventory\Models\Store;
use App\Modules\Sales\Models\CashRegister;
use App\Modules\Sales\Models\CashRegisterSession;

beforeEach(function (): void {
    $this->actingAsTenantUser();
    $this->store = Store::factory()->create();
});

describe('POST /sales/registers — criar caixa físico', function (): void {
    it('manager cria caixa com sucesso', function (): void {
        $this->postJson('/api/v1/sales/registers', [
            'store_id'  => $this->store->uuid,
            'code'      => 'CAIXA-01',
            'name'      => 'Caixa Principal',
            'is_active' => true,
        ])->assertStatus(201)
            ->assertJsonPath('data.code', 'CAIXA-01')
            ->assertJsonPath('data.name', 'Caixa Principal');
    });

    it('operador não pode criar caixa', function (): void {
        $this->restrictUserToPermissions(); // sem settings.update

        $this->postJson('/api/v1/sales/registers', [
            'store_id' => $this->store->uuid,
            'code'     => 'CAIXA-01',
            'name'     => 'Caixa 1',
        ])->assertStatus(403);
    });

    it('rejeita código duplicado na mesma loja', function (): void {
        CashRegister::factory()->create(['store_id' => $this->store->uuid, 'code' => 'CAIXA-01']);

        $this->postJson('/api/v1/sales/registers', [
            'store_id' => $this->store->uuid,
            'code'     => 'CAIXA-01',
            'name'     => 'Caixa Duplicado',
        ])->assertStatus(409);
    });
});

describe('GET /sales/registers — listar caixas', function (): void {
    it('lista caixas com sessão aberta', function (): void {
        $register = CashRegister::factory()->create(['store_id' => $this->store->uuid]);
        CashRegisterSession::factory()->open()->create([
            'store_id'         => $this->store->uuid,
            'cash_register_id' => $register->uuid,
        ]);

        $this->getJson('/api/v1/sales/registers?store_id=' . $this->store->uuid)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $register->uuid);
    });

    it('filtra por active_only', function (): void {
        CashRegister::factory()->create(['store_id' => $this->store->uuid, 'is_active' => true]);
        CashRegister::factory()->create(['store_id' => $this->store->uuid, 'is_active' => false]);

        $this->getJson('/api/v1/sales/registers?store_id=' . $this->store->uuid . '&active_only=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('POST /sales/sessions/open — abrir sessão', function (): void {
    it('abre sessão com caixa físico', function (): void {
        $register = CashRegister::factory()->create(['store_id' => $this->store->uuid]);

        $this->postJson('/api/v1/sales/sessions/open', [
            'store_id'             => $this->store->uuid,
            'cash_register_id'     => $register->uuid,
            'opening_amount_cents' => 10000,
        ])->assertStatus(201)
            ->assertJsonPath('data.cash_register_id', $register->uuid)
            ->assertJsonPath('data.opening_amount_cents', 10000)
            ->assertJsonPath('data.status', 'open');
    });

    it('impede duas sessões abertas no mesmo caixa físico', function (): void {
        $register = CashRegister::factory()->create(['store_id' => $this->store->uuid]);

        $this->postJson('/api/v1/sales/sessions/open', [
            'store_id'         => $this->store->uuid,
            'cash_register_id' => $register->uuid,
        ])->assertStatus(201);

        $this->postJson('/api/v1/sales/sessions/open', [
            'store_id'         => $this->store->uuid,
            'cash_register_id' => $register->uuid,
        ])->assertStatus(422);
    });

    it('cria movimentação de abertura (OPENING)', function (): void {
        $register = CashRegister::factory()->create(['store_id' => $this->store->uuid]);

        $response = $this->postJson('/api/v1/sales/sessions/open', [
            'store_id'             => $this->store->uuid,
            'cash_register_id'     => $register->uuid,
            'opening_amount_cents' => 5000,
        ])->assertStatus(201);

        $sessionUuid = $response->json('data.uuid');

        $this->assertDatabaseHas('cash_movements', [
            'cash_register_session_id' => $sessionUuid,
            'type'                     => 'opening',
            'amount_cents'             => 5000,
        ]);
    });

    it('gera código CAS sequencial na abertura', function (): void {
        $this->postJson('/api/v1/sales/sessions/open', [
            'store_id'             => $this->store->uuid,
            'opening_amount_cents' => 0,
        ])->assertStatus(201)
            ->assertJsonPath('data.code', 'CAS000001');
    });
});

describe('POST /sales/sessions/{session}/close — fechar sessão', function (): void {
    it('fecha sessão e computa saldo esperado', function (): void {
        $session = CashRegisterSession::factory()->open()->create([
            'store_id'             => $this->store->uuid,
            'user_id'              => $this->user->uuid,
            'opening_amount_cents' => 10000,
        ]);

        $this->postJson("/api/v1/sales/sessions/{$session->uuid}/close", [
            'closing_amount_cents' => 10000,
        ])->assertOk()
            ->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.closing_amount_cents', 10000)
            ->assertJsonPath('data.expected_balance_cents', 10000); // sem vendas = só abertura
    });

    it('detecta e registra diferença de caixa', function (): void {
        $session = CashRegisterSession::factory()->open()->create([
            'store_id'             => $this->store->uuid,
            'user_id'              => $this->user->uuid,
            'opening_amount_cents' => 10000,
        ]);

        $this->postJson("/api/v1/sales/sessions/{$session->uuid}/close", [
            'closing_amount_cents' => 8000,
            'difference_reason'    => 'Troco não contabilizado',
        ])->assertOk()
            ->assertJsonPath('data.difference_amount_cents', -2000)
            ->assertJsonPath('data.difference_reason', 'Troco não contabilizado');
    });

    it('cria movimentação de fechamento (CLOSING)', function (): void {
        $session = CashRegisterSession::factory()->open()->create([
            'store_id' => $this->store->uuid,
            'user_id'  => $this->user->uuid,
        ]);

        $this->postJson("/api/v1/sales/sessions/{$session->uuid}/close", [
            'closing_amount_cents' => 5000,
        ])->assertOk();

        $this->assertDatabaseHas('cash_movements', [
            'cash_register_session_id' => $session->uuid,
            'type'                     => 'closing',
            'amount_cents'             => 5000,
        ]);
    });

    it('não permite fechar sessão já fechada', function (): void {
        $session = CashRegisterSession::factory()->closed()->create([
            'store_id' => $this->store->uuid,
            'user_id'  => $this->user->uuid,
        ]);

        $this->postJson("/api/v1/sales/sessions/{$session->uuid}/close", [
            'closing_amount_cents' => 5000,
        ])->assertStatus(422);
    });
});

describe('DELETE /sales/registers/{register} — excluir caixa', function (): void {
    it('não pode excluir caixa com sessão aberta', function (): void {
        $register = CashRegister::factory()->create(['store_id' => $this->store->uuid]);
        CashRegisterSession::factory()->open()->create([
            'store_id'         => $this->store->uuid,
            'cash_register_id' => $register->uuid,
        ]);

        $this->deleteJson("/api/v1/sales/registers/{$register->uuid}")
            ->assertStatus(409);
    });
});
