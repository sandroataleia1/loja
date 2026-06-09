<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Purchasing\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

final class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'person_type' => 'COMPANY',
            'name'        => $this->faker->company(),
            'document'    => $this->faker->numerify('##.###.###/####-##'),
            'email'       => $this->faker->companyEmail(),
            'phone'       => $this->faker->phoneNumber(),
            'is_active'   => true,
        ];
    }
}
