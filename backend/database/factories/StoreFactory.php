<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Inventory\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Store>
 */
final class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        return [
            'name'         => $this->faker->company() . ' - ' . $this->faker->city(),
            'code'         => strtoupper($this->faker->bothify('??##')),
            'phone'        => $this->faker->phoneNumber(),
            'email'        => $this->faker->companyEmail(),
            'is_active'    => true,
            'is_ecommerce' => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function ecommerce(): static
    {
        return $this->state(['is_ecommerce' => true]);
    }
}
