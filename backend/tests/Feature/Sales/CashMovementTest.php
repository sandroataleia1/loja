<?php

declare(strict_types=1);

use App\Modules\Inventory\Models\Store;
use App\Modules\Sales\Enums\CashMovementTypeEnum;
use App\Modules\Sales\Models\CashMovement;
use App\Modules\Sales\Models\CashRegisterSession;

beforeEach(function (): void {
    $this->actingAsTenantUser();
    $this->store   = Store::factory()->create();
    $this->session = CashRegisterSession::factory()->open()->create([
        'store_id' => $this->store->uuid,
        'user_id'  => $this->user->uuid,
        'opening_amount_cents' => 20000,
    ]);
});

describe('POST /sales/sessions/{session}/withdrawal — sangria', function (): void {
    it('registra sangria com sucesso', function (): void {
        $this->postJson("/api/v1/sales/sessions/{$this->session->uuid}/withdrawal", [
            'amount_cents' => 5000,
            'description'  => 'Pagamento de fornecedor',
        ])->assertStatus(201)
            ->assertJsonPath('data.type', 'withdrawal')
            ->assertJsonPath('data.amount_cents', 5000);

        $this->assertDatabaseHas('cash_movements', [
            'cash_register_session_id' => $this->session->uuid,
            'type'                     => CashMovementTypeEnum::Withdrawal->value,
            'amount_cents'             => 5000,
        ]);
    });

    it('rejeita valor zero ou negativo', function (): void {
        $this->postJson("/api/v1/sales/sessions/{$this->session->uuid}/withdrawal", [
            'amount_cents' => 0,
        ])->assertStatus(422);
    });

    it('não permite sangria em sessão fechada', function (): void {
        $closed = CashRegisterSession::factory()->closed()->create([
            'store_id' => $this->store->uuid,
            'user_id'  => $this->user->uuid,
        ]);

        $this->postJson("/api/v1/sales/sessions/{$closed->uuid}/withdrawal", [
            'amount_cents' => 1000,
        ])->assertStatus(422);
    });

    it('movimentação de sangria é imutável (sem updated_at)', function (): void {
        $response = $this->postJson("/api/v1/sales/sessions/{$this->session->uuid}/withdrawal", [
            'amount_cents' => 1000,
        ])->assertStatus(201);

        $uuid = $response->json('data.uuid');

        $movement = CashMovement::where('uuid', $uuid)->first();
        // Imutável: a tabela não possui coluna updated_at (acessá-la sob strict
        // mode lançaria MissingAttributeException) — verifica a ausência do atributo.
        expect($movement->getAttributes())->not->toHaveKey('updated_at');
    });
});

describe('POST /sales/sessions/{session}/supply — suprimento', function (): void {
    it('registra suprimento com sucesso', function (): void {
        $this->postJson("/api/v1/sales/sessions/{$this->session->uuid}/supply", [
            'amount_cents' => 10000,
            'description'  => 'Reforço de troco',
        ])->assertStatus(201)
            ->assertJsonPath('data.type', 'supply')
            ->assertJsonPath('data.amount_cents', 10000);
    });

    it('não permite suprimento em sessão fechada', function (): void {
        $closed = CashRegisterSession::factory()->closed()->create([
            'store_id' => $this->store->uuid,
            'user_id'  => $this->user->uuid,
        ]);

        $this->postJson("/api/v1/sales/sessions/{$closed->uuid}/supply", [
            'amount_cents' => 5000,
        ])->assertStatus(422);
    });
});

describe('GET /sales/sessions/{session}/movements — listar movimentações', function (): void {
    it('lista todas as movimentações da sessão', function (): void {
        // OPENING criado na abertura + 2 manuais
        $this->postJson("/api/v1/sales/sessions/{$this->session->uuid}/withdrawal", [
            'amount_cents' => 1000,
        ]);
        $this->postJson("/api/v1/sales/sessions/{$this->session->uuid}/supply", [
            'amount_cents' => 3000,
        ]);

        $response = $this->getJson("/api/v1/sales/sessions/{$this->session->uuid}/movements")
            ->assertOk();

        expect(count($response->json('data')))->toBeGreaterThanOrEqual(2);
    });

    it('filtra movimentações por tipo', function (): void {
        $this->postJson("/api/v1/sales/sessions/{$this->session->uuid}/withdrawal", [
            'amount_cents' => 1000,
        ]);
        $this->postJson("/api/v1/sales/sessions/{$this->session->uuid}/supply", [
            'amount_cents' => 3000,
        ]);

        $this->getJson("/api/v1/sales/sessions/{$this->session->uuid}/movements?type=withdrawal")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('Saldo esperado — computeExpectedBalanceCents', function (): void {
    it('saldo esperado = abertura + suprimentos - sangrias', function (): void {
        // Abertura: 20000
        // Suprimento: +5000
        // Sangria: -3000
        // Esperado: 22000

        $this->postJson("/api/v1/sales/sessions/{$this->session->uuid}/supply", [
            'amount_cents' => 5000,
        ]);
        $this->postJson("/api/v1/sales/sessions/{$this->session->uuid}/withdrawal", [
            'amount_cents' => 3000,
        ]);

        $this->postJson("/api/v1/sales/sessions/{$this->session->uuid}/close", [
            'closing_amount_cents' => 22000,
        ])->assertOk()
            ->assertJsonPath('data.expected_balance_cents', 22000)
            ->assertJsonPath('data.difference_amount_cents', 0);
    });
});
