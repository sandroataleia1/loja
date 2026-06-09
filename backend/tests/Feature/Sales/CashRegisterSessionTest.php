<?php

declare(strict_types=1);

use App\Modules\Inventory\Models\Store;
use App\Modules\Sales\Enums\CashRegisterSessionStatusEnum;
use App\Modules\Sales\Models\CashRegisterSession;

beforeEach(function (): void {
    $this->actingAsTenantUser();
    $this->store = Store::factory()->create();
});

describe('POST /sales/sessions/open', function (): void {
    it('abre uma sessão de caixa', function (): void {
        $this->postJson('/api/v1/sales/sessions/open', [
            'store_id'             => $this->store->uuid,
            'opening_amount_cents' => 20000,
        ])->assertCreated()
            ->assertJsonPath('data.status', CashRegisterSessionStatusEnum::Open->value)
            ->assertJsonPath('data.opening_amount_cents', 20000);

        $this->assertDatabaseHas('cash_register_sessions', [
            'store_id' => $this->store->uuid,
            'status'   => CashRegisterSessionStatusEnum::Open->value,
        ]);
    });

    it('impede segunda sessão aberta para o mesmo usuário na mesma loja', function (): void {
        $this->postJson('/api/v1/sales/sessions/open', [
            'store_id' => $this->store->uuid,
        ])->assertCreated();

        $this->postJson('/api/v1/sales/sessions/open', [
            'store_id' => $this->store->uuid,
        ])->assertStatus(422);
    });
});

describe('POST /sales/sessions/{session}/close', function (): void {
    it('fecha uma sessão aberta', function (): void {
        $session = CashRegisterSession::factory()
            ->for($this->store, 'store')
            ->for($this->user, 'user')
            ->open()
            ->create();

        $this->postJson("/api/v1/sales/sessions/{$session->uuid}/close", [
            'closing_amount_cents' => 35000,
        ])->assertOk()
            ->assertJsonPath('data.status', CashRegisterSessionStatusEnum::Closed->value)
            ->assertJsonPath('data.closing_amount_cents', 35000);
    });

    it('não fecha sessão já fechada', function (): void {
        $session = CashRegisterSession::factory()
            ->for($this->store, 'store')
            ->for($this->user, 'user')
            ->closed()
            ->create();

        $this->postJson("/api/v1/sales/sessions/{$session->uuid}/close", [
            'closing_amount_cents' => 0,
        ])->assertStatus(422);
    });
});

describe('GET /sales/sessions', function (): void {
    it('lista sessões do tenant', function (): void {
        CashRegisterSession::factory()->count(3)->create(['store_id' => $this->store->uuid]);

        $this->getJson('/api/v1/sales/sessions')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });
});
