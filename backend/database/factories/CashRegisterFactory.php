<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Inventory\Models\Store;
use App\Modules\Sales\Models\CashRegister;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashRegister>
 */
final class CashRegisterFactory extends Factory
{
    protected $model = CashRegister::class;

    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        return [
            'store_id'  => Store::factory(),
            'code'      => 'CAIXA-' . str_pad((string) $seq, 2, '0', STR_PAD_LEFT),
            'name'      => 'Caixa ' . $seq,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
