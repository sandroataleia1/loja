<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Variant;
use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Modules\Inventory\Enums\TransferStatusEnum;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\Store;
use App\Modules\Inventory\Models\StockTransfer;

beforeEach(function (): void {
    $this->actingAsTenantUser();
    $this->origin      = Store::factory()->create(['name' => 'Origem']);
    $this->destination = Store::factory()->create(['name' => 'Destino']);
    $this->variant     = Variant::factory()->create();
});

describe('POST /inventory/transfers', function (): void {
    it('cria transferência no status pending', function (): void {
        $this->postJson('/api/v1/inventory/transfers', [
            'origin_store_id'      => $this->origin->uuid,
            'destination_store_id' => $this->destination->uuid,
            'items'                => [['variant_id' => $this->variant->uuid, 'quantity' => 10]],
        ])->assertCreated()
            ->assertJsonPath('data.status', TransferStatusEnum::Pending->value);
    });

    it('rejeita origem igual a destino', function (): void {
        $this->postJson('/api/v1/inventory/transfers', [
            'origin_store_id'      => $this->origin->uuid,
            'destination_store_id' => $this->origin->uuid,
            'items'                => [['variant_id' => $this->variant->uuid, 'quantity' => 5]],
        ])->assertUnprocessable();
    });
});

describe('POST /inventory/transfers/{transfer}/dispatch', function (): void {
    it('despacha transferência e debita saldo na origem', function (): void {
        InventoryBalance::factory()->for($this->origin, 'store')->for($this->variant, 'variant')
            ->create(['quantity' => 50]);

        $transfer = StockTransfer::factory()
            ->for($this->origin, 'originStore')
            ->for($this->destination, 'destinationStore')
            ->pending()
            ->hasItems(1, ['variant_id' => $this->variant->uuid, 'quantity_requested' => 10])
            ->create();

        $this->postJson("/api/v1/inventory/transfers/{$transfer->uuid}/dispatch", [
            'items' => [['variant_id' => $this->variant->uuid, 'quantity_sent' => 10]],
        ])->assertOk()
            ->assertJsonPath('data.status', TransferStatusEnum::InTransit->value);

        $this->assertDatabaseHas('inventory_balances', [
            'store_id'   => $this->origin->uuid,
            'variant_id' => $this->variant->uuid,
            'quantity'   => 40,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'store_id'   => $this->origin->uuid,
            'variant_id' => $this->variant->uuid,
            'type'       => MovementTypeEnum::Transfer->value,
            'quantity'   => -10,
        ]);
    });

    it('não despacha transferência já despachada', function (): void {
        $transfer = StockTransfer::factory()
            ->for($this->origin, 'originStore')
            ->for($this->destination, 'destinationStore')
            ->inTransit()
            ->hasItems(1, ['variant_id' => $this->variant->uuid, 'quantity_requested' => 5])
            ->create();

        $this->postJson("/api/v1/inventory/transfers/{$transfer->uuid}/dispatch", [
            'items' => [['variant_id' => $this->variant->uuid, 'quantity_sent' => 5]],
        ])->assertStatus(422);
    });
});

describe('POST /inventory/transfers/{transfer}/receive', function (): void {
    it('recebe transferência e credita saldo no destino', function (): void {
        InventoryBalance::factory()->for($this->origin, 'store')->for($this->variant, 'variant')
            ->create(['quantity' => 40]);

        $transfer = StockTransfer::factory()
            ->for($this->origin, 'originStore')
            ->for($this->destination, 'destinationStore')
            ->inTransit()
            ->hasItems(1, [
                'variant_id'         => $this->variant->uuid,
                'quantity_requested' => 10,
                'quantity_sent'      => 10,
            ])->create();

        $this->postJson("/api/v1/inventory/transfers/{$transfer->uuid}/receive", [
            'items' => [['variant_id' => $this->variant->uuid, 'quantity_received' => 10]],
        ])->assertOk()
            ->assertJsonPath('data.status', TransferStatusEnum::Received->value);

        $this->assertDatabaseHas('inventory_balances', [
            'store_id'   => $this->destination->uuid,
            'variant_id' => $this->variant->uuid,
            'quantity'   => 10,
        ]);
    });
});

describe('POST /inventory/transfers/{transfer}/cancel', function (): void {
    it('cancela transferência pending sem estorno', function (): void {
        $transfer = StockTransfer::factory()
            ->for($this->origin, 'originStore')
            ->for($this->destination, 'destinationStore')
            ->pending()
            ->hasItems(1, ['variant_id' => $this->variant->uuid, 'quantity_requested' => 5])
            ->create();

        $this->postJson("/api/v1/inventory/transfers/{$transfer->uuid}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', TransferStatusEnum::Cancelled->value);
    });

    it('cancela transferência in_transit com estorno', function (): void {
        InventoryBalance::factory()->for($this->origin, 'store')->for($this->variant, 'variant')
            ->create(['quantity' => 40]);

        $transfer = StockTransfer::factory()
            ->for($this->origin, 'originStore')
            ->for($this->destination, 'destinationStore')
            ->inTransit()
            ->hasItems(1, [
                'variant_id'         => $this->variant->uuid,
                'quantity_requested' => 10,
                'quantity_sent'      => 10,
            ])->create();

        $this->postJson("/api/v1/inventory/transfers/{$transfer->uuid}/cancel")
            ->assertOk();

        // Estorno deve restaurar 10 unidades na origem
        $this->assertDatabaseHas('inventory_balances', [
            'store_id'   => $this->origin->uuid,
            'variant_id' => $this->variant->uuid,
            'quantity'   => 50,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'store_id'       => $this->origin->uuid,
            'variant_id'     => $this->variant->uuid,
            'type'           => MovementTypeEnum::Return->value,
            'quantity'       => 10,
        ]);
    });
});
