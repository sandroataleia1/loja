<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Sync\Enums\SyncEntityTypeEnum;
use App\Modules\Sync\Enums\SyncOperationStatusEnum;
use App\Modules\Sync\Enums\SyncOperationTypeEnum;
use App\Modules\Sync\Models\SyncDevice;
use App\Modules\Sync\Models\SyncOperation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SyncOperation>
 */
final class SyncOperationFactory extends Factory
{
    protected $model = SyncOperation::class;

    public function definition(): array
    {
        $device = SyncDevice::factory()->create();

        return [
            'operation_uuid'  => Str::uuid()->toString(),
            'store_id'        => $device->store_id,
            'device_id'       => $device->uuid,
            'entity_type'     => SyncEntityTypeEnum::Sale,
            'entity_uuid'     => Str::uuid()->toString(),
            'operation_type'  => SyncOperationTypeEnum::Create,
            'batch_id'        => Str::uuid()->toString(),
            'payload'         => ['store_id' => $device->store_id, 'items' => []],
            'status'          => SyncOperationStatusEnum::Pending,
            'idempotency_key' => Str::uuid()->toString(),
            'retry_count'     => 0,
            'created_at'      => now(),
            'received_at'     => now(),
        ];
    }

    public function synced(): static
    {
        return $this->state([
            'status'       => SyncOperationStatusEnum::Synced,
            'processed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status'       => SyncOperationStatusEnum::Failed,
            'last_error'   => 'Test failure',
            'retry_count'  => 1,
            'processed_at' => now(),
        ]);
    }

    public function conflict(): static
    {
        return $this->state([
            'status'        => SyncOperationStatusEnum::Conflict,
            'entity_type'   => SyncEntityTypeEnum::Product,
            'processed_at'  => now(),
        ]);
    }
}
