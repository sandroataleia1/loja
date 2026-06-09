<?php

declare(strict_types=1);

use App\Core\Auth\Actions\AssignRoleAction;
use App\Core\Auth\Actions\GrantStoreAccessAction;
use App\Core\Auth\Actions\RevokeRoleAction;
use App\Core\Auth\Actions\RevokeStoreAccessAction;
use App\Core\Auth\DTOs\AssignRoleDTO;
use App\Core\Auth\DTOs\GrantStoreAccessDTO;
use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\Permission;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Models\TenantUser;
use App\Core\Auth\Models\TenantUserStore;
use App\Core\Auth\Models\User;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Inventory\Models\Store;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    TenantContext::set($this->tenant->uuid);
});

afterEach(fn () => TenantContext::clear());

// ── AssignRole ────────────────────────────────────────────────────────────────

it('assigns a role to a user and creates a tenant membership', function (): void {
    $role = Role::factory()->create();
    $user = User::factory()->create(['tenant_id' => $this->tenant->uuid]);

    $tenantUser = app(AssignRoleAction::class)->execute(new AssignRoleDTO(
        tenantId: $this->tenant->uuid,
        userId:   $user->uuid,
        roleId:   $role->uuid,
    ));

    expect($tenantUser)->toBeInstanceOf(TenantUser::class)
        ->and($tenantUser->role_id)->toBe($role->uuid)
        ->and($tenantUser->is_active)->toBeTrue();
});

it('replaces the role when user already has a membership', function (): void {
    $roleA = Role::factory()->create();
    $roleB = Role::factory()->create();
    $user  = User::factory()->create(['tenant_id' => $this->tenant->uuid]);

    $action = app(AssignRoleAction::class);
    $action->execute(new AssignRoleDTO($this->tenant->uuid, $user->uuid, $roleA->uuid));
    $updated = $action->execute(new AssignRoleDTO($this->tenant->uuid, $user->uuid, $roleB->uuid));

    expect(TenantUser::where('tenant_id', $this->tenant->uuid)->where('user_id', $user->uuid)->count())->toBe(1)
        ->and($updated->role_id)->toBe($roleB->uuid);
});

// ── Permissions ───────────────────────────────────────────────────────────────

it('user has permissions from assigned role', function (): void {
    $role = Role::factory()->withPermission(PermissionEnum::SalesDiscount)->create();
    $user = User::factory()->create(['tenant_id' => $this->tenant->uuid]);

    app(AssignRoleAction::class)->execute(new AssignRoleDTO($this->tenant->uuid, $user->uuid, $role->uuid));

    expect($user->hasPermission(PermissionEnum::SalesDiscount))->toBeTrue()
        ->and($user->hasPermission(PermissionEnum::FiscalCancel))->toBeFalse();
});

it('user without membership has no permissions', function (): void {
    $user = User::factory()->create(['tenant_id' => $this->tenant->uuid]);

    expect($user->hasPermission(PermissionEnum::SalesDiscount))->toBeFalse();
});

it('hasAnyPermission returns true if at least one matches', function (): void {
    $role = Role::factory()->withPermission(PermissionEnum::SalesDiscount)->create();
    $user = User::factory()->create(['tenant_id' => $this->tenant->uuid]);
    app(AssignRoleAction::class)->execute(new AssignRoleDTO($this->tenant->uuid, $user->uuid, $role->uuid));

    expect($user->hasAnyPermission(PermissionEnum::SalesCancel, PermissionEnum::SalesDiscount))->toBeTrue();
});

it('hasAllPermissions returns false if any is missing', function (): void {
    $role = Role::factory()->withPermission(PermissionEnum::SalesDiscount)->create();
    $user = User::factory()->create(['tenant_id' => $this->tenant->uuid]);
    app(AssignRoleAction::class)->execute(new AssignRoleDTO($this->tenant->uuid, $user->uuid, $role->uuid));

    expect($user->hasAllPermissions(PermissionEnum::SalesDiscount, PermissionEnum::SalesCancel))->toBeFalse();
});

// ── RevokeRole ────────────────────────────────────────────────────────────────

it('revoke marks membership inactive but preserves record', function (): void {
    $role = Role::factory()->create();
    $user = User::factory()->create(['tenant_id' => $this->tenant->uuid]);
    app(AssignRoleAction::class)->execute(new AssignRoleDTO($this->tenant->uuid, $user->uuid, $role->uuid));

    app(RevokeRoleAction::class)->execute($this->tenant->uuid, $user->uuid);

    $membership = TenantUser::where('user_id', $user->uuid)->first();
    expect($membership)->not->toBeNull()
        ->and($membership->is_active)->toBeFalse();

    // Permissions cleared after revoke
    expect($user->hasPermission(PermissionEnum::SalesDiscount))->toBeFalse();
});

// ── Store access ──────────────────────────────────────────────────────────────

it('user with no store access has unrestricted access', function (): void {
    $role = Role::factory()->create();
    $user = User::factory()->create(['tenant_id' => $this->tenant->uuid]);
    app(AssignRoleAction::class)->execute(new AssignRoleDTO($this->tenant->uuid, $user->uuid, $role->uuid));

    // No store restrictions = can access any store
    expect($user->canAccessStore('any-store-uuid-here'))->toBeTrue();
});

it('user with store allowlist can only access listed stores', function (): void {
    $role       = Role::factory()->create();
    $user       = User::factory()->create(['tenant_id' => $this->tenant->uuid]);
    $tenantUser = app(AssignRoleAction::class)->execute(
        new AssignRoleDTO($this->tenant->uuid, $user->uuid, $role->uuid),
    );

    $allowedStore   = Store::factory()->create(['tenant_id' => $this->tenant->uuid]);
    $forbiddenStore = Store::factory()->create(['tenant_id' => $this->tenant->uuid]);

    TenantUserStore::create([
        'tenant_user_id' => $tenantUser->uuid,
        'store_id'       => $allowedStore->uuid,
    ]);

    expect($user->canAccessStore($allowedStore->uuid))->toBeTrue()
        ->and($user->canAccessStore($forbiddenStore->uuid))->toBeFalse();
});

it('revoking all store access restores unrestricted access', function (): void {
    $role       = Role::factory()->create();
    $user       = User::factory()->create(['tenant_id' => $this->tenant->uuid]);
    $tenantUser = app(AssignRoleAction::class)->execute(
        new AssignRoleDTO($this->tenant->uuid, $user->uuid, $role->uuid),
    );

    $store        = Store::factory()->create(['tenant_id' => $this->tenant->uuid]);
    $otherStore   = Store::factory()->create(['tenant_id' => $this->tenant->uuid]);
    TenantUserStore::create(['tenant_user_id' => $tenantUser->uuid, 'store_id' => $store->uuid]);

    expect($user->canAccessStore($store->uuid))->toBeTrue();
    expect($user->canAccessStore($otherStore->uuid))->toBeFalse();

    // Remove the restriction
    TenantUserStore::where('tenant_user_id', $tenantUser->uuid)->delete();
    app(\App\Core\Auth\Services\PermissionCache::class)->invalidateUser($user->uuid, $this->tenant->uuid);

    expect($user->canAccessStore($otherStore->uuid))->toBeTrue();
});
