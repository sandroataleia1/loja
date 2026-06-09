<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Purchasing\Enums\PurchaseOrderStatusEnum;
use App\Modules\Purchasing\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

final class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'status'     => PurchaseOrderStatusEnum::Draft,
            'order_date' => today(),
            'subtotal'   => 0,
            'discount'   => 0,
            'total'      => 0,
        ];
    }
}
