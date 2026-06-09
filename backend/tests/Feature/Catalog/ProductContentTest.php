<?php

declare(strict_types=1);

use App\Modules\Catalog\Enums\ProductStatusEnum;
use App\Modules\Catalog\Events\ProductArchived;
use App\Modules\Catalog\Events\ProductCollectionAssigned;
use App\Modules\Catalog\Events\ProductPublished;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductCollection;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

// ─────────────────────────────────────────────────────────────────────────────
// Conteúdo comercial
// ─────────────────────────────────────────────────────────────────────────────

describe('POST /catalog/products — conteúdo comercial', function (): void {
    it('cria produto com campos de conteúdo comercial', function (): void {
        $this->postJson('/api/v1/catalog/products', [
            'name'                   => 'Vestido Floral',
            'marketing_description'  => 'O vestido mais vendido da estação!',
            'internal_notes'         => 'Fornecedor: ABC Têxtil — prazo 15 dias.',
            'season'                 => 'Verão 2026',
            'launch_date'            => '2026-09-01',
            'is_publishable'         => true,
        ])->assertStatus(201)
            ->assertJsonPath('data.marketing_description', 'O vestido mais vendido da estação!')
            ->assertJsonPath('data.season', 'Verão 2026')
            ->assertJsonPath('data.launch_date', '2026-09-01')
            ->assertJsonPath('data.is_publishable', true);
    });

    it('produto sem conteúdo não é ready_to_publish', function (): void {
        $product = Product::factory()->create([
            'is_publishable' => false,
            'description'    => null,
            'status'         => ProductStatusEnum::Draft,
        ]);

        $this->getJson("/api/v1/catalog/products/{$product->uuid}")
            ->assertOk()
            ->assertJsonPath('data.is_ready_to_publish', false);
    });

    it('produto com conteúdo completo é ready_to_publish', function (): void {
        $product = Product::factory()->create([
            'is_publishable' => true,
            'description'    => 'Descrição completa do produto.',
            'status'         => ProductStatusEnum::Draft,
        ]);

        $this->getJson("/api/v1/catalog/products/{$product->uuid}")
            ->assertOk()
            ->assertJsonPath('data.is_ready_to_publish', true);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Status Seasonal
// ─────────────────────────────────────────────────────────────────────────────

describe('ProductStatus — Seasonal', function (): void {
    it('cria produto sazonal', function (): void {
        $this->postJson('/api/v1/catalog/products', [
            'name'   => 'Coleção Natal',
            'status' => 'seasonal',
            'season' => 'Natal 2026',
        ])->assertStatus(201)
            ->assertJsonPath('data.status', 'seasonal')
            ->assertJsonPath('data.status_label', 'Sazonal');
    });

    it('produto sazonal é visível (isVisible=true)', function (): void {
        expect(ProductStatusEnum::Seasonal->isVisible())->toBeTrue();
    });

    it('produto sazonal pode ser publicado (isPublishable=true)', function (): void {
        expect(ProductStatusEnum::Seasonal->isPublishable())->toBeTrue();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Publicar / Arquivar com eventos
// ─────────────────────────────────────────────────────────────────────────────

describe('publish/archive events', function (): void {
    it('dispara ProductPublished ao publicar', function (): void {
        Event::fake([ProductPublished::class]);

        $product = Product::factory()->create(['status' => ProductStatusEnum::Draft]);

        $this->postJson("/api/v1/catalog/products/{$product->uuid}/publish")
            ->assertOk();

        Event::assertDispatched(ProductPublished::class);
    });

    it('dispara ProductArchived ao arquivar', function (): void {
        Event::fake([ProductArchived::class]);

        $product = Product::factory()->create(['status' => ProductStatusEnum::Active]);

        $this->postJson("/api/v1/catalog/products/{$product->uuid}/archive")
            ->assertOk();

        Event::assertDispatched(ProductArchived::class);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Coleções comerciais (many-to-many)
// ─────────────────────────────────────────────────────────────────────────────

describe('coleções comerciais — muitos-para-muitos', function (): void {
    it('produto pode ser adicionado a múltiplas coleções', function (): void {
        Event::fake([ProductCollectionAssigned::class]);

        $product     = Product::factory()->create();
        $blackFriday = ProductCollection::factory()->create(['name' => 'Black Friday']);
        $verao       = ProductCollection::factory()->create(['name' => 'Verão 2026']);

        $this->postJson("/api/v1/catalog/products/{$product->uuid}/commercial-collections", [
            'collection_id' => $blackFriday->uuid,
        ])->assertOk();

        $this->postJson("/api/v1/catalog/products/{$product->uuid}/commercial-collections", [
            'collection_id' => $verao->uuid,
        ])->assertOk();

        $this->getJson("/api/v1/catalog/products/{$product->uuid}/commercial-collections")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        Event::assertDispatched(ProductCollectionAssigned::class, 2);
    });

    it('não duplica entrada na mesma coleção', function (): void {
        $product    = Product::factory()->create();
        $collection = ProductCollection::factory()->create();

        $this->postJson("/api/v1/catalog/products/{$product->uuid}/commercial-collections", [
            'collection_id' => $collection->uuid,
        ])->assertOk();

        // Segunda chamada retorna sucesso mas não duplica
        $this->postJson("/api/v1/catalog/products/{$product->uuid}/commercial-collections", [
            'collection_id' => $collection->uuid,
        ])->assertOk();

        expect($product->commercialCollections()->count())->toBe(1);
    });

    it('remove produto de coleção comercial', function (): void {
        $product    = Product::factory()->create();
        $collection = ProductCollection::factory()->create();

        $product->commercialCollections()->attach($collection->uuid);

        $this->deleteJson("/api/v1/catalog/products/{$product->uuid}/commercial-collections/{$collection->uuid}")
            ->assertStatus(204);

        expect($product->commercialCollections()->count())->toBe(0);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Analytics foundation (campos nullable)
// ─────────────────────────────────────────────────────────────────────────────

describe('analytics foundation — campos nullable', function (): void {
    it('produto criado tem analytics null', function (): void {
        $product = Product::factory()->create();

        expect($product->sales_velocity)->toBeNull();
        expect($product->days_without_sale)->toBeNull();
        expect($product->stock_age)->toBeNull();
        expect($product->last_sale_at)->toBeNull();
    });

    it('last_sale_at é exposto na resource', function (): void {
        $product = Product::factory()->create([
            'last_sale_at' => now()->subDays(3),
        ]);

        $this->getJson("/api/v1/catalog/products/{$product->uuid}")
            ->assertOk()
            ->assertJsonPath('data.last_sale_at', fn ($v) => $v !== null);
    });
});
