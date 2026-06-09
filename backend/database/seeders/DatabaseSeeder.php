<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Auth\Models\User;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RbacSeeder::class);

        $tenant = Tenant::factory()->create([
            'name'       => 'Loja Demo',
            'trade_name' => 'Loja Demo',
            'legal_name' => 'Loja Demo Comércio LTDA',
            'code'       => 'TEN001',
            'slug'       => 'loja-demo',
            'plan'       => 'pro',
            'email'      => 'contato@lojademo.com.br',
        ]);

        TenantContext::set($tenant->uuid);

        $admin = User::factory()->forTenant($tenant)->admin()->create([
            'name'  => 'Admin Demo',
            'email' => 'admin@demo.com',
            'password' => Hash::make('password'),
        ]);

        User::factory()->forTenant($tenant)->count(5)->create();

        TenantContext::clear();

        $this->command->info("Tenant: {$tenant->name} ({$tenant->uuid})");
        $this->command->info("Admin: {$admin->email} / password");
    }
}
