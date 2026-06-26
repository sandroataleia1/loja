<?php

use App\Core\Auth\Models\Permission;
use App\Core\Auth\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const REMOVE_FROM_AR = [
        'financial.view',
        'financial.create',
        'financial.update',
        'financial.delete',
    ];

    public function up(): void
    {
        // Atualiza labels e módulos das permissões conforme PermissionEnum atual
        $labelsMap = [
            'financial.view'               => ['name' => 'Visualizar',          'module' => 'accounts_payable'],
            'financial.create'             => ['name' => 'Registrar lançamentos', 'module' => 'accounts_payable'],
            'financial.update'             => ['name' => 'Editar lançamentos',   'module' => 'accounts_payable'],
            'financial.delete'             => ['name' => 'Excluir lançamentos',  'module' => 'accounts_payable'],
            'financial.accounts_payable'   => ['name' => 'Acesso ao módulo',     'module' => 'accounts_payable'],
            'financial.accounts_receivable'=> ['name' => 'Acesso ao módulo',     'module' => 'accounts_receivable'],
            'financial.banking_data'       => ['name' => 'Dados bancários',      'module' => 'accounts_payable'],
        ];

        foreach ($labelsMap as $slug => $attrs) {
            Permission::where('slug', $slug)->update($attrs);
        }

        // Remove permissões AP genéricas de roles com slug accounts_receivable
        $removeIds = Permission::whereIn('slug', self::REMOVE_FROM_AR)->pluck('uuid')->all();

        if (empty($removeIds)) {
            return;
        }

        $arRoles = Role::where('slug', 'accounts_receivable')->get();

        foreach ($arRoles as $role) {
            $role->permissions()->detach($removeIds);
        }
    }

    public function down(): void {}
};
