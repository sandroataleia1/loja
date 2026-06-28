<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\CustomerAuthorizedBuyer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerAuthorizedBuyer>
 */
final class CustomerAuthorizedBuyerFactory extends Factory
{
    protected $model = CustomerAuthorizedBuyer::class;

    public function definition(): array
    {
        $customer = Customer::factory()->create();

        return [
            'customer_id'   => $customer->uuid,
            'name'          => $this->faker->name(),
            'cpf'           => null,
            'rg'            => null,
            'phone'         => $this->faker->phoneNumber(),
            'relationship'  => $this->faker->randomElement(['Filho', 'Esposa', 'Irmão']),
            'is_active'     => true,
            'authorized_at' => today()->toDateString(),
            'valid_until'   => null,
        ];
    }
}
