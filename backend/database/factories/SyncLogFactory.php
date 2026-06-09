<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Sync\Models\SyncDevice;
use App\Modules\Sync\Models\SyncLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SyncLog>
 */
final class SyncLogFactory extends Factory
{
    protected $model = SyncLog::class;

    public function definition(): array
    {
        return [
            'device_id'       => SyncDevice::factory(),
            'batch_id'        => Str::uuid()->toString(),
            'direction'       => $this->faker->randomElement(['push', 'pull']),
            'operation_count' => $this->faker->numberBetween(1, 50),
            'synced_count'    => 0,
            'failed_count'    => 0,
            'conflict_count'  => 0,
            'duration_ms'     => null,
            'created_at'      => now(),
        ];
    }

    public function push(): static
    {
        return $this->state(['direction' => 'push']);
    }

    public function pull(): static
    {
        return $this->state(['direction' => 'pull']);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attrs) => [
            'synced_count' => $attrs['operation_count'],
            'duration_ms'  => $this->faker->numberBetween(50, 2000),
            'completed_at' => now(),
        ]);
    }
}
