<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Carriers\Enums\DeliveryModeEnum;
use App\Modules\Carriers\Models\Carrier;
use Illuminate\Database\Eloquent\Factories\Factory;

final class CarrierFactory extends Factory
{
    protected $model = Carrier::class;

    public function definition(): array
    {
        return [
            'name'          => $this->faker->company() . ' Transportes',
            'trade_name'    => $this->faker->company(),
            'cnpj'          => $this->faker->numerify('##.###.###/####-##'),
            'email'         => $this->faker->companyEmail(),
            'phone'         => $this->faker->phoneNumber(),
            'is_active'     => true,
            'delivery_mode' => DeliveryModeEnum::OwnFleet,
        ];
    }
}
