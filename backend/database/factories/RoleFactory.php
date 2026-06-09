<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Enums\RoleSlugEnum;
use App\Core\Auth\Models\Permission;
use App\Core\Auth\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
final class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'name'      => $this->faker->unique()->words(2, true),
            'slug'      => $this->faker->unique()->slug(),
            'is_system' => false,
            'is_active' => true,
        ];
    }

    public function system(RoleSlugEnum $slug = RoleSlugEnum::Cashier): static
    {
        return $this->state([
            'tenant_id' => null,
            'slug'      => $slug->value,
            'name'      => $slug->label(),
            'is_system' => true,
        ]);
    }

    public function withPermission(PermissionEnum $permission): static
    {
        return $this->afterCreating(function (Role $role) use ($permission): void {
            $perm = Permission::firstOrCreate(
                ['slug' => $permission->value],
                [
                    'name'      => $permission->label(),
                    'module'    => $permission->module(),
                    'is_system' => true,
                ],
            );
            $role->permissions()->attach($perm->uuid);
        });
    }

    public function withAllPermissions(): static
    {
        return $this->afterCreating(function (Role $role): void {
            foreach (PermissionEnum::cases() as $enum) {
                $perm = Permission::firstOrCreate(
                    ['slug' => $enum->value],
                    ['name' => $enum->label(), 'module' => $enum->module(), 'is_system' => true],
                );
                $role->permissions()->syncWithoutDetaching([$perm->uuid]);
            }
        });
    }
}
