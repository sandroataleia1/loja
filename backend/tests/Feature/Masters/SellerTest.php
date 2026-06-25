<?php

declare(strict_types=1);

use App\Core\Auth\Models\TenantUser;
use App\Core\Auth\Models\User;
use App\Core\Tenancy\Models\Tenant;
use App\Modules\Sellers\Models\SellerProfile;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

describe('GET /sellers', function (): void {
    it('lista vendedores do tenant', function (): void {
        $user = User::factory()->forTenant($this->tenant)->create();
        TenantUser::create(['tenant_id' => $this->tenant->uuid, 'user_id' => $user->uuid, 'role_id' => $this->currentRole->uuid, 'is_active' => true]);
        SellerProfile::factory()->create(['tenant_id' => $this->tenant->uuid, 'user_id' => $user->uuid]);

        $this->getJson('/api/v1/sellers')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    });

    it('não retorna vendedores de outro tenant', function (): void {
        $other     = Tenant::factory()->create();
        $otherUser = User::factory()->create();
        SellerProfile::factory()->create(['tenant_id' => $other->uuid, 'user_id' => $otherUser->uuid]);

        $this->getJson('/api/v1/sellers')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });
});

describe('POST /sellers', function (): void {
    it('cria um vendedor', function (): void {
        $user = User::factory()->forTenant($this->tenant)->create();
        TenantUser::create(['tenant_id' => $this->tenant->uuid, 'user_id' => $user->uuid, 'role_id' => $this->currentRole->uuid, 'is_active' => true]);

        $response = $this->postJson('/api/v1/sellers', [
            'user_id'     => $user->uuid,
            'seller_type' => 'internal',
            'nickname'    => 'Zé Vendedor',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nickname', 'Zé Vendedor');

        $this->assertDatabaseHas('seller_profiles', ['user_id' => $user->uuid, 'tenant_id' => $this->tenant->uuid]);
    });

    it('rejeita user_id de outro tenant', function (): void {
        $other     = Tenant::factory()->create();
        $otherUser = User::factory()->create();

        $this->postJson('/api/v1/sellers', ['user_id' => $otherUser->uuid])
            ->assertUnprocessable();
    });
});

describe('PUT /sellers/{seller}', function (): void {
    it('atualiza um vendedor', function (): void {
        $user   = User::factory()->forTenant($this->tenant)->create();
        TenantUser::create(['tenant_id' => $this->tenant->uuid, 'user_id' => $user->uuid, 'role_id' => $this->currentRole->uuid, 'is_active' => true]);
        $seller = SellerProfile::factory()->create(['tenant_id' => $this->tenant->uuid, 'user_id' => $user->uuid]);

        $this->putJson("/api/v1/sellers/{$seller->uuid}", ['seller_type' => 'external'])
            ->assertOk()
            ->assertJsonPath('data.seller_type', 'external');
    });
});

describe('DELETE /sellers/{seller}', function (): void {
    it('faz soft delete do vendedor', function (): void {
        $user   = User::factory()->forTenant($this->tenant)->create();
        TenantUser::create(['tenant_id' => $this->tenant->uuid, 'user_id' => $user->uuid, 'role_id' => $this->currentRole->uuid, 'is_active' => true]);
        $seller = SellerProfile::factory()->create(['tenant_id' => $this->tenant->uuid, 'user_id' => $user->uuid]);

        $this->deleteJson("/api/v1/sellers/{$seller->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('seller_profiles', ['uuid' => $seller->uuid]);
    });
});

describe('supervisor_id auto-referencial', function (): void {
    it('aceita supervisor_id de vendedor do mesmo tenant', function (): void {
        $user1 = User::factory()->forTenant($this->tenant)->create();
        $user2 = User::factory()->forTenant($this->tenant)->create();
        TenantUser::create(['tenant_id' => $this->tenant->uuid, 'user_id' => $user1->uuid, 'role_id' => $this->currentRole->uuid, 'is_active' => true]);
        TenantUser::create(['tenant_id' => $this->tenant->uuid, 'user_id' => $user2->uuid, 'role_id' => $this->currentRole->uuid, 'is_active' => true]);

        $supervisor = SellerProfile::factory()->create(['tenant_id' => $this->tenant->uuid, 'user_id' => $user1->uuid]);

        $this->postJson('/api/v1/sellers', [
            'user_id'       => $user2->uuid,
            'supervisor_id' => $supervisor->uuid,
        ])->assertCreated();
    });
});
