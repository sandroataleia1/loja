<?php

declare(strict_types=1);

use App\Modules\Catalog\Events\ProductMediaAttached;
use App\Modules\Catalog\Models\Product;
use App\Modules\Media\Enums\MediaTypeEnum;
use App\Modules\Media\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->actingAsTenantUser();
    Storage::fake('public');
});

describe('POST /catalog/media-assets — upload de mídia', function (): void {
    it('faz upload de imagem e cria MediaAsset', function (): void {
        // PNG: o GD do container é compilado sem JPEG; PNG exercita o mesmo fluxo.
        $file = UploadedFile::fake()->image('produto.png', 800, 600);

        $this->postJson('/api/v1/catalog/media-assets', [
            'file' => $file,
            'type' => 'image',
        ])->assertStatus(201)
            ->assertJsonPath('data.type', 'image')
            ->assertJsonPath('data.mime_type', 'image/png');

        $this->assertDatabaseHas('media_assets', [
            'tenant_id' => $this->tenant->uuid,
            'type'      => 'image',
        ]);
    });

    it('rejeita tipo inválido', function (): void {
        $file = UploadedFile::fake()->image('test.png');

        $this->postJson('/api/v1/catalog/media-assets', [
            'file' => $file,
            'type' => 'emoji',
        ])->assertStatus(422);
    });
});

describe('GET /catalog/media-assets — listar mídia', function (): void {
    it('lista assets do tenant', function (): void {
        MediaAsset::factory()->count(3)->create();

        $this->getJson('/api/v1/catalog/media-assets')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('não retorna assets de outro tenant', function (): void {
        MediaAsset::factory()->count(2)->create();

        $this->actingAsTenantUser(); // novo tenant
        MediaAsset::factory()->create();

        $this->getJson('/api/v1/catalog/media-assets')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('filtra por tipo', function (): void {
        MediaAsset::factory()->image()->count(2)->create();
        MediaAsset::factory()->video()->create();

        $this->getJson('/api/v1/catalog/media-assets?type=image')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });
});

describe('POST /catalog/products/{product}/media — vincular mídia ao produto', function (): void {
    it('vincula asset ao produto e dispara evento', function (): void {
        Event::fake([ProductMediaAttached::class]);

        $product = Product::factory()->create();
        $asset   = MediaAsset::factory()->create();

        $this->postJson("/api/v1/catalog/products/{$product->uuid}/media", [
            'media_asset_id' => $asset->uuid,
            'is_primary'     => true,
            'position'       => 0,
        ])->assertOk();

        $this->assertDatabaseHas('product_media', [
            'product_id'     => $product->uuid,
            'media_asset_id' => $asset->uuid,
            'is_primary'     => true,
        ]);

        Event::assertDispatched(ProductMediaAttached::class);
    });

    it('não vincula o mesmo asset duas vezes', function (): void {
        $product = Product::factory()->create();
        $asset   = MediaAsset::factory()->create();

        $product->media()->attach($asset->uuid, ['position' => 0, 'is_primary' => false]);

        $this->postJson("/api/v1/catalog/products/{$product->uuid}/media", [
            'media_asset_id' => $asset->uuid,
        ])->assertStatus(422);
    });

    it('lista mídia vinculada ao produto', function (): void {
        $product = Product::factory()->create();
        $assets  = MediaAsset::factory()->count(3)->create();

        foreach ($assets as $i => $asset) {
            $product->media()->attach($asset->uuid, ['position' => $i, 'is_primary' => $i === 0]);
        }

        $this->getJson("/api/v1/catalog/products/{$product->uuid}/media")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });
});

describe('DELETE /catalog/products/{product}/media/{asset} — desvincular', function (): void {
    it('desvincula asset do produto', function (): void {
        $product = Product::factory()->create();
        $asset   = MediaAsset::factory()->create();

        $product->media()->attach($asset->uuid, ['position' => 0, 'is_primary' => true]);

        $this->deleteJson("/api/v1/catalog/products/{$product->uuid}/media/{$asset->uuid}")
            ->assertStatus(204);

        expect($product->media()->count())->toBe(0);
    });
});
