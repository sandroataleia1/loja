<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\Tenant;
use App\Modules\Catalog\Enums\ProductStatusEnum;
use App\Modules\Catalog\Enums\ProductTypeEnum;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Variant;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

describe('Listar produtos', function (): void {
    it('retorna produtos paginados do tenant', function (): void {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/catalog/products');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['meta' => ['current_page', 'per_page', 'total', 'last_page']]);
    });

    it('filtra por status', function (): void {
        Product::factory()->count(2)->create(['status' => ProductStatusEnum::Active]);
        Product::factory()->count(3)->create(['status' => ProductStatusEnum::Draft]);

        $this->getJson('/api/v1/catalog/products?status=active')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('filtra por marca', function (): void {
        $brand = Brand::factory()->create();
        Product::factory()->for($brand, 'brand')->count(2)->create();
        Product::factory()->count(3)->create();

        $this->getJson("/api/v1/catalog/products?brand_id={$brand->uuid}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('isola produtos entre tenants', function (): void {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $this->actingAsTenantUser($tenant1);
        Product::factory()->count(2)->create();

        $this->actingAsTenantUser($tenant2);
        Product::factory()->count(5)->create();

        $this->getJson('/api/v1/catalog/products')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    });

    it('requer autenticação', function (): void {
        auth()->forgetGuards(); // cancela o actingAs() do beforeEach
        $this->getJson('/api/v1/catalog/products')->assertUnauthorized();
    });
});

describe('Criar produto', function (): void {
    it('cria produto simples com dados mínimos', function (): void {
        $response = $this->postJson('/api/v1/catalog/products', [
            'name' => 'Tijolo Cerâmico 9x19x19cm',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Tijolo Cerâmico 9x19x19cm')
            ->assertJsonPath('data.type', 'simple')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('catalog_products', ['name' => 'Tijolo Cerâmico 9x19x19cm']);
    });

    it('cria produto com brand_id', function (): void {
        $brand = Brand::factory()->create();

        $this->postJson('/api/v1/catalog/products', [
            'name'     => 'Parafuso Philips 4,2x38mm c/100',
            'brand_id' => $brand->uuid,
        ])->assertCreated();
    });

    it('rejeita nome em branco', function (): void {
        $this->postJson('/api/v1/catalog/products', ['name' => ''])
            ->assertUnprocessable();
    });

    it('rejeita slug duplicado', function (): void {
        Product::factory()->create(['name' => 'Argamassa', 'slug' => 'argamassa']);

        // mesmo slug será gerado para o mesmo nome
        $this->postJson('/api/v1/catalog/products', ['name' => 'Argamassa'])
            ->assertConflict();
    });

    it('permite mesmo nome em tenants diferentes', function (): void {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $this->actingAsTenantUser($tenant1);
        Product::factory()->create(['name' => 'Argamassa', 'slug' => 'argamassa']);

        $this->actingAsTenantUser($tenant2);

        $this->postJson('/api/v1/catalog/products', ['name' => 'Argamassa'])
            ->assertCreated();
    });
});

describe('Exibir produto', function (): void {
    it('retorna produto com relacionamentos', function (): void {
        $product = Product::factory()->create(['name' => 'Porcelanato Polido 60x60cm']);

        $this->getJson("/api/v1/catalog/products/{$product->uuid}")
            ->assertOk()
            ->assertJsonPath('data.uuid', $product->uuid)
            ->assertJsonPath('data.name', 'Porcelanato Polido 60x60cm')
            ->assertJsonStructure(['data' => ['variants', 'images', 'tags']]);
    });

    it('retorna 404 para produto de outro tenant', function (): void {
        $otherTenant = Tenant::factory()->create();
        $this->actingAsTenantUser($otherTenant);
        $product = Product::factory()->create();

        $this->actingAsTenantUser(); // diferente tenant

        $this->getJson("/api/v1/catalog/products/{$product->uuid}")
            ->assertNotFound();
    });
});

describe('Publicar produto', function (): void {
    it('publica produto simples', function (): void {
        $product = Product::factory()->create([
            'type'   => ProductTypeEnum::Simple,
            'status' => ProductStatusEnum::Draft,
        ]);

        $this->postJson("/api/v1/catalog/products/{$product->uuid}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    });

    it('rejeita publicar produto variable sem variantes', function (): void {
        $product = Product::factory()->create([
            'type'   => ProductTypeEnum::Variable,
            'status' => ProductStatusEnum::Draft,
        ]);

        $this->postJson("/api/v1/catalog/products/{$product->uuid}/publish")
            ->assertStatus(422);
    });

    it('publica produto variable com variante ativa', function (): void {
        $product = Product::factory()->create([
            'type'   => ProductTypeEnum::Variable,
            'status' => ProductStatusEnum::Draft,
        ]);

        Variant::factory()->for($product, 'product')->create(['is_active' => true]);

        $this->postJson("/api/v1/catalog/products/{$product->uuid}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    });

    it('rejeita publicar produto já arquivado', function (): void {
        $product = Product::factory()->create(['status' => ProductStatusEnum::Archived]);

        $this->postJson("/api/v1/catalog/products/{$product->uuid}/publish")
            ->assertStatus(422);
    });
});

describe('Arquivar produto', function (): void {
    it('arquiva produto ativo', function (): void {
        $product = Product::factory()->create(['status' => ProductStatusEnum::Active]);

        $this->postJson("/api/v1/catalog/products/{$product->uuid}/archive")
            ->assertOk()
            ->assertJsonPath('data.status', 'archived');
    });
});

describe('Duplicar produto', function (): void {
    it('duplica produto como rascunho', function (): void {
        $product = Product::factory()->create(['status' => ProductStatusEnum::Active]);

        $this->postJson("/api/v1/catalog/products/{$product->uuid}/duplicate")
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseCount('catalog_products', 2);
    });
});

describe('Deletar produto', function (): void {
    it('soft deleta produto', function (): void {
        $product = Product::factory()->create();

        $this->deleteJson("/api/v1/catalog/products/{$product->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('catalog_products', ['uuid' => $product->uuid]);
    });
});
