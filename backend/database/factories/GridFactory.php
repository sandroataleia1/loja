<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Catalog\Models\AttributeGroup;
use App\Modules\Catalog\Models\Grid;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grid>
 */
final class GridFactory extends Factory
{
    protected $model = Grid::class;

    public function definition(): array
    {
        return [
            'attribute_group_id' => AttributeGroup::factory(),
            'name'               => $this->faker->words(2, true),
            'description'        => null,
        ];
    }
}
