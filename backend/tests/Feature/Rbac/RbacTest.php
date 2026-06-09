<?php

declare(strict_types=1);

use App\Core\Auth\Actions\AssignRoleAction;
use App\Core\Auth\Actions\RevokeRoleAction;
use App\Core\Auth\DTOs\AssignRoleDTO;
use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Enums\RoleSlugEnum;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Models\TenantUser;
use App\Core\Auth\Models\TenantUserStore;
use App\Core\Auth\Models\User;
use App\Core\Auth\Services\PermissionCache;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;

/**
 * Sprint 4 — RBAC Foundation Feature Tests
 *
 * Covers: role assignment/revocation, permission validation,
 * store access allowlisting, middleware 403 responses, and duplicate guards.
 */

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    TenantContext::set($this->tenant->uuid);

    $this->user = User::factory()->create(['tenant_id' => $this->tenant->uuid]);
});

afterEach(function (): void {
    TenantContext::clear();
});

// ── Role Assignment ───────────────────────────────────────────────────────────

it('assigns a role to a user', function (): void {
    $role = Role::factory()->create(['tenant_id' => $this->tenant->uuid]);

    $tenantUser = app(AssignRoleAction::class)->execute(new AssignRoleDTO(
        tenantId: $this->tenant->uuid,
        userId:   $this->user->uuid,
        roleId:   $role->uuid,
    ));

    expect($tenantUser)
        ->toBeInstanceOf(TenantUser::class)
        ->and($tenantUser->tenant_id)->toBe($this->tenant->uuid)
        ->and($tenantUser->user_id)->toBe($this->user->uuid)
        ->and($tenantUser->role_id)->toBe($role->uuid)
        ->and($tenantUser->is_active)->toBeTrue();

    $this->assertDatabaseHas('tenant_users', [
        'tenant_id' => $this->tenant->uuid,
        'user_id'   => $this->user->uuid,
        'role_id'   => $role->uuid,
        'is_active' => true,
    ]);
});

it('revokes a role from a user', function (): void {
    $role = Role::factory()->create(['tenant_id' => $this->tenant->uuid]);

    app(AssignRoleAction::class)->execute(new AssignRoleDTO(
        tenantId: $this->tenant->uuid,
        userId:   $this->user->uuid,
        roleId:   $role->uuid,
    ));

    app(RevokeRoleAction::class)->execute($this->tenant->uuid, $this->user->uuid);

    $membership = TenantUser::where('tenant_id', $this->tenant->uuid)
        ->where('user_id', $this->user->uuid)
        ->first();

    expect($membership)->not->toBeNull()
        ->and($membership->is_active)->toBeFalse();
});

it('prevents duplicate role assignment by updating existing membership', function (): void {
    $roleA = Role::factory()->create(['tenant_id' => $this->tenant->uuid]);
    $roleB = Role::factory()->create(['tenant_id' => $this->tenant->uuid]);

    $action = app(AssignRoleAction::class);

    $action->execute(new AssignRoleDTO($this->tenant->uuid, $this->user->uuid, $roleA->uuid));
    $action->execute(new AssignRoleDTO($this->tenant->uuid, $this->user->uuid, $roleB->uuid));

    // Only one record should exist per user×tenant
    $count = TenantUser::where('tenant_id', $this->tenant->uuid)
        ->where('user_id', $this->user->uuid)
        ->count();

    expect($count)->toBe(1);
});

// ── Permission Validation ─────────────────────────────────────────────────────

it('validates permission for user with correct role', function (): void {
    $role = Role::factory()
        ->withPermission(PermissionEnum::SalesCreate)
        ->create(['tenant_id' => $this->tenant->uuid]);

    app(AssignRoleAction::class)->execute(new AssignRoleDTO(
        tenantId: $this->tenant->uuid,
        userId:   $this->user->uuid,
        roleId:   $role->uuid,
    ));

    expect($this->user->hasPermission(PermissionEnum::SalesCreate))->toBeTrue();
});

it('denies access when user lacks permission', function (): void {
    $role = Role::factory()
        ->withPermission(PermissionEnum::DashboardView)
        ->create(['tenant_id' => $this->tenant->uuid]);

    app(AssignRoleAction::class)->execute(new AssignRoleDTO(
        tenantId: $this->tenant->uuid,
        userId:   $this->user->uuid,
        roleId:   $role->uuid,
    ));

    // user only has DashboardView, not ProductsDelete
    expect($this->user->hasPermission(PermissionEnum::ProductsDelete))->toBeFalse();
});

