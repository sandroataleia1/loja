<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Finance\Enums\FinancialEntryStatusEnum;
use App\Modules\Finance\Enums\FinancialEntryTypeEnum;
use App\Modules\Finance\Models\FinancialEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialEntry>
 */
final class FinancialEntryFactory extends Factory
{
    protected $model = FinancialEntry::class;

    public function definition(): array
    {
        return [
            'store_id'             => null,
            'type'                 => FinancialEntryTypeEnum::Income,
            'category_id'          => null,
            'financial_account_id' => null,
            'amount_cents'         => $this->faker->numberBetween(100, 100000),
            'due_date'             => now()->addDays($this->faker->numberBetween(0, 30)),
            'paid_at'              => null,
            'status'               => FinancialEntryStatusEnum::Pending,
            'description'          => $this->faker->sentence(),
            'reference_type'       => null,
            'reference_id'         => null,
            'external_reference'   => null,
            'reconciliation_status' => null,
        ];
    }

    public function income(): static
    {
        return $this->state(['type' => FinancialEntryTypeEnum::Income]);
    }

    public function expense(): static
    {
        return $this->state(['type' => FinancialEntryTypeEnum::Expense]);
    }

    public function paid(): static
    {
        return $this->state([
            'status'  => FinancialEntryStatusEnum::Paid,
            'paid_at' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state([
            'status'   => FinancialEntryStatusEnum::Overdue,
            'due_date' => now()->subDays($this->faker->numberBetween(1, 30)),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => FinancialEntryStatusEnum::Cancelled]);
    }
}
