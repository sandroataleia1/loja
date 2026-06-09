<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Fiscal\Enums\TaxRegimeEnum;
use App\Modules\Fiscal\Models\TaxProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxProfile>
 */
final class TaxProfileFactory extends Factory
{
    protected $model = TaxProfile::class;

    public function definition(): array
    {
        return [
            'name'     => $this->faker->words(2, true),
            'regime'   => TaxRegimeEnum::SimplesNacional,
            'metadata' => [],
        ];
    }

    public function simplesNacional(): static
    {
        return $this->state(['regime' => TaxRegimeEnum::SimplesNacional]);
    }

    public function lucroPresumido(): static
    {
        return $this->state(['regime' => TaxRegimeEnum::LucroPresumido]);
    }
}
