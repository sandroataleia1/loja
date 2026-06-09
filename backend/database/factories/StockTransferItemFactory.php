<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Catalog\Models\Variant;
use App\Modules\Inventory\Models\StockTransferItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransferItem>
 */
final class StockTransferItemFactory extends Factory
{
    protected $model = StockTransferItem::class;

    public function definition(): array
    {
        return [
            'variant_id'         => Variant::factory(),
            'quantity_requested' => $this->faker->numberBetween(1, 50),
            'quantity_sent'      => null,
            'quantity_received'  => null,
        ];
    }

    public function sent(int $quantity): static
    {
        return $this->state(['quantity_sent' => $quantity]);
    }

    public function received(int $quantity): static
    {
        return $this->state(['quantity_received' => $quantity]);
    }
}
