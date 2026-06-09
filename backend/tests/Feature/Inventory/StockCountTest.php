<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Variant;
use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Modules\Inventory\Enums\StockCountStatusEnum;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\Store;
use App\Modules\Inventory\Models\StockCount;

beforeEach(function (): void {
    $this->actingAsTenantUser();
    $this->store   = Store::factory()->create();
    $this->variant = Variant::factory()->create();

    InventoryBalance::factory()->for($this->store, 'store')->for($this->variant, 'variant')
        ->create(['quantity' => 100, 'reserved_quantity' => 0]);
});

describe('POST /inventory/counts', function (): void {
    it('cria inventário com snapshot do saldo atual', function (): void {
        $this->postJson('/api/v1/inventory/counts', [
            'store_id' => $this->store->uuid,
        ])->assertCreated()
            ->assertJsonPath('data.status', StockCountStatusEnum::InProgress->value);

        $this->assertDatabaseHas('stock_count_items', [
            'variant_id'      => $this->variant->uuid,
            'system_quantity' => 100,
        ]);
    });

    it('cria inventário parcial quando variant_ids informado', function (): void {
        $other = Variant::factory()->create();
        InventoryBalance::factory()->for($this->store, 'store')->for($other, 'variant')
            ->create(['quantity' => 50]);

        $this->postJson('/api/v1/inventory/counts', [
            'store_id'    => $this->store->uuid,
            'variant_ids' => [$this->variant->uuid],
        ])->assertCreated();

        // Apenas a variante solicitada deve estar no inventário
        $this->assertDatabaseHas('stock_count_items', ['variant_id' => $this->variant->uuid]);
        $this->assertDatabaseMissing('stock_count_items', ['variant_id' => $other->uuid]);
    });
});

describe('POST /inventory/counts/{count}/commit', function (): void {
    it('confirma inventário e ajusta saldo divergente', function (): void {
        $count = StockCount::factory()
            ->for($this->store, 'store')
            ->inProgress()
            ->hasItems(1, ['variant_id' => $this->variant->uuid, 'system_quantity' => 100])
            ->create();

        $this->postJson("/api/v1/inventory/counts/{$count->uuid}/commit", [
            'items' => [['variant_id' => $this->variant->uuid, 'counted_quantity' => 80]],
        ])->assertOk()
            ->assertJsonPath('data.status', StockCountStatusEnum::Committed->value);

        // Saldo ajustado para 80
        $this->assertDatabaseHas('inventory_balances', [
            'store_id'   => $this->store->uuid,
            'variant_id' => $this->variant->uuid,
            'quantity'   => 80,
        ]);

        // Movimento de inventário gerado com diferença de -20
        $this->assertDatabaseHas('inventory_movements', [
            'store_id'        => $this->store->uuid,
            'variant_id'      => $this->variant->uuid,
            'type'            => MovementTypeEnum::Inventory->value,
            'quantity'        => -20,
            'quantity_before' => 100,
            'quantity_after'  => 80,
        ]);
    });

    it('não gera movimento quando contagem bate com sistema', function (): void {
        $count = StockCount::factory()
            ->for($this->store, 'store')
            ->inProgress()
            ->hasItems(1, ['variant_id' => $this->variant->uuid, 'system_quantity' => 100])
            ->create();

        $this->postJson("/api/v1/inventory/counts/{$count->uuid}/commit", [
            'items' => [['variant_id' => $this->variant->uuid, 'counted_quantity' => 100]],
        ])->assertOk();

        $this->assertDatabaseMissing('inventory_movements', [
            'type' => MovementTypeEnum::Inventory->value,
        ]);
    });

    it('rejeita commit de inventário já confirmado', function (): void {
        $count = StockCount::factory()
            ->for($this->store, 'store')
            ->committed()
            ->hasItems(1, ['variant_id' => $this->variant->uuid, 'system_quantity' => 100, 'counted_quantity' => 100])
            ->create();

        $this->postJson("/api/v1/inventory/counts/{$count->uuid}/commit", [
            'items' => [['variant_id' => $this->variant->uuid, 'counted_quantity' => 90]],
        ])->assertStatus(422);
    });
});
