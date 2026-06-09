<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Brand;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

describe('GET /catalog/brands', function (): void {
    it('lista marcas do tenant', function (): void {
        Brand::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/catalog/brands');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    });

    it('não retorna marcas de outro tenant', function (): void {
        $otherTenant = \App\Core\Tenancy\Models\Tenant::factory()->create();
        Brand::factory()->count(2)->create(['tenant_id' => $otherTenant->uuid]);

        Brand::factory()->count(1)->create();

        $response = $this->getJson('/api/v1/catalog/brands');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('POST /catalog/brands', function (): void {
    it('cria uma marca', function (): void {
        $response = $this->postJson('/api/v1/catalog/brands', [
            'name'      => 'Zara Brasil',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Zara Brasil')
            ->assertJsonPath('data.slug', 'zara-brasil');

        $this->assertDatabaseHas('catalog_brands', ['name' => 'Zara Brasil']);
    });

    it('rejeita nome em branco', function (): void {
        $this->postJson('/api/v1/catalog/brands', ['name' => ''])
            ->assertUnprocessable();
    });

    it('rejeita slug duplicado', function (): void {
        Brand::factory()->create(['name' => 'Zara', 'slug' => 'zara']);

        $this->postJson('/api/v1/catalog/brands', ['name' => 'Zara'])
            ->assertStatus(409);
    });
});

describe('PUT /catalog/brands/{brand}', function (): void {
    it('atualiza uma marca', function (): void {
        $brand = Brand::factory()->create(['name' => 'Velho Nome']);

        $this->putJson("/api/v1/catalog/brands/{$brand->uuid}", ['name' => 'Novo Nome'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Novo Nome');
    });
});

describe('DELETE /catalog/brands/{brand}', function (): void {
    it('deleta uma marca (soft delete)', function (): void {
        $brand = Brand::factory()->create();

        $this->deleteJson("/api/v1/catalog/brands/{$brand->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('catalog_brands', ['uuid' => $brand->uuid]);
    });
});
