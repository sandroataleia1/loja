<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Inventory\Models\Store;
use App\Modules\Sync\Models\SyncDevice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SyncDevice>
 */
final class SyncDeviceFactory extends Factory
{
    protected $model = SyncDevice::class;

    public function definition(): array
    {
        return [
            'store_id'    => Store::factory(),
            'device_uuid' => Str::uuid()->toString(),
            'name'        => 'Caixa ' . $this->faker->numberBetween(1, 99) . ' - ' . $this->faker->city(),
            'platform'    => $this->faker->randomElement(['windows', 'android', 'ios']),
            'app_version' => $this->faker->semver(),
            'is_active'   => true,
            'last_seen_at' => now()->subMinutes($this->faker->numberBetween(0, 60)),
            'metadata'    => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function windows(): static
    {
        return $this->state(['platform' => 'windows']);
    }

    public function android(): static
    {
        return $this->state(['platform' => 'android']);
    }
}
