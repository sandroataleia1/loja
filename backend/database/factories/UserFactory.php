<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Auth\Models\User;
use App\Core\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    protected $model = User::class;

    private static ?string $password = null;

    public function definition(): array
    {
        return [
            'tenant_id'         => Tenant::factory(),
            'name'              => $this->faker->name(),
            'email'             => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => self::$password ??= Hash::make('password'),
            'is_active'         => true,
            'last_login_at'     => null,
            'remember_token'    => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(['tenant_id' => $tenant->uuid]);
    }
}
