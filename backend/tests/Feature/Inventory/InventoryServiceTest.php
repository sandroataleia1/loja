<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Variant;
use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Modules\Inventory\Models\InventoryAdjustment;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\Store;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->actingAsTenantUser();
    $this->store   = Store::factory()->create();
    $this->variant = Variant::factory()->create();
    $this->service = app(InventoryService::class);
});

// ── receive() ────────────────────────────────────────────────────────────────

it('receive() increases current_stock and creates IN movement', function (): void {
    $balance = $this->service->receive($this->store->uuid, $this->variant->uuid, 50);

    expect($balance->quantity)->toBe(50)
        ->and($balance->reserved_quantity)->toBe(0);

    $this->assertDatabaseHas('inventory_movements', [
        'store_id'   => $this->store->uuid,
        'variant_id' => $this->variant->uuid,
        'type'       => MovementTypeEnum::In->value,
        'quantity'   => 50,
    ]);
});

// ── consume() ────────────────────────────────────────────────────────────────

it('consume() decreases current_stock and creates SALE movement', function (): void {
    $saleRef = (string) Str::uuid();
    $this->service->receive($this->store->uuid, $this->variant->uuid, 30);
    $balance = $this->service->consume($this->store->uuid, $this->variant->uuid, 10, 'sale', $saleRef);

    expect($balance->quantity)->toBe(20);

    $this->assertDatabaseHas('inventory_movements', [
        'store_id'       => $this->store->uuid,
        'variant_id'     => $this->variant->uuid,
        'type'           => MovementTypeEnum::Sale->value,
        'quantity'       => -10,
        'reference_type' => 'sale',
    ]);
});

// ── adjust() ─────────────────────────────────────────────────────────────────

it('adjust() creates ADJUSTMENT movement and InventoryAdjustment record', function (): void {
    $this->service->receive($this->store->uuid, $this->variant->uuid, 100);
    $balance = $this->service->adjust($this->store->uuid, $this->variant->uuid, -5, 'Produto danificado');

    expect($balance->quantity)->toBe(95);

    $this->assertDatabaseHas('inventory_movements', [
        'type'     => MovementTypeEnum::Adjustment->value,
        'quantity' => -5,
    ]);

    $this->assertDatabaseHas('inventory_adjustments', [
        'store_id'          => $this->store->uuid,
        'variant_id'        => $this->variant->uuid,
        'previous_quantity' => 100,
        'new_quantity'      => 95,
    ]);
});

// ── reserve() ────────────────────────────────────────────────────────────────

it('reserve() increases reserved_quantity without changing current_stock', function (): void {
    $orderRef = (string) Str::uuid();
    $this->service->receive($this->store->uuid, $this->variant->uuid, 20);
    $balance = $this->service->reserve($this->store->uuid, $this->variant->uuid, 5, 'order', $orderRef);

    expect($balance->quantity)->toBe(20)
        ->and($balance->reserved_quantity)->toBe(5)
        ->and($balance->available_quantity)->toBe(15);
});

// ── release() ────────────────────────────────────────────────────────────────

it('release() decreases reserved_quantity', function (): void {
    $orderRef = (string) Str::uuid();
    $this->service->receive($this->store->uuid, $this->variant->uuid, 20);
    $this->service->reserve($this->store->uuid, $this->variant->uuid, 5, 'order', $orderRef);
    $balance = $this->service->release($this->store->uuid, $this->variant->uuid, 5, 'order', $orderRef);

    expect($balance->reserved_quantity)->toBe(0)
        ->and($balance->available_quantity)->toBe(20);
});

// ── return() ─────────────────────────────────────────────────────────────────

it('return() increases current_stock and creates RETURN movement', function (): void {
    $saleRef = (string) Str::uuid();
    $this->service->receive($this->store->uuid, $this->variant->uuid, 10);
    $this->service->consume($this->store->uuid, $this->variant->uuid, 3, 'sale', $saleRef);
    $balance = $this->service->return($this->store->uuid, $this->variant->uuid, 2, 'sale', $saleRef);

    expect($balance->quantity)->toBe(9);

    $this->assertDatabaseHas('inventory_movements', [
        'type'     => MovementTypeEnum::Return->value,
        'quantity' => 2,
    ]);
});

// ── hasAvailable() ───────────────────────────────────────────────────────────

it('hasAvailable() returns false when reservations exceed available', function (): void {
    $orderRef = (string) Str::uuid();
    $this->service->receive($this->store->uuid, $this->variant->uuid, 10);
    $this->service->reserve($this->store->uuid, $this->variant->uuid, 8, 'order', $orderRef);

    expect($this->service->hasAvailable($this->store->uuid, $this->variant->uuid, 5))->toBeFalse()
        ->and($this->service->hasAvailable($this->store->uuid, $this->variant->uuid, 2))->toBeTrue();
});

// ── ConditionalOut / ConditionalReturn ────────────────────────────────────────

it('ConditionalOut movement type decreases stock', function (): void {
    $condRef = (string) Str::uuid();
    $this->service->receive($this->store->uuid, $this->variant->uuid, 15);
    $balance = $this->service->consume(
        $this->store->uuid, $this->variant->uuid, 4, 'conditional', $condRef, MovementTypeEnum::ConditionalOut
    );

    expect($balance->quantity)->toBe(11);

    $this->assertDatabaseHas('inventory_movements', [
        'type'     => MovementTypeEnum::ConditionalOut->value,
        'quantity' => -4,
    ]);
});

it('ConditionalReturn movement type increases stock', function (): void {
    $condRef = (string) Str::uuid();
    $this->service->receive($this->store->uuid, $this->variant->uuid, 15);
    $this->service->consume($this->store->uuid, $this->variant->uuid, 4, 'conditional', $condRef, MovementTypeEnum::ConditionalOut);
    $balance = $this->service->return($this->store->uuid, $this->variant->uuid, 2, 'conditional', $condRef);

    expect($balance->quantity)->toBe(13);
});
