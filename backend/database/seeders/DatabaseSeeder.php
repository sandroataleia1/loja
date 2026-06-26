<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Auth\Actions\BootstrapTenantAction;
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
        $this->call(NcmCodeSeeder::class);
        $this->call(UnitSeeder::class);
        $this->call(PaymentSeeder::class);

        /** @var Tenant $tenant */
        $tenant = Tenant::firstOrCreate(
            ['code' => 'TEN001'],
            [
                'name'       => 'Loja Demo',
                'trade_name' => 'Loja Demo',
                'legal_name' => 'Loja Demo Comércio LTDA',
                'slug'       => 'loja-demo',
                'plan'       => 'pro',
                'email'      => 'contato@lojademo.com.br',
            ],
        );

        TenantContext::set($tenant->uuid);

        /** @var User $admin */
        $admin = User::firstOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'tenant_id' => $tenant->uuid,
                'name'      => 'Admin Demo',
                'password'  => Hash::make('password'),
                'is_active' => true,
            ],
        );

        app(BootstrapTenantAction::class)->execute($tenant->uuid, $admin);

        if (User::where('tenant_id', $tenant->uuid)->count() < 3) {
            User::factory()->forTenant($tenant)->count(5)->create();
        }

        $this->call(ConstructionCategorySeeder::class);
        $this->call(ConstructionAttributeSeeder::class);
        $this->call(ConstructionGridSeeder::class);
        $this->call(ConstructionProductSeeder::class);

        TenantContext::clear();

        $this->command->info("Tenant: {$tenant->name} ({$tenant->uuid})");
        $this->command->info("Admin: {$admin->email} / password");
    }
}
