<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Finance\Enums\FinancialInstallmentStatusEnum;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Finance\Models\FinancialInstallment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialInstallment>
 */
final class FinancialInstallmentFactory extends Factory
{
    protected $model = FinancialInstallment::class;

    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        return [
            'financial_entry_id' => FinancialEntry::factory(),
            'installment_number' => $seq,
            'due_date'           => now()->addMonths($seq),
            'amount_cents'       => $this->faker->numberBetween(1000, 10000),
            'status'             => FinancialInstallmentStatusEnum::Pending,
            'paid_at'            => null,
        ];
    }

    public function paid(): static
    {
        return $this->state([
            'status'  => FinancialInstallmentStatusEnum::Paid,
            'paid_at' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state([
            'status'   => FinancialInstallmentStatusEnum::Overdue,
            'due_date' => now()->subDays($this->faker->numberBetween(1, 30)),
        ]);
    }
}
