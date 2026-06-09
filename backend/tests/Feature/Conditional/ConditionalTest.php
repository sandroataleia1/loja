<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Variant;
use App\Modules\Conditional\Enums\ConditionalStatusEnum;
use App\Modules\Conditional\Jobs\MarkOverdueConditionalsJob;
use App\Modules\Conditional\Models\Conditional;
use App\Modules\Conditional\Models\ConditionalItem;
use App\Modules\Customers\Models\Customer;
use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\Store;

beforeEach(function (): void {
    $this->actingAsTenantUser();

    $this->store    = Store::factory()->create();
    $this->customer = Customer::factory()->create();
    $this->variant  = Variant::factory()->create();

    // Seed inventory: 20 units
    InventoryBalance::factory()
        ->for($this->store, 'store')
        ->for($this->variant, 'variant')
        ->create(['quantity' => 20, 'reserved_quantity' => 0]);
});

// ── Helper ────────────────────────────────────────────────────────────────────

function openConditional(mixed $ctx, int $quantity = 3, ?string $dueDate = null): array
{
    $response = $ctx->postJson('/api/v1/conditionals', [
        'store_id'    => $ctx->store->uuid,
        'customer_id' => $ctx->customer->uuid,
        'due_date'    => $dueDate ?? now()->addDays(7)->toDateString(),
        'items'       => [[
            'variant_id'       => $ctx->variant->uuid,
            'quantity'         => $quantity,
            'unit_price_cents' => 10000,
        ]],
    ]);

    return [$response, $response->json('data.uuid'), $response->json('data.items.0.uuid')];
}

// ── OPEN ──────────────────────────────────────────────────────────────────────

it('can open a conditional (creates ConditionalOut movements)', function (): void {
    [$response, $conditionalUuid] = openConditional($this, 3);

    $response->assertCreated()
        ->assertJsonPath('data.status', ConditionalStatusEnum::Open->value)
        ->assertJsonPath('data.total_items', 1);

    $this->assertDatabaseHas('inventory_movements', [
        'store_id'       => $this->store->uuid,
        'variant_id'     => $this->variant->uuid,
        'type'           => MovementTypeEnum::ConditionalOut->value,
        'quantity'       => -3,
        'reference_type' => 'conditional',
        'reference_id'   => $conditionalUuid,
    ]);

    // Stock should have decreased from 20 to 17
    $this->assertDatabaseHas('inventory_balances', [
        'store_id'   => $this->store->uuid,
        'variant_id' => $this->variant->uuid,
        'quantity'   => 17,
    ]);
});

it('cannot open conditional with past due_date', function (): void {
    $this->postJson('/api/v1/conditionals', [
        'store_id'    => $this->store->uuid,
        'customer_id' => $this->customer->uuid,
        'due_date'    => now()->subDay()->toDateString(),
        'items'       => [[
            'variant_id'       => $this->variant->uuid,
            'quantity'         => 1,
            'unit_price_cents' => 10000,
        ]],
    ])->assertUnprocessable();
});

// ── RETURN ────────────────────────────────────────────────────────────────────

it('can return items partially (PARTIALLY_RETURNED status)', function (): void {
    [$open, $conditionalUuid, $itemUuid] = openConditional($this, 3);
    $open->assertCreated();

    $this->postJson("/api/v1/conditionals/{$conditionalUuid}/return", [
        'returns' => [
            ['item_uuid' => $itemUuid, 'quantity' => 1],
        ],
    ])->assertOk()
        ->assertJsonPath('data.status', ConditionalStatusEnum::PartiallyReturned->value);

    // Inventory should be restored partially: 17 + 1 = 18
    $this->assertDatabaseHas('inventory_balances', [
        'store_id'   => $this->store->uuid,
        'variant_id' => $this->variant->uuid,
        'quantity'   => 18,
    ]);

    $this->assertDatabaseHas('inventory_movements', [
        'type'     => MovementTypeEnum::ConditionalReturn->value,
        'quantity' => 1,
    ]);
});

it('can return all items (RETURNED status)', function (): void {
    [$open, $conditionalUuid, $itemUuid] = openConditional($this, 3);
    $open->assertCreated();

    $this->postJson("/api/v1/conditionals/{$conditionalUuid}/return", [
        'returns' => [
            ['item_uuid' => $itemUuid, 'quantity' => 3],
        ],
    ])->assertOk()
        ->assertJsonPath('data.status', ConditionalStatusEnum::Returned->value);

    // All stock restored: 17 + 3 = 20
    $this->assertDatabaseHas('inventory_balances', [
        'store_id'   => $this->store->uuid,
        'variant_id' => $this->variant->uuid,
        'quantity'   => 20,
    ]);
});

