<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Catalog\Enums\PriceListTypeEnum;
use App\Modules\Catalog\Models\PriceList;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PriceList>
 */
final class PriceListFactory extends Factory
{
    protected $model = PriceList::class;

    public function definition(): array
    {
        return [
            'name'                 => $this->faker->words(2, true),
            'code'                 => strtoupper(Str::random(6)),
            'type'                 => PriceListTypeEnum::Retail,
            'currency'             => 'BRL',
            'max_discount_percent' => 0,
            'is_default'           => false,
            'is_active'            => true,
        ];
    }
}
