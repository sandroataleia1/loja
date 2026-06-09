<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Finance\Enums\FinancialAccountTypeEnum;
use App\Modules\Finance\Models\FinancialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialAccount>
 */
final class FinancialAccountFactory extends Factory
{
    protected $model = FinancialAccount::class;

    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        return [
            'store_id'              => null,
            'code'                  => 'ACC-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT),
            'name'                  => $this->faker->words(2, true),
            'type'                  => FinancialAccountTypeEnum::Cash,
            'is_active'             => true,
            'current_balance_cents' => 0,
        ];
    }

    public function bank(): static
    {
        return $this->state(['type' => FinancialAccountTypeEnum::Bank]);
    }

    public function digitalWallet(): static
    {
        return $this->state(['type' => FinancialAccountTypeEnum::DigitalWallet]);
    }

    public function withBalance(int $cents): static
    {
        return $this->state(['current_balance_cents' => $cents]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
