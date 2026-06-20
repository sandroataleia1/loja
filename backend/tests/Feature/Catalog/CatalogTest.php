<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Variant;
use App\Modules\Catalog\Enums\ProductStatusEnum;
use App\Modules\Catalog\Enums\ProductTypeEnum;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

// ── Brands ───────────────────────────────────────────────────────────────────

describe('Brands', function (): void {
    it('cria marca com código automático (201)', function (): void {
        $response = $this->postJson('/api/v1/catalog/brands', [
            'name'      => 'Nike',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Nike');

        expect($response->json('data.code'))->not->toBeNull();
    });

    it('lista marcas do tenant (200)', function (): void {
        Brand::factory()->count(3)->create();

        $this->getJson('/api/v1/catalog/brands')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    });

    it('não vaza marcas de outros tenants', function (): void {
        $otherTenant = \App\Core\Tenancy\Models\Tenant::factory()->create();
        Brand::factory()->create(['tenant_id' => $otherTenant->uuid]);
        Brand::factory()->count(2)->create(); // current tenant

        $this->getJson('/api/v1/catalog/brands')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('atualiza marca (200)', function (): void {
        $brand = Brand::factory()->create();

        $this->putJson("/api/v1/catalog/brands/{$brand->uuid}", ['name' => 'Adidas'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Adidas');
    });

    it('exclui marca (204)', function (): void {
        $brand = Brand::factory()->create();

        $this->deleteJson("/api/v1/catalog/brands/{$brand->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('catalog_brands', ['uuid' => $brand->uuid]);
    });
});

// ── Categories ───────────────────────────────────────────────────────────────

describe('Categories', function (): void {
    it('cria categoria com código automático (201)', function (): void {
        $response = $this->postJson('/api/v1/catalog/categories', [
            'name'      => 'Revestimentos',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Revestimentos');

        expect($response->json('data.code'))->not->toBeNull();
    });

    it('suporta hierarquia parent → child', function (): void {
        $parent = $this->postJson('/api/v1/catalog/categories', ['name' => 'Alvenaria'])
            ->assertStatus(201)
            ->json('data');

        $child = $this->postJson('/api/v1/catalog/categories', [
            'name'      => 'Argamassa e Cimento',
            'parent_id' => $parent['uuid'],
        ])->assertStatus(201)->json('data');

        expect($child['parent_id'])->toBe($parent['uuid']);
    });

    it('lista categorias do tenant (200)', function (): void {
        Category::factory()->count(4)->create();

        $this->getJson('/api/v1/catalog/categories')
            ->assertOk()
            ->assertJsonPath('success', true);
    });
});

// ── Products ─────────────────────────────────────────────────────────────────

describe('Products', function (): void {
    it('cria produto simples (201)', function (): void {
        $response = $this->postJson('/api/v1/catalog/products', [
            'name'            => 'Cimento CP II-E 50kg',
            'type'            => 'simple',
            'unit_of_measure' => 'SC',
            'status'          => 'draft',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Cimento CP II-E 50kg');

        expect($response->json('data.code'))->not->toBeNull()
            ->and($response->json('data.slug'))->not->toBeNull();
    });

    it('produto tem código único por tenant (dois tenants, mesmo nome)', function (): void {
        // Create product in tenant1 (already active via beforeEach)
        $r1 = $this->postJson('/api/v1/catalog/products', [
            'name' => 'Argamassa ACIII 20kg', 'type' => 'simple', 'unit_of_measure' => 'SC', 'status' => 'draft',
        ])->assertStatus(201)->json('data');

        // Switch to a second tenant and create same product name — should succeed
        $this->actingAsTenantUser(); // new tenant with new user and RBAC

        $r2 = $this->postJson('/api/v1/catalog/products', [
            'name' => 'Argamassa ACIII 20kg', 'type' => 'simple', 'unit_of_measure' => 'SC', 'status' => 'draft',
        ])->assertStatus(201)->json('data');

        // Codes must both exist (sequences are independent per tenant)
        expect($r1['code'])->not->toBeNull()
            ->and($r2['code'])->not->toBeNull();
    });

    it('atualiza produto (200)', function (): void {
        $product = Product::factory()->create();

        $this->putJson("/api/v1/catalog/products/{$product->uuid}", [
            'name'   => 'Produto Atualizado',
            'status' => 'active',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Produto Atualizado')
            ->assertJsonPath('data.status', 'active');
    });

    it('filtra produtos por status (200)', function (): void {
        Product::factory()->count(2)->create(['status' => ProductStatusEnum::Active]);
        Product::factory()->count(3)->create(['status' => ProductStatusEnum::Draft]);

        $this->getJson('/api/v1/catalog/products?status=active')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('filtra produtos por marca (200)', function (): void {
        $brand   = Brand::factory()->create();
        $other   = Brand::factory()->create();

        Product::factory()->count(2)->create(['brand_id' => $brand->uuid]);
        Product::factory()->count(1)->create(['brand_id' => $other->uuid]);

        $this->getJson("/api/v1/catalog/products?brand_id={$brand->uuid}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('produto pode pertencer a múltiplas categorias (201)', function (): void {
        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();

        $response = $this->postJson('/api/v1/catalog/products', [
            'name'           => 'Produto Multi-Cat',
            'type'           => 'simple',
            'status'         => 'draft',
            'category_uuids' => [$cat1->uuid, $cat2->uuid],
        ])->assertStatus(201);

        $uuid = $response->json('data.uuid');

        $this->getJson("/api/v1/catalog/products/{$uuid}")
            ->assertOk()
            ->assertJsonCount(2, 'data.categories');
    });

    it('produto tem campo visibility (200)', function (): void {
        $product = Product::factory()->create(['visibility' => 'PUBLIC']);

        $this->getJson("/api/v1/catalog/products/{$product->uuid}")
            ->assertOk()
            ->assertJsonPath('data.visibility', 'PUBLIC')
            ->assertJsonPath('data.visibility_label', 'Público');
    });
});

// ── Variants ─────────────────────────────────────────────────────────────────

describe('Variants', function (): void {
    it('cria variante com SKU único (201)', function (): void {
        $product = Product::factory()->variable()->create();

        $this->postJson('/api/v1/catalog/variants', [
            'product_id'  => $product->uuid,
            'sku'         => 'CAM-PT-M',
            'price_cents' => 4999,
            'is_default'  => true,
        ])->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sku', 'CAM-PT-M')
            ->assertJsonPath('data.sale_price', 49.99);
    });

    it('não cria variante com SKU duplicado (409)', function (): void {
        $product = Product::factory()->variable()->create();
        Variant::factory()->create(['product_id' => $product->uuid, 'sku' => 'SKU-UNICO']);

        $this->postJson('/api/v1/catalog/variants', [
            'product_id'  => $product->uuid,
            'sku'         => 'SKU-UNICO',
            'price_cents' => 2999,
        ])->assertStatus(409);
    });

    it('sale_price retorna valor em decimal (200)', function (): void {
        $product = Product::factory()->create();
        $variant = Variant::factory()->create([
            'product_id'  => $product->uuid,
            'price_cents' => 15990,
        ]);

        $this->getJson("/api/v1/catalog/products/{$product->uuid}/variants")
            ->assertOk()
            ->assertJsonPath('data.0.sale_price', 159.9);
    });
});
