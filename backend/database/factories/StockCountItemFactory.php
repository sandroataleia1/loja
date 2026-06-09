<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Catalog\Models\Variant;
use App\Modules\Inventory\Models\StockCountItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockCountItem>
 */
final class StockCountItemFactory extends Factory
{
    protected $model = StockCountItem::class;

    public function definition(): array
    {
        $system = $this->faker->numberBetween(0, 100);

        return [
            'variant_id'       => Variant::factory(),
            'system_quantity'  => $system,
            'counted_quantity' => null,
        ];
    }

    public function counted(?int $quantity = null): static
    {
        return $this->state(['counted_quantity' => $quantity ?? $this->faker->numberBetween(0, 100)]);
    }
}
