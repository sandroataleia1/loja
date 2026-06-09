<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Auth\Models\User;
use App\Modules\Sales\Enums\CashMovementTypeEnum;
use App\Modules\Sales\Models\CashMovement;
use App\Modules\Sales\Models\CashRegisterSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashMovement>
 */
final class CashMovementFactory extends Factory
{
    protected $model = CashMovement::class;

    public function definition(): array
    {
        $session = CashRegisterSession::factory()->create();

        return [
            'store_id'                 => $session->store_id,
            'cash_register_session_id' => $session->uuid,
            'type'                     => CashMovementTypeEnum::Supply,
            'amount_cents'             => $this->faker->numberBetween(100, 50000),
            'description'              => $this->faker->optional()->sentence(),
            'created_by'               => User::factory(),
        ];
    }

    public function withdrawal(): static
    {
        return $this->state(['type' => CashMovementTypeEnum::Withdrawal]);
    }

    public function supply(): static
    {
        return $this->state(['type' => CashMovementTypeEnum::Supply]);
    }

    public function sale(): static
    {
        return $this->state(['type' => CashMovementTypeEnum::Sale]);
    }
}
