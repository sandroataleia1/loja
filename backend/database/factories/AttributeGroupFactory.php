<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Catalog\Enums\AttributeTypeEnum;
use App\Modules\Catalog\Models\AttributeGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AttributeGroup>
 */
final class AttributeGroupFactory extends Factory
{
    protected $model = AttributeGroup::class;

    public function definition(): array
    {
        $name = ucfirst($this->faker->word());

        return [
            'name'       => $name,
            'slug'       => Str::slug($name) . '-' . Str::random(4),
            'type'       => $this->faker->randomElement(AttributeTypeEnum::cases()),
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}
