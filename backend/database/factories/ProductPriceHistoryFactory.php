<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductPriceHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPriceHistory>
 */
final class ProductPriceHistoryFactory extends Factory
{
    protected $model = ProductPriceHistory::class;

    public function definition(): array
    {
        $oldPrice = $this->faker->numberBetween(5000, 100000);
        $newPrice = (int) ($oldPrice * $this->faker->randomFloat(2, 0.7, 1.5));

        return [
            'product_id'      => Product::factory(),
            'variant_id'      => null,
            'old_price_cents' => $oldPrice,
            'new_price_cents' => $newPrice,
            'changed_by'      => null,
            'changed_at'      => now()->subDays($this->faker->numberBetween(1, 365)),
            'reason'          => null,
        ];
    }

    public function priceIncrease(): static
    {
        return $this->state(fn ($attrs) => [
            'new_price_cents' => (int) ($attrs['old_price_cents'] * 1.2),
        ]);
    }

    public function priceDecrease(): static
    {
        return $this->state(fn ($attrs) => [
            'new_price_cents' => (int) ($attrs['old_price_cents'] * 0.8),
        ]);
    }

    public function withReason(string $reason): static
    {
        return $this->state(['reason' => $reason]);
    }
}
