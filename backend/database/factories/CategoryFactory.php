<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
final class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'name'      => ucfirst($name),
            'slug'      => Str::slug($name) . '-' . Str::random(4),
            'is_active' => true,
            'parent_id' => null,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function child(Category $parent): static
    {
        return $this->state(['parent_id' => $parent->uuid]);
    }
}
