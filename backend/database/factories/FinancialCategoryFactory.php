<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Finance\Enums\FinancialCategoryTypeEnum;
use App\Modules\Finance\Models\FinancialCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialCategory>
 */
final class FinancialCategoryFactory extends Factory
{
    protected $model = FinancialCategory::class;

    public function definition(): array
    {
        return [
            'name'      => $this->faker->words(2, true),
            'type'      => FinancialCategoryTypeEnum::Income,
            'is_active' => true,
        ];
    }

    public function expense(): static
    {
        return $this->state(['type' => FinancialCategoryTypeEnum::Expense]);
    }

    public function income(): static
    {
        return $this->state(['type' => FinancialCategoryTypeEnum::Income]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
