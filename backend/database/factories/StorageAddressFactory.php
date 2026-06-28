<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Catalog\Models\StorageAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

final class StorageAddressFactory extends Factory
{
    protected $model = StorageAddress::class;

    public function definition(): array
    {
        return [
            'aisle'     => $this->faker->randomLetter(),
            'rack'      => str_pad((string) $this->faker->numberBetween(1, 20), 2, '0', STR_PAD_LEFT),
            'shelf'     => $this->faker->randomLetter(),
            'position'  => str_pad((string) $this->faker->numberBetween(1, 10), 2, '0', STR_PAD_LEFT),
            'is_active' => true,
        ];
    }
}
