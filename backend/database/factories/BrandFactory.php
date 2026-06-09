<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Catalog\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brand>
 */
final class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name'      => $name,
            'slug'      => Str::slug($name) . '-' . Str::random(4),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