// ── CONVERT ───────────────────────────────────────────────────────────────────

it('can convert items partially (PARTIALLY_CONVERTED status)', function (): void {
    [$open, $conditionalUuid, $itemUuid] = openConditional($this, 3);
    $open->assertCreated();

    $this->postJson("/api/v1/conditionals/{$conditionalUuid}/convert", [
        'conversions' => [
            ['item_uuid' => $itemUuid, 'quantity' => 2],
        ],
    ])->assertOk()
        ->assertJsonPath('data.status', ConditionalStatusEnum::PartiallyConverted->value);

    $this->assertDatabaseHas('conditional_items', [
        'uuid'         => $itemUuid,
        'sold_quantity' => 2,
    ]);
});

it('can convert all items (CONVERTED status)', function (): void {
    [$open, $conditionalUuid, $itemUuid] = openConditional($this, 3);
    $open->assertCreated();

    $this->postJson("/api/v1/conditionals/{$conditionalUuid}/convert", [
        'conversions' => [
            ['item_uuid' => $itemUuid, 'quantity' => 3],
        ],
    ])->assertOk()
        ->assertJsonPath('data.status', ConditionalStatusEnum::Converted->value);
});

// ── CANCEL ────────────────────────────────────────────────────────────────────

it('can cancel an open conditional (restores inventory)', function (): void {
    [$open, $conditionalUuid] = openConditional($this, 5);
    $open->assertCreated();

    $this->postJson("/api/v1/conditionals/{$conditionalUuid}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', ConditionalStatusEnum::Cancelled->value);

    // All 5 units restored: 15 + 5 = 20
    $this->assertDatabaseHas('inventory_balances', [
        'store_id'   => $this->store->uuid,
        'variant_id' => $this->variant->uuid,
        'quantity'   => 20,
    ]);
});

it('cannot cancel a returned/converted conditional', function (): void {
    [$open, $conditionalUuid, $itemUuid] = openConditional($this, 3);
    $open->assertCreated();

    // First convert all
    $this->postJson("/api/v1/conditionals/{$conditionalUuid}/convert", [
        'conversions' => [['item_uuid' => $itemUuid, 'quantity' => 3]],
    ])->assertOk();

    // Then try to cancel — policy blocks because status.canCancel() = false → 403
    $this->postJson("/api/v1/conditionals/{$conditionalUuid}/cancel")
        ->assertForbidden();
});

// ── OVERDUE JOB ───────────────────────────────────────────────────────────────

it('overdue job marks expired conditionals as OVERDUE', function (): void {
    [$open, $conditionalUuid] = openConditional($this, 2, now()->addDays(7)->toDateString());
    $open->assertCreated();

    // Manually set expires_at to past
    Conditional::where('uuid', $conditionalUuid)->update([
        'expires_at' => now()->subDay(),
    ]);

    // Run the job
    (new MarkOverdueConditionalsJob())->handle();

    $this->assertDatabaseHas('conditionals', [
        'uuid'   => $conditionalUuid,
        'status' => ConditionalStatusEnum::Overdue->value,
    ]);

    $this->assertDatabaseHas('conditional_status_history', [
        'conditional_id' => $conditionalUuid,
        'current_status' => ConditionalStatusEnum::Overdue->value,
    ]);
});

// ── FILTERING ─────────────────────────────────────────────────────────────────

it('can filter by status', function (): void {
    openConditional($this, 2);

    $this->getJson('/api/v1/conditionals?status=open')
        ->assertOk()
        ->assertJsonPath('data.0.status', ConditionalStatusEnum::Open->value);

    $this->getJson('/api/v1/conditionals?status=converted')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('can filter overdue conditionals', function (): void {
    [$open, $conditionalUuid] = openConditional($this, 2, now()->addDays(7)->toDateString());
    $open->assertCreated();

    // Initially no overdue conditionals
    $this->getJson('/api/v1/conditionals?overdue=1')
        ->assertOk()
        ->assertJsonCount(0, 'data');

    // Manually expire
    Conditional::where('uuid', $conditionalUuid)->update(['expires_at' => now()->subDay()]);

    $this->getJson('/api/v1/conditionals?overdue=1')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
