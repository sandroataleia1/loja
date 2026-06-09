<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Variant;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\Store;
use App\Modules\Sync\Enums\SyncEntityTypeEnum;
use App\Modules\Sync\Models\SyncDevice;
use App\Modules\Sync\Models\SyncPullCheckpoint;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->actingAsTenantUser();
    $this->store = Store::factory()->create(['tenant_id' => $this->tenant->uuid]);
    $this->device = SyncDevice::factory()->create([
        'store_id'  => $this->store->uuid,
        'tenant_id' => $this->tenant->uuid,
    ]);
});

describe('sync_pull — pull incremental', function (): void {
    it('retorna produtos modificados após checkpoint', function (): void {
        // Produto antigo (antes do checkpoint)
        $old = Product::factory()->create([
            'tenant_id'  => $this->tenant->uuid,
            'updated_at' => now()->subHours(2),
        ]);

        // Produto recente (após checkpoint)
        $new = Product::factory()->create([
            'tenant_id'  => $this->tenant->uuid,
            'updated_at' => now()->subMinutes(5),
        ]);

        $checkpoint = now()->subHour()->toIso8601String();

        $this->postJson('/api/v1/sync/pull', [
            'device_uuid'  => $this->device->device_uuid,
            'batch_id'     => Str::uuid()->toString(),
            'entity_types' => ['product'],
            'checkpoints'  => ['product' => $checkpoint],
        ])->assertStatus(200)
            ->assertJsonPath('data.synced_count', 1)
            ->assertJsonStructure(['data' => [
                'data' => ['product'],
                'checkpoints',
            ]]);

        $response = $this->postJson('/api/v1/sync/pull', [
            'device_uuid'  => $this->device->device_uuid,
            'batch_id'     => Str::uuid()->toString(),
            'entity_types' => ['product'],
            'checkpoints'  => ['product' => $checkpoint],
        ])->json();

        expect($response['data']['data']['product']['entities'])->toHaveCount(1)
            ->and($response['data']['data']['product']['entities'][0]['uuid'])->toBe($new->uuid);
    });

    it('usa checkpoint do banco quando não enviado no request', function (): void {
        SyncPullCheckpoint::create([
            'tenant_id'      => $this->tenant->uuid,
            'device_id'      => $this->device->uuid,
            'entity_type'    => SyncEntityTypeEnum::Product,
            'last_synced_at' => now()->subHour(),
            'updated_at'     => now(),
        ]);

        Product::factory()->create([
            'tenant_id'  => $this->tenant->uuid,
            'updated_at' => now()->subMinutes(30),
        ]);

        // Sem checkpoint no request → usa o checkpoint do banco (1h atrás),
        // retornando o produto modificado há 30min. (O pull avança o checkpoint
        // de forma otimista, então a verificação é feita neste primeiro pull.)
        $response = $this->postJson('/api/v1/sync/pull', [
            'device_uuid'  => $this->device->device_uuid,
            'batch_id'     => Str::uuid()->toString(),
            'entity_types' => ['product'],
            'checkpoints'  => [],
        ])->assertStatus(200)->json();

        expect($response['data']['data']['product']['entities'])->toHaveCount(1);
    });

    it('avança checkpoint após pull', function (): void {
        $this->postJson('/api/v1/sync/pull', [
            'device_uuid'  => $this->device->device_uuid,
            'batch_id'     => Str::uuid()->toString(),
            'entity_types' => ['product'],
            'checkpoints'  => [],
        ])->assertStatus(200);

        $checkpoint = SyncPullCheckpoint::where('device_id', $this->device->uuid)
            ->where('entity_type', SyncEntityTypeEnum::Product)
            ->first();

        expect($checkpoint)->not->toBeNull()
            ->and($checkpoint->last_synced_at)->not->toBeNull();
    });

    it('retorna inventory filtrado pela loja do device', function (): void {
        $otherStore = Store::factory()->create(['tenant_id' => $this->tenant->uuid]);

        InventoryBalance::factory()->create([
            'store_id'   => $this->store->uuid,
            'tenant_id'  => $this->tenant->uuid,
            'updated_at' => now()->subMinutes(5),
        ]);

        InventoryBalance::factory()->create([
            'store_id'   => $otherStore->uuid,
            'tenant_id'  => $this->tenant->uuid,
            'updated_at' => now()->subMinutes(5),
        ]);

        $response = $this->postJson('/api/v1/sync/pull', [
            'device_uuid'  => $this->device->device_uuid,
            'batch_id'     => Str::uuid()->toString(),
            'entity_types' => ['inventory'],
            'checkpoints'  => ['inventory' => now()->subHour()->toIso8601String()],
        ])->assertStatus(200)->json();

        expect($response['data']['data']['inventory']['entities'])->toHaveCount(1);
    });

    it('pull múltiplas entity_types em um request', function (): void {
        Product::factory()->create(['tenant_id' => $this->tenant->uuid]);
        Variant::factory()->create(['tenant_id' => $this->tenant->uuid]);

        $response = $this->postJson('/api/v1/sync/pull', [
            'device_uuid'  => $this->device->device_uuid,
            'batch_id'     => Str::uuid()->toString(),
            'entity_types' => ['product', 'product_variant'],
            'checkpoints'  => [
                'product'         => now()->subHours(2)->toIso8601String(),
                'product_variant' => now()->subHours(2)->toIso8601String(),
            ],
        ])->assertStatus(200)->json();

        expect($response['data']['data'])->toHaveKeys(['product', 'product_variant']);
    });

    it('rejeita device não registrado', function (): void {
        $this->postJson('/api/v1/sync/pull', [
            'device_uuid'  => Str::uuid()->toString(),
            'batch_id'     => Str::uuid()->toString(),
            'entity_types' => ['product'],
        ])->assertStatus(422);
    });
});
