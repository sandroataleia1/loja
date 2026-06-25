<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Auth\Models\User;
use App\Modules\Sellers\Enums\SellerTypeEnum;
use App\Modules\Sellers\Models\SellerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

final class SellerProfileFactory extends Factory
{
    protected $model = SellerProfile::class;

    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'nickname'        => $this->faker->firstName(),
            'seller_type'     => SellerTypeEnum::Internal,
            'commission_rate' => $this->faker->randomFloat(2, 1, 10),
            'is_active'       => true,
        ];
    }
}
