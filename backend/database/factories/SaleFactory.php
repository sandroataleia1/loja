<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Inventory\Models\Store;
use App\Modules\Sales\Enums\SaleStatusEnum;
use App\Modules\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
final class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(5000, 500000);

        return [
            'store_id'             => Store::factory(),
            'status'               => SaleStatusEnum::Draft,
            'subtotal_cents'       => $subtotal,
            'discount_total_cents' => 0,
            'tax_total_cents'      => 0,
            'total_cents'          => $subtotal,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => SaleStatusEnum::Draft]);
    }

    public function completed(): static
    {
        return $this->state([
            'status'       => SaleStatusEnum::Completed,
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status'       => SaleStatusEnum::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function withTotal(int $totalCents): static
    {
        return $this->state([
            'subtotal_cents' => $totalCents,
            'total_cents'    => $totalCents,
        ]);
    }
}
