<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\Tenant;
use App\Modules\Inventory\Models\Store;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

describe('GET /inventory/stores', function (): void {
    it('lista lojas do tenant', function (): void {
        Store::factory()->count(3)->create();

        $this->getJson('/api/v1/inventory/stores')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    });

    it('não retorna lojas de outro tenant', function (): void {
        $other = Tenant::factory()->create();
        Store::factory()->count(2)->create(['tenant_id' => $other->uuid]);
        Store::factory()->count(1)->create();

        $this->getJson('/api/v1/inventory/stores')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('POST /inventory/stores', function (): void {
    it('cria uma loja', function (): void {
        $this->postJson('/api/v1/inventory/stores', [
            'name' => 'Loja Centro',
            'code' => 'CTR01',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Loja Centro')
            ->assertJsonPath('data.code', 'CTR01');

        $this->assertDatabaseHas('stores', ['name' => 'Loja Centro', 'code' => 'CTR01']);
    });

    it('rejeita código duplicado', function (): void {
        Store::factory()->create(['code' => 'CTR01']);

        $this->postJson('/api/v1/inventory/stores', ['name' => 'Outra', 'code' => 'CTR01'])
            ->assertStatus(409);
    });

    it('rejeita campos obrigatórios ausentes', function (): void {
        $this->postJson('/api/v1/inventory/stores', [])
            ->assertUnprocessable();
    });
});

describe('PUT /inventory/stores/{store}', function (): void {
    it('atualiza nome da loja', function (): void {
        $store = Store::factory()->create(['name' => 'Antigo']);

        $this->putJson("/api/v1/inventory/stores/{$store->uuid}", ['name' => 'Novo'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Novo');
    });
});

describe('DELETE /inventory/stores/{store}', function (): void {
    it('deleta loja com soft delete', function (): void {
        $store = Store::factory()->create();

        $this->deleteJson("/api/v1/inventory/stores/{$store->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('stores', ['uuid' => $store->uuid]);
    });
});
