<?php

use App\Core\Auth\Models\Permission;
use App\Core\Auth\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $perm = Permission::where('slug', 'dashboard.view')->first();
        if (! $perm) return;

        Role::where('slug', 'accounts_receivable')
            ->get()
            ->each(fn ($role) => $role->permissions()->detach($perm->uuid));
    }

    public function down(): void {}
};
