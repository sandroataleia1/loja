<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductShareLink;
use App\Modules\Catalog\Services\ProductDatasheetService;
use App\Modules\Purchasing\Models\Supplier;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

describe('Product com supplier', function (): void {
    it('relação supplier carregada no resource', function (): void {
        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $product  = Product::factory()->create([
            'tenant_id'   => $this->tenant->uuid,
            'supplier_id' => $supplier->uuid,
        ]);

        expect($product->supplier)->not->toBeNull()
            ->and($product->supplier->uuid)->toBe($supplier->uuid);
    });

    it('isCurrentlyOnSale retorna true quando is_on_sale e dentro do prazo', function (): void {
        $product = Product::factory()->create([
            'tenant_id'    => $this->tenant->uuid,
            'is_on_sale'   => true,
            'sale_ends_at' => now()->addDays(5),
        ]);

        expect($product->isCurrentlyOnSale())->toBeTrue();
    });

    it('isCurrentlyOnSale retorna false quando sale_ends_at no passado', function (): void {
        $product = Product::factory()->create([
            'tenant_id'    => $this->tenant->uuid,
            'is_on_sale'   => true,
            'sale_ends_at' => now()->subDays(1),
        ]);

        expect($product->isCurrentlyOnSale())->toBeFalse();
    });

    it('isCurrentlyOnSale retorna false quando is_on_sale é false', function (): void {
        $product = Product::factory()->create([
            'tenant_id'  => $this->tenant->uuid,
            'is_on_sale' => false,
        ]);

        expect($product->isCurrentlyOnSale())->toBeFalse();
    });

    it('scope onSale filtra produtos em promoção', function (): void {
        Product::factory()->count(2)->create([
            'tenant_id'  => $this->tenant->uuid,
            'is_on_sale' => true,
        ]);
        Product::factory()->count(3)->create([
            'tenant_id'  => $this->tenant->uuid,
            'is_on_sale' => false,
        ]);

        $onSale = Product::where('tenant_id', $this->tenant->uuid)->onSale()->get();

        expect($onSale)->toHaveCount(2);
    });

    it('scope fromSupplier filtra por fornecedor', function (): void {
        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->uuid]);
        Product::factory()->count(2)->create([
            'tenant_id'   => $this->tenant->uuid,
            'supplier_id' => $supplier->uuid,
        ]);
        Product::factory()->count(3)->create(['tenant_id' => $this->tenant->uuid]);

        $fromSupplier = Product::where('tenant_id', $this->tenant->uuid)
            ->fromSupplier($supplier->uuid)
            ->get();

        expect($fromSupplier)->toHaveCount(2);
    });
});

describe('Ficha técnica PDF', function (): void {
    it('generatePdf retorna conteúdo não vazio', function (): void {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->uuid]);

        $service = app(ProductDatasheetService::class);
        $pdf     = $service->generatePdf($product);

        expect($pdf)->toBeString()->not->toBeEmpty();
    });

    it('share gera token válido por 24h', function (): void {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->uuid]);

        $response = $this->postJson("/api/v1/catalog/products/{$product->uuid}/datasheet/share");

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['token', 'url', 'expires_at']]);

        $data = $response->json('data');
        expect($data['token'])->toHaveLength(64)
            ->and($data['url'])->toContain('catalog/public/products/');
    });

    it('link de compartilhamento fica ativo por 24 horas', function (): void {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->uuid]);

        $service = app(ProductDatasheetService::class);
        $link    = $service->generateShareLink($product);

        expect($link->isExpired())->toBeFalse()
            ->and(now()->diffInHours($link->expires_at))->toBeBetween(23, 25);
    });

    it('acesso público retorna dados do produto', function (): void {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $link    = ProductShareLink::create([
            'tenant_id'  => $this->tenant->uuid,
            'product_id' => $product->uuid,
            'token'      => bin2hex(random_bytes(32)),
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->getJson("/api/v1/catalog/public/products/{$link->token}");

        $response->assertOk()
            ->assertJsonPath('data.uuid', $product->uuid);

        expect($link->fresh()->view_count)->toBe(1);
    });

    it('acesso público com link expirado retorna 410', function (): void {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $link    = ProductShareLink::create([
            'tenant_id'  => $this->tenant->uuid,
            'product_id' => $product->uuid,
            'token'      => bin2hex(random_bytes(32)),
            'expires_at' => now()->subHours(1),
        ]);

        $response = $this->getJson("/api/v1/catalog/public/products/{$link->token}");

        $response->assertStatus(410);
    });
});
