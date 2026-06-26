<?php

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\Permission;
use App\Core\Auth\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const NEW_ROLES = [
        'accounts_payable' => [
            'name'        => 'Contas a Pagar',
            'permissions' => [
                'dashboard.view',
                'customers.view',
                'suppliers.view',
                'sales.view',
                'financial.view', 'financial.create', 'financial.update', 'financial.delete',
                'financial.accounts_payable',
                'financial.banking_data',
                'purchase_orders.view',
            ],
        ],
        'accounts_receivable' => [
            'name'        => 'Contas a Receber',
            'permissions' => [
                'dashboard.view',
                'customers.view',
                'sales.view',
                'financial.view', 'financial.create', 'financial.update', 'financial.delete',
                'financial.accounts_receivable',
            ],
        ],
    ];

    private const NEW_PERMISSIONS = [
        'financial.accounts_payable'   => ['name' => 'Contas a Pagar',   'module' => 'financial'],
        'financial.accounts_receivable'=> ['name' => 'Contas a Receber', 'module' => 'financial'],
        'financial.banking_data'       => ['name' => 'Dados bancários',  'module' => 'financial'],
    ];

    public function up(): void
    {
        // 1. Ensure new permissions exist
        $permMap = [];
        foreach (self::NEW_PERMISSIONS as $slug => $data) {
            $perm = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $data['name'], 'module' => $data['module'], 'is_system' => true],
            );
            $perm->update(['name' => $data['name'], 'module' => $data['module']]);
            $permMap[$slug] = $perm->uuid;
        }

        // Also load existing permission UUIDs we need
        $allPerms = Permission::whereIn('slug', collect(self::NEW_ROLES)
            ->flatMap(fn ($r) => $r['permissions'])
            ->unique()
            ->values()
            ->all()
        )->pluck('uuid', 'slug');

        // 2. Create/update system roles (tenant_id = null)
        foreach (self::NEW_ROLES as $slug => $def) {
            $systemRole = Role::firstOrCreate(
                ['slug' => $slug, 'tenant_id' => null],
                ['name' => $def['name'], 'is_system' => true, 'is_active' => true],
            );
            $systemRole->update(['name' => $def['name']]);

            $ids = collect($def['permissions'])
                ->map(fn ($s) => $allPerms[$s] ?? null)
                ->filter()
                ->values()
                ->all();

            $systemRole->permissions()->sync($ids);
        }

        // 3. For each existing tenant, create tenant-level copies
        $tenantIds = Role::whereNotNull('tenant_id')
            ->select('tenant_id')
            ->distinct()
            ->pluck('tenant_id');

        foreach ($tenantIds as $tenantId) {
            foreach (self::NEW_ROLES as $slug => $def) {
                $tenantRole = Role::firstOrCreate(
                    ['slug' => $slug, 'tenant_id' => $tenantId],
                    ['name' => $def['name'], 'is_system' => false, 'is_active' => true],
                );

                $ids = collect($def['permissions'])
                    ->map(fn ($s) => $allPerms[$s] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                $tenantRole->permissions()->sync($ids);
            }
        }
    }

    public function down(): void
    {
        Role::whereIn('slug', ['accounts_payable', 'accounts_receivable'])->delete();

        Permission::whereIn('slug', [
            'financial.accounts_payable',
            'financial.accounts_receivable',
            'financial.banking_data',
        ])->delete();
    }
};
