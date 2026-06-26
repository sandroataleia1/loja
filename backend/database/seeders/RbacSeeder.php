<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Enums\RoleSlugEnum;
use App\Core\Auth\Models\Permission;
use App\Core\Auth\Models\PlatformRole;
use App\Core\Auth\Models\Role;
use Illuminate\Database\Seeder;

final class RbacSeeder extends Seeder
{
    /**
     * Permissões por role de sistema.
     * null = todas as permissões (owner).
     */
    private const ROLE_PERMISSIONS = [
        RoleSlugEnum::Owner->value => null,

        RoleSlugEnum::Manager->value => [
            'dashboard.view',
            'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'inventory.view', 'inventory.adjust', 'inventory.transfer',
            'sales.view', 'sales.create', 'sales.cancel', 'sales.discount',
            'cashier.open', 'cashier.close', 'cashier.reopen',
            'financial.view', 'financial.create', 'financial.update', 'financial.delete',
            'fiscal.view', 'fiscal.issue',
            'users.view', 'users.create', 'users.update',
            'settings.view',
            'suppliers.view', 'suppliers.create', 'suppliers.update',
            'purchase_orders.view', 'purchase_orders.create', 'purchase_orders.approve',
            'purchase_receipts.receive',
        ],

        RoleSlugEnum::Salesperson->value => [
            'dashboard.view',
            'customers.view', 'customers.create', 'customers.update',
            'products.view',
            'sales.view', 'sales.create', 'sales.discount',
            'cashier.open', 'cashier.close',
        ],

        RoleSlugEnum::Cashier->value => [
            'dashboard.view',
            'customers.view',
            'sales.view', 'sales.create', 'sales.cancel',
            'cashier.open', 'cashier.close', 'cashier.reopen',
        ],

        RoleSlugEnum::StockOperator->value => [
            'dashboard.view',
            'customers.view',
            'products.view', 'products.create', 'products.update',
            'inventory.view', 'inventory.adjust', 'inventory.transfer',
        ],

        RoleSlugEnum::Financial->value => [
            'dashboard.view',
            'customers.view',
            'sales.view',
            'financial.view', 'financial.create', 'financial.update', 'financial.delete',
            'financial.accounts_payable', 'financial.accounts_receivable', 'financial.banking_data',
            'fiscal.view', 'fiscal.issue',
        ],

        RoleSlugEnum::AccountsPayable->value => [
            'dashboard.view',
            'customers.view',
            'suppliers.view',
            'sales.view',
            'financial.view', 'financial.create', 'financial.update', 'financial.delete',
            'financial.accounts_payable',
            'financial.banking_data',
            'purchase_orders.view',
        ],

        RoleSlugEnum::AccountsReceivable->value => [
            'dashboard.view',
            'customers.view',
            'sales.view',
            'financial.view', 'financial.create', 'financial.update', 'financial.delete',
            'financial.accounts_receivable',
        ],
    ];

    private const ROLE_NAMES = [
        RoleSlugEnum::Owner->value         => 'Proprietário',
        RoleSlugEnum::Manager->value       => 'Gerente',
        RoleSlugEnum::Salesperson->value   => 'Vendedor',
        RoleSlugEnum::Cashier->value       => 'Caixa',
        RoleSlugEnum::StockOperator->value => 'Operador de Estoque',
        RoleSlugEnum::Financial->value          => 'Financeiro',
        RoleSlugEnum::AccountsPayable->value    => 'Contas a Pagar',
        RoleSlugEnum::AccountsReceivable->value => 'Contas a Receber',
    ];

    public function run(): void
    {
        // 1. Criar/atualizar todas as permissões
        $permissions = [];
        foreach (PermissionEnum::cases() as $enum) {
            $perm = Permission::firstOrCreate(
                ['slug' => $enum->value],
                [
                    'name'      => $enum->label(),
                    'module'    => $enum->module(),
                    'is_system' => true,
                ],
            );

            // Atualizar name/module caso o enum tenha mudado
            $perm->update([
                'name'   => $enum->label(),
                'module' => $enum->module(),
            ]);

            $permissions[$enum->value] = $perm;
        }

        // 2. Criar/atualizar roles de sistema e atribuir permissões
        foreach (self::ROLE_NAMES as $slug => $name) {
            $role = Role::firstOrCreate(
                ['slug' => $slug, 'tenant_id' => null],
                ['name' => $name, 'is_system' => true, 'is_active' => true],
            );

            $role->update(['name' => $name]);

            $allowedSlugs = self::ROLE_PERMISSIONS[$slug];

            if ($allowedSlugs === null) {
                $permIds = collect($permissions)->pluck('uuid')->all();
            } else {
                $permIds = collect($permissions)
                    ->filter(fn ($p) => in_array($p->slug, $allowedSlugs, true))
                    ->pluck('uuid')
                    ->all();
            }

            $role->permissions()->sync($permIds);
        }

        // 3. Criar platform roles (super-admin SaaS)
        $platformRoles = [
            'super_admin' => 'Super Administrador',
            'support'     => 'Suporte',
        ];

        foreach ($platformRoles as $slug => $name) {
            PlatformRole::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        $this->command?->info(
            sprintf(
                'RBAC: %d permissões, %d roles de sistema, %d platform roles criados/atualizados.',
                count($permissions),
                count(self::ROLE_NAMES),
                count($platformRoles),
            )
        );
    }
}
