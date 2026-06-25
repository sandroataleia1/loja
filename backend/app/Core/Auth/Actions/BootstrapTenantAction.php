<?php

declare(strict_types=1);

namespace App\Core\Auth\Actions;

use App\Core\Auth\Enums\RoleSlugEnum;
use App\Core\Auth\Models\Permission;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Models\TenantUser;
use App\Core\Auth\Models\User;
use App\Core\Auth\Services\PermissionCache;
use App\Core\Tenancy\Models\TenantSettings;
use App\Modules\Catalog\Models\PriceList;
use App\Modules\Finance\Models\CostCenter;
use Illuminate\Support\Collection;

/**
 * Inicializa o contexto RBAC para um tenant recém-criado.
 *
 * Responsabilidades:
 *  1. Copia os 6 roles de sistema para o tenant (como roles próprias do tenant)
 *  2. Atribui o role OWNER ao usuário administrador
 *  3. Cria (ou atualiza) o TenantUser com role OWNER
 *
 * Deve ser executado dentro da transação de criação do tenant.
 */
final class BootstrapTenantAction
{
    /**
     * @param  string $tenantId UUID do tenant criado
     * @param  User   $adminUser Usuário administrador que receberá o role OWNER
     */
    public function execute(string $tenantId, User $adminUser, ?PermissionCache $cache = null): TenantUser
    {
        // 1. Garantir que todas as permissões do sistema existam
        $allPermissions = Permission::all()->keyBy('slug');

        // 2. Buscar roles de sistema (tenant_id = null) e replicar para o tenant
        $systemRoles = Role::whereNull('tenant_id')
            ->where('is_system', true)
            ->with('permissions')
            ->get();

        /** @var Collection<string, Role> $tenantRoles role_slug → Role */
        $tenantRoles = collect();

        foreach ($systemRoles as $systemRole) {
            $tenantRole = Role::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'slug'      => $systemRole->slug,
                ],
                [
                    'name'      => $systemRole->name,
                    'is_system' => false,
                    'is_active' => true,
                ],
            );

            // Sync permissions from system role
            $permissionIds = $systemRole->permissions->pluck('uuid')->all();
            $tenantRole->permissions()->sync($permissionIds);

            $tenantRoles->put($systemRole->slug, $tenantRole);
        }

        // Fallback: if no system roles exist yet (e.g. seeder not run),
        // create an owner role with all permissions directly.
        if ($tenantRoles->isEmpty()) {
            $ownerRole = Role::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'slug'      => RoleSlugEnum::Owner->value,
                ],
                [
                    'name'      => RoleSlugEnum::Owner->label(),
                    'is_system' => false,
                    'is_active' => true,
                ],
            );

            $ownerRole->permissions()->sync($allPermissions->pluck('uuid')->all());
            $tenantRoles->put(RoleSlugEnum::Owner->value, $ownerRole);
        }

        // 3. Resolve the OWNER role for this tenant
        $ownerRole = $tenantRoles->get(RoleSlugEnum::Owner->value);

        if ($ownerRole === null) {
            // Last resort: create owner role manually
            $ownerRole = Role::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'slug'      => RoleSlugEnum::Owner->value,
                ],
                [
                    'name'      => RoleSlugEnum::Owner->label(),
                    'is_system' => false,
                    'is_active' => true,
                ],
            );
        }

        // 4. Create or update TenantUser with OWNER role
        /** @var TenantUser $tenantUser */
        $tenantUser = TenantUser::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'user_id'   => $adminUser->uuid,
            ],
            [
                'role_id'   => $ownerRole->uuid,
                'is_active' => true,
            ],
        );

        ($cache ?? app(PermissionCache::class))->invalidateUser($adminUser->uuid, $tenantId);

        // Cria as configurações do sistema com defaults para o novo tenant.
        TenantSettings::forTenant($tenantId);

        // Cria centros de custo padrão para o novo tenant.
        $defaultCostCenters = [
            ['code' => '1',   'name' => 'Administrativo', 'type' => 'ADMINISTRATIVE'],
            ['code' => '2',   'name' => 'Comercial',      'type' => 'COMMERCIAL'],
            ['code' => '3',   'name' => 'Logística',      'type' => 'LOGISTICS'],
            ['code' => '4',   'name' => 'Compras',        'type' => 'PURCHASING'],
            ['code' => '5',   'name' => 'Financeiro',     'type' => 'FINANCIAL'],
        ];
        foreach ($defaultCostCenters as $cc) {
            CostCenter::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => $cc['code']],
                ['name' => $cc['name'], 'type' => $cc['type'], 'is_active' => true],
            );
        }

        // Cria tabelas de preço padrão para o novo tenant.
        $defaultPriceLists = [
            ['code' => 'VAREJO',   'name' => 'Varejo',        'type' => 'retail',         'is_default' => true],
            ['code' => 'ATACADO',  'name' => 'Atacado',       'type' => 'wholesale',      'is_default' => false],
            ['code' => 'REPRES',   'name' => 'Representante', 'type' => 'representative', 'is_default' => false],
            ['code' => 'CUSTO',    'name' => 'Custo',         'type' => 'cost',           'is_default' => false],
        ];
        foreach ($defaultPriceLists as $pl) {
            PriceList::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => $pl['code']],
                array_merge($pl, ['is_active' => true, 'currency' => 'BRL']),
            );
        }

        return $tenantUser;
    }
}
