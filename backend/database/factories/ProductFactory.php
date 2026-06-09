<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Catalog\Enums\ProductGenderEnum;
use App\Modules\Catalog\Enums\ProductStatusEnum;
use App\Modules\Catalog\Enums\ProductTypeEnum;
use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'name'              => $name,
            'slug'              => Str::slug($name) . '-' . Str::random(4),
            'type'              => ProductTypeEnum::Simple,
            'gender'            => ProductGenderEnum::All,
            'status'            => ProductStatusEnum::Draft,
            'is_featured'       => false,
            'is_digital'        => false,
            'description'       => $this->faker->sentence(),
            'short_description' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => ProductStatusEnum::Active]);
    }

    public function variable(): static
    {
        return $this->state(['type' => ProductTypeEnum::Variable]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
