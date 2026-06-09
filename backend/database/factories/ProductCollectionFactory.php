<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Catalog\Models\ProductCollection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductCollection>
 */
final class ProductCollectionFactory extends Factory
{
    protected $model = ProductCollection::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'name'        => ucwords($name),
            'slug'        => Str::slug($name) . '-' . Str::random(4),
            'description' => $this->faker->optional()->sentence(),
            'is_active'   => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
