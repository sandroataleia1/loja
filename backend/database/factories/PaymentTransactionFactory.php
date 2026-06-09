<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Sales\Enums\PaymentMethodEnum;
use App\Modules\Sales\Enums\PaymentStatusEnum;
use App\Modules\Sales\Models\PaymentTransaction;
use App\Modules\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentTransaction>
 */
final class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        return [
            'sale_id'      => Sale::factory(),
            'method'       => PaymentMethodEnum::Cash,
            'amount_cents' => $this->faker->numberBetween(1000, 50000),
            'status'       => PaymentStatusEnum::Paid,
            'paid_at'      => now(),
        ];
    }

    public function paid(): static
    {
        return $this->state(['status' => PaymentStatusEnum::Paid, 'paid_at' => now()]);
    }

    public function pending(): static
    {
        return $this->state(['status' => PaymentStatusEnum::Pending, 'paid_at' => null]);
    }

    public function forSale(Sale $sale): static
    {
        return $this->state([
            'sale_id'      => $sale->uuid,
            'amount_cents' => $sale->total_cents,
        ]);
    }
}
