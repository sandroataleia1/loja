<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Auth\Models\User;
use App\Modules\Inventory\Models\Store;
use App\Modules\Sales\Enums\CashRegisterSessionStatusEnum;
use App\Modules\Sales\Models\CashRegister;
use App\Modules\Sales\Models\CashRegisterSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashRegisterSession>
 */
final class CashRegisterSessionFactory extends Factory
{
    protected $model = CashRegisterSession::class;

    public function definition(): array
    {
        $store = Store::factory()->create();

        return [
            'store_id'             => $store->uuid,
            'cash_register_id'     => null,
            'user_id'              => User::factory(),
            'closed_by'            => null,
            'status'               => CashRegisterSessionStatusEnum::Open,
            'opening_amount_cents' => $this->faker->numberBetween(0, 50000),
            'closing_amount_cents' => null,
            'expected_balance_cents'  => null,
            'difference_amount_cents' => null,
            'difference_reason'    => null,
            'opened_at'            => now(),
            'closed_at'            => null,
        ];
    }

    public function withRegister(): static
    {
        return $this->state(function (array $attrs): array {
            $register = CashRegister::factory()->create(['store_id' => $attrs['store_id']]);

            return ['cash_register_id' => $register->uuid];
        });
    }

    public function open(): static
    {
        return $this->state([
            'status'    => CashRegisterSessionStatusEnum::Open,
            'closed_at' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (): array => [
            'status'                  => CashRegisterSessionStatusEnum::Closed,
            'closing_amount_cents'    => $this->faker->numberBetween(10000, 100000),
            'expected_balance_cents'  => $this->faker->numberBetween(10000, 100000),
            'difference_amount_cents' => $this->faker->numberBetween(-5000, 5000),
            'closed_at'               => now(),
        ]);
    }
}
