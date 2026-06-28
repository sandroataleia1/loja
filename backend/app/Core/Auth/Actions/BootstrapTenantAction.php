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
use App\Modules\Financial\Models\CollectionRule;
use App\Modules\Settings\Models\CustomFieldDefinition;
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
            ['code' => 'ESPECIAL', 'name' => 'Especial',      'type' => 'special',        'is_default' => false],
            ['code' => 'CUSTO',    'name' => 'Custo',         'type' => 'cost',           'is_default' => false],
        ];
        foreach ($defaultPriceLists as $pl) {
            PriceList::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => $pl['code']],
                array_merge($pl, ['is_active' => true, 'currency' => 'BRL']),
            );
        }

        // Cria régua de cobrança padrão para o novo tenant (3/10/30/60 dias).
        $defaultCollectionRules = [
            [
                'name'             => 'WhatsApp — 3 dias de atraso',
                'trigger_days'     => 3,
                'action_type'      => 'whatsapp',
                'message_template' => 'Olá {cliente}, sua parcela de {valor} venceu em {vencimento}. Por favor, regularize sua situação.',
                'sort_order'       => 1,
            ],
            [
                'name'             => 'E-mail — 10 dias de atraso',
                'trigger_days'     => 10,
                'action_type'      => 'email',
                'message_template' => 'Prezado(a) {cliente}, sua parcela de {valor} vencida em {vencimento} ainda está em aberto. Entre em contato conosco para regularização.',
                'sort_order'       => 2,
            ],
            [
                'name'             => 'Bloquear cliente — 30 dias de atraso',
                'trigger_days'     => 30,
                'action_type'      => 'block_customer',
                'message_template' => null,
                'sort_order'       => 3,
            ],
            [
                'name'             => 'Notificar gerente — 60 dias de atraso',
                'trigger_days'     => 60,
                'action_type'      => 'notify_seller',
                'message_template' => null,
                'sort_order'       => 4,
            ],
        ];
        foreach ($defaultCollectionRules as $rule) {
            CollectionRule::firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $rule['name']],
                array_merge($rule, ['tenant_id' => $tenantId, 'is_active' => true]),
            );
        }

        // Cria campos customizados padrão para o novo tenant.
        $defaultCustomFields = [
            // entity: customer
            ['entity_type' => 'customer', 'label' => 'Nome do cônjuge',   'field_key' => 'spouse_name',    'field_type' => 'text',   'sort_order' => 1],
            ['entity_type' => 'customer', 'label' => 'CPF do cônjuge',    'field_key' => 'spouse_cpf',     'field_type' => 'text',   'sort_order' => 2],
            ['entity_type' => 'customer', 'label' => 'Profissão',         'field_key' => 'profession',     'field_type' => 'text',   'sort_order' => 3],
            ['entity_type' => 'customer', 'label' => 'Renda mensal (R$)', 'field_key' => 'monthly_income', 'field_type' => 'number', 'sort_order' => 4],
            [
                'entity_type' => 'customer',
                'label'       => 'Como nos conheceu',
                'field_key'   => 'how_found_us',
                'field_type'  => 'select',
                'options'     => ['Indicação', 'Google', 'Redes sociais', 'Passou na frente', 'Outros'],
                'sort_order'  => 5,
            ],
            ['entity_type' => 'customer', 'label' => 'Referência de obra', 'field_key' => 'work_reference', 'field_type' => 'text', 'sort_order' => 6],
            // entity: order
            ['entity_type' => 'order', 'label' => 'Nome da obra',          'field_key' => 'work_name',      'field_type' => 'text', 'sort_order' => 1],
            ['entity_type' => 'order', 'label' => 'Endereço da obra',      'field_key' => 'work_address',   'field_type' => 'text', 'sort_order' => 2],
            ['entity_type' => 'order', 'label' => 'Arquiteto/Responsável', 'field_key' => 'architect_name', 'field_type' => 'text', 'sort_order' => 3],
        ];
        foreach ($defaultCustomFields as $field) {
            CustomFieldDefinition::firstOrCreate(
                ['tenant_id' => $tenantId, 'entity_type' => $field['entity_type'], 'field_key' => $field['field_key']],
                array_merge($field, ['tenant_id' => $tenantId, 'is_active' => true, 'is_required' => false]),
            );
        }

        return $tenantUser;
    }
}
