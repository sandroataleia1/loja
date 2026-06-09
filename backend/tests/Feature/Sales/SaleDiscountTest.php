<?php

declare(strict_types=1);

use App\Modules\Inventory\Models\Store;
use App\Modules\Sales\Enums\DiscountTypeEnum;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;

beforeEach(function (): void {
    $this->actingAsTenantUser();
    $this->store = Store::factory()->create();
    $this->sale  = Sale::factory()->for($this->store, 'store')->withTotal(10000)->draft()->create();
    $this->item  = SaleItem::factory()->create([
        'sale_id'        => $this->sale->uuid,
        'quantity'       => 2,
        'unit_price_cents' => 5000,
        'subtotal_cents' => 10000,
        'total_cents'    => 10000,
    ]);
});

describe('POST /sales/{sale}/discounts — desconto na venda', function (): void {
    it('aplica desconto fixo na venda', function (): void {
        $this->postJson("/api/v1/sales/{$this->sale->uuid}/discounts", [
            'type'         => DiscountTypeEnum::Fixed->value,
            'amount_cents' => 2000,
            'reason'       => 'Fidelidade',
        ])->assertOk()
            ->assertJsonPath('data.discount_total_cents', 2000)
            ->assertJsonPath('data.total_cents', 8000);
    });

    it('aplica desconto percentual na venda', function (): void {
        $this->postJson("/api/v1/sales/{$this->sale->uuid}/discounts", [
            'type'       => DiscountTypeEnum::Percentage->value,
            'percentage' => 10,
        ])->assertOk()
            ->assertJsonPath('data.discount_total_cents', 1000)
            ->assertJsonPath('data.total_cents', 9000);
    });

    it('rejeita desconto maior que subtotal', function (): void {
        $this->postJson("/api/v1/sales/{$this->sale->uuid}/discounts", [
            'type'         => DiscountTypeEnum::Fixed->value,
            'amount_cents' => 15000,
        ])->assertStatus(422);
    });

    it('não permite desconto em venda concluída', function (): void {
        $sale = Sale::factory()->for($this->store, 'store')->completed()->create();

        $this->postJson("/api/v1/sales/{$sale->uuid}/discounts", [
            'type'         => DiscountTypeEnum::Fixed->value,
            'amount_cents' => 100,
        ])->assertStatus(422);
    });
});

describe('POST /sales/{sale}/discounts — desconto no item', function (): void {
    it('aplica desconto fixo em item específico', function (): void {
        $this->postJson("/api/v1/sales/{$this->sale->uuid}/discounts", [
            'type'         => DiscountTypeEnum::Fixed->value,
            'amount_cents' => 1000,
            'sale_item_id' => $this->item->uuid,
        ])->assertOk();

        $this->assertDatabaseHas('sale_items', [
            'uuid'                  => $this->item->uuid,
            'discount_amount_cents' => 1000,
            'total_cents'           => 9000,
        ]);
    });

    it('registra desconto na tabela sale_discounts', function (): void {
        $this->postJson("/api/v1/sales/{$this->sale->uuid}/discounts", [
            'type'         => DiscountTypeEnum::Fixed->value,
            'amount_cents' => 500,
            'reason'       => 'Negociação',
        ])->assertOk();

        $this->assertDatabaseHas('sale_discounts', [
            'sale_id'      => $this->sale->uuid,
            'amount_cents' => 500,
            'reason'       => 'Negociação',
        ]);
    });
});
