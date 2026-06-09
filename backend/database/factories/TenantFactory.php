<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Tenancy\Models\Tenant;
use App\Shared\Enums\PlanEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
final class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name'          => $name,
            'slug'          => Str::slug($name).'-'.Str::random(4),
            'plan'          => $this->faker->randomElement(PlanEnum::cases()),
            'is_active'     => true,
            'settings'      => null,
            'trial_ends_at' => now()->addDays(14),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function plan(PlanEnum $plan): static
    {
        return $this->state(['plan' => $plan]);
    }
}
