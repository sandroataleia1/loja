<?php

declare(strict_types=1);

use App\Modules\Catalog\Enums\BarcodeTypeEnum;
use App\Modules\Catalog\Models\CatalogBarcode;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Variant;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

// ── Lookup por código de barras ───────────────────────────────────────────────

describe('GET /catalog/barcode/{value}', function (): void {
    it('retorna produto pelo código de barras EAN-13', function (): void {
        $product = Product::factory()->create();
        $variant = Variant::factory()->create(['product_id' => $product->uuid]);

        CatalogBarcode::create([
            'tenant_id'    => $this->tenant->uuid,
            'variant_id'   => $variant->uuid,
            'barcode_type' => BarcodeTypeEnum::Ean13->value,
            'value'        => '7891234567890',
            'is_primary'   => true,
        ]);

        $response = $this->getJson('/api/v1/catalog/barcode/7891234567890');

        $response->assertOk()
            ->assertJsonPath('data.product.uuid', $product->uuid)
            ->assertJsonPath('data.matched_variant', $variant->uuid)
            ->assertJsonPath('data.barcode_type', 'ean13');
    });

    it('retorna 404 para código de barras inexistente', function (): void {
        $this->getJson('/api/v1/catalog/barcode/0000000000000')
            ->assertStatus(404);
    });

    it('não retorna produto de outro tenant pelo mesmo código de barras', function (): void {
        $otherTenant = \App\Core\Tenancy\Models\Tenant::factory()->create();
        $product     = Product::factory()->create(['tenant_id' => $otherTenant->uuid]);
        $variant     = Variant::factory()->create(['tenant_id' => $otherTenant->uuid, 'product_id' => $product->uuid]);

        CatalogBarcode::create([
            'tenant_id'    => $otherTenant->uuid,
            'variant_id'   => $variant->uuid,
            'barcode_type' => BarcodeTypeEnum::Ean13->value,
            'value'        => '9999999999999',
            'is_primary'   => true,
        ]);

        // Tenant atual não tem esse produto → 404
        $this->getJson('/api/v1/catalog/barcode/9999999999999')
            ->assertStatus(404);
    });
});

// ── Unicidade por tenant ──────────────────────────────────────────────────────

describe('Unicidade de código de barras', function (): void {
    it('rejeita código de barras duplicado no mesmo tenant', function (): void {
        $product  = Product::factory()->create();
        $variant1 = Variant::factory()->create(['product_id' => $product->uuid]);
        $variant2 = Variant::factory()->create(['product_id' => $product->uuid]);

        CatalogBarcode::create([
            'tenant_id'    => $this->tenant->uuid,
            'variant_id'   => $variant1->uuid,
            'barcode_type' => BarcodeTypeEnum::Ean13->value,
            'value'        => '1234567890123',
            'is_primary'   => true,
        ]);

        expect(fn () => CatalogBarcode::create([
            'tenant_id'    => $this->tenant->uuid,
            'variant_id'   => $variant2->uuid,
            'barcode_type' => BarcodeTypeEnum::Ean13->value,
            'value'        => '1234567890123',
            'is_primary'   => false,
        ]))->toThrow(\Illuminate\Database\UniqueConstraintViolationException::class);
    });

    it('aceita mesmo código de barras em tenants diferentes', function (): void {
        $other   = \App\Core\Tenancy\Models\Tenant::factory()->create();
        $product = Product::factory()->create();
        $variant = Variant::factory()->create(['product_id' => $product->uuid]);

        // Tenant atual
        CatalogBarcode::create([
            'tenant_id'    => $this->tenant->uuid,
            'variant_id'   => $variant->uuid,
            'barcode_type' => BarcodeTypeEnum::Ean13->value,
            'value'        => '5555555555555',
            'is_primary'   => true,
        ]);

        $product2 = Product::factory()->create(['tenant_id' => $other->uuid]);
        $variant2 = Variant::factory()->create(['tenant_id' => $other->uuid, 'product_id' => $product2->uuid]);

        // Outro tenant — não deve lançar exceção
        CatalogBarcode::create([
            'tenant_id'    => $other->uuid,
            'variant_id'   => $variant2->uuid,
            'barcode_type' => BarcodeTypeEnum::Ean13->value,
            'value'        => '5555555555555',
            'is_primary'   => true,
        ]);

        expect(CatalogBarcode::withoutTenantScope()->where('value', '5555555555555')->count())->toBe(2);
    });
});
