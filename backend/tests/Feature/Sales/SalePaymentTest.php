<?php

declare(strict_types=1);

use App\Modules\Inventory\Models\Store;
use App\Modules\Sales\Enums\PaymentMethodEnum;
use App\Modules\Sales\Enums\PaymentStatusEnum;
use App\Modules\Sales\Models\Sale;

beforeEach(function (): void {
    $this->actingAsTenantUser();
    $this->store = Store::factory()->create();
    $this->sale  = Sale::factory()->for($this->store, 'store')->withTotal(10000)->draft()->create();
});

describe('POST /sales/{sale}/payments', function (): void {
    it('adiciona pagamento CASH marcado como PAID imediatamente', function (): void {
        $this->postJson("/api/v1/sales/{$this->sale->uuid}/payments", [
            'method'       => PaymentMethodEnum::Cash->value,
            'amount_cents' => 10000,
        ])->assertCreated()
            ->assertJsonPath('data.status', PaymentStatusEnum::Paid->value)
            ->assertJsonPath('data.method', PaymentMethodEnum::Cash->value);

        $this->assertDatabaseHas('payment_transactions', [
            'sale_id'      => $this->sale->uuid,
            'status'       => PaymentStatusEnum::Paid->value,
            'amount_cents' => 10000,
        ]);
    });

    it('adiciona pagamento CREDIT_CARD como PENDING', function (): void {
        $this->postJson("/api/v1/sales/{$this->sale->uuid}/payments", [
            'method'       => PaymentMethodEnum::CreditCard->value,
            'amount_cents' => 10000,
        ])->assertCreated()
            ->assertJsonPath('data.status', PaymentStatusEnum::Pending->value);
    });

    it('aceita múltiplos pagamentos para cobrir total', function (): void {
        $this->postJson("/api/v1/sales/{$this->sale->uuid}/payments", [
            'method'       => PaymentMethodEnum::Cash->value,
            'amount_cents' => 5000,
        ])->assertCreated();

        $this->postJson("/api/v1/sales/{$this->sale->uuid}/payments", [
            'method'       => PaymentMethodEnum::Pix->value,
            'amount_cents' => 5000,
        ])->assertCreated();

        $this->assertDatabaseCount('payment_transactions', 2);
    });

    it('rejeita pagamento em venda já concluída', function (): void {
        $sale = Sale::factory()->for($this->store, 'store')->completed()->create();

        $this->postJson("/api/v1/sales/{$sale->uuid}/payments", [
            'method'       => PaymentMethodEnum::Cash->value,
            'amount_cents' => 1000,
        ])->assertStatus(422);
    });
});
