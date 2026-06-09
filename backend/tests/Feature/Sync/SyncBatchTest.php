<?php

declare(strict_types=1);

use App\Modules\Inventory\Models\Store;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sync\Enums\SyncEntityTypeEnum;
use App\Modules\Sync\Enums\SyncOperationStatusEnum;
use App\Modules\Sync\Events\SyncBatchCompleted;
use App\Modules\Sync\Events\SyncBatchReceived;
use App\Modules\Sync\Events\SyncConflictDetected;
use App\Modules\Sync\Models\SyncDevice;
use App\Modules\Sync\Models\SyncOperation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->actingAsTenantUser();
    $this->store = Store::factory()->create(['tenant_id' => $this->tenant->uuid]);
    $this->device = SyncDevice::factory()->create([
        'store_id'  => $this->store->uuid,
        'tenant_id' => $this->tenant->uuid,
    ]);
});

describe('sync_batch — push de operações PDV', function (): void {
    it('processa batch com venda criada com sucesso', function (): void {
        Event::fake([SyncBatchReceived::class, SyncBatchCompleted::class]);

        $operationUuid = Str::uuid()->toString();
        $entityUuid    = Str::uuid()->toString();
        $batchId       = Str::uuid()->toString();

        $this->postJson('/api/v1/sync/push', [
            'device_uuid' => $this->device->device_uuid,
            'batch_id'    => $batchId,
            'operations'  => [[
                'operation_uuid'  => $operationUuid,
                'entity_type'     => 'sale',
                'entity_uuid'     => $entityUuid,
                'operation_type'  => 'create',
                'idempotency_key' => Str::uuid()->toString(),
                'payload'         => [
                    'store_id'      => $this->store->uuid,
                    'sales_channel' => 'pdv',
                    'items'         => [[
                        'sku_snapshot'     => 'SKU-001',
                        'name_snapshot'    => 'Produto Teste',
                        'quantity'         => 2,
                        'unit_price_cents' => 5000,
                    ]],
                ],
                'created_at' => now()->toIso8601String(),
            ]],
        ])->assertStatus(200)
            ->assertJsonPath('data.batch_id', $batchId)
            ->assertJsonPath('data.synced_count', 1)
            ->assertJsonPath('data.failed_count', 0)
            ->assertJsonPath('data.conflict_count', 0);

        Event::assertDispatched(SyncBatchReceived::class);
        Event::assertDispatched(SyncBatchCompleted::class);

        $this->assertDatabaseHas('sync_operations', [
            'tenant_id'  => $this->tenant->uuid,
            'batch_id'   => $batchId,
            'status'     => SyncOperationStatusEnum::Synced->value,
        ]);
    });

    it('retorna conflict para entidades backend-owned (product)', function (): void {
        Event::fake([SyncConflictDetected::class]);

        $idempotencyKey = Str::uuid()->toString();

        $this->postJson('/api/v1/sync/push', [
            'device_uuid' => $this->device->device_uuid,
            'batch_id'    => Str::uuid()->toString(),
            'operations'  => [[
                'operation_uuid'  => Str::uuid()->toString(),
                'entity_type'     => SyncEntityTypeEnum::Product->value,
                'entity_uuid'     => Str::uuid()->toString(),
                'operation_type'  => 'create',
                'idempotency_key' => $idempotencyKey,
                'payload'         => ['name' => 'Produto Inválido'],
                'created_at'      => now()->toIso8601String(),
            ]],
        ])->assertStatus(200)
            ->assertJsonPath('data.conflict_count', 1)
            ->assertJsonPath('data.synced_count', 0);

        Event::assertDispatched(SyncConflictDetected::class);
    });

    it('é idempotente: reenvio do mesmo idempotency_key não cria duplicata', function (): void {
        $idempotencyKey = Str::uuid()->toString();
        $entityUuid     = Str::uuid()->toString();

        $payload = [
            'device_uuid' => $this->device->device_uuid,
            'batch_id'    => Str::uuid()->toString(),
            'operations'  => [[
                'operation_uuid'  => Str::uuid()->toString(),
                'entity_type'     => 'sale',
                'entity_uuid'     => $entityUuid,
                'operation_type'  => 'create',
                'idempotency_key' => $idempotencyKey,
                'payload'         => [
                    'store_id'     => $this->store->uuid,
                    'items'        => [[
                        'sku_snapshot'     => 'SKU-IDM',
                        'name_snapshot'    => 'Idempotência',
                        'quantity'         => 1,
                        'unit_price_cents' => 10000,
                    ]],
                ],
                'created_at' => now()->toIso8601String(),
            ]],
        ];

        $this->postJson('/api/v1/sync/push', $payload)->assertStatus(200);
        $payload['batch_id'] = Str::uuid()->toString();
        $this->postJson('/api/v1/sync/push', $payload)->assertStatus(200);

        expect(SyncOperation::where('idempotency_key', $idempotencyKey)->count())->toBe(1);
        expect(Sale::where('sync_uuid', $entityUuid)->count())->toBe(1);
    });

    it('rejeita device_uuid de dispositivo inativo', function (): void {
        $this->device->update(['is_active' => false]);

        $this->postJson('/api/v1/sync/push', [
            'device_uuid' => $this->device->device_uuid,
            'batch_id'    => Str::uuid()->toString(),
            'operations'  => [[
                'operation_uuid'  => Str::uuid()->toString(),
                'entity_type'     => 'sale',
                'entity_uuid'     => Str::uuid()->toString(),
                'operation_type'  => 'create',
                'idempotency_key' => Str::uuid()->toString(),
                'payload'         => [],
                'created_at'      => now()->toIso8601String(),
            ]],
        ])->assertStatus(422);
    });

    it('rejeita batch com device_uuid não registrado', function (): void {
        $this->postJson('/api/v1/sync/push', [
            'device_uuid' => Str::uuid()->toString(),
            'batch_id'    => Str::uuid()->toString(),
            'operations'  => [[
                'operation_uuid'  => Str::uuid()->toString(),
                'entity_type'     => 'sale',
                'entity_uuid'     => Str::uuid()->toString(),
                'operation_type'  => 'create',
                'idempotency_key' => Str::uuid()->toString(),
                'payload'         => [],
                'created_at'      => now()->toIso8601String(),
            ]],
        ])->assertStatus(422);
    });

    it('processa múltiplas operações no mesmo batch', function (): void {
        $operations = [];
        for ($i = 0; $i < 5; $i++) {
            $operations[] = [
                'operation_uuid'  => Str::uuid()->toString(),
                'entity_type'     => 'sale',
                'entity_uuid'     => Str::uuid()->toString(),
                'operation_type'  => 'create',
                'idempotency_key' => Str::uuid()->toString(),
                'payload'         => [
                    'store_id' => $this->store->uuid,
                    'items'    => [[
                        'sku_snapshot'     => "SKU-{$i}",
                        'name_snapshot'    => "Produto {$i}",
                        'quantity'         => 1,
                        'unit_price_cents' => 1000,
                    ]],
                ],
                'created_at' => now()->toIso8601String(),
            ];
        }

        $this->postJson('/api/v1/sync/push', [
            'device_uuid' => $this->device->device_uuid,
            'batch_id'    => Str::uuid()->toString(),
            'operations'  => $operations,
        ])->assertStatus(200)
            ->assertJsonPath('data.operation_count', 5)
            ->assertJsonPath('data.synced_count', 5);
    });
});
