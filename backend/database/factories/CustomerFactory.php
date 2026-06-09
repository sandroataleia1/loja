<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Customers\Enums\PersonTypeEnum;
use App\Modules\Customers\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
final class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'person_type'         => PersonTypeEnum::Individual,
            'name'                => $this->faker->name(),
            'cpf'                 => null,
            'email'               => $this->faker->unique()->safeEmail(),
            'is_default_consumer' => false,
            'is_active'           => true,
        ];
    }

    public function defaultConsumer(): static
    {
        return $this->state([
            'name'                => 'Consumidor Final',
            'cpf'                 => null,
            'email'               => null,
            'is_default_consumer' => true,
        ]);
    }

    public function withCpf(): static
    {
        return $this->state([
            'cpf' => $this->faker->numerify('###.###.###-##'),
        ]);
    }

    public function withDocument(string $document = null): static
    {
        return $this->state([
            'document' => $document ?? $this->faker->numerify('###.###.###-##'),
        ]);
    }

    public function company(): static
    {
        return $this->state([
            'person_type' => PersonTypeEnum::Company,
            'trade_name'  => $this->faker->company(),
        ]);
    }
}
