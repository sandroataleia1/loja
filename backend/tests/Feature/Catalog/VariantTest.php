<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Variant;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

describe('Listar variantes', function (): void {
    it('retorna variantes do produto', function (): void {
        $product = Product::factory()->create();
        Variant::factory()->for($product, 'product')->count(3)->create();

        $this->getJson("/api/v1/catalog/products/{$product->uuid}/variants")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });
});

describe('Criar variante', function (): void {
    it('cria variante com dados mínimos', function (): void {
        $product = Product::factory()->create();

        $response = $this->postJson('/api/v1/catalog/variants', [
            'product_id'  => $product->uuid,
            'sku'         => 'CBL-2P5-100M',
            'price_cents' => 5990,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.sku', 'CBL-2P5-100M')
            ->assertJsonPath('data.price_cents', 5990);
    });

    it('rejeita SKU duplicado', function (): void {
        $product = Product::factory()->create();
        Variant::factory()->for($product, 'product')->create(['sku' => 'CBL-2P5-100M']);

        $this->postJson('/api/v1/catalog/variants', [
            'product_id'  => $product->uuid,
            'sku'         => 'CBL-2P5-100M',
            'price_cents' => 3990,
        ])->assertConflict();
    });
});

describe('Deletar variante', function (): void {
    it('soft deleta variante', function (): void {
        $product = Product::factory()->create();
        $variant = Variant::factory()->for($product, 'product')->create();

        $this->deleteJson("/api/v1/catalog/products/{$product->uuid}/variants/{$variant->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('catalog_variants', ['uuid' => $variant->uuid]);
    });
});
