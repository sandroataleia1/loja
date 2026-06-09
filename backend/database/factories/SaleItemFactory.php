<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Catalog\Models\Variant;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
final class SaleItemFactory extends Factory
{
    protected $model = SaleItem::class;

    public function definition(): array
    {
        $qty      = $this->faker->numberBetween(1, 5);
        $price    = $this->faker->numberBetween(1000, 50000);
        $subtotal = $qty * $price;

        return [
            'sale_id'               => Sale::factory(),
            'product_variant_id'    => Variant::factory(),
            'sku_snapshot'          => strtoupper($this->faker->bothify('SKU-####')),
            'name_snapshot'         => $this->faker->words(3, true),
            'attributes_snapshot'   => [],
            'quantity'              => $qty,
            'unit_price_cents'      => $price,
            'cost_price_cents'      => (int) ($price * 0.6),
            'discount_amount_cents' => 0,
            'subtotal_cents'        => $subtotal,
            'total_cents'           => $subtotal,
        ];
    }
}
