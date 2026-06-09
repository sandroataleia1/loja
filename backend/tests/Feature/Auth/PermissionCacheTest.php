<?php

declare(strict_types=1);

use App\Core\Auth\Actions\AssignRoleAction;
use App\Core\Auth\DTOs\AssignRoleDTO;
use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\Permission;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Models\User;
use App\Core\Auth\Services\PermissionCache;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    TenantContext::set($this->tenant->uuid);
    Cache::flush();
});

afterEach(function (): void {
    Cache::flush();
    TenantContext::clear();
});

it('caches permissions after first load', function (): void {
    $role = Role::factory()->withPermission(PermissionEnum::SalesDiscount)->create();
    $user = User::factory()->create(['tenant_id' => $this->tenant->uuid]);

    app(AssignRoleAction::class)->execute(new AssignRoleDTO($this->tenant->uuid, $user->uuid, $role->uuid));

    $cache = app(PermissionCache::class);

    // First call hits DB
    $perms1 = $cache->getPermissions($user->uuid, $this->tenant->uuid);

    // Second call should come from cache (same result)
    $perms2 = $cache->getPermissions($user->uuid, $this->tenant->uuid);

    expect($perms1)->toBe($perms2)
        ->and($perms1)->toContain(PermissionEnum::SalesDiscount->value);
});

it('invalidation clears cached permissions', function (): void {
    $role = Role::factory()->withPermission(PermissionEnum::SalesDiscount)->create();
    $user = User::factory()->create(['tenant_id' => $this->tenant->uuid]);

    app(AssignRoleAction::class)->execute(new AssignRoleDTO($this->tenant->uuid, $user->uuid, $role->uuid));

    $cache = app(PermissionCache::class);
    $cache->getPermissions($user->uuid, $this->tenant->uuid); // warm cache

    // Add a new permission to the role
    $newPerm = Permission::firstOrCreate(
        ['slug' => PermissionEnum::SalesCancel->value],
        ['name' => PermissionEnum::SalesCancel->label(), 'module' => PermissionEnum::SalesCancel->module()],
    );
    $role->permissions()->attach($newPerm->uuid);

    // Cache still returns old value before invalidation
    $stale = $cache->getPermissions($user->uuid, $this->tenant->uuid);
    expect($stale)->not->toContain(PermissionEnum::SalesCancel->value);

    // After invalidation, fresh value is loaded
    $cache->invalidateUser($user->uuid, $this->tenant->uuid);
    $fresh = $cache->getPermissions($user->uuid, $this->tenant->uuid);
    expect($fresh)->toContain(PermissionEnum::SalesCancel->value);
});

it('returns empty permissions for user without membership', function (): void {
    $user  = User::factory()->create(['tenant_id' => $this->tenant->uuid]);
    $cache = app(PermissionCache::class);

    expect($cache->getPermissions($user->uuid, $this->tenant->uuid))->toBe([]);
});

it('returns null stores (unrestricted) for user with no store restrictions', function (): void {
    $role = Role::factory()->create();
    $user = User::factory()->create(['tenant_id' => $this->tenant->uuid]);
    app(AssignRoleAction::class)->execute(new AssignRoleDTO($this->tenant->uuid, $user->uuid, $role->uuid));

    $cache  = app(PermissionCache::class);
    $stores = $cache->getAllowedStores($user->uuid, $this->tenant->uuid);

    expect($stores)->toBeNull(); // null = unrestricted
});
