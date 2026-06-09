<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Variant;
use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\Store;

beforeEach(function (): void {
    $this->actingAsTenantUser();
    $this->store   = Store::factory()->create();
    $this->variant = Variant::factory()->create();
});

describe('GET /inventory/balances', function (): void {
    it('lista saldos do tenant', function (): void {
        InventoryBalance::factory()->for($this->store, 'store')->for($this->variant, 'variant')->create([
            'quantity' => 50,
        ]);

        $this->getJson('/api/v1/inventory/balances')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    });

    it('filtra por loja', function (): void {
        $other = Store::factory()->create();
        InventoryBalance::factory()->for($this->store, 'store')->create(['quantity' => 10]);
        InventoryBalance::factory()->for($other, 'store')->create(['quantity' => 20]);

        $this->getJson("/api/v1/inventory/balances?store_id={$this->store->uuid}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('POST /inventory/balances/adjust', function (): void {
    it('ajusta estoque positivo (entrada)', function (): void {
        $this->postJson('/api/v1/inventory/balances/adjust', [
            'store_id'   => $this->store->uuid,
            'variant_id' => $this->variant->uuid,
            'quantity'   => 100,
            'type'       => MovementTypeEnum::Purchase->value,
        ])->assertOk()
            ->assertJsonPath('data.quantity', 100);

        $this->assertDatabaseHas('inventory_balances', [
            'store_id'   => $this->store->uuid,
            'variant_id' => $this->variant->uuid,
            'quantity'   => 100,
        ]);
    });

    it('ajusta estoque negativo (saída) respeitando saldo', function (): void {
        InventoryBalance::factory()->for($this->store, 'store')->for($this->variant, 'variant')->create([
            'quantity'          => 50,
            'reserved_quantity' => 0,
        ]);

        $this->postJson('/api/v1/inventory/balances/adjust', [
            'store_id'   => $this->store->uuid,
            'variant_id' => $this->variant->uuid,
            'quantity'   => -30,
            'type'       => MovementTypeEnum::Loss->value,
        ])->assertOk()
            ->assertJsonPath('data.quantity', 20);
    });

    it('rejeita saída que resulta em negativo quando tenant não permite', function (): void {
        InventoryBalance::factory()->for($this->store, 'store')->for($this->variant, 'variant')->create([
            'quantity' => 5,
        ]);

        $this->postJson('/api/v1/inventory/balances/adjust', [
            'store_id'   => $this->store->uuid,
            'variant_id' => $this->variant->uuid,
            'quantity'   => -10,
            'type'       => MovementTypeEnum::Loss->value,
        ])->assertStatus(422);
    });

    it('cria movimento imutável para cada ajuste', function (): void {
        $this->postJson('/api/v1/inventory/balances/adjust', [
            'store_id'   => $this->store->uuid,
            'variant_id' => $this->variant->uuid,
            'quantity'   => 50,
            'type'       => MovementTypeEnum::Purchase->value,
        ])->assertOk();

        $this->assertDatabaseHas('inventory_movements', [
            'store_id'       => $this->store->uuid,
            'variant_id'     => $this->variant->uuid,
            'quantity'       => 50,
            'quantity_before' => 0,
            'quantity_after'  => 50,
        ]);
    });

    it('calcula available_quantity corretamente', function (): void {
        InventoryBalance::factory()->for($this->store, 'store')->for($this->variant, 'variant')->create([
            'quantity'          => 100,
            'reserved_quantity' => 30,
        ]);

        $this->getJson("/api/v1/inventory/balances?store_id={$this->store->uuid}")
            ->assertOk()
            ->assertJsonPath('data.0.available_quantity', 70);
    });
});

describe('GET /inventory/movements', function (): void {
    it('lista movimentos do tenant', function (): void {
        $this->postJson('/api/v1/inventory/balances/adjust', [
            'store_id'   => $this->store->uuid,
            'variant_id' => $this->variant->uuid,
            'quantity'   => 10,
            'type'       => MovementTypeEnum::Purchase->value,
        ])->assertOk();

        $this->getJson('/api/v1/inventory/movements')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });
});
