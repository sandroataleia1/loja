<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialTransfer>
 */
final class FinancialTransferFactory extends Factory
{
    protected $model = FinancialTransfer::class;

    public function definition(): array
    {
        return [
            'origin_account_id'      => FinancialAccount::factory(),
            'destination_account_id' => FinancialAccount::factory(),
            'amount_cents'           => $this->faker->numberBetween(100, 50000),
            'transferred_at'         => now(),
            'description'            => $this->faker->sentence(),
        ];
    }
}
