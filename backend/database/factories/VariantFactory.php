<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Variant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Variant>
 */
final class VariantFactory extends Factory
{
    protected $model = Variant::class;

    public function definition(): array
    {
        return [
            'product_id'  => Product::factory(),
            'sku'         => strtoupper(Str::random(3) . '-' . rand(100, 999)),
            'price_cents' => $this->faker->numberBetween(1990, 49990),
            'cost_cents'  => $this->faker->numberBetween(500, 20000),
            'is_active'   => true,
            'is_default'  => false,
            'sort_order'  => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
