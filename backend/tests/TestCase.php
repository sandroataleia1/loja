<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\Permission;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Models\TenantUser;
use App\Core\Auth\Models\User;
use App\Core\Auth\Services\PermissionCache;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected ?Tenant $tenant = null;

    protected ?User $user = null;

    protected ?Role $currentRole = null;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::clear();
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    /**
     * Autentica um usuário com um tenant.
     * Por padrão, o usuário recebe um role com TODAS as permissões do sistema.
     * Use restrictUserToPermissions() em testes que precisam verificar acesso negado.
     */
    protected function actingAsTenantUser(?Tenant $tenant = null): static
    {
        $this->tenant = $tenant ?? Tenant::factory()->create();
        $this->user   = User::factory()->forTenant($this->tenant)->create();

        TenantContext::set($this->tenant->uuid);

        $this->currentRole = $this->createRoleWithPermissions(
            $this->tenant->uuid,
            PermissionEnum::cases(),
        );

        TenantUser::create([
            'tenant_id' => $this->tenant->uuid,
            'user_id'   => $this->user->uuid,
            'role_id'   => $this->currentRole->uuid,
            'is_active' => true,
        ]);

        // Invalida cache para garantir que as permissões sejam carregadas do DB
        app(PermissionCache::class)->invalidateUser($this->user->uuid, $this->tenant->uuid);

        return $this->actingAs($this->user);
    }

    /**
     * Restringe o usuário atual às permissões informadas.
     * Use em testes que verificam que usuários com poucas permissões são bloqueados (403).
     *
     * Exemplo:
     *   $this->restrictUserToPermissions(PermissionEnum::SalesView);
     *   // usuário sem FinancialCreate → 403 em POST /finance/accounts
     */
    protected function restrictUserToPermissions(PermissionEnum ...$permissions): void
    {
        if ($this->currentRole === null || $this->user === null || $this->tenant === null) {
            return;
        }

        $this->currentRole->permissions()->sync(
            $this->resolvePermissionUuids($permissions),
        );

        app(PermissionCache::class)->invalidateUser($this->user->uuid, $this->tenant->uuid);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** @param PermissionEnum[] $permissions */
    private function createRoleWithPermissions(string $tenantId, array $permissions): Role
    {
        $role = Role::create([
            'tenant_id' => $tenantId,
            'name'      => 'Test Owner',
            'slug'      => 'test-owner-' . uniqid(),
            'is_system' => false,
            'is_active' => true,
        ]);

        $role->permissions()->sync(
            $this->resolvePermissionUuids($permissions),
        );

        return $role;
    }

    /** @param PermissionEnum[] $permissions */
    private function resolvePermissionUuids(array $permissions): array
    {
        $uuids = [];
        foreach ($permissions as $enum) {
            $permission = Permission::firstOrCreate(
                ['slug' => $enum->value],
                [
                    'name'      => $enum->label(),
                    'module'    => $enum->module(),
                    'is_system' => true,
                ],
            );
            $uuids[] = $permission->uuid;
        }

        return $uuids;
    }
}
