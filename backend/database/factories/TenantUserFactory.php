<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Auth\Models\Role;
use App\Core\Auth\Models\TenantUser;
use App\Core\Auth\Models\User;
use App\Core\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantUser>
 */
final class TenantUserFactory extends Factory
{
    protected $model = TenantUser::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id'   => User::factory(),
            'role_id'   => Role::factory(),
            'is_active' => true,
            'joined_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
