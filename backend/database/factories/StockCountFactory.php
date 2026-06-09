<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Inventory\Enums\StockCountStatusEnum;
use App\Modules\Inventory\Models\Store;
use App\Modules\Inventory\Models\StockCount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockCount>
 */
final class StockCountFactory extends Factory
{
    protected $model = StockCount::class;

    public function definition(): array
    {
        return [
            'store_id'   => Store::factory(),
            'status'     => StockCountStatusEnum::InProgress,
            'started_at' => now(),
            'notes'      => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => StockCountStatusEnum::Draft, 'started_at' => null]);
    }

    public function inProgress(): static
    {
        return $this->state(['status' => StockCountStatusEnum::InProgress, 'started_at' => now()]);
    }

    public function committed(): static
    {
        return $this->state([
            'status'       => StockCountStatusEnum::Committed,
            'committed_at' => now(),
        ]);
    }
}
