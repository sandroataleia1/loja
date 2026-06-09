<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Inventory\Enums\TransferStatusEnum;
use App\Modules\Inventory\Models\Store;
use App\Modules\Inventory\Models\StockTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransfer>
 */
final class StockTransferFactory extends Factory
{
    protected $model = StockTransfer::class;

    public function definition(): array
    {
        return [
            'origin_store_id'      => Store::factory(),
            'destination_store_id' => Store::factory(),
            'status'               => TransferStatusEnum::Pending,
            'notes'                => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => TransferStatusEnum::Pending]);
    }

    public function inTransit(): static
    {
        return $this->state([
            'status'        => TransferStatusEnum::InTransit,
            'dispatched_at' => now(),
        ]);
    }

    public function received(): static
    {
        return $this->state([
            'status'      => TransferStatusEnum::Received,
            'received_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status'       => TransferStatusEnum::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