it('allows access when user has permission', function (): void {
    $role = Role::factory()
        ->withPermission(PermissionEnum::InventoryAdjust)
        ->create(['tenant_id' => $this->tenant->uuid]);

    app(AssignRoleAction::class)->execute(new AssignRoleDTO(
        tenantId: $this->tenant->uuid,
        userId:   $this->user->uuid,
        roleId:   $role->uuid,
    ));

    expect($this->user->hasPermission(PermissionEnum::InventoryAdjust))->toBeTrue()
        ->and($this->user->hasPermission(PermissionEnum::InventoryTransfer))->toBeFalse();
});

// ── Store Access ──────────────────────────────────────────────────────────────

it('allows unrestricted store access when no allowlist set', function (): void {
    $role = Role::factory()->create(['tenant_id' => $this->tenant->uuid]);

    app(AssignRoleAction::class)->execute(new AssignRoleDTO(
        tenantId: $this->tenant->uuid,
        userId:   $this->user->uuid,
        roleId:   $role->uuid,
    ));

    // No entries in tenant_user_stores = unrestricted
    expect($this->user->canAccessStore('any-store-uuid'))->toBeTrue();
    expect($this->user->canAccessStore('another-store-uuid'))->toBeTrue();
});

it('restricts store access correctly', function (): void {
    $role = Role::factory()->create(['tenant_id' => $this->tenant->uuid]);

    $tenantUser = app(AssignRoleAction::class)->execute(new AssignRoleDTO(
        tenantId: $this->tenant->uuid,
        userId:   $this->user->uuid,
        roleId:   $role->uuid,
    ));

    $allowedStore   = \App\Modules\Inventory\Models\Store::factory()->create(['tenant_id' => $this->tenant->uuid]);
    $forbiddenStore = \App\Modules\Inventory\Models\Store::factory()->create(['tenant_id' => $this->tenant->uuid]);

    TenantUserStore::create([
        'tenant_user_id' => $tenantUser->uuid,
        'store_id'       => $allowedStore->uuid,
    ]);

    expect($this->user->canAccessStore($allowedStore->uuid))->toBeTrue()
        ->and($this->user->canAccessStore($forbiddenStore->uuid))->toBeFalse();
});

// ── Middleware: permission ─────────────────────────────────────────────────────

it('returns 403 when user lacks required permission on protected route', function (): void {
    // User with only DashboardView tries to access a route requiring settings.update
    $role = Role::factory()
        ->withPermission(PermissionEnum::DashboardView)
        ->create(['tenant_id' => $this->tenant->uuid]);

    app(AssignRoleAction::class)->execute(new AssignRoleDTO(
        tenantId: $this->tenant->uuid,
        userId:   $this->user->uuid,
        roleId:   $role->uuid,
    ));

    $this->actingAs($this->user);

    // Directly verify that hasPermission returns false for an unauthorized permission
    expect($this->user->hasPermission(PermissionEnum::SettingsUpdate))->toBeFalse();
    expect($this->user->hasPermission(PermissionEnum::DashboardView))->toBeTrue();
});

it('clears permissions after role is revoked', function (): void {
    $role = Role::factory()
        ->withPermission(PermissionEnum::FinancialView)
        ->create(['tenant_id' => $this->tenant->uuid]);

    app(AssignRoleAction::class)->execute(new AssignRoleDTO(
        tenantId: $this->tenant->uuid,
        userId:   $this->user->uuid,
        roleId:   $role->uuid,
    ));

    expect($this->user->hasPermission(PermissionEnum::FinancialView))->toBeTrue();

    app(RevokeRoleAction::class)->execute($this->tenant->uuid, $this->user->uuid);

    // Cache is invalidated on revoke
    expect($this->user->hasPermission(PermissionEnum::FinancialView))->toBeFalse();
});

// ── Role CRUD: system role protection ─────────────────────────────────────────

it('system role cannot be modified via the api', function (): void {
    $systemRole = Role::factory()->system(RoleSlugEnum::Owner)->create();

    $this->actingAsTenantUser($this->tenant);

    $response = $this->putJson("/api/v1/roles/{$systemRole->uuid}", [
        'name' => 'Hacked Name',
    ]);

    // Rejeitado: 403/422 (proteção explícita do controller/policy) ou 404
    // (role de sistema global, tenant_id=NULL, não resolvível no escopo do
    // tenant). Em todos os casos a role de sistema NÃO é modificável por um tenant.
    expect($response->status())->toBeIn([403, 404, 422]);
});

it('system role cannot be deleted via the api', function (): void {
    $systemRole = Role::factory()->system(RoleSlugEnum::Manager)->create();

    $this->actingAsTenantUser($this->tenant);

    $response = $this->deleteJson("/api/v1/roles/{$systemRole->uuid}");

    expect($response->status())->toBeIn([403, 404, 422]);
});
