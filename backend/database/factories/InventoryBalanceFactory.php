<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Catalog\Models\Variant;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryBalance>
 */
final class InventoryBalanceFactory extends Factory
{
    protected $model = InventoryBalance::class;

    public function definition(): array
    {
        return [
            'store_id'          => Store::factory(),
            'variant_id'        => Variant::factory(),
            'quantity'          => $this->faker->numberBetween(0, 500),
            'reserved_quantity' => 0,
        ];
    }
}
