<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\PriceList;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductPrice;
use App\Modules\Catalog\Models\Variant;
use App\Modules\Catalog\Services\PriceResolverService;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

// ── PriceList CRUD ────────────────────────────────────────────────────────────

describe('PriceList CRUD', function (): void {
    it('cria uma tabela de preços', function (): void {
        $response = $this->postJson('/api/v1/catalog/price-lists', [
            'name' => 'Atacado VIP',
            'code' => 'ATACADO_VIP',
            'type' => 'wholesale',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'ATACADO_VIP')
            ->assertJsonPath('data.type', 'wholesale');
    });

    it('lista tabelas de preços ativas', function (): void {
        PriceList::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/catalog/price-lists');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('soft-deleta tabela de preços', function (): void {
        $list = PriceList::factory()->create();

        $this->deleteJson("/api/v1/catalog/price-lists/{$list->uuid}")
            ->assertStatus(204);

        $this->assertSoftDeleted('price_lists', ['uuid' => $list->uuid]);
    });
});

// ── PriceResolverService ──────────────────────────────────────────────────────

describe('PriceResolverService', function (): void {
    it('retorna preço específico da variante', function (): void {
        /** @var \App\Core\Tenancy\Models\Tenant $tenant */
        $tenant  = $this->tenant;
        $product = Product::factory()->create();
        $variant = Variant::factory()->create(['product_id' => $product->uuid, 'price_cents' => 5000]);
        $list    = PriceList::factory()->create(['tenant_id' => $tenant->uuid]);

        ProductPrice::create([
            'tenant_id'    => $tenant->uuid,
            'price_list_id' => $list->uuid,
            'variant_id'   => $variant->uuid,
            'price_cents'  => 4500,
        ]);

        $service = app(PriceResolverService::class);
        $price   = $service->resolve($tenant->uuid, $variant->uuid, $list->uuid);

        expect($price)->toBe(4500);
    });

    it('faz fallback para preço do produto quando variante não tem preço na tabela', function (): void {
        $tenant  = $this->tenant;
        $product = Product::factory()->create(['base_price_cents' => 3000]);
        $variant = Variant::factory()->create(['product_id' => $product->uuid, 'price_cents' => 5000]);
        $list    = PriceList::factory()->create(['tenant_id' => $tenant->uuid]);

        ProductPrice::create([
            'tenant_id'    => $tenant->uuid,
            'price_list_id' => $list->uuid,
            'product_id'   => $product->uuid,
            'price_cents'  => 2800,
        ]);

        $service = app(PriceResolverService::class);
        $price   = $service->resolve($tenant->uuid, $variant->uuid, $list->uuid);

        expect($price)->toBe(2800);
    });

    it('faz fallback final para price_cents base da variante', function (): void {
        $tenant  = $this->tenant;
        $product = Product::factory()->create();
        $variant = Variant::factory()->create(['product_id' => $product->uuid, 'price_cents' => 5000]);
        $list    = PriceList::factory()->create(['tenant_id' => $tenant->uuid]);

        $service = app(PriceResolverService::class);
        $price   = $service->resolve($tenant->uuid, $variant->uuid, $list->uuid);

        expect($price)->toBe(5000);
    });

    it('não retorna preço fora do período de vigência', function (): void {
        $tenant  = $this->tenant;
        $product = Product::factory()->create();
        $variant = Variant::factory()->create(['product_id' => $product->uuid, 'price_cents' => 5000]);
        $list    = PriceList::factory()->create(['tenant_id' => $tenant->uuid]);

        ProductPrice::create([
            'tenant_id'    => $tenant->uuid,
            'price_list_id' => $list->uuid,
            'variant_id'   => $variant->uuid,
            'price_cents'  => 1000,
            'valid_from'   => now()->addDays(10)->toDateString(),
            'valid_to'     => now()->addDays(30)->toDateString(),
        ]);

        $service = app(PriceResolverService::class);
        $price   = $service->resolve($tenant->uuid, $variant->uuid, $list->uuid);

        // Preço específico não vigente → fallback para base
        expect($price)->toBe(5000);
    });

    it('invalida cache ao chamar invalidate()', function (): void {
        Cache::spy();

        $service = app(PriceResolverService::class);
        $service->invalidate('tenant-id', 'list-id', 'variant-id');

        Cache::shouldHaveReceived('forget')->with('price:tenant-id:list-id:variant-id');
    });
});
