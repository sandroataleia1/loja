<?php

declare(strict_types=1);

use App\Core\Auth\Enums\TenantUserStatusEnum;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Models\TenantUser;
use App\Core\Auth\Models\User;
use App\Core\Auth\Services\PermissionCache;
use Illuminate\Support\Facades\Cache;

// TestCase e RefreshDatabase configurados globalmente em tests/Pest.php

// ── createUser ────────────────────────────────────────────────────────────────

test('createUser cria usuário e TenantUser com role', function (): void {
    $this->actingAsTenantUser();

    $role = Role::create([
        'tenant_id' => $this->tenant->uuid,
        'name'      => 'Operador',
        'slug'      => 'operador-' . uniqid(),
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/users', [
        'name'     => 'João Novo',
        'email'    => 'joao.novo@test.com',
        'password' => 'Senha@123',
        'role_id'  => $role->uuid,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.user.email', 'joao.novo@test.com');

    expect(User::where('email', 'joao.novo@test.com')->exists())->toBeTrue()
        ->and(TenantUser::where('tenant_id', $this->tenant->uuid)->count())->toBeGreaterThanOrEqual(2);
});

// ── changeRole + cache invalidation ──────────────────────────────────────────

test('changeRole invalida o cache de permissões do usuário', function (): void {
    $this->actingAsTenantUser();

    // Popula o cache
    $cache = app(PermissionCache::class);
    $perms = $cache->getPermissions($this->user->uuid, $this->tenant->uuid);
    expect($perms)->not->toBeEmpty();

    // Cria um novo role para troca
    $newRole = Role::create([
        'tenant_id' => $this->tenant->uuid,
        'name'      => 'Limitado',
        'slug'      => 'limitado-' . uniqid(),
        'is_active' => true,
    ]);

    $tenantUser = TenantUser::where('tenant_id', $this->tenant->uuid)
        ->where('user_id', $this->user->uuid)
        ->firstOrFail();

    $response = $this->putJson("/api/v1/users/{$tenantUser->uuid}/role", [
        'role_id' => $newRole->uuid,
    ]);

    $response->assertOk();

    // Cache deve estar limpo após changeRole
    $cacheKey = "rbac.{$this->tenant->uuid}.{$this->user->uuid}.permissions";
    expect(Cache::has($cacheKey))->toBeFalse();
});

// ── lookupByEmail ─────────────────────────────────────────────────────────────

test('lookupByEmail retorna usuário existente', function (): void {
    $this->actingAsTenantUser();

    $other = User::factory()->create(['email' => 'outro@tenant.com']);

    $response = $this->getJson('/api/v1/users/lookup?email=outro@tenant.com');

    $response->assertOk()
        ->assertJsonPath('data.email', 'outro@tenant.com');
});

test('lookupByEmail retorna 404 para e-mail inexistente', function (): void {
    $this->actingAsTenantUser();

    $response = $this->getJson('/api/v1/users/lookup?email=nao@existe.com');

    $response->assertStatus(404);
});

test('lookupByEmail retorna 422 se já é membro do tenant', function (): void {
    $this->actingAsTenantUser();

    $response = $this->getJson("/api/v1/users/lookup?email={$this->user->email}");

    $response->assertStatus(422);
});

// ── suspend ───────────────────────────────────────────────────────────────────

test('suspend altera status para suspended', function (): void {
    $this->actingAsTenantUser();

    $targetUser  = User::factory()->forTenant($this->tenant)->create();
    $tenantUser  = TenantUser::create([
        'tenant_id' => $this->tenant->uuid,
        'user_id'   => $targetUser->uuid,
        'is_active' => true,
    ]);

    $response = $this->putJson("/api/v1/users/{$tenantUser->uuid}/suspend");

    $response->assertOk();

    $tenantUser->refresh();

    expect($tenantUser->status)->toBe(TenantUserStatusEnum::Suspended)
        ->and($tenantUser->is_active)->toBeFalse();
});

// ── grantStoreAccess / revokeStoreAccess ─────────────────────────────────────

test('grantStoreAccess adiciona loja ao allowlist', function (): void {
    $this->actingAsTenantUser();

    $targetUser = User::factory()->forTenant($this->tenant)->create();
    $tenantUser = TenantUser::create([
        'tenant_id' => $this->tenant->uuid,
        'user_id'   => $targetUser->uuid,
        'is_active' => true,
    ]);

    $store = \App\Modules\Inventory\Models\Store::factory()->create([
        'tenant_id' => $this->tenant->uuid,
    ]);

    $response = $this->postJson("/api/v1/users/{$tenantUser->uuid}/stores", [
        'store_id' => $store->uuid,
    ]);

    $response->assertCreated();
});

test('revokeStoreAccess remove loja do allowlist', function (): void {
    $this->actingAsTenantUser();

    $targetUser = User::factory()->forTenant($this->tenant)->create();
    $tenantUser = TenantUser::create([
        'tenant_id' => $this->tenant->uuid,
        'user_id'   => $targetUser->uuid,
        'is_active' => true,
    ]);

    $store = \App\Modules\Inventory\Models\Store::factory()->create([
        'tenant_id' => $this->tenant->uuid,
    ]);

    // Grant e depois revoke
    $this->postJson("/api/v1/users/{$tenantUser->uuid}/stores", [
        'store_id' => $store->uuid,
    ]);

    $response = $this->deleteJson("/api/v1/users/{$tenantUser->uuid}/stores/{$store->uuid}");

    $response->assertOk();
});
